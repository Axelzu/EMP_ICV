<?php
require "../config/db.php";
require "../auth/guard.php";
require "../lib/mac.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id = $_POST['empresa_id'];
    $dep    = $_POST['dependencia'];
    $mod    = $_POST['marca_modelo'];
    $ser    = $_POST['serie'];
    $tipo   = $_POST['tipo_color'];

    // MAC opcional: se usa para reconocer los avisos automáticos de la impresora
    $mac_txt = trim($_POST['mac'] ?? '');
    $mac = $mac_txt === '' ? null : normalizarMac($mac_txt);
    if ($mac_txt !== '' && $mac === null) {
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&error=mac_invalida");
        exit;
    }

    // 1. PRIMERO: Verificamos si la serie ya existe para no lanzar el error fatal
    $check_sql = "SELECT serie FROM equipos WHERE serie = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $ser);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Si ya existe, regresamos con un mensaje de error amigable
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&error=serie_duplicada");
        exit;
    }

    // La MAC identifica una sola impresora: no puede repetirse
    if ($mac !== null) {
        $chk = $conn->prepare("SELECT id FROM equipos WHERE mac = ?");
        $chk->bind_param("s", $mac);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&error=mac_duplicada");
            exit;
        }
    }

    // 2. SI NO EXISTE: Procedemos con el INSERT
    // qr_token: identificador único que se imprime en el código QR pegado al equipo (RF-03)
    $sql = "INSERT INTO equipos (empresa_id, dependencia, marca_modelo, serie, tipo_color, mac, qr_token) VALUES (?, ?, ?, ?, ?, ?, MD5(CONCAT(?, RAND(), NOW())))";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error en la preparación: " . $conn->error);
    }

    $stmt->bind_param("issssss", $emp_id, $dep, $mod, $ser, $tipo, $mac, $ser);

    if ($stmt->execute()) {
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&msg=equipo_creado");
        exit;
    } else {
        echo "Error al registrar: " . $stmt->error;
    }
}
