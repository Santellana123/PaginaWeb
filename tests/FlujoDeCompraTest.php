<?php
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
// Usamos JWT para generar un token de prueba
use Firebase\JWT\JWT;

class FlujoDeCompraTest extends TestCase
{
    private $cliente;
    private $dbConn; // Conexión a la BD para verificar resultados

    // --- 1. Configuración de la Prueba ---
    protected function setUp(): void
    {
        // El cliente Guzzle para hacer peticiones HTTP
        $this->cliente = new Client([
            'base_uri' => 'http://localhost/ecomerce/PaginaWeb/',
            'http_errors' => false // No lanzar excepciones en 4xx o 5xx
        ]);

        // --- ¡IMPORTANTE! ---
        // Conexión directa a la BD para VERIFICAR los resultados
        // Cambia 'root', '' y 'ecomerce' si tus credenciales son otras
        $this->dbConn = new mysqli(
            '127.0.0.1',  // Host
            'root',       // Usuario BD
            '',           // Contraseña BD
            'ecomerce'    // Nombre de la BD
        );

        if ($this->dbConn->connect_error) {
            $this->fail("No se pudo conectar a la base de datos de prueba: " . $this->dbConn->connect_error);
        }
    }

    // --- 2. Limpieza después de la prueba ---
    protected function tearDown(): void
    {
        // Cierra la conexión a la BD
        if ($this->dbConn) {
            $this->dbConn->close();
        }
    }

    /**
     * Esta es la Prueba de Integración principal.
     * Simula un flujo completo de compra de un usuario.
     */
    public function testFlujoDeCompraCompleto()
    {
        // --- 1. ARRANGE (Preparar el entorno) ---
        
        // IDs de prueba (basados en tu SQL)
        $idUsuario = 2; // 'vender@gmail.com'
        $idProducto = 1; // 'Guantes'
        $stockInicial = 20; // Stock conocido

        // Limpiamos la BD para un estado conocido
        $this->limpiarBaseDeDatos($idUsuario, $idProducto, $stockInicial);

        // Generamos un Token de autenticación válido para este usuario
        $token = $this->obtenerTokenParaUsuario($idUsuario, 'vender@gmail.com');

        // --- 2. ACT (Actuar) ---

        // ----- PASO A: Agregar producto al carrito -----
        $respuestaCarrito = $this->cliente->request('POST', 'api/carrito.php', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'json' => [
                'accion' => 'agregar',
                'id_producto' => $idProducto,
                'cantidad' => 1
            ]
        ]);
        
        // ----- PASO B: Realizar la compra (Checkout) -----
        $respuestaCompra = $this->cliente->request('POST', 'api/comprar.php', [
            'headers' => ['Authorization' => 'Bearer ' . $token]
     ]);
        $datosCompra = json_decode($respuestaCompra->getBody()->getContents());


        // --- 3. ASSERT (Verificar los resultados) ---

        // Verificación de las APIs (Módulos Carrito y Compra)
        $this->assertEquals(200, $respuestaCarrito->getStatusCode(), "API Carrito falló");
        $this->assertEquals(200, $respuestaCompra->getStatusCode(), "API Compra falló");
        $this->assertTrue($datosCompra->success, "La API de Compra reportó un error");

        // Verificación de Integridad de Datos (Efectos en la BD)
        
        // 1. ¿Se redujo el stock del producto?
        $stockFinal = $this->dbConn->query("SELECT stock FROM Producto WHERE id_Producto = $idProducto")->fetch_assoc()['stock'];
        $this->assertEquals($stockInicial - 1, $stockFinal, "El stock no se redujo correctamente");

        // 2. ¿Se creó el Pedido?
        $pedido = $this->dbConn->query("SELECT * FROM Pedido WHERE id_Usuario = $idUsuario");
        $this->assertEquals(1, $pedido->num_rows, "El pedido no se creó en la BD");
        
        // 3. ¿Se creó el Detalle del Pedido?
        $idPedido = $pedido->fetch_assoc()['id_Pedido'];
        $detalle = $this->dbConn->query("SELECT * FROM Detalle_Pedido WHERE id_Pedido = $idPedido AND id_Producto = $idProducto");
        $this->assertEquals(1, $detalle->num_rows, "El detalle del pedido no se creó");

        // 4. ¿Se vació el carrito?
        $carrito = $this->dbConn->query("SELECT * FROM Carrito_Producto cp JOIN Carrito c ON cp.id_Carrito = c.id_Carrito WHERE c.id_Usuario = $idUsuario");
        $this->assertEquals(0, $carrito->num_rows, "El carrito no se vació después de la compra");
    }

    /**
     * Helper para limpiar la BD antes de la prueba.
     * Esto hace que la prueba sea REPETIBLE.
     */
    private function limpiarBaseDeDatos($idUsuario, $idProducto, $stockInicial)
    {
        // Borrar pedidos anteriores de este usuario
        $this->dbConn->query("DELETE FROM Detalle_Pedido WHERE id_Pedido IN (SELECT id_Pedido FROM Pedido WHERE id_Usuario = $idUsuario)");
        $this->dbConn->query("DELETE FROM Pedido WHERE id_Usuario = $idUsuario");
        
        // Vaciar el carrito de este usuario
        $this->dbConn->query("DELETE FROM Carrito_Producto WHERE id_Carrito IN (SELECT id_Carrito FROM Carrito WHERE id_Usuario = $idUsuario)");
        
        // Restaurar el stock del producto
        $this->dbConn->query("UPDATE Producto SET stock = $stockInicial WHERE id_Producto = $idProducto");
    }

    /**
     * Helper para generar un Token JWT válido.
     * Esto evita tener que llamar a la API de login.
     */
    private function obtenerTokenParaUsuario($idUsuario, $nombre)
    {
        require_once 'vendor/autoload.php';
        define('JWT_SECRET_KEY', 'erick123');
        define('JWT_ALGORITHM', 'HS256');

        $issuedAt = time();
        $tokenData = [
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $issuedAt + 3600, // Expira en 1 hora
            'data' => [
                'id_usuario' => $idUsuario,
                'nombre' => $nombre,
                'tipo_usuario' => 1 // Asumimos tipo 1
            ]
        ];
        return JWT::encode($tokenData, JWT_SECRET_KEY, JWT_ALGORITHM);
    }
}