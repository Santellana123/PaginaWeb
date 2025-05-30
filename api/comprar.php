<?php
require_once '../config/db.php';
require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Content-Type: application/json");

// Validar token
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
if (!str_starts_with($authHeader, 'Bearer ')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token no proporcionado']);
    exit;
}
$token = str_replace('Bearer ', '', $authHeader);
try {
    $decoded = JWT::decode($token, new Key('erick123', 'HS256'));
    $id_usuario = $decoded->data->id_usuario;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token inválido']);
    exit;
}

// Obtener el carrito
$stmt = $conn->prepare("SELECT id_Carrito FROM Carrito WHERE id_Usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Carrito no encontrado']);
    exit;
}
$id_carrito = $result->fetch_assoc()['id_Carrito'];

// Obtener productos del carrito
$stmt = $conn->prepare("
    SELECT cp.id_Producto, cp.cantidad, p.Precio, p.stock
    FROM Carrito_Producto cp
    JOIN Producto p ON cp.id_Producto = p.id_Producto
    WHERE cp.id_Carrito = ?
");
$stmt->bind_param("i", $id_carrito);
$stmt->execute();
$productos = $stmt->get_result();

if ($productos->num_rows === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Carrito vacío']);
    exit;
}

// Iniciar transacción
$conn->begin_transaction();

try {
    $total = 0;
    $items = [];
    while ($row = $productos->fetch_assoc()) {
        if ($row['cantidad'] > $row['stock']) {
            throw new Exception("Stock insuficiente para el producto ID " . $row['id_Producto']);
        }
        $subtotal = $row['Precio'] * $row['cantidad'];
        $total += $subtotal;
        $items[] = [
            'id_Producto' => $row['id_Producto'],
            'cantidad' => $row['cantidad'],
            'precio_unitario' => $row['Precio'],
            'subtotal' => $subtotal
        ];
    }

    // Crear pedido
    $stmt = $conn->prepare("INSERT INTO Pedido (id_Usuario, fecha_Pedido, estado_pedido, total) VALUES (?, NOW(), 'Procesando', ?)");
    $stmt->bind_param("id", $id_usuario, $total);
    $stmt->execute();
    $id_pedido = $stmt->insert_id;

    // Insertar detalles y actualizar stock
    $stmtDetalle = $conn->prepare("INSERT INTO Detalle_Pedido (id_Pedido, id_Producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
    $stmtStock = $conn->prepare("UPDATE Producto SET stock = stock - ? WHERE id_Producto = ?");

    foreach ($items as $item) {
        $stmtDetalle->bind_param("iiidd", $id_pedido, $item['id_Producto'], $item['cantidad'], $item['precio_unitario'], $item['subtotal']);
        $stmtDetalle->execute();

        $stmtStock->bind_param("ii", $item['cantidad'], $item['id_Producto']);
        $stmtStock->execute();
    }

    // Limpiar el carrito
    $stmt = $conn->prepare("DELETE FROM Carrito_Producto WHERE id_Carrito = ?");
    $stmt->bind_param("i", $id_carrito);
    $stmt->execute();

    // Confirmar transacción
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Compra realizada con éxito']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la compra: ' . $e->getMessage()]);
}
?>

