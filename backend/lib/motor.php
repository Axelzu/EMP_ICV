<?php
/**
 * Motor Analítico Predictivo de Contingencia (RF-06, CU-04, CU-05, CU-06) y revisión de omisiones (RF-05).
 *
 * Por equipo: (1) proyecta el contador de cierre del mes con series temporales, (2) estima el desgaste del tóner y la
 * fecha del próximo mantenimiento, (3) agenda automáticamente un ticket de mantenimiento preventivo al cruzar el umbral.
 *
 * Candidatos de proyección (se elige el de menor error en un backtest sobre los últimos meses reales):
 *   - red neuronal MLP (perceptrón multicapa con ventana de rezagos, implementada en PHP)
 *   - tendencia lineal (regresión por mínimos cuadrados)
 *   - promedio móvil (referencia)
 * El MAE se mide sobre el consumo mensual (más exigente que medirlo sobre el contador acumulado).
 */

const MOTOR_MESES_FIABLE = 6;          // menos historial => resultado "preliminar"
const MOTOR_HORIZONTE_DIAS = 60;       // ventana para agendar mantenimiento
const MOTOR_TONER_CRITICO_PCT = 10;
const MOTOR_ANTICIPACION_VISITA = 7;   // días antes del umbral en que se agenda la visita

/* ---------------------------- Series temporales ---------------------------- */

function motor_ym_idx($ym) { [$y, $m] = array_map('intval', explode('-', $ym)); return $y * 12 + ($m - 1); }
function motor_idx_ym($i) { return sprintf('%04d-%02d', intdiv($i, 12), ($i % 12) + 1); }

/** @param array $lecturas [['fecha'=>'YYYY-MM-DD HH:MM:SS','valor'=>n]] ordenadas. Último valor de cada mes, interpolando huecos. */
function motor_serie_mensual($lecturas) {
    $porMes = [];
    foreach ($lecturas as $l) $porMes[substr($l['fecha'], 0, 7)] = (float)$l['valor'];
    if (!$porMes) return [];
    ksort($porMes);
    $claves = array_keys($porMes);
    $out = [];
    foreach ($claves as $i => $ym) {
        if ($i > 0) {
            $prev = motor_ym_idx($claves[$i - 1]);
            $gap = motor_ym_idx($ym) - $prev;
            $v0 = $porMes[$claves[$i - 1]];
            $v1 = $porMes[$ym];
            for ($g = 1; $g < $gap; $g++) $out[] = ['ym' => motor_idx_ym($prev + $g), 'valor' => $v0 + ($v1 - $v0) * $g / $gap];
        }
        $out[] = ['ym' => $ym, 'valor' => $porMes[$ym]];
    }
    return $out;
}

function motor_consumos($serie) {
    $c = [];
    for ($i = 1; $i < count($serie); $i++) $c[] = max(0, $serie[$i]['valor'] - $serie[$i - 1]['valor']);
    return $c;
}

/* --------------------------------- Modelos --------------------------------- */

function motor_modelo_promedio($c, $pasos) {
    $v = array_slice($c, -3);
    return array_fill(0, $pasos, array_sum($v) / count($v));
}

function motor_modelo_tendencia($c, $pasos) {
    $v = array_values(array_slice($c, -6));
    $n = count($v);
    $mx = ($n - 1) / 2;
    $my = array_sum($v) / $n;
    $num = 0; $den = 0;
    for ($i = 0; $i < $n; $i++) { $num += ($i - $mx) * ($v[$i] - $my); $den += ($i - $mx) ** 2; }
    $pend = $den > 0 ? $num / $den : 0;
    $out = [];
    for ($s = 1; $s <= $pasos; $s++) $out[] = max(0, $my + $pend * (($n - 1 + $s) - $mx));
    return $out;
}

/** Perceptrón multicapa: ventana de 3 consumos -> 6 neuronas tanh -> salida lineal. Entrenamiento por descenso de gradiente. */
function motor_modelo_mlp($c, $pasos) {
    $escala = max(max($c), 1);
    $n = array_map(fn($x) => $x / $escala, $c);
    $w = min(3, count($n) - 1);
    $muestras = [];
    for ($i = $w; $i < count($n); $i++) $muestras[] = [array_slice($n, $i - $w, $w), $n[$i]];

    $H = 6; $lr = 0.05;
    mt_srand(7); // pesos iniciales reproducibles
    $r = fn() => (mt_rand() / mt_getrandmax() - 0.5);
    $W1 = []; $b1 = array_fill(0, $H, 0.0); $W2 = []; $b2 = 0.0;
    for ($h = 0; $h < $H; $h++) {
        for ($j = 0; $j < $w; $j++) $W1[$h][$j] = $r();
        $W2[$h] = $r();
    }
    for ($ep = 0; $ep < 1500; $ep++) {
        foreach ($muestras as [$x, $t]) {
            $a = []; $y = $b2;
            for ($h = 0; $h < $H; $h++) {
                $z = $b1[$h];
                for ($j = 0; $j < $w; $j++) $z += $W1[$h][$j] * $x[$j];
                $a[$h] = tanh($z);
                $y += $W2[$h] * $a[$h];
            }
            $err = $y - $t;
            for ($h = 0; $h < $H; $h++) {
                $dz = $err * $W2[$h] * (1 - $a[$h] * $a[$h]);
                $W2[$h] -= $lr * $err * $a[$h];
                for ($j = 0; $j < $w; $j++) $W1[$h][$j] -= $lr * $dz * $x[$j];
                $b1[$h] -= $lr * $dz;
            }
            $b2 -= $lr * $err;
        }
    }
    $ventana = array_slice($n, -$w);
    $out = [];
    for ($s = 0; $s < $pasos; $s++) {
        $y = $b2;
        for ($h = 0; $h < $H; $h++) {
            $z = $b1[$h];
            for ($j = 0; $j < $w; $j++) $z += $W1[$h][$j] * $ventana[$j];
            $y += $W2[$h] * tanh($z);
        }
        $y = max(0, $y);
        $out[] = $y * $escala;
        $ventana[] = $y; array_shift($ventana);
    }
    return $out;
}

function motor_modelos() {
    return [
        ['nombre' => 'MLP', 'fn' => 'motor_modelo_mlp', 'minimo' => 4],
        ['nombre' => 'tendencia-lineal', 'fn' => 'motor_modelo_tendencia', 'minimo' => 3],
        ['nombre' => 'promedio-movil', 'fn' => 'motor_modelo_promedio', 'minimo' => 1],
    ];
}

/** MAE (%) del modelo sobre los últimos meses: se entrena sin ese mes y se compara con lo ocurrido. */
function motor_backtest($fn, $c) {
    $ks = min(3, count($c) - 3);
    if ($ks < 1) return null;
    $suma = 0; $cnt = 0;
    for ($k = 1; $k <= $ks; $k++) {
        $real = $c[count($c) - $k];
        if ($real <= 0) continue;
        $pred = $fn(array_slice($c, 0, count($c) - $k), 1)[0];
        $suma += abs($pred - $real) / $real;
        $cnt++;
    }
    return $cnt ? $suma / $cnt * 100 : null;
}

/** Proyecta el contador acumulado al cierre de $periodo ('YYYY-MM') usando solo el historial anterior a ese mes. */
function motor_proyectar($lecturas, $periodo) {
    $serie = array_values(array_filter(motor_serie_mensual($lecturas), fn($p) => $p['ym'] < $periodo));
    if (count($serie) < 2) return null;
    $c = motor_consumos($serie);
    $pasos = motor_ym_idx($periodo) - motor_ym_idx($serie[count($serie) - 1]['ym']);

    $mejor = null;
    foreach (motor_modelos() as $m) {
        if (count($c) < $m['minimo']) continue;
        $mae = motor_backtest($m['fn'], $c);
        if ($mejor === null || ($mae !== null && ($mejor['mae'] === null || $mae < $mejor['mae']))) $mejor = ['modelo' => $m, 'mae' => $mae];
    }
    if ($mejor === null) $mejor = ['modelo' => motor_modelos()[2], 'mae' => null];

    $esperado = ($mejor['modelo']['fn'])($c, $pasos);
    $ultimo = $serie[count($serie) - 1]['valor'];
    return [
        'estimado'        => (int)round($ultimo + array_sum($esperado)),
        'mae'             => $mejor['mae'],
        'modelo'          => $mejor['modelo']['nombre'],
        'meses'           => count($serie),
        'preliminar'      => count($serie) < MOTOR_MESES_FIABLE,
        'consumo_mensual' => $esperado[0],
    ];
}

/* ------------------------- Procesamiento por equipo ------------------------- */

function motor_filas($conn, $sql, $tipos = '', ...$params) {
    $st = $conn->prepare($sql);
    if ($tipos !== '') $st->bind_param($tipos, ...$params);
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

function motor_tecnico_menos_cargado($conn) {
    $r = motor_filas($conn, "SELECT u.id FROM users u
        LEFT JOIN tickets_mantenimiento t ON t.tecnico_id = u.id AND t.estado = 'Pendiente'
        WHERE u.rol = 'tecnico' AND u.activo = 1
        GROUP BY u.id ORDER BY COUNT(t.id) ASC, u.id ASC LIMIT 1");
    return $r ? (int)$r[0]['id'] : null;
}

function motor_procesar_equipo($conn, $eq, DateTimeImmutable $hoy) {
    $periodo = $hoy->format('Y-m');
    $eid = (int)$eq['id'];
    $lect = motor_filas($conn, "SELECT fecha, contador_bn, contador_color, toner_pct FROM lecturas
        WHERE equipo_id = ? AND origen <> 'ESTIMADO' ORDER BY fecha ASC, id ASC", 'i', $eid);
    if (count($lect) < 2) return ['equipo' => $eq['serie'], 'omitido' => 'historial insuficiente (menos de 2 lecturas)'];

    $bn = motor_proyectar(array_map(fn($l) => ['fecha' => $l['fecha'], 'valor' => $l['contador_bn']], $lect), $periodo);
    if (!$bn) return ['equipo' => $eq['serie'], 'omitido' => 'historial mensual insuficiente'];
    $hayColor = (bool)array_filter($lect, fn($l) => (int)$l['contador_color'] > 0);
    $col = $hayColor ? motor_proyectar(array_map(fn($l) => ['fecha' => $l['fecha'], 'valor' => $l['contador_color']], $lect), $periodo) : null;

    $ultima = $lect[count($lect) - 1];
    $totalActual = (int)$ultima['contador_bn'] + (int)$ultima['contador_color'];
    $consumoMes = $bn['consumo_mensual'] + ($col ? $col['consumo_mensual'] : 0);
    $velocidad = max($consumoMes / 30, 0.0001); // páginas por día

    // Tóner derivado de la velocidad del contador
    $rend = (int)($eq['rendimiento_toner'] ?: 20000);
    $conToner = null;
    foreach (array_reverse($lect) as $l) { if ($l['toner_pct'] !== null) { $conToner = $l; break; } }
    if ($conToner) {
        $desde = $totalActual - ((int)$conToner['contador_bn'] + (int)$conToner['contador_color']);
        $restantes = max(0, ($conToner['toner_pct'] / 100) * $rend - $desde);
    } else {
        $restantes = $rend - ($totalActual % $rend); // se asume cambio de tóner al completar cada rendimiento
    }
    $hastaCritico = max(0, $restantes - (MOTOR_TONER_CRITICO_PCT / 100) * $rend);
    $diasToner = $hastaCritico / $velocidad;
    $proyectadoCierre = $bn['estimado'] + ($col ? $col['estimado'] : 0);
    $tonerCierre = max(0, (($restantes - max(0, $proyectadoCierre - $totalActual)) / $rend) * 100);

    // Umbral de copias para mantenimiento preventivo
    $um = motor_filas($conn, "SELECT MAX(fecha_completado) f FROM tickets_mantenimiento WHERE equipo_id = ? AND estado = 'Completado'", 'i', $eid);
    $base = $lect[0];
    if (!empty($um[0]['f'])) {
        $previas = array_values(array_filter($lect, fn($l) => $l['fecha'] <= $um[0]['f']));
        if ($previas) $base = $previas[count($previas) - 1];
    }
    $desdeMant = $totalActual - ((int)$base['contador_bn'] + (int)$base['contador_color']);
    $umbral = (int)($eq['umbral_mantenimiento'] ?: 100000);
    $diasMant = max(0, ($umbral - $desdeMant) / $velocidad);

    $diasCritico = min($diasToner, $diasMant);
    $fechaUmbral = $hoy->modify('+' . (int)floor($diasCritico) . ' days');
    $porToner = $diasToner <= $diasMant;

    // Guardar proyección (no se pisa una proyección ya aplicada al informe)
    $prev = motor_filas($conn, "SELECT aplicada FROM proyecciones WHERE equipo_id = ? AND periodo = ?", 'is', $eid, $periodo);
    $mae = $bn['mae'] === null ? null : round($bn['mae'], 2);
    if (!$prev || !(int)$prev[0]['aplicada']) {
        $st = $conn->prepare("INSERT INTO proyecciones (equipo_id, periodo, contador_bn_estimado, contador_color_estimado, mae_pct, meses_historial,
                    preliminar, toner_pct_estimado, fecha_umbral_mantenimiento, modelo)
            VALUES (?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE contador_bn_estimado=VALUES(contador_bn_estimado), contador_color_estimado=VALUES(contador_color_estimado),
                mae_pct=VALUES(mae_pct), meses_historial=VALUES(meses_historial), preliminar=VALUES(preliminar),
                toner_pct_estimado=VALUES(toner_pct_estimado), fecha_umbral_mantenimiento=VALUES(fecha_umbral_mantenimiento),
                modelo=VALUES(modelo), generada_en=CURRENT_TIMESTAMP");
        $colEst = $col ? $col['estimado'] : 0;
        $prel = $bn['preliminar'] ? 1 : 0;
        $tonerR = round($tonerCierre, 2);
        $fu = $fechaUmbral->format('Y-m-d');
        $st->bind_param("isiidiidss", $eid, $periodo, $bn['estimado'], $colEst, $mae, $bn['meses'], $prel, $tonerR, $fu, $bn['modelo']);
        $st->execute();
    }

    // Ticket de mantenimiento preventivo automático (RF-06 -> RF-03)
    $ticket = null;
    if ($diasCritico <= MOTOR_HORIZONTE_DIAS) {
        $ab = motor_filas($conn, "SELECT COUNT(*) n FROM tickets_mantenimiento WHERE equipo_id = ? AND estado = 'Pendiente'", 'i', $eid);
        if (!(int)$ab[0]['n']) {
            $agenda = $fechaUmbral->modify('-' . MOTOR_ANTICIPACION_VISITA . ' days');
            if ($agenda < $hoy) $agenda = $hoy;
            $paginasMes = (int)round($velocidad * 30);
            $motivo = $porToner
                ? "Desgaste de tóner proyectado: nivel crítico (" . MOTOR_TONER_CRITICO_PCT . "%) hacia " . $fechaUmbral->format('Y-m-d') . " (aprox. $paginasMes págs/mes)."
                : "Se alcanzará el umbral de $umbral copias para mantenimiento hacia " . $fechaUmbral->format('Y-m-d') . ".";
            $tec = motor_tecnico_menos_cargado($conn);
            $fa = $agenda->format('Y-m-d');
            $st = $conn->prepare("INSERT INTO tickets_mantenimiento (equipo_id, motivo, tecnico_id, fecha_agendada) VALUES (?,?,?,?)");
            $st->bind_param("isis", $eid, $motivo, $tec, $fa);
            $st->execute();
            $msg = "Ticket de mantenimiento preventivo agendado para {$eq['serie']} ($fa).";
            $st = $conn->prepare("INSERT INTO notificaciones (tipo, mensaje, equipo_id) VALUES ('MANTENIMIENTO', ?, ?)");
            $st->bind_param("si", $msg, $eid);
            $st->execute();
            $ticket = $fa;
        }
    }

    return ['equipo' => $eq['serie'], 'periodo' => $periodo, 'estimado_bn' => $bn['estimado'], 'estimado_color' => $col ? $col['estimado'] : 0,
            'mae_pct' => $mae, 'modelo' => $bn['modelo'], 'preliminar' => $bn['preliminar'], 'toner_cierre_pct' => round($tonerCierre, 1),
            'fecha_umbral' => $fechaUmbral->format('Y-m-d'), 'ticket_agendado' => $ticket];
}

/* ------------------------- Omisiones y contingencia ------------------------- */

/** RF-05: alerta de equipos sin reporte y, al vencer el plazo, aplicación automática de la estimación. */
function motor_revisar_omisiones($conn, DateTimeImmutable $hoy, $cfg) {
    $periodo = $hoy->format('Y-m');
    $dia = (int)$hoy->format('j');
    $plazo = (int)($cfg['plazo_dia'] ?? 7);
    $diaAlerta = max(1, $plazo - (int)($cfg['alerta_dias_antes'] ?? 5));
    $out = ['alertas' => 0, 'aplicadas' => 0];

    $pend = motor_filas($conn, "SELECT e.id, e.serie FROM equipos e
        WHERE e.estado <> 'Fuera de servicio'
          AND NOT EXISTS (SELECT 1 FROM lecturas l WHERE l.equipo_id = e.id AND l.origen <> 'ESTIMADO' AND DATE_FORMAT(l.fecha,'%Y-%m') = ?)",
        's', $periodo);
    foreach ($pend as $eq) {
        $eid = (int)$eq['id'];
        if ($dia >= $diaAlerta) {
            $msg = "Equipo {$eq['serie']} sin reporte de $periodo (plazo: día $plazo). Se activó el motor de contingencia.";
            $ya = motor_filas($conn, "SELECT COUNT(*) n FROM notificaciones WHERE equipo_id = ? AND tipo = 'OMISION' AND mensaje = ?", 'is', $eid, $msg);
            if (!(int)$ya[0]['n']) {
                $st = $conn->prepare("INSERT INTO notificaciones (tipo, mensaje, equipo_id) VALUES ('OMISION', ?, ?)");
                $st->bind_param("si", $msg, $eid);
                $st->execute();
                $out['alertas']++;
            }
        }
        if ($dia >= $plazo) {
            $st = $conn->prepare("UPDATE proyecciones SET aplicada = 1, aplicada_por = NULL, aplicada_en = NOW() WHERE equipo_id = ? AND periodo = ? AND aplicada = 0");
            $st->bind_param("is", $eid, $periodo);
            $st->execute();
            $out['aplicadas'] += $st->affected_rows;
        }
    }
    return $out;
}

/** Ciclo completo: proyecciones + tickets + alertas de omisión. */
function motor_ejecutar($conn, ?DateTimeImmutable $hoy = null, $cfg = null) {
    $hoy = $hoy ?: new DateTimeImmutable('today noon');
    $cfg = $cfg ?: (file_exists(__DIR__ . '/../config/ingesta.php') ? require __DIR__ . '/../config/ingesta.php' : []);
    $equipos = motor_filas($conn, "SELECT * FROM equipos WHERE estado <> 'Fuera de servicio'");
    $res = [];
    foreach ($equipos as $eq) {
        try { $res[] = motor_procesar_equipo($conn, $eq, $hoy); }
        catch (Throwable $e) { $res[] = ['equipo' => $eq['serie'], 'error' => $e->getMessage()]; }
    }
    return ['equipos' => $res, 'omisiones' => motor_revisar_omisiones($conn, $hoy, $cfg)];
}
