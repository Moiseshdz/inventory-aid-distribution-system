<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/auth.php';

$check = $mysqli->query("SELECT COUNT(*) c FROM usuarios");
if ($check && ($check->fetch_assoc()['c'] ?? 0) > 0) {
  header('Location: login.php'); exit;
}

$msg = null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $usuario = trim($_POST['usuario'] ?? '');
  $clave = $_POST['clave'] ?? '';
  if ($nombre && $usuario && $clave) {
    $hash = password_hash($clave, PASSWORD_BCRYPT);
    $stmt = $mysqli->prepare("INSERT INTO usuarios(nombre, usuario, pass_hash, rol) VALUES(?,?,?, 'admin')");
    if($stmt){
      $stmt->bind_param("sss",$nombre,$usuario,$hash);
      if($stmt->execute()){
        $msg = "Administrador creado. Ve a <a href='login.php'>iniciar sesión</a>.";
      } else { $msg = "Error: ".$stmt->error; }
      $stmt->close();
    } else { $msg = "Error en la consulta: ".$mysqli->error; }
  } else { $msg = "Completa todos los campos."; }
}

include __DIR__ . '/header.php';
?>
<div class="row">
  <div class="col-md-6 col-md-offset-3">
    <div class="panel panel-default">
      <div class="panel-heading"><h3 class="panel-title">Configuración inicial: crear administrador</h3></div>
      <div class="panel-body">
        <?php if($msg): ?><div class="alert alert-info"><?= $msg ?></div><?php endif; ?>
        <form method="POST">
          <div class="form-group"><label>Nombre</label><input name="nombre" class="form-control" required></div>
          <div class="form-group"><label>Usuario</label><input name="usuario" class="form-control" required></div>
          <div class="form-group"><label>Contraseña</label><input type="password" name="clave" class="form-control" required></div>
          <button class="btn btn-primary" type="submit">Crear administrador</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>