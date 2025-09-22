<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/conexion.php';
require_login();
require_role(['operador', 'admin']);

$msg = null;

// Función para guardar fotos en /uploads/
function guardar_foto_benef($input = 'foto')
{
  if (!empty($_FILES[$input]['name'])) {
    $dir = __DIR__ . '/uploads/';
    if (!is_dir($dir))
      mkdir($dir, 0777, true);

    $file = time() . '_' . basename($_FILES[$input]['name']);
    $dest = $dir . $file;

    if (move_uploaded_file($_FILES[$input]['tmp_name'], $dest)) {
      return 'uploads/' . $file;
    }
  }
  return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $curp = trim($_POST['curp'] ?? '');
  $telefono = trim($_POST['telefono'] ?? '');
  $domicilio = trim($_POST['domicilio'] ?? '');
  $producto_ids = $_POST['producto_id'] ?? [];
  $cantidades = $_POST['cantidad'] ?? [];

  // Guardar fotos (se almacenan en la tabla entregas)
  $foto = guardar_foto_benef('foto') ?? '';
  $foto_ine = guardar_foto_benef('foto_ine') ?? '';

  if (empty($producto_ids)) {
    $msg = "Selecciona al menos un producto.";
  } else {
    // buscar/crear beneficiario
    $beneficiario_id = null;
    if ($curp) {
      $stmt = $mysqli->prepare("SELECT id FROM beneficiarios WHERE curp=? LIMIT 1");
      $stmt->bind_param("s", $curp);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($row = $res->fetch_assoc()) {
        $beneficiario_id = (int) $row['id'];
      }
      $stmt->close();
    }

    // si no existe, lo creamos
    if (!$beneficiario_id) {
      $stmt = $mysqli->prepare("INSERT INTO beneficiarios(nombre,curp,telefono,domicilio) VALUES(?,?,?,?)");
      $stmt->bind_param("ssss", $nombre, $curp, $telefono, $domicilio);
      $stmt->execute();
      $beneficiario_id = $stmt->insert_id;
      $stmt->close();
    } else {
      // solo actualizamos teléfono y domicilio, nunca fotos
      $stmt = $mysqli->prepare("UPDATE beneficiarios SET telefono=?, domicilio=? WHERE id=?");
      $stmt->bind_param("ssi", $telefono, $domicilio, $beneficiario_id);
      $stmt->execute();
      $stmt->close();
    }

    // crear la entrega (con fotos)
    $stmt = $mysqli->prepare("INSERT INTO entregas(beneficiario_id, foto, foto_ine) VALUES(?,?,?)");
    $stmt->bind_param("iss", $beneficiario_id, $foto, $foto_ine);
    $stmt->execute();
    $entrega_id = $stmt->insert_id;
    $stmt->close();

    // recorrer productos
    $ok = true;
    for ($i = 0; $i < count($producto_ids); $i++) {
      $pid = (int) $producto_ids[$i];
      $cant = max(1, (int) $cantidades[$i]);

      // validar stock
      $stock = 0;
      $stmt = $mysqli->prepare("SELECT cantidad FROM productos WHERE id=?");
      $stmt->bind_param("i", $pid);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($p = $res->fetch_assoc()) {
        $stock = (int) $p['cantidad'];
      }
      $stmt->close();

      if ($stock < $cant) {
        $ok = false;
        $msg = "❌ No hay suficiente stock para el producto ID $pid.";
        break;
      }

      // registrar detalle
      $stmt = $mysqli->prepare("INSERT INTO entrega_detalles(entrega_id,producto_id,cantidad) VALUES(?,?,?)");
      $stmt->bind_param("iii", $entrega_id, $pid, $cant);
      $stmt->execute();
      $stmt->close();

      // descontar stock
      $stmt = $mysqli->prepare("UPDATE productos SET cantidad=cantidad-? WHERE id=?");
      $stmt->bind_param("ii", $cant, $pid);
      $stmt->execute();
      $stmt->close();
    }

    if ($ok) {
      $msg = "✅ Entrega registrada correctamente.";
    }
  }
}

$productos = $mysqli->query("SELECT id,nombre,cantidad FROM productos WHERE cantidad>0 ORDER BY nombre");

include __DIR__ . '/header.php';
?>
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">Registrar entrega</h3>
  </div>
  <div class="panel-body">
    <?php if ($msg): ?>
      <div class="alert alert-info"><?= $msg ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
      <div class="row">
        <div class="col-sm-6">
          <div class="form-group"><label>Nombre beneficiario</label><input name="nombre" class="form-control" required></div>
        </div>
        <div class="col-sm-6">
          <div class="form-group"><label>CURP (opcional)</label><input name="curp" maxlength="18" class="form-control"></div>
        </div>
        <div class="col-sm-6">
          <div class="form-group"><label>Teléfono</label><input name="telefono" class="form-control"></div>
        </div>
        <div class="col-sm-6">
          <div class="form-group"><label>Domicilio</label><input name="domicilio" class="form-control"></div>
        </div>
        <div class="col-sm-6">
          <div class="form-group"><label>Foto beneficiario</label><input type="file" name="foto" class="form-control" accept="image/*"></div>
        </div>
        <div class="col-sm-6">
          <div class="form-group"><label>Foto INE</label><input type="file" name="foto_ine" class="form-control" accept="image/*"></div>
        </div>

        <!-- Productos -->
        <div id="productos-container">
          <div class="row producto-row" style="margin-bottom:10px;">
            <div class="col-xs-12 col-sm-6">
              <select name="producto_id[]" class="form-control" required>
                <option value="">-- Selecciona --</option>
                <?php $productos->data_seek(0); while ($row = $productos->fetch_assoc()): ?>
                  <option value="<?= (int) $row['id'] ?>">
                    <?= htmlspecialchars($row['nombre']) ?> (<?= (int) $row['cantidad'] ?> disponibles)
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-xs-8 col-sm-4" style="margin-top:5px;">
              <input type="number" name="cantidad[]" class="form-control" min="1" value="1">
            </div>
            <div class="col-xs-4 col-sm-2" style="margin-top:5px;">
              <button type="button" class="btn btn-danger btn-remove" style="width:100%;">X</button>
            </div>
          </div>
        </div>
        <button type="button" class="btn btn-default" id="add-producto">➕ Agregar otro producto</button>

        <hr>
        <button class="btn btn-primary" type="submit">Registrar</button>
    </form>
  </div>
</div>

<script>
  document.getElementById('add-producto').onclick = function () {
    const cont = document.getElementById('productos-container');
    const firstRow = cont.querySelector('.producto-row');
    const row = firstRow.cloneNode(true);

    row.querySelector('select').value = '';
    row.querySelector('input').value = 1;
    row.querySelector('.btn-remove').onclick = function () { row.remove(); };

    cont.appendChild(row);
  };

  document.querySelectorAll('.btn-remove').forEach(btn => {
    btn.onclick = function () { btn.closest('.producto-row').remove(); };
  });
</script>

<?php include __DIR__ . '/footer.php'; ?>
