<?php 
session_start(); 
require_once 'config/db.php';
require_once 'vendor/autoload.php'; // Cargar Composer

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Clave secreta (debe estar en config/jwt.php o definida aquí)
define('JWT_SECRET_KEY', 'erick123');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRE_TIME', 3600); // 1 hora

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // La contraseña que el usuario escribe en el formulario (puede tener 'ñ')
    $correo = $_POST['correo'];
    $contraseña_form = $_POST['contraseña']; // Renombrada para claridad
    
    error_log("Intento de inicio de sesión para: " . $correo);
    
    // --- CORRECCIÓN 1 ---
    // La tabla se llama 'Usuario' (Mayúscula) como en tu SQL
    $sql = "SELECT * FROM Usuario WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    if ($usuario) {
        error_log(message: "Usuario encontrado con ID: " . $usuario['id_Usuario']);
        
        // --- CORRECCIÓN 2 ---
        // La columna en la BD se llama 'contrasena' (con 'a')
        // Comparamos la contraseña del formulario con la de la BD
        $verificacion = password_verify($contraseña_form, $usuario['contrasena']);
        
        error_log("Resultado de verificación de contraseña: " . ($verificacion ? "EXITOSO" : "FALLIDO"));

        if ($verificacion) {
            // Guardar información básica en sesión
            $_SESSION['id_usuario'] = $usuario['id_Usuario'];
            $_SESSION['nombre_usuario'] = $usuario['nombre'];
            $_SESSION['id_tipo_de_usuario'] = $usuario['id_tipo_de_usuario'];

            // 🚀 Generar token JWT
            $tokenId = base64_encode(random_bytes(50));
            $issuedAt = time();
            $notBefore = $issuedAt + 10;          // Puede usarse después de 10 segundos
            $expire = $issuedAt + JWT_EXPIRE_TIME; // Expira en 1 hora

            $tokenData = [
                'iat' => $issuedAt,
                'nbf' => $notBefore,
                'exp' => $expire,
                'data' => [
                    'id_usuario' => $usuario['id_Usuario'],
                    'nombre' => $usuario['nombre'],
                    'tipo_usuario' => $usuario['id_tipo_de_usuario']
                ]
            ];

            $secretKey = JWT_SECRET_KEY;
            $jwt = JWT::encode($tokenData, $secretKey, JWT_ALGORITHM);

            // Guardar token en sesión para pasarlo al frontend
            $_SESSION['token'] = $jwt;

            header("Location: inicio.php");
            exit;
        } else {
            // --- CORRECCIÓN 3 (Opcional pero recomendada) ---
            // Usar 'contrasena' (con 'a') para el log
            error_log("Hash almacenado (primeros 20): " . substr($usuario['contrasena'], 0, 20));
            header("Location: login.php?error=1");
            exit;
        }
    } else {
        error_log("Usuario no encontrado con el correo: " . $correo);
        header("Location: login.php?error=1");
        exit;
    }
}
?>