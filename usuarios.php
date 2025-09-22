<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/conexion.php';
require_login();
require_role('admin');

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

include __DIR__ . '/header.php';

// Crear / Editar
if($_SERVER['REQUEST_METHOD']==='POST'){
  $nombre = trim($_POST['nombre']??'');
  $usuario = trim($_POST['usuario']??'');
  $rol = $_POST['rol'] ?? 'operador';
  $clave = $_POST['clave'] ?? '';

  if($action==='create'){
    $hash = password_hash($clave, PASSWORD_BCRYPT);
    $stmt=$mysqli->prepare("INSERT INTO usuarios(nombre,usuario,pass_hash,rol) VALUES(?,?,?,?)");
    $stmt->bind_param("ssss",$nombre,$usuario,$hash,$rol);
    if($stmt->execute()){
      echo '<div class="alert alert-success">Usuario creado.</div>';
    } else { 
      echo '<div class="alert alert-danger">Error: '.$stmt->error.'</div>'; 
    }
    $stmt->close();
  } elseif($action==='edit' && $id){
    if($clave){
      $hash = password_hash($clave, PASSWORD_BCRYPT);
      $stmt=$mysqli->prepare("UPDATE usuarios SET nombre=?, usuario=?, rol=?, pass_hash=? WHERE id=?");
      $stmt->bind_param("ssssi",$nombre,$usuario,$rol,$hash,$id);
    } else {
      $stmt=$mysqli->prepare("UPDATE usuarios SET nombre=?, usuario=?, rol=? WHERE id=?");
      $stmt->bind_param("sssi",$nombre,$usuario,$rol,$id);
    }
    if($stmt->execute()){
      echo '<div class="alert alert-success">Usuario actualizado.</div>';
    } else { 
      echo '<div class="alert alert-danger">Error: '.$stmt->error.'</div>'; 
    }
    $stmt->close();
  }
}

// Eliminar
if($action==='delete' && $id){
  $stmt=$mysqli->prepare("DELETE FROM usuarios WHERE id=?");
  $stmt->bind_param("i",$id);
  if($stmt->execute()){
    echo '<div class="alert alert-success">Usuario eliminado.</div>';
  } else { 
    echo '<div class="alert alert-danger">Error: '.$stmt->error.'</div>'; 
  }
  $stmt->close();
}

// Formulario crear/editar
if($action==='create' || ($action==='edit' && $id)){
  $row = ['nombre'=>'','usuario'=>'','rol'=>'operador'];
  if($action==='edit'){
    $stmt=$mysqli->prepare("SELECT * FROM usuarios WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $res=$stmt->get_result();
    $row=$res->fetch_assoc();
    $stmt->close();
    if(!$row){ $row = ['nombre'=>'','usuario'=>'','rol'=>'operador']; }
  }
  ?>
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title"><?= $action==='create'?'Nuevo usuario':'Editar usuario' ?></h3>
    </div>
    <div class="panel-body">
      <form method="POST">
        <div class="form-group">
          <label>Nombre</label>
          <input name="nombre" class="form-control" value="<?= htmlspecialchars($row['nombre']??'') ?>" required>
        </div>
        <div class="form-group">
          <label>Usuario</label>
          <input name="usuario" class="form-control" value="<?= htmlspecialchars($row['usuario']??'') ?>" required>
        </div>
        <div class="form-group">
          <label>Rol</label>
          <select name="rol" class="form-control">
            <option value="operador" <?= ($row['rol']??'')==='operador'?'selected':'' ?>>operador</option>
            <option value="admin" <?= ($row['rol']??'')==='admin'?'selected':'' ?>>admin</option>
          </select>
        </div>
        <div class="form-group">
          <label>Contraseña <?= $action==='edit' ? '(dejar en blanco para mantener)' : '' ?></label>
          <input type="password" name="clave" class="form-control" <?= $action==='create'?'required':'' ?>>
        </div>
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn btn-default" href="usuarios.php">Cancelar</a>
      </form>
    </div>
  </div>
  <?php
} else {
  $res = $mysqli->query("SELECT id, nombre, usuario, rol FROM usuarios ORDER BY id DESC");
  ?>
  <div class="panel panel-default">
    <div class="panel-heading" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;">
      <h3 class="panel-title">Usuarios</h3>
      <a class="btn btn-primary" href="usuarios.php?action=create">Nuevo</a>
    </div>
    <div class="panel-body">
      <div class="table-responsive">
        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Usuario</th>
              <th>Rol</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php while($u = $res->fetch_assoc()): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td><?= htmlspecialchars($u['nombre']) ?></td>
              <td><?= htmlspecialchars($u['usuario']) ?></td>
              <td><span class="label label-default"><?= htmlspecialchars($u['rol']) ?></span></td>
              <td>
                <a class="btn btn-default btn-sm" href="usuarios.php?action=edit&id=<?= (int)$u['id'] ?>">Editar</a>
                <a href="#" 
                   class="btn btn-danger btn-sm btn-delete" 
                   data-id="<?= (int)$u['id'] ?>" 
                   data-nombre="<?= htmlspecialchars($u['nombre']) ?>">
                   Eliminar
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal Eliminar -->
  <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Confirmar eliminación</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>¿Seguro que deseas eliminar al usuario <strong id="usuarioNombre"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          <a id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // popup eliminar
    document.querySelectorAll('.btn-delete').forEach(btn=>{
      btn.addEventListener('click', function(e){
        e.preventDefault();
        let id = this.dataset.id;
        let nombre = this.dataset.nombre;
        document.getElementById('usuarioNombre').textContent = nombre;
        document.getElementById('btnConfirmarEliminar').setAttribute('href', 'usuarios.php?action=delete&id='+id);
        $('#modalEliminar').modal('show');
      });
    });
  </script>
  <?php
}
include __DIR__ . '/footer.php';
?>
