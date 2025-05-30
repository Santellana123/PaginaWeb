<?php
session_start();
$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;
$jwt_token = $_SESSION['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle de producto | Mi E-Commerce</title>
  <link rel="stylesheet" href="estilos/producto.css">
  <link rel="stylesheet" href="estilos/carrito.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .sin-stock { color: red; font-weight: bold; }
    .boton-editar { background-color: #2575fc; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-top: 10px; }
  </style>
</head>
<body>
<header class="header">
  <div class="logo"><i class="fas fa-shopping-bag"></i> Mi E-Commerce</div>
  <form action="buscar.php" method="get" class="busqueda" style="flex-grow: 1; margin: 0 1rem;">
    <input type="text" name="q" placeholder="Buscar productos..." required style="padding: 8px; width: 100%; border-radius: 25px; border: none;">
    <button type="submit" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #2575fc;"><i class="fas fa-search"></i></button>
  </form>
  <div class="acciones">
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

<main>
  <div class="producto-detalle" id="detalle-producto"></div>
  <div class="comentarios">
    <h3>Comentarios</h3>
    <div id="lista-comentarios"></div>

    <h4>Deja tu comentario:</h4>
   <form id="form-comentario">
      <input type="hidden" name="id_producto" value="<?= $id_producto ?>">
      <input type="hidden" name="id_usuario" value="<?= $_SESSION['id_usuario'] ?? 0 ?>">

      <textarea name="comentario" required></textarea>
      <br>
      <label>Calificación:
        <select name="calificacion" required>
          <option value="5">5 ⭐</option>
          <option value="4">4 ⭐</option>
          <option value="3">3 ⭐</option>
          <option value="2">2 ⭐</option>
          <option value="1">1 ⭐</option>
        </select>
      </label>
      <br><br>
      <input type="submit" value="Enviar comentario">
    </form>
  </div>
</main>

<script>
const productoId = <?= $id_producto ?>;
const jwtToken = "<?= $jwt_token ?>";

function cargarProducto() {
  fetch(`api/producto.php?id=${productoId}`)
    .then(res => res.json())
    .then(data => {
      if (!data.success) return alert('Producto no encontrado');

      // Suponiendo que la nueva API devuelve el producto directamente en data.producto
      const p = data.producto;
      const html = `
        <img src="${p.url}" alt="Producto">
        <div class="info-producto">
          <h2>${p.nombre_Producto}</h2>
          <p><strong>Precio:</strong> $${parseFloat(p.Precio).toFixed(2)}</p>
          <p><strong>Stock:</strong> ${p.stock}</p>
          ${p.stock == 0 ? '<p class="sin-stock">Producto agotado</p>' : ''}
          <p><strong>Descripción:</strong><br>${p.descripcion}</p>
          ${
            p.stock > 0 && jwtToken
              ? `<button onclick="agregarAlCarrito(${p.id_Producto})">Agregar al carrito</button>`
              : jwtToken
              ? '<p class="sin-stock">Producto no disponible</p>'
              : '<p><a href="login.php">Inicia sesión para agregar al carrito</a></p>'
          }
        </div>`;

      document.getElementById('detalle-producto').innerHTML = html;
    })
    .catch(err => {
      console.error('Error al cargar producto:', err);
      alert('Error al cargar el producto.');
    });
}


function cargarComentarios() {
  fetch(`api/comentarios.php?id_producto=${productoId}`)
    .then(res => res.json())
    .then(data => {
      const contenedor = document.getElementById('lista-comentarios');
      if (!data.success) {
        contenedor.innerHTML = `<p>Error al cargar comentarios: ${data.message}</p>`;
        return;
      }

      const comentarios = data.comentarios;
      if (comentarios.length === 0) {
        contenedor.innerHTML = '<p>No hay comentarios aún.</p>';
        return;
      }

      contenedor.innerHTML = comentarios.map(c => `
        <div class='comentario'>
          <p><strong>${c.nombre}</strong> (${c.calificacion}⭐)</p>
          <p>${c.comentario}</p>
          <small>${c.fecha_Comentario}</small>
        </div>
      `).join('');
    })
    .catch(error => {
      console.error("Error al cargar comentarios:", error);
      document.getElementById('lista-comentarios').innerHTML = '<p>Error al cargar comentarios.</p>';
    });
}

function agregarAlCarrito(idProducto) {
  if (!jwtToken) {
    alert("Debes iniciar sesión primero.");
    location.href = 'login.php';
    return;
  }
  fetch('api/carrito.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer ' + jwtToken
    },
    body: JSON.stringify({
      accion: 'agregar',
      id_producto: idProducto,
      cantidad: 1
    })
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert("Producto agregado al carrito");
        location.reload();
      } else {
        alert("Error: " + data.message);
      }
    });
}

document.getElementById('form-comentario').addEventListener('submit', e => {
  e.preventDefault();
  if (!jwtToken) return alert("Debes iniciar sesión para comentar.");

  const form = new FormData(e.target);
  const payload = {
    id_producto: form.get('id_producto'),
    comentario: form.get('comentario').trim(),
    calificacion: form.get('calificacion')
  };

  if (!payload.comentario) {
    alert("El comentario no puede estar vacío.");
    return;
  }

 fetch('api/comentarios.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + jwtToken
  },
  body: JSON.stringify(payload)
})
  .then(async res => {
    if (!res.ok) {
      const text = await res.text();
      throw new Error(`HTTP ${res.status}: ${text}`);
    }
    return res.json();
  })
  .then(data => {
    if (data.success) {
      alert("Comentario guardado");
      cargarComentarios();
      e.target.reset();
    } else {
      alert("Error: " + data.message);
    }
  })
  .catch(err => {
    console.error("Error al enviar comentario:", err);
    alert("Error inesperado al enviar el comentario: " + err.message);
  });

});


cargarProducto();
cargarComentarios();
</script>
</body>
</html>
