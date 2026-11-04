<?php
/**
 * Registro de lecturas recibidas por correo (RF-02 / CU-02).
 * Reglas: el equipo debe estar registrado (por número de serie) y el contador no puede ser menor al último real.
 */

/**
 * $origen: 'SMTP' para lecturas por correo, 'COLECTOR' para las que envía el colector de contadores.
 * @return array{ok:bool, motivo?:string, equipo_id?:int}
 */
function registrarLecturaSmtp($conn, $rep, $origen = 'SMTP') {
    $st = $conn->prepare("SELECT id FROM equipos WHERE serie = ? LIMIT 1");
    $st->bind_param("s", $rep['serie']);
    $st->execute();
    $eq = $st->get_result()->fetch_assoc();
    if (!$eq) return ['ok' => false, 'motivo' => 'Serie no registrada: ' . $rep['serie']];
    $equipoId = (int)$eq['id'];

    // RF-02: un contador no puede ser menor al último registrado
    $st = $conn->prepare("SELECT contador_bn, contador_color FROM lecturas
                          WHERE equipo_id = ? AND origen <> 'ESTIMADO' ORDER BY fecha DESC, id DESC LIMIT 1");
    $st->bind_param("i", $equipoId);
    $st->execute();
    $ult = $st->get_result()->fetch_assoc();
    if ($ult && ($rep['contador_bn'] < (int)$ult['contador_bn'] || $rep['contador_color'] < (int)$ult['contador_color'])) {
        $msg = "Lectura $origen inconsistente en serie {$rep['serie']}: {$rep['contador_bn']} B/N es menor al último registrado ({$ult['contador_bn']}).";
        $n = $conn->prepare("INSERT INTO notificaciones (tipo, mensaje, equipo_id) VALUES ('INCONSISTENCIA', ?, ?)");
        $n->bind_param("si", $msg, $equipoId);
        $n->execute();
        return ['ok' => false, 'motivo' => 'Contador menor al último registrado (inconsistencia)'];
    }

    $fecha = $rep['fecha'] ?: date('Y-m-d H:i:s');
    $toner = $rep['toner_pct'];
    $ins = $conn->prepare("INSERT INTO lecturas (equipo_id, fecha, contador_bn, contador_color, toner_pct, origen)
                           VALUES (?, ?, ?, ?, ?, ?)");
    $ins->bind_param("isiiis", $equipoId, $fecha, $rep['contador_bn'], $rep['contador_color'], $toner, $origen);
    $ins->execute();
    return ['ok' => true, 'equipo_id' => $equipoId];
}
