<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Ver Productos</title>
  <link rel="stylesheet" href="estilos/carrito.css">
  <link rel="stylesheet" href="estilos/ver_productos.css">
  <link rel="stylesheet" href="estilos/footer.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    /* Estilos para paginación */
    .paginacion {
      margin-top: 1rem;
      display: flex;
      justify-content: center;
      gap: 0.5rem;
    }
    .paginacion button {
      padding: 0.5rem 0.8rem;
      border: none;
      background-color: #007bff;
      color: white;
      cursor: pointer;
      border-radius: 4px;
    }
    .paginacion button:disabled {
      background-color: #aaa;
      cursor: default;
    }
    #categoryMenu {
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>

<header class="header">
  <div class="logo"><i class="fas fa-shopping-bag"></i> Mi E-Commerce</div>
  <div class="acciones">
    <button id="filterButton"><i class="fas fa-filter"></i> Filtrar</button>
    <button onclick="location.href='inicio.php'"><i class="fas fa-home"></i> Inicio</button>
    <button onclick="location.href='carrito.php'"><i class="fas fa-shopping-cart"></i> Carrito</button>
    <?php if (isset($_SESSION['nombre_usuario'])): ?>
      <button><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nombre_usuario']) ?></button>
      <button onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</button>
    <?php else: ?>
      <button onclick="location.href='login.php'"><i class="fas fa-sign-in-alt"></i> Iniciar sesión</button>
    <?php endif; ?>
  </div>
</header>

<main class="main-content">
  <h2>Todos los Productos</h2>

  <div id="categoryMenu" style="display:none;">
    <label for="categoriaSelect">Categoría:</label>
    <select id="categoriaSelect">
      <option value="">Todas</option>
      <!-- Las categorías se cargarán dinámicamente -->
    </select>
  </div>

  <div id="productos" class="grid-productos">
    <p>Cargando productos...</p>
  </div>

  <div class="paginacion" id="paginacion">
    <!-- Botones de paginación -->
  </div>
</main>

<footer class="main-footer">
  <div class="footer-container">
    <div class="footer-box">
      <h3>🛍️ Mi E-Commerce</h3>
      <p>Tu tienda online con los mejores productos del mercado.</p>
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

<script>
document.getElementById('filterButton').addEventListener('click', function () {
  const menu = document.getElementById('categoryMenu');
  menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
});

const productosContenedor = document.getElementById('productos');
const paginacionContenedor = document.getElementById('paginacion');
const categoriaSelect = document.getElementById('categoriaSelect');

let paginaActual = 1;
let totalPaginas = 1;
let categoriaActual = "";

// Función para guardar estado en sessionStorage
function guardarEstado() {
  sessionStorage.setItem('paginaActual', paginaActual);
  sessionStorage.setItem('categoriaActual', categoriaActual);
}

// Función para cargar estado de sessionStorage
function cargarEstado() {
  const pagina = sessionStorage.getItem('paginaActual');
  const categoria = sessionStorage.getItem('categoriaActual');

  if (pagina) paginaActual = parseInt(pagina);
  if (categoria) {
    categoriaActual = categoria;
    categoriaSelect.value = categoriaActual;
  }
}

// Cargar categorías para filtro
async function cargarCategorias() {
  try {
    const res = await fetch('api/categorias.php'); // Debes crear esta API o usar tu propia ruta
    const data = await res.json();

    if (!data.success || !data.categorias) {
      console.warn('No se encontraron categorías o hubo un error.');
      return;
    }

    // Vaciar select y agregar opciones
    categoriaSelect.innerHTML = '<option value="">Todas</option>';
    data.categorias.forEach(cat => {
      categoriaSelect.innerHTML += `<option value="${cat.id_Categoria}">${cat.nombre_Categoria}</option>`;
    });
  } catch (error) {
    console.error('Error al cargar categorías:', error);
  }
}

// Función para cargar productos desde la API con paginación y filtro categoría
async function cargarProductos() {
  productosContenedor.innerHTML = '<p>Cargando productos...</p>';
  paginacionContenedor.innerHTML = '';

  try {
    let url = `api/producto.php?pagina=${paginaActual}&limite=6`;
    if (categoriaActual) {
      url += `&categoria=${categoriaActual}`;
    }

    const res = await fetch(url);
    const data = await res.json();

    if (!data.success || !data.productos || data.productos.length === 0) {
      productosContenedor.innerHTML = '<p>No hay productos disponibles.</p>';
      return;
    }

    totalPaginas = data.total_paginas;
    productosContenedor.innerHTML = '';

    data.productos.forEach(producto => {
      productosContenedor.innerHTML += `
        <div class="card-producto">
          <img src="${producto.url || 'default.jpg'}" alt="${producto.nombre_Producto}">
          <h3>${producto.nombre_Producto}</h3>
          <p class="precio">$${parseFloat(producto.Precio).toFixed(2)}</p>
          <p class="categoria">${producto.nombre_Categoria || 'Sin categoría'}</p>
          <a href="producto.php?id=${producto.id_Producto}" class="btn-detalle">Ver Detalle</a>
        </div>
      `;
    });

    // Crear botones de paginación
    paginacionContenedor.innerHTML = '';

    // Botón anterior
    const btnAnterior = document.createElement('button');
    btnAnterior.textContent = 'Anterior';
    btnAnterior.disabled = paginaActual === 1;
    btnAnterior.onclick = () => {
      if (paginaActual > 1) {
        paginaActual--;
        guardarEstado();
        cargarProductos();
      }
    };
    paginacionContenedor.appendChild(btnAnterior);

    // Botones numéricos
    for (let i = 1; i <= totalPaginas; i++) {
      const btn = document.createElement('button');
      btn.textContent = i;
      btn.disabled = i === paginaActual;
      btn.onclick = () => {
        paginaActual = i;
        guardarEstado();
        cargarProductos();
      };
      paginacionContenedor.appendChild(btn);
    }

    // Botón siguiente
    const btnSiguiente = document.createElement('button');
    btnSiguiente.textContent = 'Siguiente';
    btnSiguiente.disabled = paginaActual === totalPaginas;
    btnSiguiente.onclick = () => {
      if (paginaActual < totalPaginas) {
        paginaActual++;
        guardarEstado();
        cargarProductos();
      }
    };
    paginacionContenedor.appendChild(btnSiguiente);

  } catch (error) {
    console.error('Error al cargar productos:', error);
    productosContenedor.innerHTML = '<p>Error al cargar los productos.</p>';
  }
}

// Evento cuando cambia la categoría
categoriaSelect.addEventListener('change', () => {
  categoriaActual = categoriaSelect.value;
  paginaActual = 1;
  guardarEstado();
  cargarProductos();
});

// Al cargar la página, cargar estado, categorías y productos
window.onload = async () => {
  cargarEstado();
  await cargarCategorias();
  cargarProductos();
};
</script>

</body>
</html>
