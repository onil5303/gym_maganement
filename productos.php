<?php
include 'conexion.php';

$mensaje = "";

// Manejar el formulario de agregar producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar'])) {
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $descripcion = $_POST['descripcion'];

    $sql = "INSERT INTO Productos (nombre, precio, descripcion) VALUES ('$nombre', '$precio', '$descripcion')";
    
    if ($conn->query($sql) === TRUE) {
            header("Location: productos.php");
            exit();
    } else {
        $mensaje = "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Manejar la solicitud de eliminación de producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar'])) {
    $id = $_POST['id'];

    $sql = "DELETE FROM Productos WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
            header("Location: productos.php");
            exit();
    } else {
        $mensaje = "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Obtener la lista de productos
$productos = $conn->query("SELECT * FROM Productos");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
       <?php include 'menu.php'; ?>
    <h1>Gestión de Productos</h1>
    <form method="POST" action="">
        <input type="hidden" name="agregar" value="1">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required>

        <label for="precio">Precio:</label>
        <input type="number" step="0.01" id="precio" name="precio" required>

        <label for="descripcion">Descripción:</label>
        <textarea id="descripcion" name="descripcion" rows="4" required></textarea>

        <button type="submit">Agregar Producto</button>
    </form>
    <span class="mensaje"><?php echo $mensaje; ?></span>

    <h2>Lista de Productos</h2>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $productos->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['nombre']; ?></td>
                <td><?php echo $row['precio']; ?></td>
                <td><?php echo $row['descripcion']; ?></td>
                <td>
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="eliminar" value="1">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <button type="submit" onclick="return confirm('¿Está seguro de que desea eliminar este producto?')">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</body>
</html>
