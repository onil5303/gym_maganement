<?php
include 'conexion.php';

$cliente_id = $_GET['cliente_id'];

$sql = "SELECT IF(SUM(precio - monto_pagado) > 0, 1, 0) AS debe_dinero
        FROM Pagos
        WHERE cliente_id = '$cliente_id' AND (precio - monto_pagado) > 0";
$result = $conn->query($sql);

$response = array('debe_dinero' => 0);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $response['debe_dinero'] = $row['debe_dinero'];
}

echo json_encode($response);
?>
