<?php
session_start();
header('Content-Type: application/json');
require_once 'config/db.php';

// Verificar permisos
if (!isset($_SESSION['id_usuario']) || ($_SESSION['id_tipo_de_usuario'] != 1 && $_SESSION['id_tipo_de_usuario'] != 2)) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Recibir y sanitizar datos
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precio = floatval($_POST['precio'] ?? 0);
$stock = intval($_POST['stock'] ?? 0);
$categoria_id = intval($_POST['categoria_id'] ?? 0);

// Validar campos obligatorios
if ($nombre === '' || $descripcion === '' || $precio <= 0 || $categoria_id <= 0 || $stock < 0) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios y deben ser válidos.']);
    exit;
}

// Validar imagen
if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error al subir la imagen.']);
    exit;
}

$imagen = $_FILES['imagen'];
$check = getimagesize($imagen['tmp_name']);
if ($check === false) {
    echo json_encode(['success' => false, 'message' => 'El archivo no es una imagen válida.']);
    exit;
}

if ($imagen['size'] > 5000000) {
    echo json_encode(['success' => false, 'message' => 'La imagen es demasiado grande. Máximo permitido: 5MB.']);
    exit;
}

$ext = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
$ext_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $ext_permitidas)) {
    echo json_encode(['success' => false, 'message' => 'Solo se permiten imágenes en formato: JPG, JPEG, PNG, GIF o WEBP.']);
    exit;
}

// Preparar carpeta de imágenes
$directorio_imagenes = 'imagenes/productos/';
if (!is_dir($directorio_imagenes)) {
    mkdir($directorio_imagenes, 0777, true);
}

$nombre_imagen = uniqid('prod_') . '.' . $ext;
$ruta_imagen = $directorio_imagenes . $nombre_imagen;

if (!move_uploaded_file($imagen['tmp_name'], $ruta_imagen)) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la imagen.']);
    exit;
}

// Insertar producto con stock
$sql = "INSERT INTO Producto (nombre_Producto, descripcion, Precio, stock, id_categoria) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssdii", $nombre, $descripcion, $precio, $stock, $categoria_id);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al agregar el producto: ' . $stmt->error]);
    exit;
}

$id_producto = $stmt->insert_id;

// Insertar imagen
$sql_imagen = "INSERT INTO Imagen (id_Producto, url) VALUES (?, ?)";
$stmt_imagen = $conn->prepare($sql_imagen);
$stmt_imagen->bind_param("is", $id_producto, $ruta_imagen);
$stmt_imagen->execute();

echo json_encode(['success' => true, 'message' => 'Producto agregado exitosamente.']);

