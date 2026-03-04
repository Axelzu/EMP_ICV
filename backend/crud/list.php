<?php
include __DIR__ . '/../config/db.php';

// $empresa_id YA VIENE desde empresa.php
if (!isset($empresa_id)) {
    die("Empresa no definida");
}

// Consultamos las nuevas columnas de la base de datos
$sql = "SELECT id, dependencia, marca_modelo, serie, copias_bn, copias_color, impresiones_bn, impresiones_color 
        FROM impresoras_formulario 
        WHERE empresa_id = ? 
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<table class="table table-hover align-middle text-center">
    <thead class="table-dark">
        <tr>
            <th class="small">Depto / Dependencia</th>
            <th class="small">Marca/Modelo</th>
            <th class="small">Serie</th>
            <th class="small">Cop. B/N</th>
            <th class="small">Cop. Col</th>
            <th class="small">Imp. B/N</th>
            <th class="small">Imp. Col</th>
            <th class="small text-info">Total</th>
            <th class="small">Acciones</th>
        </tr>
    </thead>

    <tbody>
        <?php while ($row = $result->fetch_assoc()) { 
            // Sumamos todos los contadores para mostrar el total en la tabla
            $total_general = $row['copias_bn'] + $row['copias_color'] + $row['impresiones_bn'] + $row['impresiones_color'];
        ?>
            <tr>
                <td class="small fw-bold"><?= htmlspecialchars($row['dependencia']) ?></td>
                <td class="small"><?= htmlspecialchars($row['marca_modelo']) ?></td>
                <td class="small"><?= htmlspecialchars($row['serie']) ?></td>
                <td class="small"><?= number_format($row['copias_bn']) ?></td>
                <td class="small"><?= number_format($row['copias_color']) ?></td>
                <td class="small"><?= number_format($row['impresiones_bn']) ?></td>
                <td class="small"><?= number_format($row['impresiones_color']) ?></td>
                <td class="small fw-bold text-primary"><?= number_format($total_general) ?></td>
                <td>
                    <div class="btn-group">
                        <a href="../../backend/crud/edit.php?id=<?= $row['id'] ?>&empresa_id=<?= $empresa_id ?>"
                           class="btn btn-sm btn-warning">
                           ✏️
                        </a>

                        <a href="../../backend/crud/delete.php?id=<?= $row['id'] ?>&empresa_id=<?= $empresa_id ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('¿Eliminar este registro?')">
                           🗑️
                        </a>
                    </div>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>