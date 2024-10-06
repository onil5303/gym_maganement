<?php
include 'conexion.php';

$term = $_GET['term'];
$sql = "SELECT id, nombre FROM Productos WHERE nombre LIKE '%$term%'";
$result = $conn->query($sql);

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = ["id" => $row['id'], "label" => $row['nombre']];
}

echo json_encode($suggestions);
?>