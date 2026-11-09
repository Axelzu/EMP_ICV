<?php
/**
 * Funciones compartidas de lecturas y estado de sincronización.
 * Estados de un equipo en un periodo (YYYY-MM): Reportado (lectura real), Estimado (proyección aplicada), Pendiente.
 */

/**
 * Estado de todos los equipos para un periodo.
 * Devuelve filas con: id, serie, dependencia, marca_modelo, empresa, estado, ultima_fecha, origen, proyección (si existe).
 */
function estadoEquipos($conn, $periodo, $empresa_id = 0, $departamento = '') {
    $sql = "SELECT e.id, e.serie, e.dependencia, e.marca_modelo, e.tipo_color, e.empresa_id, emp.nombre AS empresa,
                   (SELECT l.contador_bn FROM lecturas l WHERE l.equipo_id = e.id AND l.origen <> 'ESTIMADO'
                      AND DATE_FORMAT(l.fecha,'%Y-%m') = ? ORDER BY l.fecha DESC, l.id DESC LIMIT 1) AS real_bn,
                   (SELECT l.contador_color FROM lecturas l WHERE l.equipo_id = e.id AND l.origen <> 'ESTIMADO'
                      AND DATE_FORMAT(l.fecha,'%Y-%m') = ? ORDER BY l.fecha DESC, l.id DESC LIMIT 1) AS real_color,
                   (SELECT l.origen FROM lecturas l WHERE l.equipo_id = e.id AND l.origen <> 'ESTIMADO'
                      AND DATE_FORMAT(l.fecha,'%Y-%m') = ? ORDER BY l.fecha DESC, l.id DESC LIMIT 1) AS origen,
                   (SELECT l.fecha FROM lecturas l WHERE l.equipo_id = e.id AND l.origen <> 'ESTIMADO' ORDER BY l.fecha DESC, l.id DESC LIMIT 1) AS ultima_fecha,
                   p.id AS proy_id, p.contador_bn_estimado, p.contador_color_estimado, p.mae_pct, p.preliminar,
                   p.toner_pct_estimado, p.fecha_umbral_mantenimiento, p.modelo, p.aplicada
            FROM equipos e
            JOIN empresas emp ON emp.id = e.empresa_id
            LEFT JOIN proyecciones p ON p.equipo_id = e.id AND p.periodo = ?
            WHERE e.estado <> 'Fuera de servicio'";
    if ($empresa_id) $sql .= " AND e.empresa_id = " . (int)$empresa_id;
    $tipos = "ssss"; $params = [$periodo, $periodo, $periodo, $periodo];
    if ($departamento !== '') { $sql .= " AND e.dependencia = ?"; $tipos .= "s"; $params[] = $departamento; }
    $sql .= " ORDER BY emp.nombre, e.dependencia";

    $st = $conn->prepare($sql);
    $st->bind_param($tipos, ...$params);
    $st->execute();
    $res = $st->get_result();
    $filas = [];
    while ($f = $res->fetch_assoc()) {
        if ($f['real_bn'] !== null)                       $f['estado'] = 'Reportado';
        elseif ($f['proy_id'] && (int)$f['aplicada'] === 1) $f['estado'] = 'Estimado';
        else                                               $f['estado'] = 'Pendiente';
        $filas[] = $f;
    }
    return $filas;
}

function periodoValido($p) {
    return is_string($p) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $p) === 1;
}

/** Fecha válida en formato AAAA-MM-DD. */
function fechaValida($f) {
    if (!is_string($f) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) !== 1) return false;
    [$y, $m, $d] = array_map('intval', explode('-', $f));
    return checkdate($m, $d, $y);
}

/**
 * Lecturas reales de los equipos en un rango de fechas (RF-04: informe por rango y departamento).
 * Por equipo: cierre = última lectura real dentro del rango; apertura = última lectura real anterior al rango.
 * Devuelve filas con: id, serie, dependencia, marca_modelo, empresa, cierre_bn, cierre_color, origen, ap_bn, ap_color (null si no hay) y fecha_cierre.
 * Los equipos sin lectura real en el rango se devuelven con cierre_bn = null.
 */
function lecturasRango($conn, $desde, $hasta, $empresa_id = 0, $departamento = '') {
    $sql = "SELECT e.id, e.serie, e.dependencia, e.marca_modelo, emp.nombre AS empresa
            FROM equipos e JOIN empresas emp ON emp.id = e.empresa_id
            WHERE e.estado <> 'Fuera de servicio'";
    $tipos = ""; $params = [];
    if ($empresa_id) { $sql .= " AND e.empresa_id = ?"; $tipos .= "i"; $params[] = (int)$empresa_id; }
    if ($departamento !== '') { $sql .= " AND e.dependencia = ?"; $tipos .= "s"; $params[] = $departamento; }
    $sql .= " ORDER BY emp.nombre, e.dependencia";
    $st = $conn->prepare($sql);
    if ($params) $st->bind_param($tipos, ...$params);
    $st->execute();
    $equipos = $st->get_result()->fetch_all(MYSQLI_ASSOC);

    $ini = $desde . ' 00:00:00';
    $fin = $hasta . ' 23:59:59';
    $stCierre = $conn->prepare("SELECT contador_bn, contador_color, origen, fecha FROM lecturas
                                WHERE equipo_id = ? AND origen <> 'ESTIMADO' AND fecha BETWEEN ? AND ?
                                ORDER BY fecha DESC, id DESC LIMIT 1");
    $stApert = $conn->prepare("SELECT contador_bn, contador_color FROM lecturas
                               WHERE equipo_id = ? AND origen <> 'ESTIMADO' AND fecha < ?
                               ORDER BY fecha DESC, id DESC LIMIT 1");
    foreach ($equipos as &$e) {
        $eid = (int)$e['id'];
        $stCierre->bind_param("iss", $eid, $ini, $fin);
        $stCierre->execute();
        $c = $stCierre->get_result()->fetch_assoc();
        $stApert->bind_param("is", $eid, $ini);
        $stApert->execute();
        $a = $stApert->get_result()->fetch_assoc();
        $e['cierre_bn']    = $c ? (int)$c['contador_bn'] : null;
        $e['cierre_color'] = $c ? (int)$c['contador_color'] : null;
        $e['origen']       = $c ? $c['origen'] : null;
        $e['fecha_cierre'] = $c ? $c['fecha'] : null;
        $e['ap_bn']        = $a ? (int)$a['contador_bn'] : null;
        $e['ap_color']     = $a ? (int)$a['contador_color'] : null;
    }
    unset($e);
    return $equipos;
}
