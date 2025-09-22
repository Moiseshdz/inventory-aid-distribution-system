<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/conexion.php';
require_login();

$tot_prod = $mysqli->query("SELECT COUNT(*) c FROM productos")->fetch_assoc()['c'] ?? 0;
$tot_ben = $mysqli->query("SELECT COUNT(*) c FROM beneficiarios")->fetch_assoc()['c'] ?? 0;
$tot_ent = $mysqli->query("SELECT COUNT(*) c FROM entregas")->fetch_assoc()['c'] ?? 0;

// Traer últimas 8 entregas
$ultimas = $mysqli->query("
  SELECT 
    e.id AS entrega_id,
    DATE(e.fecha) AS fecha_dia,
    e.fecha,
    b.nombre AS beneficiario,
    b.curp,
    b.telefono,
    b.domicilio,
    e.foto,          -- ✅ fotos vienen de entregas
    e.foto_ine,      -- ✅ fotos vienen de entregas
    GROUP_CONCAT(CONCAT(p.nombre, '|', d.cantidad, '|', IFNULL(p.foto,'')) SEPARATOR ';;') AS productos_concat
  FROM entregas e
  JOIN beneficiarios b ON b.id = e.beneficiario_id
  JOIN entrega_detalles d ON d.entrega_id = e.id
  JOIN productos p ON p.id = d.producto_id
  GROUP BY e.id
  ORDER BY e.fecha DESC
  LIMIT 8
");

// Agrupar entregas y armar resumen
$entregas_por_fecha = [];
$resumen_productos = [];
$total_general = 0;
while ($row = $ultimas->fetch_assoc()) {
  $fecha = $row['fecha_dia'];
  $productos = [];
  if (!empty($row['productos_concat'])) {
    $parts = explode(';;', $row['productos_concat']);
    foreach ($parts as $p) {
      list($nombre, $cantidad, $foto) = explode('|', $p);
      $productos[] = ['nombre' => $nombre, 'cantidad' => $cantidad, 'foto' => $foto];

      // Resumen general
      if (!isset($resumen_productos[$nombre])) {
        $resumen_productos[$nombre] = ['total' => 0, 'beneficiarios' => []];
      }
      $resumen_productos[$nombre]['total'] += (int) $cantidad;
      $total_general += (int) $cantidad;

      // Guardar beneficiario completo
      $resumen_productos[$nombre]['beneficiarios'][] = [
        'nombre' => $row['beneficiario'],
        'curp' => $row['curp'],
        'telefono' => $row['telefono'],
        'domicilio' => $row['domicilio'],
        'foto' => $row['foto'],
        'foto_ine' => $row['foto_ine'],
        'fecha' => $row['fecha'],
        'productos' => $productos,
        'cantidad' => (int) $cantidad
      ];
    }
  }
  $row['productos'] = $productos;
  $entregas_por_fecha[$fecha][] = $row;
}

include __DIR__ . '/header.php';
?>

<div class="row">
  <div class="col-sm-4">
    <div class="well">
      <strong>Productos:</strong>
      <span class="label label-default"><?= (int) $tot_prod ?></span>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="well">
      <strong>Beneficiarios:</strong>
      <span class="label label-default"><?= (int) $tot_ben ?></span>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="well">
      <strong>Entregas:</strong>
      <span class="label label-default"><?= (int) $tot_ent ?></span>
    </div>
  </div>
</div>

<!-- Últimas entregas -->
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">Últimas entregas</h3>
  </div>
  <div class="panel-body">
    <?php foreach ($entregas_por_fecha as $fecha => $lista): ?>
      <h4 style="margin-top:20px;"><?= htmlspecialchars($fecha) ?></h4>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Beneficiario</th>
              <th>Ver</th>
            </tr>
          </thead>
          <tbody>
            <?php $contador = 1; ?>
            <?php foreach ($lista as $row): ?>
              <tr>
                <td><?= $contador++ ?></td>
                <td><?= htmlspecialchars($row['beneficiario']) ?></td>
                <td>
                  <a href="#" class="btn btn-info btn-sm ver-beneficiario"
                    data-nombre="<?= htmlspecialchars($row['beneficiario']) ?>"
                    data-curp="<?= htmlspecialchars($row['curp']) ?>"
                    data-telefono="<?= htmlspecialchars($row['telefono']) ?>"
                    data-domicilio="<?= htmlspecialchars($row['domicilio']) ?>"
                    data-foto="<?= htmlspecialchars($row['foto']) ?>"
                    data-fotoine="<?= htmlspecialchars($row['foto_ine']) ?>"
                    data-fecha="<?= htmlspecialchars($row['fecha']) ?>"
                    data-productos='<?= json_encode($row['productos'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                    Ver
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<style>
  a#listaBeneficiarios {
    text-decoration: none;
    cursor: pointer;
    color: #337ab7;
    font-size: 13px;
    padding-top: 0;
  }

  a#listaBeneficiarios:hover {
    text-decoration: none;
    color: #ddb42fff;
  }
</style>

<!-- Resumen -->
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">Resumen de productos entregados
      <span style="float:right;">Total: (<?= $total_general ?>).</span>
    </h3>
  </div>
  <div class="panel-body">
    <ul>
      <?php foreach ($resumen_productos as $producto => $info): ?>
        <li>
          <strong><?= htmlspecialchars($producto) ?>:</strong>
          <?= $info['total'] ?> entregados
          <br><small>Beneficiarios:</small><br>
          <?php $i = 1; ?>
          <?php foreach ($info['beneficiarios'] as $ben): ?>
            <?= $i++ ?>.
            <a href="#" id="listaBeneficiarios" class="btn btn-link ver-beneficiario"
              data-nombre="<?= htmlspecialchars($ben['nombre']) ?>" data-curp="<?= htmlspecialchars($ben['curp']) ?>"
              data-telefono="<?= htmlspecialchars($ben['telefono']) ?>"
              data-domicilio="<?= htmlspecialchars($ben['domicilio']) ?>" data-foto="<?= htmlspecialchars($ben['foto']) ?>"
              data-fotoine="<?= htmlspecialchars($ben['foto_ine']) ?>" data-fecha="<?= htmlspecialchars($ben['fecha']) ?>"
              data-productos='<?= json_encode($ben['productos'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
              <?= htmlspecialchars($ben['nombre']) ?> (<?= $ben['cantidad'] ?>)
            </a><br>
          <?php endforeach; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalBeneficiario" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <button type="button" class="close" data-dismiss="modal" style="color:white;">&times;</button>
        <h4 class="modal-title">Datos del Beneficiario</h4>
      </div>
      <div class="modal-body">
        <p><strong>Nombre:</strong> <span id="b-nombre"></span></p>
        <p><strong>CURP:</strong> <span id="b-curp"></span></p>
        <p><strong>Teléfono:</strong> <span id="b-telefono"></span></p>
        <p><strong>Domicilio:</strong> <span id="b-domicilio"></span></p>
        <p><strong>Fecha:</strong> <span id="b-fecha"></span></p>
        <hr>
        <p><strong>Productos entregados:</strong></p>
        <div id="b-productos"></div>
        <hr>
        <p><strong>Foto beneficiario:</strong></p>
        <div id="b-foto"></div>
        <p><strong>Foto INE:</strong></p>
        <div id="b-fotoine"></div>
      </div>
    </div>
  </div>
</div>

<script>
  document.querySelectorAll('.ver-beneficiario').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      document.getElementById('b-nombre').textContent = this.dataset.nombre;
      document.getElementById('b-curp').textContent = this.dataset.curp;
      document.getElementById('b-telefono').textContent = this.dataset.telefono;
      document.getElementById('b-domicilio').textContent = this.dataset.domicilio;
      document.getElementById('b-fecha').textContent = this.dataset.fecha;

      let productos = JSON.parse(this.dataset.productos);
      let html = "";
      productos.forEach(p => {
        html += `<div><strong>${p.nombre}</strong> (x${p.cantidad})<br>`;
        if (p.foto) {
          html += `<img src="${p.foto}" style="max-height:60px;margin-top:5px;border-radius:6px;">`;
        }
        html += `</div><hr>`;
      });
      document.getElementById('b-productos').innerHTML = html;

      if (this.dataset.foto) {
        document.getElementById('b-foto').innerHTML = '<img src="' + this.dataset.foto + '" style="max-width:100%;max-height:200px;border-radius:6px;">';
      } else {
        document.getElementById('b-foto').innerHTML = '<em>Sin foto</em>';
      }
      if (this.dataset.fotoine) {
        document.getElementById('b-fotoine').innerHTML = '<img src="' + this.dataset.fotoine + '" style="max-width:100%;max-height:200px;border-radius:6px;">';
      } else {
        document.getElementById('b-fotoine').innerHTML = '<em>Sin foto</em>';
      }

      $('#modalBeneficiario').modal('show');
    });
  });
</script>

<?php include __DIR__ . '/footer.php'; ?>