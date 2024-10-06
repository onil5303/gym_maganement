
<?php
$servername = getenv('DB_SERVERNAME');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');

$conn = new mysqli($localhost = "localhost", $username = "root", $password = "root1", $dbname = "gym_management"
);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    // Manejo de error más robusto
    http_response_code(500);
    echo "Error de conexión a la base de datos. Por favor, inténtelo más tarde.";
    exit();
}
?>

<?php
