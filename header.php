<?php
require_once __DIR__ . '/auth.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars(constant('APP_NAME')) ?></title>
  <link rel="stylesheet" href="https://framework-gb.cdn.gob.mx/assets/styles/main.css">
  <link rel="stylesheet" href="https://framework-gb.cdn.gob.mx/assets/styles/gobmx.css">
  <script src="https://framework-gb.cdn.gob.mx/assets/scripts/jquery.min.js"></script>
  <script src="https://framework-gb.cdn.gob.mx/assets/scripts/bootstrap.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    body {
      font-family: "Patria", "noto Sans", "Helvetica", "Arial", sans-serif;
      font-size: 14px;
      line-height: 1.5;
      color: #333;
      background-color: #f9f9f9;
    }
    /* Flecha animada */
    .caret-anim {
      transition: transform 0.3s ease;
      display: inline-block;
    }
    .caret-anim.rotate {
      transform: rotate(180deg);
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-inverse">
    <div class="container">
      <!-- Header con botón toggle -->
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#menu-principal" aria-expanded="false">
          <span style="font-size:20px; color:white;">
            <i class="fa fa-caret-down caret-anim"></i>
          </span>
        </button>
        <a class="navbar-brand" href="index.php"><?= htmlspecialchars(constant('APP_NAME')) ?></a>
      </div>

      <!-- Menú colapsable -->
      <div class="collapse navbar-collapse" id="menu-principal">
        <ul class="nav navbar-nav navbar-right">
          <?php if ($user): ?>
            <li>
              <a>Sesión: <strong><?= htmlspecialchars($user['nombre']) ?></strong>
                (<?= htmlspecialchars($user['rol']) ?>)</a>
            </li>
            <li><a href="logout.php">Cerrar sesión</a></li>
          <?php else: ?>
            <li><a href="login.php">Iniciar sesión</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <main class="container" style="margin-top:20px">
    <?php if ($user): ?>
      <ol class="breadcrumb">
        <li><a href="index.php">Inicio</a></li>
        <li><a href="historial.php">Historial</a></li>
        <?php if ($user['rol'] === 'admin'): ?>
          <li><a href="productos.php">Productos</a></li>
          <li><a href="usuarios.php">Usuarios</a></li>
        <?php endif; ?>
        <li><a href="entregas.php">Registrar entrega</a></li>
      </ol>
    <?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function(){
  const toggleBtn = document.querySelector(".navbar-toggle");
  if(!toggleBtn) return;
  const icon = toggleBtn.querySelector(".caret-anim");

  // Cambiar rotación en el evento de Bootstrap collapse
  $('#menu-principal').on('shown.bs.collapse', function () {
    icon.classList.add("rotate"); // gira hacia arriba
  });
  $('#menu-principal').on('hidden.bs.collapse', function () {
    icon.classList.remove("rotate"); // vuelve hacia abajo
  });
});
</script>
