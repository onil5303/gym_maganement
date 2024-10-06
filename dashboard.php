<?php
include 'conexion.php';
date_default_timezone_set('America/El_Salvador');

// Configurar el locale a español para que los nombres de los meses aparezcan en español
setlocale(LC_TIME, 'es_ES.UTF-8');

// Obtener la fecha seleccionada (por defecto es hoy)
$fecha_seleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// Obtener el total de visitas del día
$total_visitas_hoy = $conn->query("SELECT COUNT(*) AS total FROM Asistencias WHERE DATE(fecha) = CURDATE()")->fetch_assoc()['total'];

// Obtener el ingreso del día seleccionado
$ingreso_dia_seleccionado = $conn->query("
    SELECT SUM(precio) AS total 
    FROM Pagos 
    WHERE DATE(fecha_pago) = '$fecha_seleccionada'
")->fetch_assoc()['total'];

// Obtener los egresos del día seleccionado
$egreso_dia_seleccionado = $conn->query("
    SELECT SUM(total) AS total 
    FROM egresos 
    WHERE DATE(fecha_hora) = '$fecha_seleccionada'
")->fetch_assoc()['total'];

// Obtener los ingresos mensuales del año seleccionado
$anio_seleccionado = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

$ingresos_mensuales = $conn->query("
    SELECT MONTH(fecha_pago) AS mes, SUM(precio) AS total 
    FROM Pagos 
    WHERE YEAR(fecha_pago) = $anio_seleccionado
    GROUP BY MONTH(fecha_pago)
")->fetch_all(MYSQLI_ASSOC);

// Obtener los egresos mensuales del año seleccionado
$egresos_mensuales = $conn->query("
    SELECT MONTH(fecha_hora) AS mes, SUM(total) AS total 
    FROM egresos 
    WHERE YEAR(fecha_hora) = $anio_seleccionado
    GROUP BY MONTH(fecha_hora)
")->fetch_all(MYSQLI_ASSOC);

// Obtener el total de dinero pendiente basado en los clientes que deben
$sql_clientes_deben = "
    SELECT 
        c.nombre AS nombre_cliente,
        SUM(p.precio - COALESCE(p.monto_pagado, 0)) AS monto_pendiente
    FROM 
        Pagos p
    INNER JOIN 
        Clientes c ON p.cliente_id = c.id
    WHERE 
        p.pago_pendiente = 1
    GROUP BY 
        p.cliente_id
";
$result_clientes_deben = $conn->query($sql_clientes_deben);

$total_pendiente = 0;
while ($row = $result_clientes_deben->fetch_assoc()) {
    $total_pendiente += $row['monto_pendiente'];
}

// Obtener el total recolectado y egresos totales en todo el año
$total_anual = $conn->query("
    SELECT SUM(precio) AS total_anual
    FROM Pagos
    WHERE YEAR(fecha_pago) = $anio_seleccionado
")->fetch_assoc()['total_anual'];
if ($total_anual === null) {
    $total_anual = 0;
}

$total_egresos_anual = $conn->query("
    SELECT SUM(total) AS total_egresos_anual
    FROM egresos
    WHERE YEAR(fecha_hora) = $anio_seleccionado
")->fetch_assoc()['total_egresos_anual'];
if ($total_egresos_anual === null) {
    $total_egresos_anual = 0;
}


$total_ingresos_menos_egresos = $conn->query("
    SELECT 
        IFNULL(SUM(precio), 0) - IFNULL(
            (SELECT SUM(total) FROM egresos WHERE YEAR(fecha_hora) = $anio_seleccionado), 
            0
        ) AS total_ingresos_menos_egresos
    FROM Pagos
    WHERE YEAR(fecha_pago) = $anio_seleccionado
")->fetch_assoc()['total_ingresos_menos_egresos'];

if ($total_ingresos_menos_egresos === null) {
    $total_ingresos_menos_egresos = 0;
}



?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
    <script>
    $(function() {
        $("#datepicker").datepicker({
            dateFormat: "yy-mm-dd",
            onSelect: function(dateText) {
                window.location.href = "dashboard.php?fecha=" + dateText;
            }
        });

        $("#selectAnio").on("change", function() {
            var anio = $("#selectAnio").val();
            window.location.href = "dashboard.php?anio=" + anio;
        });
    });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.5.1/chart.min.js"></script>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/jquery-ui.css">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <?php include 'menu.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4">Resumen</h1>

        <div class="row mb-4">
            <!-- Ingresos del día -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">Total de ingresos del día</h3>
                        <div class="form-group">
                            <label for="datepicker">Selecciona un día:</label>
                            <input type="text" id="datepicker" name="fecha" class="form-control" value="<?php echo htmlspecialchars($fecha_seleccionada); ?>">
                        </div>
                        <h3 class="card-text" style="text-align: center">$<?php echo number_format($ingreso_dia_seleccionado, 2); ?></h3>
                    </div>
                </div>

                            <!-- Ingresos y Egresos Mensuales -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h3 class="card-title">Ingresos y Egresos Mensuales del Año</h3>
                        <div class="form-group">
                            <label for="selectAnio">Selecciona un año:</label>
                            <select id="selectAnio" class="form-control">
                                <?php
                                $anio_actual = date('Y');
                                for ($i = $anio_actual; $i >= $anio_actual - 10; $i--) {
                                    $selected = ($i == $anio_seleccionado) ? 'selected' : '';
                                    echo "<option value='$i' $selected>$i</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <ul class="list-group mt-3">
                            <?php foreach ($ingresos_mensuales as $data): ?>
                                <li class="list-group-item"><?php echo ucfirst(strftime('%B', mktime(0, 0, 0, $data['mes'], 1))) . ': Ingresos $' . number_format($data['total'], 2); ?></li>
                            <?php endforeach; ?>
                            <?php foreach ($egresos_mensuales as $data): ?>
                                <li class="list-group-item"><?php echo ucfirst(strftime('%B', mktime(0, 0, 0, $data['mes'], 1))) . ': Egresos $' . number_format($data['total'], 2); ?></li>
                            <?php endforeach; ?>
                            <li class="list-group-item font-weight-bold">Total Ingresos Anual: $<?php echo number_format($total_anual, 2); ?></li>
                            <li class="list-group-item font-weight-bold">Total Egresos Anual: $<?php echo number_format($total_egresos_anual, 2); ?></li>
                            <li class="list-group-item font-weight-bold">Ingreso Anual Final : $<?php echo number_format($total_ingresos_menos_egresos, 2); ?></li>
                        </ul>
                    </div>
                </div>


            </div>

            <!-- Egresos del día -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">Total de egresos del día</h3>
                        <h3 class="card-text" style="text-align: center">$<?php echo number_format($egreso_dia_seleccionado, 2); ?></h3>
                    </div>
                </div>

            </div>

            <!-- Dinero pendiente -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">Total dinero pendiente</h3>
                        <h3 class="card-text" style="text-align: center">$<?php echo number_format($total_pendiente, 2); ?></h3>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <h3 class="card-title">Clientes que deben</h3>
                        <ul class="list-group">
                            <?php $result_clientes_deben->data_seek(0); // Reiniciar puntero de resultados ?>
                            <?php while ($row = $result_clientes_deben->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <?php echo $row['nombre_cliente'] . ': $' . number_format($row['monto_pendiente'], 2); ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>
            </div>


        </div>
    </div>
</body>
</html>
