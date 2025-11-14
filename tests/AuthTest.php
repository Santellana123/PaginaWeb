<?php
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class AuthTest extends TestCase
{
    private $cliente;

    protected function setUp(): void
    {
        $this->cliente = new Client([
            'base_uri' => 'http://localhost/ecomerce/PaginaWeb/', // <-- ¡Modifica esta URL!
            'cookies' => true, // Habilitamos cookies para manejar la sesión
            'allow_redirects' => false // Desactivamos redirecciones para ver el 'Location'
        ]);
    }

    /**
     * Prueba el "camino feliz": un usuario con credenciales correctas.
     */
    public function testLoginExitoso()
    {
        // 1. Actuar (Act)
        // Simulamos un envío de formulario POST a 'validar_login.php'
        $respuesta = $this->cliente->request('POST', 'validar_login.php', [
            'form_params' => [
                'correo' => 'carlos@gmail.com', // Usuario de tu BD
                'contraseña' => 'tec179' // <-- DEBES PONER LA CONTRASEÑA REAL
            ]
        ]);

        // 2. Afirmar (Assert)
        // Afirmamos que la respuesta fue un "302 Found" (redirección)
        $this->assertEquals(302, $respuesta->getStatusCode());
        
        // Afirmamos que nos está redireccionando a 'inicio.php'
        $this->assertEquals('inicio.php', $respuesta->getHeaderLine('Location'));
    }

    /**
     * Prueba el "camino triste": un usuario con credenciales incorrectas.
     */
    public function testLoginFallido()
    {
        // 1. Actuar (Act)
        $respuesta = $this->cliente->request('POST', 'validar_login.php', [
            'form_params' => [
                'correo' => 'vender@gmail.com',
                'contraseña' => 'contraseña_incorrecta'
            ]
        ]);

        // 2. Afirmar (Assert)
        // Afirmamos que nos redirecciona de vuelta a 'login.php' con un error
        $this->assertEquals(302, $respuesta->getStatusCode());
        $this->assertEquals('login.php?error=1', $respuesta->getHeaderLine('Location'));
    }
}