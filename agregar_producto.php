<?php
session_start();
require_once 'config/db.php';

// Verificar permisos para mostrar formulario
if (!isset($_SESSION['id_usuario']) || ($_SESSION['id_tipo_de_usuario'] != 1 && $_SESSION['id_tipo_de_usuario'] != 2)) {
    header("Location: inicio.php");
    exit();
}

// Obtener categorías
$cats = [];
$result = $conn->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");
while ($row = $result->fetch_assoc()) {
    $cats[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Agregar Producto</title>
<link rel="stylesheet" href="estilos/agregar_producto.css" />
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-agregar');

    form.addEventListener('submit', async e => {
        e.preventDefault();

        const formData = new FormData(form);

        const btnSubmit = form.querySelector('input[type="submit"]');
        btnSubmit.disabled = true;
        btnSubmit.value = 'Guardando...';

        try {
            const res = await fetch('crear_producto.php', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            alert(data.message);

            if (data.success) {
                form.reset();
            }
        } catch (error) {
            alert('Error inesperado al enviar el formulario.');
            console.error(error);
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.value = 'Agregar Producto';
        }
    });
});
</script>
</head>
<body>

<h2>Agregar Nuevo Producto</h2>

<form id="form-agregar" enctype="multipart/form-data" method="post">
    <label for="nombre">Nombre del Producto:</label><br />
    <input type="text" name="nombre" id="nombre" required /><br /><br />

    <label for="descripcion">Descripción:</label><br />
    <textarea name="descripcion" id="descripcion" required></textarea><br /><br />

    <label for="precio">Precio:</label><br />
    <input type="number" step="0.01" name="precio" id="precio" required /><br /><br />

    <label for="stock">Stock:</label><br />
    <input type="number" name="stock" id="stock" min="0" value="0" required /><br /><br />

    <label for="categoria">Categoría:</label><br />
    <select name="categoria_id" id="categoria" required>
        <option value="">-- Selecciona una categoría --</option>
        <?php foreach ($cats as $cat): ?>
            <option value="<?= $cat['id_categoria'] ?>">
                <?= htmlspecialchars($cat['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select><br /><br />

    <label for="imagen">Imagen del Producto:</label><br />
    <input type="file" name="imagen" id="imagen" accept="image/*" required /><br /><br />

    <input type="submit" value="Agregar Producto" />
</form>

</body>
</html>
