<?php
require "../config/db.php";
require "../auth/guard.php";
require "../lib/mac.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Capturamos los datos que vienen del formulario
    $emp_id    = $_POST['empresa_id'];
    $dep       = $_POST['dependencia'];
    $mod       = $_POST['marca_modelo'];
    $ser_nueva = $_POST['serie']; // La serie que el usuario pudo haber editado
    $ser_old   = $_POST['serie_original']; // Necesitamos la serie anterior para el WHERE
    $tipo      = $_POST['tipo_color'];

    // MAC opcional (vacía = sin MAC)
    $mac_txt = trim($_POST['mac'] ?? '');
    $mac = $mac_txt === '' ? null : normalizarMac($mac_txt);
    if ($mac_txt !== '' && $mac === null) {
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&error=mac_invalida");
        exit;
    }
    if ($mac !== null) {
        // No puede coincidir con la MAC de otro equipo
        $chk = $conn->prepare("SELECT id FROM equipos WHERE mac = ? AND serie <> ?");
        $chk->bind_param("ss", $mac, $ser_old);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&error=mac_duplicada");
            exit;
        }
    }

    // 2. Actualizamos los datos del equipo donde la serie coincida con la original
    $sql = "UPDATE equipos
            SET dependencia = ?, marca_modelo = ?, serie = ?, tipo_color = ?, mac = ?
            WHERE serie = ? AND empresa_id = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error en la preparación de la base de datos: " . $conn->error);
    }

    // "ssssssi" -> 6 Strings (dep, mod, ser_nueva, tipo, mac, ser_old) y 1 Entero (emp_id)
    $stmt->bind_param("ssssssi", $dep, $mod, $ser_nueva, $tipo, $mac, $ser_old, $emp_id);

    if ($stmt->execute()) {
        // Si todo sale bien, regresamos a la página de la empresa con mensaje de éxito
        header("Location: ../../frontend/pages/empresa.php?empresa_id=$emp_id&status=updated");
        exit;
    } else {
        echo "Error al actualizar el equipo: " . $stmt->error;
    }
} else {
    echo "Acceso no permitido.";
}
