<?php
include 'conexion.php';
date_default_timezone_set('America/El_Salvador');

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['agregar'])) {
        $cliente_nombre = $_POST['cliente_nombre'];
        $fecha_pago = $_POST['fecha_pago'];
        $tipo_membresia = $_POST['tipo_membresia'];
        $pago_pendiente = isset($_POST['pago_pendiente']) ? 1 : 0;
        $monto_pagado = $_POST['monto_pagado'];
        $comentarios = $_POST['comentarios'];
        
        // Obtener el ID del cliente a partir del nombre
        $sql = "SELECT id FROM Clientes WHERE nombre = '$cliente_nombre'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $cliente_id = $row['id'];
            
            // Obtener el precio del tipo de membresía o producto
            $precio = getPrice($tipo_membresia);

            // Calcular la fecha de expiración
            $fecha_expiracion = calculateExpiration($fecha_pago, $tipo_membresia);

            // Si el pago no es pendiente, establecer el monto pagado como el precio total
            if (!$pago_pendiente) {
                $monto_pagado = $precio;
            }

            $sql = "INSERT INTO Pagos (cliente_id, fecha_pago, tipo_membresia, fecha_expiracion, precio, pago_pendiente, monto_pagado, comentarios) 
                    VALUES ('$cliente_id', '$fecha_pago', '$tipo_membresia', '$fecha_expiracion', '$precio', '$pago_pendiente', '$monto_pagado', '$comentarios')";

            if ($conn->query($sql) === TRUE) {
                header("Location: pagos.php");
                exit();
            } else {
                $mensaje = "Error: " . $sql . "<br>" . $conn->error;
            }
        } else {
                header("Location: pagos.php");
                exit();
        }
    }

    if (isset($_POST['eliminar'])) {
        $id = $_POST['id'];

        $sql = "DELETE FROM Pagos WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
                header("Location: pagos.php");
                exit();
        } else {
            $mensaje = "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    if (isset($_POST['editar'])) {
        $id = $_POST['id'];
        $cliente_nombre = $_POST['cliente_nombre'];
        $fecha_pago = $_POST['fecha_pago'];
        $tipo_membresia = $_POST['tipo_membresia'];
        $pago_pendiente = isset($_POST['pago_pendiente']) ? 1 : 0;
        $monto_pagado = $_POST['monto_pagado'];
        $comentarios = $_POST['comentarios'];

        // Obtener el ID del cliente a partir del nombre
        $sql = "SELECT id FROM Clientes WHERE nombre = '$cliente_nombre'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $cliente_id = $row['id'];

            // Obtener el precio del tipo de membresía o producto
            $precio = getPrice($tipo_membresia);

            // Calcular la fecha de expiración
            $fecha_expiracion = calculateExpiration($fecha_pago, $tipo_membresia);

            // Si el pago no es pendiente, establecer el monto pagado como el precio total
            if (!$pago_pendiente) {
                $monto_pagado = $precio;
            }

            $sql = "UPDATE Pagos SET cliente_id='$cliente_id', fecha_pago='$fecha_pago', tipo_membresia='$tipo_membresia', fecha_expiracion='$fecha_expiracion', precio='$precio', pago_pendiente='$pago_pendiente', monto_pagado='$monto_pagado', comentarios='$comentarios' WHERE id='$id'";

            if ($conn->query($sql) === TRUE) {
                header("Location: pagos.php");
                exit();
            } else {
                $mensaje = "Error: " . $sql . "<br>" . $conn->error;
            }
        } else {
                header("Location: pagos.php");
                exit();
        }
    }
}

function calculateExpiration($fecha_pago, $tipo_membresia) {
    if (in_array($tipo_membresia, ['Diaria', 'Semanal', 'Quincenal', 'Mensual'])) {
        $fecha = new DateTime($fecha_pago);
        if ($tipo_membresia == 'Diaria') {
            $fecha->modify('+1 day');
        } elseif ($tipo_membresia == 'Semanal') {
            $fecha->modify('+7 days');
        } elseif ($tipo_membresia == 'Quincenal') {
            $fecha->modify('+15 days');
        } else {
            $fecha->modify('+30 days');
        }
        return $fecha->format('Y-m-d');
    }
    return "No aplica";
}

function getPrice($tipo_membresia) {
    global $conn;
    if (in_array($tipo_membresia, ['Diaria', 'Semanal','Quincenal', 'Mensual'])) {
        // Define precios para tipos de membresía
        $precios = [
            'Diaria' => 1,
            'Semanal' => 3,
            'Quincenal' => 6,
            'Mensual' => 10
        ];
        return $precios[$tipo_membresia];
    } else {
        // Obtener precio del producto
        $sql = "SELECT precio FROM Productos WHERE nombre = '$tipo_membresia'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['precio'];
        }
    }
    return 0.00;
}

function getMembershipOptions() {
    global $conn;
    $options = [
        'Diaria' => 'Diaria',
        'Semanal' => 'Semanal',
        'Quincenal' => 'Quincenal',
        'Mensual' => 'Mensual'
    ];
    $result = $conn->query("SELECT nombre FROM Productos");
    while ($row = $result->fetch_assoc()) {
        $options[$row['nombre']] = $row['nombre'];
    }
    return $options;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagos</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="assets/css/jquery-ui.css">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <script src="assets/bootstrap/js/jquery-3.6.0.min.js"></script>
    <script src="assets/bootstrap/js/jquery-ui.js"></script>
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
         

        .checkbox-container {
            display: flex;
            align-items: center;
        }
        .checkbox-container input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }
        .mt-custom {
            margin-top: 80px; /* Asegura que el contenido no se oculte detrás del header */
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <?php include 'menu.php'; ?>
    <div class="container mt-custom" >
        <h1 class="mb-4">Gestión de Pagos</h1>
        <form method="POST" action="" class="mb-4">
            <input type="hidden" name="agregar" value="1">
            <div class="mb-3">
                <label for="cliente_nombre" class="form-label">Cliente:</label>
                <input type="text" id="cliente_nombre" name="cliente_nombre" class="form-control">
            </div>
            <div class="mb-3">
                <label for="fecha_pago" class="form-label">Fecha de Pago:</label>
                <input type="date" id="fecha_pago" name="fecha_pago" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="mb-3">
                <label for="tipo_membresia" class="form-label">Tipo de Membresía o Producto:</label>
                <select id="tipo_membresia" name="tipo_membresia" class="form-select" onchange="updateMontoPagado()">
                    <?php
                    $options = getMembershipOptions();
                    foreach ($options as $value => $label) {
                        echo "<option value=\"$value\">$label</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="checkbox-container mb-3">
                <input type="checkbox" name="pago_pendiente" id="pago_pendiente" onclick="toggleMontoPagado()">
                <label for="pago_pendiente">¿Pago Pendiente o Parcial?</label>
            </div>
            <div class="mb-3">
                <input type="number" name="monto_pagado" id="monto_pagado" class="form-control" placeholder="Monto Pagado" step="0.01" style="display:none;">
            </div>
            <div class="mb-3">
                <textarea name="comentarios" class="form-control" placeholder="Comentarios"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Registrar Pago</button>
        </form>
        <span class="text-danger"><?php echo $mensaje; ?></span>

        <h2 class="mb-4">Lista de Pagos</h2>
        <div class="mb-3 text-center">
            <label for="fecha_filtro">Mostrar fecha específica:</label>
            <input type="text" id="datepicker" class="form-control" placeholder="Selecciona una fecha..." style="width: 70%; margin: 0 auto;">
        </div>
        <input type="text" id="buscar_pago" class="form-control mb-3" placeholder="Buscar en la lista de pagos...">

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Fecha de Pago</th>
                    <th>Tipo de Membresía o Producto</th>
                    <th>Fecha de Expiración</th>
                    <th>Pago Completo</th>
                    <th>Monto Pagado</th>
                    <th>Comentarios</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="resultado_busqueda">
            <?php
            $result = $conn->query("SELECT Pagos.*, Clientes.nombre AS cliente_nombre 
                                    FROM Pagos 
                                    JOIN Clientes ON Pagos.cliente_id = Clientes.id");
            while ($row = $result->fetch_assoc()) {
                $tipo_membresia = $row['tipo_membresia'];
                $fecha_expiracion = $row['fecha_expiracion'] == 'No aplica' ? 'No aplica' : $row['fecha_expiracion'];
                $precio = getPrice($tipo_membresia);
                echo "<tr>
                        <td>{$row['cliente_nombre']}</td>
                        <td>{$row['fecha_pago']}</td>
                        <td>{$tipo_membresia} - $precio</td>
                        <td>{$fecha_expiracion}</td>
                        <td>" . ($row['pago_pendiente'] ? 'No' : 'Sí') . "</td>
                        <td>{$row['monto_pagado']}</td>
                        <td>{$row['comentarios']}</td>
                        <td>
                            <div class=\"btn-group\" role=\"group\">
                                <form method=\"POST\" action=\"\" style=\"display:inline;\">
                                    <input type=\"hidden\" name=\"eliminar\" value=\"1\">
                                    <input type=\"hidden\" name=\"id\" value=\"{$row['id']}\">
                                    <button type=\"submit\" class=\"btn btn-danger btn-sm\" onclick=\"return confirm('¿Está seguro de que desea eliminar este pago?')\">Eliminar</button>
                                </form>
                                <button class=\"btn btn-success btn-sm\" onclick=\"editPago({$row['id']}, '{$row['cliente_nombre']}', '{$row['fecha_pago']}', '{$row['tipo_membresia']}', {$row['pago_pendiente']}, {$row['monto_pagado']}, '{$row['comentarios']}')\">Editar</button>
                            </div>
                        </td>
                      </tr>";
            }
            ?>
            </tbody>
        </table>
    </div>

    <div id="dialog-form" title="Editar Pago" style="display:none;">
        <form method="POST" action="">
            <input type="hidden" name="editar" value="1">
            <input type="hidden" id="edit_id" name="id">
            <div class="mb-3">
                <label for="edit_cliente_nombre" class="form-label">Cliente:</label>
                <input type="text" id="edit_cliente_nombre" name="cliente_nombre" class="form-control">
            </div>
            <div class="mb-3">
                <label for="edit_fecha_pago" class="form-label">Fecha de Pago:</label>
                <input type="date" id="edit_fecha_pago" name="fecha_pago" class="form-control">
            </div>
            <div class="mb-3">
                <label for="edit_tipo_membresia" class="form-label">Tipo de Membresía o Producto:</label>
                <select id="edit_tipo_membresia" name="tipo_membresia" class="form-select" onchange="updateEditMontoPagado()">
                    <?php
                    $options = getMembershipOptions();
                    foreach ($options as $value => $label) {
                        echo "<option value=\"$value\">$label</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="checkbox-container mb-3">
                <input type="checkbox" name="pago_pendiente" id="edit_pago_pendiente" onclick="toggleEditMontoPagado()">
                <label for="edit_pago_pendiente">¿Pago Pendiente o Parcial?</label>
            </div>
            <div class="mb-3">
                <input type="number" name="monto_pagado" id="edit_monto_pagado" class="form-control" placeholder="Monto Pagado" step="0.01">
            </div>
            <div class="mb-3">
                <textarea name="comentarios" id="edit_comentarios" class="form-control" placeholder="Comentarios"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            $('#cliente_nombre').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: 'buscar_cliente.php',
                        type: 'GET',
                        dataType: 'json',
                        data: { term: request.term },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $('#cliente_nombre').val(ui.item.label);
                    return false;
                }
            });

            $('#buscar_pago').on('input', function() {
                var term = $(this).val().toLowerCase();
                $('#resultado_busqueda tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(term) > -1);
                });
            });

            $("#datepicker").datepicker({
                dateFormat: "yy-mm-dd",
                onSelect: function(dateText) {
                    var selectedDate = dateText;
                    $('#resultado_busqueda tr').filter(function() {
                        $(this).toggle($(this).find('td:nth-child(2)').text().indexOf(selectedDate) > -1);
                    });
                }
            }).datepicker("setDate", new Date());

            $("#dialog-form").dialog({
                autoOpen: false,
                height: 400,
                width: 350,
                modal: true
            });
        });

        function toggleMontoPagado() {
            var checkbox = document.getElementById('pago_pendiente');
            var montoPagadoInput = document.getElementById('monto_pagado');
            if (checkbox.checked) {
                montoPagadoInput.style.display = 'block';
                montoPagadoInput.value = '';
            } else {
                var tipoMembresia = document.getElementById('tipo_membresia').value;
                montoPagadoInput.value = getPrice(tipoMembresia);
                montoPagadoInput.style.display = 'none';
            }
        }

        function toggleEditMontoPagado() {
            var checkbox = document.getElementById('edit_pago_pendiente');
            var montoPagadoInput = document.getElementById('edit_monto_pagado');
            if (checkbox.checked) {
                montoPagadoInput.style.display = 'block';
                montoPagadoInput.value = '';
            } else {
                var tipoMembresia = document.getElementById('edit_tipo_membresia').value;
                montoPagadoInput.value = getPrice(tipoMembresia);
                montoPagadoInput.style.display = 'none';
            }
        }

        function getPrice(tipoMembresia) {
            switch (tipoMembresia) {
                case 'Diaria':
                    return 1;
                case 'Semanal':
                    return 3;
                case 'Quincenal':
                    return 5;
                case 'Mensual':
                    return 10;
                default:
                    return 0;
            }
        }

        function updateMontoPagado() {
            var checkbox = document.getElementById('pago_pendiente');
            var tipoMembresia = document.getElementById('tipo_membresia').value;
            if (checkbox.checked) {
                var montoPagadoInput = document.getElementById('monto_pagado');
                montoPagadoInput.value = '';
            } else {
                var montoPagadoInput = document.getElementById('monto_pagado');
                montoPagadoInput.value = getPrice(tipoMembresia);
            }
        }

        function updateEditMontoPagado() {
            var checkbox = document.getElementById('edit_pago_pendiente');
            var tipoMembresia = document.getElementById('edit_tipo_membresia').value;
            if (checkbox.checked) {
                var montoPagadoInput = document.getElementById('edit_monto_pagado');
                montoPagadoInput.value = '';
            } else {
                var montoPagadoInput = document.getElementById('edit_monto_pagado');
                montoPagadoInput.value = getPrice(tipoMembresia);
            }
        }

        function editPago(id, cliente_nombre, fecha_pago, tipo_membresia, pago_pendiente, monto_pagado, comentarios) {
            $("#edit_id").val(id);
            $("#edit_cliente_nombre").val(cliente_nombre);
            $("#edit_fecha_pago").val(fecha_pago);
            $("#edit_tipo_membresia").val(tipo_membresia);
            $("#edit_pago_pendiente").prop('checked', pago_pendiente);
            $("#edit_monto_pagado").val(monto_pagado);
            $("#edit_comentarios").val(comentarios);
            toggleEditMontoPagado();
            $("#dialog-form").dialog("open");
        }
    </script>
</body>
</html>
