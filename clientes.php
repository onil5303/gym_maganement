<?php
include 'conexion.php';
date_default_timezone_set('America/El_Salvador');

$nombreError = $telefonoError = $mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['agregar'])) {
        $nombre = $_POST['nombre'];
        $telefono = $_POST['telefono'];

        // Validación del formulario
        if (empty($nombre)) {
            $nombreError = "El nombre es obligatorio.";
        }

        if (empty($telefono)) {
            $telefonoError = "El teléfono es obligatorio.";
        } elseif (!preg_match("/^[0-9]{8}$/", $telefono)) {
            $telefonoError = "El teléfono debe tener 8 dígitos.";
        }

        // Si no hay errores, registrar el cliente
        if (empty($nombreError) && empty($telefonoError)) {
            $sql = "INSERT INTO Clientes (nombre, telefono) VALUES ('$nombre', '$telefono')";
            if ($conn->query($sql) === TRUE) {
                // Redirigir a la misma página después de registrar el cliente
                header("Location: clientes.php");
                exit();
            } else {
                $mensaje = "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    }

    if (isset($_POST['eliminar'])) {
        $id = $_POST['id'];

        $sql = "DELETE FROM Clientes WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
            header("Location: clientes.php");
            exit();
        } else {
            $mensaje = "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    if (isset($_POST['editar'])) {
        $id = $_POST['id'];
        $nombre = $_POST['nombre'];
        $telefono = $_POST['telefono'];

        // Validación del formulario
        if (empty($nombre)) {
            $nombreError = "El nombre es obligatorio.";
        }

        if (empty($telefono)) {
            $telefonoError = "El teléfono es obligatorio.";
        } elseif (!preg_match("/^[0-9]{8}$/", $telefono)) {
            $telefonoError = "El teléfono debe tener 8 dígitos.";
        }

        // Si no hay errores, actualizar el cliente
        if (empty($nombreError) && empty($telefonoError)) {
            $sql = "UPDATE Clientes SET nombre='$nombre', telefono='$telefono' WHERE id='$id'";
            if ($conn->query($sql) === TRUE) {
                // Redirigir a la misma página después de actualizar el cliente
                header("Location: clientes.php");
                exit();
            } else {
                $mensaje = "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    }
}

// Obtener la lista de clientes con su última membresía y si deben dinero
$clientes = $conn->query("
SELECT c.id, c.nombre, c.telefono, 
       IFNULL(p.fecha_expiracion, 'No aplica') AS fecha_expiracion,
       CASE 
           WHEN p.fecha_expiracion IS NULL THEN 'Sin membresía'
           WHEN p.fecha_expiracion > CURDATE() THEN 'Vigente'
           WHEN p.fecha_expiracion = CURDATE() THEN 'Pendiente de pago'
           WHEN p.fecha_expiracion < CURDATE() THEN 'Expirada'
       END AS estado_membresia,
       IFNULL(SUM(p2.precio - p2.monto_pagado), 0) AS monto_deuda
FROM Clientes c
LEFT JOIN (
    SELECT cliente_id, MAX(fecha_expiracion) AS fecha_expiracion
    FROM Pagos
    WHERE tipo_membresia IN ('Diaria', 'Semanal', 'Quincenal', 'Mensual')
    GROUP BY cliente_id
) p ON c.id = p.cliente_id
LEFT JOIN Pagos p2 ON c.id = p2.cliente_id AND p2.pago_pendiente = 1
GROUP BY c.id
ORDER BY c.nombre ASC
");

function getEstadoColor($estado) {
    switch ($estado) {
        case 'Vigente':
            return 'rgba(0, 128, 0, 0.1)';
        case 'Pendiente de pago':
            return 'rgba(255, 165, 0, 0.1)';
        case 'Expirada':
            return 'rgba(255, 0, 0, 0.1)';
        case 'Sin membresía':
            return 'rgba(128, 128, 128, 0)';
        default:
            return '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/jquery-ui.css">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    <div class="container mt-4">
        <h1 class="mb-4">Gestión de Clientes</h1>
        <form method="POST" action="" class="mb-4">
            <input type="hidden" name="agregar" value="1">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre:</label>
                <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo isset($nombre) ? $nombre : ''; ?>">
                <span class="text-danger"><?php echo $nombreError; ?></span>
            </div>
            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono:</label>
                <input type="text" id="telefono" name="telefono" class="form-control" value="<?php echo isset($telefono) ? $telefono : ''; ?>">
                <span class="text-danger"><?php echo $telefonoError; ?></span>
            </div>
            <button type="submit" class="btn btn-primary">Registrar Cliente</button>
        </form>
        <span class="text-danger"><?php echo $mensaje; ?></span>

        <h2 class="mb-4">Lista de Clientes</h2>
        <input type="text" id="buscar_cliente" class="form-control mb-3" placeholder="Buscar cliente...">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Fecha de Vencimiento</th>
                    <th>Estado de Membresía</th>
                    <th>Debe Dinero</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="resultado_busqueda">
            <?php
            while ($row = $clientes->fetch_assoc()) {
                $debe_dinero = $row['monto_deuda'] > 0 ? 'Sí - ' . number_format($row['monto_deuda'], 2) : '-';
                $estado_color = getEstadoColor($row['estado_membresia']);
                echo "<tr style='background-color: $estado_color;'>";
                echo "<td>{$row['nombre']}</td>";
                echo "<td>{$row['telefono']}</td>";
                echo "<td>{$row['fecha_expiracion']}</td>";
                echo "<td>{$row['estado_membresia']}</td>";
                echo "<td style='text-align: center;'>{$debe_dinero}</td>";
                echo "<td>
                        <form method='POST' action='' style='display:inline;'>
                            <input type='hidden' name='eliminar' value='1'>
                            <input type='hidden' name='id' value='{$row['id']}'>
                            <button type='submit' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Está seguro de que desea eliminar este cliente?\")'>Eliminar</button>
                        </form>
                        <button class='btn btn-success btn-sm button-edit' onclick=\"editCliente('{$row['id']}', '{$row['nombre']}', '{$row['telefono']}')\" style='background-color: green;'>Editar</button>
                      </td>";
                echo "</tr>";
            }
            ?>
            </tbody>
        </table>
    </div>

    <div id="dialog-form" title="Editar Cliente" style="display:none;">
        <form method="POST" action="">
            <input type="hidden" name="editar" value="1">
            <input type="hidden" id="edit_id" name="id">
            <div class="mb-3">
                <label for="edit_nombre" class="form-label">Nombre:</label>
                <input type="text" id="edit_nombre" name="nombre" class="form-control">
            </div>
            <div class="mb-3">
                <label for="edit_telefono" class="form-label">Teléfono:</label>
                <input type="text" id="edit_telefono" name="telefono" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    </div>

    <script src="assets/bootstrap/js/jquery-3.6.0.min.js"></script>
    <script src="assets/bootstrap/js/jquery-ui.js"></script>
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#buscar_cliente').on('input', function() {
                var term = $(this).val().toLowerCase();
                $('#resultado_busqueda tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(term) > -1);
                });
            });

            $("#dialog-form").dialog({
                autoOpen: false,
                height: 400,
                width: 350,
                modal: true
            });
        });

        function editCliente(id, nombre, telefono) {
            $("#edit_id").val(id);
            $("#edit_nombre").val(nombre);
            $("#edit_telefono").val(telefono);
            $("#dialog-form").dialog("open");
        }
    </script>
</body>
</html>
