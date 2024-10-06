<?php
include 'conexion.php';
date_default_timezone_set('America/El_Salvador');

// Establecer la zona horaria
date_default_timezone_set('America/El_Salvador');

// Configurar la zona horaria de la sesión de MySQL
$conn->query("SET time_zone = '-06:00'");

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['agregar'])) {
        $cliente_nombre = $_POST['cliente_nombre'];
        $fecha = $_POST['fecha'] . ' ' . date('H:i:s');
        
        // Obtener el ID del cliente a partir del nombre
        $sql = "SELECT id FROM clientes WHERE nombre = '$cliente_nombre'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $cliente_id = $row['id'];
            
            // Obtener el último tipo de membresía y fecha de expiración del cliente
            $sql = "SELECT tipo_membresia, fecha_expiracion FROM pagos WHERE cliente_id = '$cliente_id' AND tipo_membresia IN ('Diaria', 'Semanal', 'Quincenal', 'Mensual') ORDER BY fecha_expiracion DESC LIMIT 1";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $tipo_membresia = $row['tipo_membresia'];
                $fecha_expiracion = $row['fecha_expiracion'];
            } else {
                // Si no hay membresía activa, marcar como Producto y No aplica
                $tipo_membresia = 'Producto';
                $fecha_expiracion = 'No aplica';
            }

            $sql = "INSERT INTO asistencias (cliente_id, fecha, tipo_membresia, fecha_expiracion) 
                    VALUES ('$cliente_id', '$fecha', '$tipo_membresia', '$fecha_expiracion')";

            if ($conn->query($sql) === TRUE) {
                header("Location: asistencia.php");
                exit();
            } else {
                $mensaje = "Error: " . $sql . "<br>" . $conn->error;
            }
        } else {
            $mensaje = "Cliente no encontrado";
        }
    }

    if (isset($_POST['eliminar'])) {
        $id = $_POST['id'];

        $sql = "DELETE FROM asistencias WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
            header("Location: asistencia.php");
            exit();
        } else {
            $mensaje = "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Obtener fecha para filtrar
$fecha_filtro = isset($_GET['fecha_filtro']) ? $_GET['fecha_filtro'] : date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="assets/css/jquery-ui.css">
    <script src="assets/bootstrap/js/jquery-3.6.0.min.js"></script>
    <script src="assets/bootstrap/js/jquery-ui.js"></script>
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
        .deuda {
            background-color: #ffebcc; /* Fondo naranja suave */
            color: black;
        }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    <div class="container mt-4">
        <h1 class="mb-4">Registro de Asistencia</h1>
        <form method="POST" action="" class="mb-4">
            <input type="hidden" name="agregar" value="1">
            <div class="mb-3">
                <label for="fecha" class="form-label">Fecha:</label>
                <input type="date" id="fecha" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="mb-3">
                <label for="cliente_nombre" class="form-label">Cliente:</label>
                <input type="text" id="cliente_nombre" name="cliente_nombre" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Registrar Asistencia</button>
        </form>
        <span class="text-danger"><?php echo $mensaje; ?></span>

        <h2 class="mb-4">Lista de Asistencias</h2>
        <div class="mb-3">
            <label for="fecha_filtro" class="form-label" style="text-align: center;">Mostrar fecha específica:</label>
            <input type="text" id="fecha_filtro" name="fecha_filtro" class="form-control" style="width: 70%; margin: 0 auto; display: block;">
        </div>
        <div class="mb-4">
            <input type="text" id="buscar_asistencia" class="form-control" placeholder="Buscar en la lista de asistencias..." style="width: 70%; margin: 0 auto; display: block;">
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>No. Asistencia</th> <!-- Nueva columna -->
                    <th>Fecha y Hora</th>
                    <th>Cliente</th>
                    <th>Tipo de Membresía</th>
                    <th>Fecha de Expiración</th>
                    <th>Deuda</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="resultado_busqueda">
            <?php
            // Obtener datos de asistencias con número de fila
            $result = $conn->query("SELECT asistencias.*, clientes.nombre AS cliente_nombre, 
                                    IFNULL(SUM(pagos.precio - pagos.monto_pagado), 0) AS deuda 
                                    FROM asistencias 
                                    JOIN clientes ON asistencias.cliente_id = clientes.id
                                    LEFT JOIN pagos ON asistencias.cliente_id = pagos.cliente_id
                                    WHERE DATE(asistencias.fecha) = '$fecha_filtro'
                                    GROUP BY asistencias.id
                                    ORDER BY asistencias.fecha ASC");

            // Contador para número de asistencia
            $numero_asistencia = 1;
            while ($row = $result->fetch_assoc()) {
                $deuda = $row['deuda'];
                $deuda_texto = $deuda > 0 ? 'Sí, $' . number_format($deuda, 2) : 'No';
                $deuda_clase = $deuda > 0 ? 'deuda' : '';
                echo "<tr class='$deuda_clase'>
                        <td>{$numero_asistencia}</td> <!-- Nueva columna para el número de asistencia -->
                        <td>{$row['fecha']}</td>
                        <td>{$row['cliente_nombre']}</td>
                        <td>{$row['tipo_membresia']}</td>
                        <td>{$row['fecha_expiracion']}</td>
                        <td>$deuda_texto</td>
                        <td>
                            <form method=\"POST\" action=\"\" style=\"display:inline;\">
                                <input type=\"hidden\" name=\"eliminar\" value=\"1\">
                                <input type=\"hidden\" name=\"id\" value=\"{$row['id']}\">
                                <button type=\"submit\" class=\"btn btn-danger btn-sm\" onclick=\"return confirm('¿Está seguro de que desea eliminar esta asistencia?')\">Eliminar</button>
                            </form>
                        </td>
                      </tr>";
                $numero_asistencia++; // Incrementa el contador
            }
            ?>
            </tbody>
        </table>
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

            $('#fecha_filtro').datepicker({
                dateFormat: 'yy-mm-dd',
                onSelect: function(dateText) {
                    window.location.href = '?fecha_filtro=' + dateText;
                }
            }).datepicker('setDate', '<?php echo $fecha_filtro; ?>');

            $('#buscar_asistencia').on('input', function() {
                var term = $(this).val().toLowerCase();
                $('#resultado_busqueda tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(term) > -1);
                });
            });
        });
    </script>
</body>
</html>
