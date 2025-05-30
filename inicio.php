<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Inicio | Mi E-Commerce</title>
  <link rel="stylesheet" href="estilos/inicio.css">
  <link rel="stylesheet" href="estilos/banner.css">
  <link rel="stylesheet" href="estilos/footer.css">
  <link rel="stylesheet" href="estilos/carrito.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css ">
  <link href="https://fonts.googleapis.com/css2?family=Poppins :wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>

<!-- HEADER -->
<header class="header">
  <div class="logo"><i class="fas fa-shopping-bag"></i> Mi E-Commerce</div>
  <div class="acciones">
    <button onclick="location.href='inicio.php'"><i class="fas fa-home"></i> Inicio</button>
    <button onclick="location.href='carrito.php'"><i class="fas fa-shopping-cart"></i> Carrito</button>
    
    <?php if (isset($_SESSION['nombre_usuario'])): ?>
      <button><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nombre_usuario']) ?></button>
      <button onclick="location.href='perfil.php'"><i class="fas fa-user-cog"></i> Perfil</button>

      <?php if (isset($_SESSION['id_tipo_de_usuario']) && ($_SESSION['id_tipo_de_usuario'] == 1 || $_SESSION['id_tipo_de_usuario'] == 2)): ?>
        <button onclick="location.href='agregar_producto.php'"><i class="fas fa-plus-circle"></i> Agregar Producto</button>
      <?php endif; ?>

      <button onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</button>
    <?php else: ?>
      <button onclick="location.href='login.php'"><i class="fas fa-sign-in-alt"></i> Iniciar sesión</button>
    <?php endif; ?>
    
    <div class="filter-menu">
      <button id="filterButton"><i class="fas fa-filter"></i> Filtrar</button>
      <div id="categoryMenu" style="display: none;">
        <a href="#" class="category-item" data-id="">Todas</a>
        <a href="#" class="category-item" data-id="1">Tecnología</a>
        <a href="#" class="category-item" data-id="2">Ropa</a>
        <a href="#" class="category-item" data-id="3">Hogar</a>
        <!-- Más categorías según tu BD -->
      </div>
    </div>
  </div>
</header>

<!-- BANNER -->
<div class="banner" id="banner">
  <div class="banner-content" id="banner-content">
    <p id="banner-texto">TODO EN TECNOLOGÍA</p>
    <h2 id="banner-titulo">EN LA MEJOR PLATAFORMA</h2>
    <button onclick="location.href='ver_productos.php'">CONOCE MÁS</button>
  </div>
  <button class="arrow left" onclick="cambiarBanner(-1)">&#10094;</button>
  <button class="arrow right" onclick="cambiarBanner(1)">&#10095;</button>
</div>

<!-- PRODUCTOS -->
<main id="productos-container">
  <p>Cargando productos...</p>
</main>

<!-- FOOTER -->
<footer class="main-footer">
  <div class="footer-container">
    <div class="footer-box">
      <h3>🛍️ Mi E-Commerce</h3>
      <p>Tu tienda online con los mejores productos del mercado. Calidad garantizada al mejor precio.</p>
    </div>
    <div class="footer-box">
      <h3>Enlaces Rápidos</h3>
      <ul>
        <li><a href="inicio.php">Inicio</a></li>
        <li><a href="colecciones.php">Colecciones</a></li>
        <li><a href="carrito.php">Carrito</a></li>
        <li><a href="login.php">Iniciar Sesión</a></li>
        <li><a href="registro.php">Registrarse</a></li>
      </ul>
    </div>
    <div class="footer-box">
      <h3>Contacto</h3>
      <ul>
        <li>📧 contacto@miecommerce.com</li>
        <li>📞 +57 300 123 4567</li>
        <li>📍 Bogotá, Colombia</li>
      </ul>
    </div>
    <div class="footer-box">
      <h3>Síguenos</h3>
      <div class="social-links">
        <a href="#"><i class="fab fa-facebook-f"></i> Facebook</a><br>
        <a href="#"><i class="fab fa-instagram"></i> Instagram</a><br>
        <a href="#"><i class="fab fa-twitter"></i> Twitter</a><br>
        <a href="#"><i class="fab fa-youtube"></i> YouTube</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> Mi E-Commerce - Todos los derechos reservados.</p>
  </div>
</footer>

<!-- SCRIPTS -->
<script>
// Pasar PHP -> JS
const token = '<?= $_SESSION['token'] ?? '' ?>';
const idUsuario = <?= $_SESSION['id_usuario'] ?? 'null' ?>;
const tipoUsuario = <?= $_SESSION['id_tipo_de_usuario'] ?? 'null' ?>;
</script>

<script>
// Menú de categorías
document.getElementById('filterButton').addEventListener('click', function() {
  const categoryMenu = document.getElementById('categoryMenu');
  categoryMenu.style.display = categoryMenu.style.display === 'block' ? 'none' : 'block';
});

document.querySelectorAll('.category-item').forEach(item => {
  item.addEventListener('click', function(e) {
    e.preventDefault();
    const categoryId = this.getAttribute('data-id');
    fetchProductosPorCategoria(categoryId);
    document.getElementById('categoryMenu').style.display = 'none';
  });
});

function fetchProductosPorCategoria(categoria_id) {
  const container = document.getElementById('productos-container');
  container.innerHTML = '<p>Cargando productos...</p>';

  let url = 'api/producto.php';
  if (categoria_id) {
    url += `?categoria_id=${categoria_id}`;
  }

  fetch(url, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Error en la respuesta de la API');
    }
    return response.json();
  })
  .then(data => {
    container.innerHTML = '';

    if (!data.success || !Array.isArray(data.productos) || data.productos.length === 0) {
      container.innerHTML = '<p>No hay productos disponibles.</p>';
      return;
    }

    data.productos.forEach(prod => {
      const div = document.createElement('div');
      div.className = 'producto';
      div.innerHTML = `
        <img src="${prod.url || 'img/default-product.png'}" alt="Producto">
        <h3>${prod.nombre_Producto}</h3>
        <p>$${parseFloat(prod.Precio).toFixed(2)}</p>
        <button onclick="window.location.href='producto.php?id=${prod.id_Producto}'">Ver más</button>
      `;
      container.appendChild(div);
    });
  })
  .catch(error => {
    console.error('Error al cargar productos:', error);
    container.innerHTML = '<p>Error al cargar productos. Intenta más tarde.</p>';
  });
}


// Banner rotativo
const banners = [
  { imagen: 'banner/computadora.png', titulo: 'EN LA MEJOR PLATAFORMA', texto: 'TODO EN TECNOLOGÍA' },
  { imagen: 'banner/celular.jpg', titulo: 'INNOVACIÓN EN TUS MANOS', texto: 'LOS MEJORES CELULARES' },
  { imagen: 'banner/ropa.jpg', titulo: 'LA MODA EN TUS MANOS', texto: 'AL MEJOR PRECIO Y CALIDAD' }
];

let index = 0;

function mostrarBanner(i) {
  const banner = document.getElementById('banner');
  const titulo = document.getElementById('banner-titulo');
  const texto = document.getElementById('banner-texto');

  banner.style.backgroundImage = `url('${banners[i].imagen}')`;
  titulo.textContent = banners[i].titulo;
  texto.textContent = banners[i].texto;
}

function cambiarBanner(direccion) {
  index = (index + direccion + banners.length) % banners.length;
  mostrarBanner(index);
}

document.addEventListener('DOMContentLoaded', () => {
  mostrarBanner(index);
  fetchProductosPorCategoria(); // Cargar todos los productos al inicio
});
</script>

</body>
</html>