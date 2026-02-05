<?php
include __DIR__ . '/../config/db.php';

// $empresa_id YA VIENE desde empresa.php
if (!isset($empresa_id)) {
    die("Empresa no definida");
}

$sql = "SELECT * FROM impresoras_formulario WHERE empresa_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<<table class="table table-hover align-middle text-center">
    <thead class="table-dark">
        <tr>
            <th>Marca</th>
            <th>N° Serie</th>
            <th>Contador</th>
            <th>Acciones</th>
        </tr>
    </thead>

    <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row['marca_impresora']) ?></td>
                <td><?= htmlspecialchars($row['numero_serie']) ?></td>
                <td><?= htmlspecialchars($row['contador_general']) ?></td>
                <td>
                    <a href="../../backend/crud/edit.php?id=<?= $row['id'] ?>&empresa_id=<?= $empresa_id ?>"
                       class="btn btn-sm btn-warning">
                        ✏️ Editar
                    </a>

                    <a href="../../backend/crud/delete.php?id=<?= $row['id'] ?>&empresa_id=<?= $empresa_id ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('¿Eliminar este registro?')">
                        🗑️ Eliminar
                    </a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>
