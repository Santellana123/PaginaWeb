<?php
// Estas dos líneas son para depurar. Puedes borrarlas cuando los tests pasen.
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
$id_categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

$offset = ($pagina - 1) * $por_pagina;

// --- CASO 1: Obtener por ID ---
if ($id_producto !== null && $id_producto > 0) {

    // --- SQL CORREGIDA (con MIN() para GROUP BY) ---
    $sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, p.stock, p.descripcion, 
                   MIN(i.url) AS url, 
                   MIN(c.nombre) AS nombre_Categoria
            FROM Producto p 
            LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
            LEFT JOIN Categoria c ON p.id_Categoria = c.id_Categoria
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

// --- CASO 2: Obtener por Término de Búsqueda ---
} elseif ($termino !== null && $termino !== '') {

    // --- SQL CORREGIDA (con MIN() para GROUP BY) ---
    $sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, p.stock, p.descripcion, 
                   MIN(i.url) AS url, 
                   MIN(c.nombre) AS nombre_Categoria
            FROM Producto p 
            LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
            LEFT JOIN Categoria c ON p.id_Categoria = c.id_Categoria
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

// --- CASO 3: Obtener Paginado (Esta era la línea 125) ---
} else {
    $params = [];
    $tipos_params = "";
    
    $sql_total = "SELECT COUNT(*) AS total FROM Producto";
    if ($id_categoria > 0) {
        $sql_total .= " WHERE id_Categoria = ?";
        $params[] = $id_categoria;
        $tipos_params .= "i";
    }

    $stmt_total = $conn->prepare($sql_total);
    if (!$stmt_total) {
         echo json_encode(["success" => false, "message" => "Error en conteo total: " . $conn->error]);
         exit;
    }
    if ($tipos_params) {
        $stmt_total->bind_param($tipos_params, ...$params);
    }
    $stmt_total->execute();
    $total_result = $stmt_total->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_productos = $total_row['total'];
    $total_paginas = ceil($total_productos / $por_pagina);

    // --- SQL CORREGIDA (con MIN() para GROUP BY) ---
    $sql = "SELECT p.id_Producto, p.nombre_Producto, p.Precio, p.stock, p.descripcion, 
                   MIN(i.url) AS url, 
                   MIN(c.nombre) AS nombre_Categoria
            FROM Producto p 
            LEFT JOIN Imagen i ON p.id_Producto = i.id_Producto 
            LEFT JOIN Categoria c ON p.id_Categoria = c.id_Categoria";
    
    if (!$id_categoria > 0) {
        $params = [];
        $tipos_params = "";
    }

    if ($id_categoria > 0) {
        $sql .= " WHERE p.id_Categoria = ?";
    }

    $sql .= " GROUP BY p.id_Producto LIMIT ? OFFSET ?";

    $params[] = $por_pagina;
    $params[] = $offset;
    $tipos_params .= "ii";
    
    // Esta era tu línea 125
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Error en la preparación: " . $conn->error]);
        exit;
    }

    $stmt->bind_param($tipos_params, ...$params);
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