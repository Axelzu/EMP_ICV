<?php
/**
 * Avisos automáticos de alertas que envía la impresora (p. ej. Ricoh "Notificación alertas dispositivo"):
 * tóner casi vacío, falta de papel, tapa abierta, etc.
 *
 * Estos correos NO traen número de serie ni contadores: el equipo se identifica por su MAC o su nombre de host.
 */
require_once __DIR__ . '/mac.php';
require_once __DIR__ . '/motor.php';

/**
 * Interpreta un aviso de equipo. Devuelve null si el texto no parece un aviso de alertas.
 *
 * @return array{mac:?string, hostname:?string, ip:?string, modelo:?string, categoria:string, resumen:string, detalle:string}|null
 */
function parsearAvisoEquipo($asunto, $cuerpo) {
    $texto = (string)$cuerpo;

    $mac = null;
    if (preg_match('/Direcci[oó]n\s+MAC\s*\R\s*\w+:\s*([0-9A-Fa-f]{2}(?:[:\-][0-9A-Fa-f]{2}){5})/u', $texto, $m)
        || preg_match('/\b([0-9A-Fa-f]{2}(?::[0-9A-Fa-f]{2}){5})\b/', $texto, $m)) {
        $mac = normalizarMac($m[1]);
    }
    $hostname = preg_match('/Nombre\s+de\s+host:\s*(\S+)/iu', $texto, $m) ? trim($m[1]) : null;
    $ip       = preg_match('/Direcci[oó]n\s+IPv4:\s*([\d.]+)/iu', $texto, $m) ? $m[1] : null;
    $modelo   = preg_match('/Nombre\s+modelo:\s*([^\r\n]+)/iu', $texto, $m) ? trim($m[1]) : null;

    if ($mac === null && $hostname === null) return null;
    if (stripos($asunto, 'alerta') === false && stripos($texto, 'Detalles:') === false) return null;

    // Estado detallado: líneas de evento sin repetir (el aviso las lista una vez por cada función de la máquina)
    $lineas = [];
    foreach (preg_split('/\R/u', $texto) as $l) {
        $l = trim($l);
        if ($l === '' || substr($l, -1) === ':') continue;
        if (preg_match('/^(Queda poco|No hay papel|Tapa abierta|Atasco|Sin t[oó]ner|T[oó]ner|Cartucho|Fuera de l[ií]nea|Error|Abra|Cierre|Cerrar|Prepare)/iu', $l)) {
            $lineas[$l] = true;
        }
    }
    $detalle = implode("\n", array_keys($lineas));

    // Evento principal: último segmento del asunto ("... : Aviso ICV : Cerrar tapa frontal")
    $partes = array_map('trim', explode(' : ', (string)$asunto));
    $resumen = count($partes) > 1 ? end($partes) : '';
    if ($resumen === '' && $lineas) $resumen = (string)array_key_first($lineas);
    if ($resumen === '') return null;

    if (preg_match('/t[oó]ner|cartucho/iu', $resumen))         $cat = 'TONER';
    elseif (preg_match('/papel|bandeja|atasco/iu', $resumen))  $cat = 'PAPEL';
    elseif (preg_match('/tapa|cubierta/iu', $resumen))         $cat = 'TAPA';
    else                                                       $cat = 'OTRO';

    return ['mac' => $mac, 'hostname' => $hostname, 'ip' => $ip, 'modelo' => $modelo,
            'categoria' => $cat, 'resumen' => mb_substr($resumen, 0, 255), 'detalle' => $detalle];
}

/**
 * Registra un aviso de equipo. Un aviso de tóner agenda además un ticket de mantenimiento/reposición.
 *
 * @return array{ok:bool, motivo?:string, equipo_id?:int, ticket?:bool, duplicado?:bool}
 */
function registrarAvisoEquipo($conn, $aviso) {
    $eq = null;
    if ($aviso['mac']) {
        $st = $conn->prepare("SELECT id, mac, hostname FROM equipos WHERE mac = ? LIMIT 1");
        $st->bind_param("s", $aviso['mac']);
        $st->execute();
        $eq = $st->get_result()->fetch_assoc();
    }
    if (!$eq && $aviso['hostname']) {
        $st = $conn->prepare("SELECT id, mac, hostname FROM equipos WHERE hostname = ? LIMIT 1");
        $st->bind_param("s", $aviso['hostname']);
        $st->execute();
        $eq = $st->get_result()->fetch_assoc();
    }
    if (!$eq) {
        return ['ok' => false, 'motivo' => 'Equipo no registrado (MAC ' . ($aviso['mac'] ?: '-') . ', host ' . ($aviso['hostname'] ?: '-') . ')'];
    }
    $eid = (int)$eq['id'];

    // Aprende los datos que falten: si el equipo avisó por host, se guarda su MAC (y viceversa)
    if ($aviso['mac'] && !$eq['mac']) {
        $u = $conn->prepare("UPDATE equipos SET mac = ? WHERE id = ?");
        $u->bind_param("si", $aviso['mac'], $eid);
        $u->execute();
    }
    if ($aviso['hostname'] && !$eq['hostname']) {
        $u = $conn->prepare("UPDATE equipos SET hostname = ? WHERE id = ?");
        $u->bind_param("si", $aviso['hostname'], $eid);
        $u->execute();
    }

    // La impresora repite avisos: no se duplica el mismo evento dentro de 6 horas
    $st = $conn->prepare("SELECT COUNT(*) n FROM alertas_equipo WHERE equipo_id = ? AND resumen = ? AND recibido_en > (NOW() - INTERVAL 6 HOUR)");
    $st->bind_param("is", $eid, $aviso['resumen']);
    $st->execute();
    if ((int)$st->get_result()->fetch_assoc()['n'] > 0) return ['ok' => true, 'equipo_id' => $eid, 'duplicado' => true];

    $ins = $conn->prepare("INSERT INTO alertas_equipo (equipo_id, categoria, resumen, detalle) VALUES (?, ?, ?, ?)");
    $ins->bind_param("isss", $eid, $aviso['categoria'], $aviso['resumen'], $aviso['detalle']);
    $ins->execute();

    // Aviso de tóner => ticket de mantenimiento/reposición, si no hay uno pendiente
    $ticket = false;
    if ($aviso['categoria'] === 'TONER') {
        $st = $conn->prepare("SELECT COUNT(*) n FROM tickets_mantenimiento WHERE equipo_id = ? AND estado = 'Pendiente'");
        $st->bind_param("i", $eid);
        $st->execute();
        if ((int)$st->get_result()->fetch_assoc()['n'] === 0) {
            $motivo = 'Aviso de la impresora: ' . $aviso['resumen'];
            $tec = motor_tecnico_menos_cargado($conn);
            $fecha = date('Y-m-d');
            $t = $conn->prepare("INSERT INTO tickets_mantenimiento (equipo_id, motivo, tecnico_id, fecha_agendada) VALUES (?, ?, ?, ?)");
            $t->bind_param("isis", $eid, $motivo, $tec, $fecha);
            $t->execute();
            $ticket = true;
        }
    }
    return ['ok' => true, 'equipo_id' => $eid, 'ticket' => $ticket];
}
