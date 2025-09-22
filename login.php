<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/auth.php';

if (is_logged_in()) { header('Location: index.php'); exit; }

// Si no hay usuarios, ir a setup_admin
$check = $mysqli->query("SELECT COUNT(*) c FROM usuarios");
if ($check && ($check->fetch_assoc()['c'] ?? 0) == 0) {
  header('Location: setup_admin.php'); exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $usuario = trim($_POST['usuario'] ?? '');
  $clave   = $_POST['clave'] ?? '';

  $stmt = $mysqli->prepare("SELECT id, nombre, usuario, pass_hash, rol FROM usuarios WHERE usuario=? LIMIT 1");
  if($stmt){
    $stmt->bind_param("s",$usuario);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
      if (password_verify($clave, $row['pass_hash'])) {
        $_SESSION['user'] = ['id'=>$row['id'], 'nombre'=>$row['nombre'], 'usuario'=>$row['usuario'], 'rol'=>$row['rol']];
        header('Location: index.php'); exit;
      } else { $error = "Credenciales inválidas."; }
    } else { $error = "Credenciales inválidas."; }
    $stmt->close();
  } else { $error = "Error en la consulta: " . $mysqli->error; }
}

include __DIR__ . '/header.php';
?>
<div class="row">
  <div class="col-md-6 col-md-offset-3">
    <div class="panel panel-default">
      <div class="panel-heading"><h3 class="panel-title">Iniciar sesión</h3></div>
      <div class="panel-body">
        <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
          <div class="form-group"><label>Usuario</label><input name="usuario" class="form-control" required></div>
          <div class="form-group"><label>Contraseña</label><input type="password" name="clave" class="form-control" required></div>
          <button class="btn btn-primary" type="submit">Entrar</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>