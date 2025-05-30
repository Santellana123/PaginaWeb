<?php 
require_once '../config/db.php';
require_once '../config/auth.php';

header("Content-Type: application/json");

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    if (!isset($_GET['id_producto'])) {
        echo json_encode(['success' => false, 'message' => 'ID de producto no especificado']);
        exit;
    }

    $id_producto = intval($_GET['id_producto']);

    $sql = "SELECT c.id_Comentario, c.comentario, c.calificacion, c.fecha_Comentario, u.nombre 
            FROM Comentario c
            JOIN Usuario u ON c.id_Usuario = u.id_Usuario
            WHERE c.id_Producto = ?
            ORDER BY c.fecha_Comentario DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();

    $comentarios = [];
    while ($row = $result->fetch_assoc()) {
        $comentarios[] = $row;
    }

    echo json_encode(['success' => true, 'comentarios' => $comentarios]);
    exit;
}

if ($metodo === 'POST') {
    $datos_usuario = verificar_token(); // valida token y retorna datos usuario

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id_producto'], $data['comentario'], $data['calificacion'])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    $id_producto = intval($data['id_producto']);
    $comentario = htmlspecialchars(trim($data['comentario']));
    $calificacion = intval($data['calificacion']);
    $id_usuario = $datos_usuario->id_usuario ?? null;

    if (!$id_usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario no identificado en el token']);
        exit;
    }

    if ($calificacion < 1 || $calificacion > 5) {
        echo json_encode(['success' => false, 'message' => 'La calificación debe estar entre 1 y 5']);
        exit;
    }

    $sql = "INSERT INTO Comentario (id_Producto, id_Usuario, comentario, calificacion, fecha_Comentario)
            VALUES (?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisi", $id_producto, $id_usuario, $comentario, $calificacion);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Comentario guardado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el comentario']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método no permitido']);
exit;
