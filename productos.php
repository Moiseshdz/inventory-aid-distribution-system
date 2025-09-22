<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/conexion.php';
require_login();
require_role('admin');

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

function guardar_foto($input='foto'){
  if(!empty($_FILES[$input]['name'])){
    $dir = __DIR__ . '/uploads/';
    if(!is_dir($dir)) mkdir($dir,0777,true);
    $file = time().'_'.basename($_FILES[$input]['name']);
    $dest = $dir.$file;
    if(move_uploaded_file($_FILES[$input]['tmp_name'],$dest)){
      return 'uploads/'.$file;
    }
  }
  return null;
}

include __DIR__ . '/header.php';

// Crear o actualizar producto
if($_SERVER['REQUEST_METHOD']==='POST'){
  $nombre = trim($_POST['nombre'] ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');
  $cantidad = (int)($_POST['cantidad'] ?? 0);
  $foto = guardar_foto('foto');
  if($foto === null) $foto = ''; 

  if($action==='create'){
    $stmt=$mysqli->prepare("INSERT INTO productos(nombre,descripcion,cantidad,foto) VALUES(?,?,?,?)");
    $stmt->bind_param("ssis",$nombre,$descripcion,$cantidad,$foto);
    if($stmt->execute()){
      echo '<div class="alert alert-success">Producto creado.</div>';
    } else { 
      echo '<div class="alert alert-danger">Error: '.$stmt->error.'</div>'; 
    }
    $stmt->close();
  } elseif($action==='edit' && $id){
    if(!empty($foto)){
      $stmt=$mysqli->prepare("UPDATE productos SET nombre=?, descripcion=?, cantidad=?, foto=? WHERE id=?");
      $stmt->bind_param("ssisi",$nombre,$descripcion,$cantidad,$foto,$id);
    } else {
      $stmt=$mysqli->prepare("UPDATE productos SET nombre=?, descripcion=?, cantidad=? WHERE id=?");
      $stmt->bind_param("ssii",$nombre,$descripcion,$cantidad,$id);
    }
    if($stmt->execute()){
      echo '<div class="alert alert-success">Producto actualizado.</div>';
    } else { 
      echo '<div class="alert alert-danger">Error: '.$stmt->error.'</div>'; 
    }
    $stmt->close();
  }
}

// Eliminar producto
if($action==='delete' && $id){
  $stmt=$mysqli->prepare("DELETE FROM productos WHERE id=?");
  $stmt->bind_param("i",$id);
  if($stmt->execute()){
    echo '<div class="alert alert-success">Producto eliminado.</div>';
  } else { 
    echo '<div class="alert alert-danger">Error: '.$stmt->error.'</div>'; 
  }
  $stmt->close();
}

// Formulario crear/editar
if($action==='create' || ($action==='edit' && $id)){
  $row = ['nombre'=>'','descripcion'=>'','cantidad'=>0,'foto'=>null];
  if($action==='edit'){
    $stmt=$mysqli->prepare("SELECT * FROM productos WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $res=$stmt->get_result();
    $row=$res->fetch_assoc();
    $stmt->close();
    if(!$row){ 
      $row = ['nombre'=>'','descripcion'=>'','cantidad'=>0,'foto'=>null];
    }
  }
  ?>
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title"><?= $action==='create'?'Nuevo producto':'Editar producto' ?></h3>
    </div>
    <div class="panel-body">
      <form method="POST" enctype="multipart/form-data">
        <div class="row">
          <div class="form-group col-xs-12 col-sm-6">
            <label>Nombre</label>
            <input name="nombre" class="form-control" value="<?= htmlspecialchars($row['nombre']) ?>" required>
          </div>
          <div class="form-group col-xs-12 col-sm-6">
            <label>Cantidad</label>
            <input type="number" name="cantidad" class="form-control" value="<?= (int)$row['cantidad'] ?>" required>
          </div>
          <div class="form-group col-xs-12">
            <label>Descripción</label>
            <textarea name="descripcion" class="form-control"><?= htmlspecialchars($row['descripcion']) ?></textarea>
          </div>
          <div class="form-group col-xs-12">
            <label>Foto</label>
            <input type="file" name="foto" class="form-control">
            <?php if(!empty($row['foto'])): ?>
              <p><img src="<?= htmlspecialchars($row['foto']) ?>" style="max-height:120px;max-width:100%;"></p>
            <?php endif; ?>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn btn-default" href="productos.php">Cancelar</a>
      </form>
    </div>
  </div>
  <?php
} else {
  $res = $mysqli->query("SELECT * FROM productos ORDER BY id DESC");
  $contador = 1;
  ?>
  <div class="panel panel-default">
    <div class="panel-heading">
      <div class="row" style="align-items:center;">
        <div class="col-xs-12 col-sm-6">
          <h3 class="panel-title">Productos</h3>
        </div>
        <div class="col-xs-12 col-sm-6" style="margin-top:10px;display:flex;gap:10px;">
          <input type="text" id="buscador" class="form-control" placeholder="Buscar producto...">
          <a class="btn btn-primary" href="productos.php?action=create">Nuevo</a>
        </div>
      </div>
    </div>
    <div class="panel-body">
      <div class="table-responsive">
        <table class="table table-striped table-bordered" id="tablaProductos">
          <thead>
            <tr>
              <th>#</th>
              <th>Nombre</th>
              <th>Cantidad</th>
              <th>Foto</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php while($p = $res->fetch_assoc()): ?>
            <tr>
              <td><?= $contador++ ?></td>
              <td><?= htmlspecialchars($p['nombre']) ?></td>
              <td><?= (int)$p['cantidad'] ?></td>
              <td>
                <?php if($p['foto']): ?>
                  <img src="<?= htmlspecialchars($p['foto']) ?>" style="height:48px;max-width:100px;">
                <?php endif; ?>
              </td>
              <td>
                <a class="btn btn-default btn-sm" href="productos.php?action=edit&id=<?= (int)$p['id'] ?>">Editar</a>
                <a href="#" 
                   class="btn btn-danger btn-sm btn-delete" 
                   data-id="<?= (int)$p['id'] ?>" 
                   data-nombre="<?= htmlspecialchars($p['nombre']) ?>">
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
          <p>¿Seguro que deseas eliminar el producto <strong id="productoNombre"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          <a id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // buscador en vivo
    document.getElementById('buscador').addEventListener('keyup', function(){
      let filtro = this.value.toLowerCase();
      let filas = document.querySelectorAll("#tablaProductos tbody tr");
      filas.forEach(function(fila){
        let texto = fila.textContent.toLowerCase();
        fila.style.display = texto.indexOf(filtro) > -1 ? '' : 'none';
      });
    });

    // popup eliminar
    document.querySelectorAll('.btn-delete').forEach(btn=>{
      btn.addEventListener('click', function(e){
        e.preventDefault();
        let id = this.dataset.id;
        let nombre = this.dataset.nombre;
        document.getElementById('productoNombre').textContent = nombre;
        document.getElementById('btnConfirmarEliminar').setAttribute('href', 'productos.php?action=delete&id='+id);
        $('#modalEliminar').modal('show');
      });
    });
  </script>
  <?php
}
include __DIR__ . '/footer.php';
?>
