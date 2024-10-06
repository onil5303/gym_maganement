
<?php
include 'conexion.php';
include 'menu.php';

$fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
$fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';

// Query to get the egresos between the selected dates
$query = "SELECT * FROM egresos";
if ($fecha_inicio && $fecha_fin) {
    $query .= " WHERE fecha_hora BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}
$query .= " ORDER BY fecha_hora ASC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Egreso</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Datepicker CSS/JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Registrar Egreso</h2>
    <form action="egresos.php" method="POST">
        <div class="form-group">
            <label for="fecha_hora">Fecha y Hora:</label>
            <input type="datetime-local" id="fecha_hora" name="fecha_hora" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción del Egreso:</label>
            <input type="text" id="descripcion" name="descripcion" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="total">Total del Egreso:</label>
            <input type="number" step="0.01" id="total" name="total" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Registrar Egreso</button>
    </form>
    
    <hr>
    
    <h2 class="mt-5 mb-4">Ver Egresos Registrados</h2>
    <form action="egresos.php" method="POST" class="form-inline mb-3">
        <div class="form-group mr-3">
            <label for="fecha_inicio" class="mr-2">Fecha de inicio:</label>
            <input type="text" id="fecha_inicio" name="fecha_inicio" class="form-control datepicker" value="<?php echo $fecha_inicio; ?>">
        </div>
        <div class="form-group mr-3">
            <label for="fecha_fin" class="mr-2">Fecha de fin:</label>
            <input type="text" id="fecha_fin" name="fecha_fin" class="form-control datepicker" value="<?php echo $fecha_fin; ?>">
        </div>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
    </form>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Fecha y Hora</th>
                <th>Descripción</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['fecha_hora'] . "</td>";
                echo "<td>" . $row['descripcion'] . "</td>";
                echo "<td>" . $row['total'] . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3' class='text-center'>No se encontraron egresos</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<script>
$(document).ready(function(){
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
});
</script>

</body>
</html>
