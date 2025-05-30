<?php
// jwt.php

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Clave secreta y configuración
define('JWT_SECRET_KEY', 'erick123');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRE_TIME', 3600); // 1 hora

/**
 * Genera un nuevo token JWT para un usuario
 *
 * @param array $payload Datos a incluir en el token (ej: ['id_usuario' => 123])
 * @return string Token codificado
 */
function generate_jwt($payload) {
    // Añadir tiempo de expiración
    $payload['exp'] = time() + JWT_EXPIRE_TIME;
    return JWT::encode($payload, JWT_SECRET_KEY, JWT_ALGORITHM);
}

/**
 * Decodifica un token JWT y devuelve su contenido
 *
 * @param string $token Token JWT
 * @return object Payload decodificado
 * @throws Exception Si el token es inválido o está expirado
 */
function decode_jwt($token) {
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, JWT_ALGORITHM));
        return $decoded;
    } catch (\Firebase\JWT\SignatureInvalidException $e) {
        throw new Exception("Firma del token inválida");
    } catch (\Firebase\JWT\ExpiredException $e) {
        throw new Exception("Token expirado");
    } catch (Exception $e) {
        throw new Exception("Token inválido: " . $e->getMessage());
    }
}
