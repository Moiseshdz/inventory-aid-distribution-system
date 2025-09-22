<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/conexion.php';
require_login();

// Traemos entregas con todos los detalles
$query = "
  SELECT 
    e.id AS entrega_id,
    e.fecha, 
    b.id AS beneficiario_id,
    b.nombre AS beneficiario, 
    b.curp,
    b.telefono,
    b.domicilio,
    e.foto,        -- ✅ fotos vienen de entregas
    e.foto_ine,    -- ✅ fotos vienen de entregas
    GROUP_CONCAT(CONCAT(p.nombre, '|', d.cantidad, '|', IFNULL(p.foto,'')) SEPARATOR ';;') AS productos_concat
  FROM entregas e
  JOIN beneficiarios b ON b.id = e.beneficiario_id
  JOIN entrega_detalles d ON d.entrega_id = e.id
  JOIN productos p ON p.id = d.producto_id
  GROUP BY e.id
  ORDER BY e.fecha DESC
";

$res = $mysqli->query($query);

// Organizar entregas por fecha
$entregas_por_fecha = [];
while ($row = $res->fetch_assoc()) {
  $productos = [];
  if (!empty($row['productos_concat'])) {
    $parts = explode(';;', $row['productos_concat']);
    foreach ($parts as $p) {
      list($nombre, $cantidad, $foto) = explode('|', $p);
      $productos[] = ['nombre' => $nombre, 'cantidad' => $cantidad, 'foto' => $foto];
    }
  }
  $row['productos'] = $productos;
  $entregas_por_fecha[$row['fecha']][] = $row;
}

include __DIR__ . '/header.php';
?>
<div class="panel panel-default">
  <div class="panel-heading" style="display:flex;justify-content:space-between;align-items:center;">
    <h3 class="panel-title">Historial de entregas</h3>
    <input type="text" id="buscador" class="form-control" style="max-width:250px;" placeholder="Buscar...">
  </div>
  <div class="panel-body">
    <?php foreach ($entregas_por_fecha as $fecha => $entregas): ?>
      <h4 style="margin-top:20px;"><?= htmlspecialchars($fecha) ?></h4>
      <div class="table-responsive">
        <table class="table table-striped historial-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Ver</th>
              <th>Productos</th>
              <th>Beneficiario</th>
              <th>Foto</th>
            </tr>
          </thead>
          <tbody>
            <?php $contador = 1; ?>
            <?php foreach ($entregas as $row): ?>
              <tr>
                <td><?= $contador++ ?></td>
                <td>
                  <a href="#"
                     class="btn btn-info btn-sm ver-beneficiario"
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
                <td>
                  <?php foreach ($row['productos'] as $p): ?>
                    <?= htmlspecialchars($p['nombre']) ?> (<?= (int) $p['cantidad'] ?>)<br>
                  <?php endforeach; ?>
                </td>
                <td><?= htmlspecialchars($row['beneficiario']) ?></td>
                <td>
                  <?php if ($row['foto']): ?>
                    <img src="<?= htmlspecialchars($row['foto']) ?>" style="height:64px;border-radius:6px">
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Modal Bootstrap -->
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
// === Buscador en JS ===
document.getElementById('buscador').addEventListener('keyup', function(){
  let filtro = this.value.toLowerCase();
  document.querySelectorAll(".historial-table tbody tr").forEach(function(fila){
    let textoFila = fila.textContent.toLowerCase();
    fila.style.display = textoFila.includes(filtro) ? '' : 'none';
  });
});

// === Popup ===
document.querySelectorAll('.ver-beneficiario').forEach(function(el){
  el.addEventListener('click', function(e){
    e.preventDefault();
    document.getElementById('b-nombre').textContent = this.dataset.nombre;
    document.getElementById('b-curp').textContent = this.dataset.curp;
    document.getElementById('b-telefono').textContent = this.dataset.telefono;
    document.getElementById('b-domicilio').textContent = this.dataset.domicilio;
    document.getElementById('b-fecha').textContent = this.dataset.fecha;

    let productos = JSON.parse(this.dataset.productos);
    let html = "";
    productos.forEach(p => {
      html += `<div style="margin-bottom:10px;">
                 <strong>${p.nombre}</strong> (x${p.cantidad})<br>`;
      if (p.foto) {
        html += `<img src="${p.foto}" style="height:60px;border-radius:6px;margin-top:5px;">`;
      }
      html += `</div>`;
    });
    document.getElementById('b-productos').innerHTML = html;

    document.getElementById('b-foto').innerHTML = this.dataset.foto
      ? '<img src="'+this.dataset.foto+'" style="max-width:100%;">'
      : '<em>Sin foto</em>';

    document.getElementById('b-fotoine').innerHTML = this.dataset.fotoine
      ? '<img src="'+this.dataset.fotoine+'" style="max-width:100%;">'
      : '<em>Sin foto</em>';

    $('#modalBeneficiario').modal('show');
  });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
