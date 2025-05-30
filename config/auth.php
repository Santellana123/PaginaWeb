<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

define('JWT_SECRET_KEY', 'erick123');
define('JWT_ALGORITHM', 'HS256');

function verificar_token() {
    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(["error" => "Token no proporcionado"]);
        exit;
    }

    $authHeader = $headers['Authorization'];
    if (!str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(403);
        echo json_encode(["error" => "Formato de token inválido"]);
        exit;
    }

    $jwt = trim(str_replace('Bearer', '', $authHeader));

    try {
        $payload = JWT::decode($jwt, new Key(JWT_SECRET_KEY, JWT_ALGORITHM));
        return $payload->data; // aquí están los datos que pusiste en el token
    } catch (Exception $e) {
        http_response_code(403);
        echo json_encode(["error" => "Token inválido", "detalle" => $e->getMessage()]);
        exit;
    }
}

