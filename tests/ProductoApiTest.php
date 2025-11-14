<?php
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client; // El cliente HTTP

class ProductoApiTest extends TestCase
{
    private $cliente;

    protected function setUp(): void
    {
        // Configuramos Guzzle para que apunte a nuestro servidor local
        $this->cliente = new Client([
            'base_uri' => 'http://localhost/ecomerce/PaginaWeb/' // <-- ¡Modifica esta URL!
        ]);
    }

    /**
     * Prueba que el endpoint devuelva la lista general de productos.
     */
    public function testObtenerListaDeProductosPaginada()
    {
        // 1. Actuar (Act)
        // Hacemos una petición GET a 'api/producto.php'
        $respuesta = $this->cliente->request('GET', 'api/producto.php?pagina=1&limite=6');
        
        // 2. Afirmar (Assert)
        // Afirmamos que la respuesta fue "200 OK"
        $this->assertEquals(200, $respuesta->getStatusCode());

        // Decodificamos la respuesta JSON
        $datos = json_decode($respuesta->getBody());

        // Afirmamos que la API nos dijo que fue exitoso
        $this->assertTrue($datos->success);
        
        // Afirmamos que la API nos devolvió un array de productos
        $this->assertIsArray($datos->productos);

        // Afirmamos que nos devolvió la paginación correcta
        $this->assertEquals(1, $datos->pagina_actual);
    }

    /**
     * Prueba que el endpoint devuelva un producto específico por ID.
     * (Basado en los datos de tu ecomerce.sql)
     */
    public function testObtenerProductoExistentePorId()
    {
        // 1. Actuar (Act)
        // Hacemos una petición GET a 'api/producto.php?id=1'
        $respuesta = $this->cliente->request('GET', 'api/producto.php?id=1');
        
        // 2. Afirmar (Assert)
        $this->assertEquals(200, $respuesta->getStatusCode());
        $datos = json_decode($respuesta->getBody());

        $this->assertTrue($datos->success);
        // Afirmamos que es el producto correcto
        $this->assertEquals('Guantes', $datos->producto->nombre_Producto);
    }
}