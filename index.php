<?php

include 'conexion.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Management</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-4">Bienvenido al sistema de gestión del gimnasio</h1>
        <nav class="navbar navbar-expand-lg navbar-light bg-light" >
            <ul class="navbar-nav mr-auto" style="text-align: center">
                <li class="nav-item"><a class="nav-link" href="clientes.php">1-Clientes</a></li>
                <li class="nav-item"><a class="nav-link" href="pagos.php">2-Pagos</a></li>
                <li class="nav-item"><a class="nav-link" href="asistencia.php">3-Asistencia</a></li>
                <li class="nav-item"><a class="nav-link" href="egresos.php">Egresos</a></li>
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Resumen</a></li>
                <li class="nav-item"><a class="nav-link" href="productos.php">Productos</a></li>
            </ul>
        </nav>
    </div>
</body>
</html>
