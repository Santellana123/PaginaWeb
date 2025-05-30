<?php
session_start();
$termino = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
$jwt_token = $_SESSION['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Resultados de búsqueda</title>
  <link rel="stylesheet" href="estilos/inicio.css" />
</head>
<body>

<header>
  <div class="logo">🛍️ Mi E-Commerce</div>
  <div class="acciones"><button onclick="location.href='inicio.php'">Inicio</button></div>   
  <form action="buscar.php" method="get" class="busqueda">
    <input type="text" id="input-busqueda" name="q" value="<?= $termino ?>" placeholder="Buscar productos..." />
    <button type="submit">🔍</button>
  </form>

  <div class="acciones">
    <button onclick="location.href='carrito.php'">🛒 Carrito</button>
    
    <?php if (isset($_SESSION['nombre_usuario'])): ?>
      <span>👤 <?= htmlspecialchars($_SESSION['nombre_usuario']) ?></span>
      <?php if ($_SESSION['id_tipo_de_usuario'] == 1 || $_SESSION['id_tipo_de_usuario'] == 2): ?>
        <button onclick="location.href='agregar_producto.php'">Agregar Producto</button>
      <?php endif; ?>
      <button onclick="location.href='logout.php'">Cerrar sesión</button>
    <?php else: ?>
      <button onclick="location.href='login.php'">Iniciar sesión</button>
    <?php endif; ?>
  </div>
</header>

<main>
  <h2>Resultados para: "<span id="termino"><?= $termino ?></span>"</h2>

  <div class="productos" id="contenedor-productos">
    <p>Cargando productos...</p>
  </div>
</main>

<script>
const termino = "<?= $termino ?>";
const contenedor = document.getElementById('contenedor-productos');
const spanTermino = document.getElementById('termino');

function mostrarMensaje(msg) {
  contenedor.innerHTML = `<p>${msg}</p>`;
}

function crearProductoHTML(p) {
  return `
    <div class="producto">
      <a href="producto.php?id=${p.id_Producto}">
        <img src="${p.url || 'imagenes/default.png'}" alt="${p.nombre_Producto}" />
        <h3>${p.nombre_Producto}</h3>
        <p>$${parseFloat(p.Precio).toFixed(2)}</p>
      </a>
    </div>
  `;
}

function buscarProductos(query) {
  if (!query) {
    mostrarMensaje('Ingresa un término para buscar productos.');
    spanTermino.textContent = '';
    return;
  }

  spanTermino.textContent = query;
  contenedor.innerHTML = '<p>Cargando productos...</p>';

  fetch(`api/producto.php?q=${encodeURIComponent(query)}`)
    .then(res => res.json())
    .then(data => {
      if (!data.success || !data.productos || data.productos.length === 0) {
        mostrarMensaje(`No se encontraron productos para "${query}".`);
        return;
      }
      contenedor.innerHTML = data.productos.map(crearProductoHTML).join('');
    })
    .catch(err => {
      console.error('Error al cargar productos:', err);
      mostrarMensaje('Error inesperado al buscar productos.');
    });
}

buscarProductos(termino);

// Opcional: búsqueda en vivo (descomenta si quieres)
// document.getElementById('input-busqueda').addEventListener('input', e => {
//   buscarProductos(e.target.value);
// });
</script>

</body>
</html>
