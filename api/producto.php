<?php
require_once '../config/db.php';
require_once '../config/auth.php';

header("Content-Type: application/json");

$requiere_auth = $_SERVER['REQUEST_METHOD'] !== 'GET';
if ($requiere_auth) {
    verificar_token();
}

$id_producto = isset($_GET['id']) ? intval($_GET['id']) : null;
$termino = isset($_GET['q']) ? trim($_GET['q']) : null;
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$por_pagina = isset($_GET['limite']) ? intval($_GET['limite']) : 6;

$offset = ($pagina - 1) * $por_pagina;

if ($id_producto !== null && $id_producto > 0) {
    $sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, p.stock, p.descripcion, i.url 
            FROM Producto p 
            LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
            WHERE p.id_Producto = ?
            GROUP BY p.id_Producto
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Error en la preparación: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(["success" => true, "producto" => $row]);
    } else {
        echo json_encode(["success" => false, "message" => "Producto no encontrado"]);
    }

} elseif ($termino !== null && $termino !== '') {
    $sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, p.stock, p.descripcion, i.url 
            FROM Producto p 
            LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
            WHERE p.nombre_Producto LIKE ? OR p.descripcion LIKE ?
            GROUP BY p.id_Producto";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Error en la preparación: " . $conn->error]);
        exit;
    }

    $like = "%$termino%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }

    echo json_encode(["success" => true, "productos" => $productos]);

} else {
    $sql_total = "SELECT COUNT(*) AS total FROM Producto";
    $total_result = $conn->query($sql_total);
    $total_row = $total_result->fetch_assoc();
    $total_productos = $total_row['total'];
    $total_paginas = ceil($total_productos / $por_pagina);

    $sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, p.stock, p.descripcion, i.url 
            FROM Producto p 
            LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
            GROUP BY p.id_Producto 
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Error en la preparación: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("ii", $por_pagina, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }

    echo json_encode([
        "success" => true,
        "productos" => $productos,
        "pagina_actual" => $pagina,
        "total_paginas" => $total_paginas,
        "total_productos" => $total_productos
    ]);
}
?>
