<?php
/**
 * Funciones compartidas de lecturas y estado de sincronización.
 * Estados de un equipo en un periodo (YYYY-MM): Reportado (lectura real), Estimado (proyección aplicada), Pendiente.
 */

/**
 * Estado de todos los equipos para un periodo.
 * Devuelve filas con: id, serie, dependencia, marca_modelo, empresa, estado, ultima_fecha, origen, proyección (si existe).
 */
function estadoEquipos($conn, $periodo, $empresa_id = 0) {
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
    $sql .= " ORDER BY emp.nombre, e.dependencia";

    $st = $conn->prepare($sql);
    $st->bind_param("ssss", $periodo, $periodo, $periodo, $periodo);
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
