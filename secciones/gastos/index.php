<?php
$thetitle = "Gastos";
include("../../templates/header.php"); ?>
<?php
include("../../bd.php");
$id_condominio = $_SESSION['idcondominio'];

// =============================================
// LÓGICA PHP (INTACTA)
// =============================================

// 1. PROCESAR NUEVO GASTO
if ($_POST && isset($_POST['sel_mas_menos']) && !isset($_POST['es_predeterminado'])) {
   $tipo_gasto = $_POST["sel_mas_menos"];
   $detalles = $_POST["detalles"];
   $monto = $_POST["monto"];
   $quincena = $_POST["quincena"];
   $mes = $_POST["mes"] ?? $_SESSION['mes'];
   $anio = $_POST["anio"] ?? $_SESSION['anio'];

   try {
      $sentencia = $conexion->prepare("INSERT INTO tbl_gastos (tipo_gasto, detalles, monto, quincena, mes, anio, id_condominio) VALUES (:tipo_gasto, :detalles, :monto, :quincena, :mes, :anio, :id_condominio)");
      $sentencia->execute([':tipo_gasto' => $tipo_gasto, ':detalles' => $detalles, ':monto' => $monto, ':quincena' => $quincena, ':mes' => $mes, ':anio' => $anio, ':id_condominio' => $id_condominio]);
      $_SESSION['mensaje'] = "✅ Gasto registrado";
      $_SESSION['tipo_mensaje'] = "success";
      header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $mes . "&anio=" . $anio . "&periodo=" . $quincena);
      exit;
   } catch (Exception $e) {
      $_SESSION['mensaje'] = "❌ Error: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }
}

// 2. AGREGAR PREDETERMINADO
if ($_POST && isset($_POST['es_predeterminado'])) {
   $tipo_gasto = $_POST["sel_mas_menos"];
   $detalles = $_POST["detalles"];
   $monto = $_POST["monto"];
   try {
      $check = $conexion->prepare("SELECT id FROM tbl_gastos_predeterminados WHERE detalles = :det");
      $check->execute([':det' => $detalles]);
      if ($check->fetch()) {
         $_SESSION['mensaje'] = "⚠️ Ya existe este gasto";
         $_SESSION['tipo_mensaje'] = "warning";
      } else {
         $sql = "INSERT INTO tbl_gastos_predeterminados (tipo_gasto, detalles, monto, quincena, activo) VALUES (:t, :d, :m, '15', 1)";
         $conexion->prepare($sql)->execute([':t' => $tipo_gasto, ':d' => $detalles, ':m' => $monto]);
         $_SESSION['mensaje'] = "✅ Configuración guardada";
         $_SESSION['tipo_mensaje'] = "success";
      }
      header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $_GET['mes'] . "&anio=" . $_GET['anio'] . "&periodo=" . $_GET['periodo']);
      exit;
   } catch (Exception $e) { /*...*/
   }
}

// 3. GENERAR GASTOS
if (isset($_GET['generar_gastos'])) {
   $mes = $_GET['mes'];
   $anio = $_GET['anio'];
   $count = 0;
   try {
      $pred = $conexion->query("SELECT * FROM tbl_gastos_predeterminados WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);
      foreach ($pred as $g) {
         $check = $conexion->prepare("SELECT id FROM tbl_gastos WHERE detalles=:d AND mes=:m AND anio=:a AND quincena=:q");
         $check->execute([':d' => $g['detalles'], ':m' => $mes, ':a' => $anio, ':q' => $g['quincena']]);
         if (!$check->fetch()) {
            $ins = $conexion->prepare("INSERT INTO tbl_gastos (tipo_gasto, detalles, monto, quincena, mes, anio, id_condominio) VALUES (:t, :d, :mo, :q, :m, :a, :id)");
            $ins->execute([':t' => $g['tipo_gasto'], ':d' => $g['detalles'], ':mo' => $g['monto'], ':q' => $g['quincena'], ':m' => $mes, ':a' => $anio, ':id' => $id_condominio]);
            $count++;
         }
      }
      $_SESSION['mensaje'] = "✅ Se generaron $count gastos.";
      $_SESSION['tipo_mensaje'] = "success";
      header("Location: " . $_SERVER['PHP_SELF'] . "?mes=$mes&anio=$anio&periodo=15");
      exit;
   } catch (Exception $e) { /*...*/
   }
}

// 4. ELIMINAR/TOGGLE/EDITAR
if (isset($_GET['toggle_predeterminado'])) {
   $conexion->prepare("UPDATE tbl_gastos_predeterminados SET activo = NOT activo WHERE id = ?")->execute([$_GET['toggle_predeterminado']]);
   header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $_GET['mes'] . "&anio=" . $_GET['anio'] . "&periodo=" . $_GET['periodo']);
   exit;
}
if (isset($_GET['eliminar_predeterminado'])) {
   $conexion->prepare("DELETE FROM tbl_gastos_predeterminados WHERE id = ?")->execute([$_GET['eliminar_predeterminado']]);
   header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $_GET['mes'] . "&anio=" . $_GET['anio'] . "&periodo=" . $_GET['periodo']);
   exit;
}
if (isset($_GET['txID'])) {
   $conexion->prepare("DELETE FROM tbl_gastos WHERE id=?")->execute([$_GET['txID']]);
   $_SESSION['mensaje'] = "✅ Gasto eliminado";
   $_SESSION['tipo_mensaje'] = "success";
   header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . ($_GET['mes'] ?? '') . "&anio=" . ($_GET['anio'] ?? '') . "&periodo=" . ($_GET['periodo'] ?? ''));
   exit;
}
if ($_POST && isset($_POST['editar_gasto'])) {
   $conexion->prepare("UPDATE tbl_gastos SET monto=?, detalles=?, quincena=? WHERE id=?")->execute([$_POST['nuevo_monto'], $_POST['nuevos_detalles'], $_POST['nueva_quincena'], $_POST['id_gasto']]);
   $_SESSION['mensaje'] = "✅ Gasto actualizado";
   $_SESSION['tipo_mensaje'] = "success";
   header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $_GET['mes'] . "&anio=" . $_GET['anio'] . "&periodo=" . $_GET['periodo']);
   exit;
}

// CONFIGURACIÓN Y CONSULTAS
$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$anios = range(2025, date('Y') + 5);
$mes_filtro = $_GET['mes'] ?? $meses[date('n') - 1];
$anio_filtro = $_GET['anio'] ?? date('Y');
$periodo_filtro = $_GET['periodo'] ?? '15';

$tipos_gastos = [
   'Nomina_Empleados' => ['icon' => '👥', 'label' => 'Nómina', 'color' => 'primary'],
   'Servicios_Basicos' => ['icon' => '💡', 'label' => 'Servicios', 'color' => 'warning'],
   'Gastos_Menores_Material_Gastable' => ['icon' => '🧹', 'label' => 'Materiales', 'color' => 'info'],
   'Imprevistos' => ['icon' => '⚠️', 'label' => 'Imprevistos', 'color' => 'danger'],
   'Cargos_Bancarios' => ['icon' => '🏦', 'label' => 'Bancarios', 'color' => 'secondary'],
   'Servicios_Igualados' => ['icon' => '🤝', 'label' => 'Igualas', 'color' => 'success']
];

function obtenerGastos($conn, $tipo, $id, $m, $a, $p)
{
   $sql = "SELECT * FROM tbl_gastos WHERE id_condominio=? AND tipo_gasto=? AND mes=? AND anio=?";
   if ($p != 'completo') $sql .= " AND quincena=?";
   $sql .= " ORDER BY id DESC";
   $stmt = $conn->prepare($sql);
   if ($p != 'completo') $stmt->execute([$id, $tipo, $m, $a, $p]);
   else $stmt->execute([$id, $tipo, $m, $a]);
   return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$gastos_data = [];
$total_15 = 0;
$total_30 = 0;
$total_mes = 0;

$stmt_totales = $conexion->prepare("SELECT quincena, SUM(monto) as total FROM tbl_gastos WHERE id_condominio=? AND mes=? AND anio=? GROUP BY quincena");
$stmt_totales->execute([$id_condominio, $mes_filtro, $anio_filtro]);
while ($r = $stmt_totales->fetch(PDO::FETCH_ASSOC)) {
   if ($r['quincena'] == '15') $total_15 = $r['total'];
   if ($r['quincena'] == '30') $total_30 = $r['total'];
}
$total_mes = $total_15 + $total_30;

foreach ($tipos_gastos as $k => $v) {
   $gastos_data[$k] = obtenerGastos($conexion, $k, $id_condominio, $mes_filtro, $anio_filtro, $periodo_filtro);
}

$pred = $conexion->query("SELECT * FROM tbl_gastos_predeterminados ORDER BY tipo_gasto")->fetchAll(PDO::FETCH_ASSOC);
$activos_count = count(array_filter($pred, function ($v) {
   return $v['activo'] == 1;
}));
?>

<style>
   body {
      background-color: #eef2f6;
   }

   .card-resumen {
      border: none;
      border-radius: 15px;
      color: white;
      margin-bottom: 20px;
      transition: transform 0.2s;
   }

   .card-resumen:hover {
      transform: scale(1.02);
   }

   /* Diseño de las tarjetas de categorías */
   .expense-card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      height: 100%;
      /* Misma altura */
      transition: box-shadow 0.3s;
   }

   .expense-card:hover {
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
   }

   .expense-header {
      border-top-left-radius: 12px;
      border-top-right-radius: 12px;
      padding: 15px;
      font-weight: bold;
      display: flex;
      justify-content: space-between;
      align-items: center;
   }

   .expense-body {
      padding: 0;
      max-height: 250px;
      /* Altura máxima para scroll */
      overflow-y: auto;
   }

   .table-expense td {
      vertical-align: middle;
      font-size: 0.9rem;
   }

   /* Scrollbar bonito */
   .expense-body::-webkit-scrollbar {
      width: 6px;
   }

   .expense-body::-webkit-scrollbar-track {
      background: #f1f1f1;
   }

   .expense-body::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 10px;
   }

   .expense-body::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
   }

   .bg-gradient-1 {
      background: linear-gradient(45deg, #000000ff 0%, #565656ff 100%);
   }

   .bg-gradient-2 {
      background: linear-gradient(45deg, #000000ff 0%, #565656ff 100%);
   }

   .bg-gradient-3 {
      background: linear-gradient(45deg, #a0a0a0ff 0%, #dbdbdbff 100%);
   }
</style>

<div class="container-fluid px-4 py-4">

   <?php if (isset($_SESSION['mensaje'])): ?>
      <div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show" role="alert">
         <strong><?php echo $_SESSION['mensaje']; ?></strong>
         <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['mensaje']);
      unset($_SESSION['tipo_mensaje']); ?>
   <?php endif; ?>

   <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
         <h2 class="fw-bold text-dark mb-0">📊 Tablero de Gastos</h2>
         <small class="text-muted">Vista consolidada del mes</small>
      </div>
      <div class="d-flex gap-2">
         <button class="btn btn-dark shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalGastosPredeterminados">
            ⚙️ Configurar Fijos <span class="badge bg-warning text-dark ms-1"><?php echo $activos_count; ?></span>
         </button>
         <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevoGasto">
            ➕ Nuevo Gasto
         </button>
         <button class="btn btn-outline-primary bg-white shadow-sm" onclick="confirmarGeneracion()">
            ⚡ Generar Mes
         </button>
      </div>
   </div>

   <div class="card shadow-sm border-0 mb-4">
      <div class="card-body py-3">
         <form action="" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
               <div class="input-group">
                  <span class="input-group-text bg-white"><i class="fas fa-calendar"></i></span>
                  <select name="mes" class="form-select fw-bold">
                     <?php foreach ($meses as $m) echo "<option value='$m' " . ($m == $mes_filtro ? 'selected' : '') . ">$m</option>"; ?>
                  </select>
               </div>
            </div>
            <div class="col-md-2">
               <select name="anio" class="form-select fw-bold">
                  <?php foreach ($anios as $a) echo "<option value='$a' " . ($a == $anio_filtro ? 'selected' : '') . ">$a</option>"; ?>
               </select>
            </div>
            <div class="col-md-3">
               <select name="periodo" class="form-select fw-bold">
                  <option value="15" <?php echo $periodo_filtro == '15' ? 'selected' : ''; ?>>1ra Quincena (1-15)</option>
                  <option value="30" <?php echo $periodo_filtro == '30' ? 'selected' : ''; ?>>2da Quincena (16-30)</option>
                  <option value="completo" <?php echo $periodo_filtro == 'completo' ? 'selected' : ''; ?>>Mes Completo</option>
               </select>
            </div>
            <div class="col-md-2">
               <button type="submit" class="btn btn-secondary w-100 fw-bold">Filtrar</button>
            </div>
         </form>
      </div>
   </div>

   <div class="row mb-4">
      <div class="col-md-4">
         <div class="card-resumen bg-gradient-1 p-3">
            <small class="text-uppercase opacity-75 fw-bold">Total 1ra Quincena</small>
            <h3 class="mb-0 fw-bold">RD$ <?php echo number_format($total_15, 2); ?></h3>
         </div>
      </div>
      <div class="col-md-4">
         <div class="card-resumen bg-gradient-2 p-3">
            <small class="text-uppercase opacity-75 fw-bold">Total 2da Quincena</small>
            <h3 class="mb-0 fw-bold">RD$ <?php echo number_format($total_30, 2); ?></h3>
         </div>
      </div>
      <div class="col-md-4">
         <div class="card-resumen bg-gradient-3 p-3 text-dark">
            <small class="text-uppercase opacity-75 fw-bold">Total General Mes</small>
            <h3 class="mb-0 fw-bold">RD$ <?php echo number_format($total_mes, 2); ?></h3>
         </div>
      </div>
   </div>

   <div class="row g-4">
      <?php foreach ($tipos_gastos as $key => $info):
         $data = $gastos_data[$key];
         $subtotal = array_sum(array_column($data, 'monto'));
         // Colores dinámicos basados en el array $tipos_gastos
         $color = $info['color'] ?? 'primary';
      ?>
         <div class="col-md-6 col-xl-4">
            <div class="expense-card bg-white">
               <div class="expense-header bg-soft-<?php echo $color; ?> border-bottom border-<?php echo $color; ?>">
                  <div class="d-flex align-items-center text-<?php echo $color; ?>">
                     <span class="fs-4 me-2"><?php echo $info['icon']; ?></span>
                     <span><?php echo $info['label']; ?></span>
                  </div>
                  <span class="badge bg-<?php echo $color; ?> rounded-pill fs-6 shadow-sm">
                     RD$ <?php echo number_format($subtotal, 2); ?>
                  </span>
               </div>

               <div class="expense-body">
                  <?php if (empty($data)): ?>
                     <div class="text-center py-4 text-muted opacity-50">
                        <i class="fas fa-folder-open fa-2x mb-2"></i>
                        <p class="mb-0 small fw-bold">Sin registros</p>
                     </div>
                  <?php else: ?>
                     <table class="table table-expense table-hover mb-0">
                        <thead class="table-light sticky-top">
                           <tr>
                              <th class="ps-3 border-0">Detalle</th>
                              <th class="text-center border-0" style="width: 50px;">Q</th>
                              <th class="text-end border-0">Monto</th>
                              <th class="text-end pe-3 border-0" style="width: 60px;"></th>
                           </tr>
                        </thead>
                        <tbody>
                           <?php foreach ($data as $g): ?>
                              <tr>
                                 <td class="ps-3 text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($g['detalles']); ?>">
                                    <?php echo $g['detalles']; ?>
                                 </td>
                                 <td class="text-center">
                                    <span class="badge <?php echo $g['quincena'] == '15' ? 'bg-secondary' : 'bg-dark'; ?> rounded-circle p-1" style="width: 24px; height: 24px; display:inline-flex; justify-content:center; align-items:center;">
                                       <?php echo $g['quincena'] == '15' ? '1' : '2'; ?>
                                    </span>
                                 </td>
                                 <td class="text-end fw-bold text-dark">
                                    <?php echo number_format($g['monto'], 2); ?>
                                 </td>
                                 <td class="text-end pe-3">
                                    <div class="dropdown">
                                       <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                                          <i class="fas fa-ellipsis-v"></i>
                                       </button>
                                       <ul class="dropdown-menu dropdown-menu-end shadow">
                                          <li>
                                             <button class="dropdown-item small"
                                                data-bs-toggle="modal" data-bs-target="#modalEditarGasto"
                                                data-id="<?php echo $g['id']; ?>"
                                                data-detalles="<?php echo htmlspecialchars($g['detalles']); ?>"
                                                data-monto="<?php echo $g['monto']; ?>"
                                                data-quincena="<?php echo $g['quincena']; ?>"
                                                data-tipo="<?php echo $g['tipo_gasto']; ?>">
                                                ✏️ Editar
                                             </button>
                                          </li>
                                          <li>
                                             <hr class="dropdown-divider">
                                          </li>
                                          <li>
                                             <button class="dropdown-item small text-danger"
                                                onclick="borrar(<?php echo $g['id']; ?>, '<?php echo $mes_filtro; ?>', '<?php echo $anio_filtro; ?>', '<?php echo $periodo_filtro; ?>')">
                                                🗑️ Eliminar
                                             </button>
                                          </li>
                                       </ul>
                                    </div>
                                 </td>
                              </tr>
                           <?php endforeach; ?>
                        </tbody>
                     </table>
                  <?php endif; ?>
               </div>
            </div>
         </div>
      <?php endforeach; ?>
   </div>

</div>

<div class="modal fade" id="modalNuevoGasto" tabindex="-1">
   <div class="modal-dialog">
      <div class="modal-content border-0 shadow">
         <div class="modal-header bg-primary text-white">
            <h5 class="modal-title fw-bold">➕ Registrar Gasto</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form action="" method="post" id="formNuevoGasto">
               <div class="mb-3">
                  <label class="form-label small fw-bold text-muted">Categoría</label>
                  <select name="sel_mas_menos" class="form-select" required>
                     <option value="">Seleccionar...</option>
                     <?php foreach ($tipos_gastos as $k => $v) echo "<option value='$k'>{$v['label']}</option>"; ?>
                  </select>
               </div>
               <div class="row mb-3">
                  <div class="col-6">
                     <label class="form-label small fw-bold text-muted">Quincena</label>
                     <select name="quincena" class="form-select">
                        <option value="15">1ra (1-15)</option>
                        <option value="30">2da (16-30)</option>
                     </select>
                  </div>
                  <div class="col-6">
                     <label class="form-label small fw-bold text-muted">Monto</label>
                     <div class="input-group">
                        <span class="input-group-text">RD$</span>
                        <input type="number" step="0.01" class="form-control" name="monto" required>
                     </div>
                  </div>
               </div>
               <div class="mb-3">
                  <label class="form-label small fw-bold text-muted">Detalle</label>
                  <input type="text" class="form-control" name="detalles" required placeholder="Ej: Pago de luz área común">
               </div>
               <input type="hidden" name="mes" value="<?php echo $mes_filtro; ?>">
               <input type="hidden" name="anio" value="<?php echo $anio_filtro; ?>">
            </form>
         </div>
         <div class="modal-footer bg-light">
            <button type="submit" form="formNuevoGasto" class="btn btn-primary w-100 fw-bold">Guardar Gasto</button>
         </div>
      </div>
   </div>
</div>

<div class="modal fade" id="modalEditarGasto" tabindex="-1">
   <div class="modal-dialog">
      <div class="modal-content border-0 shadow">
         <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title fw-bold">✏️ Editar Gasto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <form action="" method="post" id="formEditarGasto">
               <input type="hidden" name="editar_gasto" value="1">
               <input type="hidden" name="id_gasto" id="gasto_id">

               <div class="mb-3">
                  <label class="form-label small fw-bold text-muted">Categoría (Solo lectura)</label>
                  <input type="text" class="form-control bg-light" id="gasto_tipo_display" readonly>
               </div>
               <div class="row mb-3">
                  <div class="col-6">
                     <label class="form-label small fw-bold text-muted">Quincena</label>
                     <select name="nueva_quincena" id="gasta_quincena" class="form-select">
                        <option value="15">1ra (1-15)</option>
                        <option value="30">2da (16-30)</option>
                     </select>
                  </div>
                  <div class="col-6">
                     <label class="form-label small fw-bold text-muted">Monto</label>
                     <div class="input-group">
                        <span class="input-group-text">RD$</span>
                        <input type="number" step="0.01" class="form-control" name="nuevo_monto" id="gasto_monto" required>
                     </div>
                  </div>
               </div>
               <div class="mb-3">
                  <label class="form-label small fw-bold text-muted">Detalle</label>
                  <input type="text" class="form-control" name="nuevos_detalles" id="gasto_detalles" required>
               </div>
            </form>
         </div>
         <div class="modal-footer bg-light">
            <button type="submit" form="formEditarGasto" class="btn btn-warning w-100 fw-bold">Actualizar</button>
         </div>
      </div>
   </div>
</div>

<div class="modal fade" id="modalGastosPredeterminados" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content border-0 shadow">
         <div class="modal-header bg-dark text-white">
            <h5 class="modal-title fw-bold">🔧 Configuración de Gastos Fijos</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body bg-light">
            <div class="card mb-4 border-0 shadow-sm">
               <div class="card-body">
                  <h6 class="card-title fw-bold text-primary mb-3">Nuevo Gasto Fijo Mensual</h6>
                  <form action="" method="post" class="row g-2">
                     <input type="hidden" name="es_predeterminado" value="1">
                     <div class="col-md-3">
                        <select name="sel_mas_menos" class="form-select form-select-sm" required>
                           <option value="">Tipo...</option>
                           <?php foreach ($tipos_gastos as $k => $v) echo "<option value='$k'>{$v['label']}</option>"; ?>
                        </select>
                     </div>
                     <div class="col-md-5">
                        <input type="text" class="form-control form-select-sm" name="detalles" placeholder="Detalle" required>
                     </div>
                     <div class="col-md-3">
                        <input type="number" step="0.01" class="form-control form-select-sm" name="monto" placeholder="Monto" required>
                     </div>
                     <div class="col-md-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">➕</button>
                     </div>
                  </form>
               </div>
            </div>

            <div class="card border-0 shadow-sm">
               <div class="card-body p-0">
                  <table class="table table-sm table-striped mb-0">
                     <thead class="bg-secondary text-white">
                        <tr>
                           <th>Estado</th>
                           <th>Tipo</th>
                           <th>Detalle</th>
                           <th>Monto</th>
                           <th></th>
                        </tr>
                     </thead>
                     <tbody>
                        <?php foreach ($pred as $gp): ?>
                           <tr>
                              <td>
                                 <a href="?toggle_predeterminado=<?php echo $gp['id'] . "&mes=$mes_filtro&anio=$anio_filtro&periodo=$periodo_filtro"; ?>"
                                    class="text-decoration-none text-<?php echo $gp['activo'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $gp['activo'] ? '<i class="fas fa-toggle-on fa-lg"></i>' : '<i class="fas fa-toggle-off fa-lg"></i>'; ?>
                                 </a>
                              </td>
                              <td class="small"><?php echo $tipos_gastos[$gp['tipo_gasto']]['label'] ?? $gp['tipo_gasto']; ?></td>
                              <td class="small"><?php echo $gp['detalles']; ?></td>
                              <td class="small fw-bold">RD$ <?php echo number_format($gp['monto'], 2); ?></td>
                              <td class="text-end">
                                 <a href="?eliminar_predeterminado=<?php echo $gp['id'] . "&mes=$mes_filtro&anio=$anio_filtro&periodo=$periodo_filtro"; ?>"
                                    class="text-danger" onclick="return confirm('¿Borrar?')">🗑️</a>
                              </td>
                           </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

<script>
   function borrar(id, mes, anio, periodo) {
      Swal.fire({
         title: '¿Eliminar Gasto?',
         text: "Se descontará del total.",
         icon: 'warning',
         showCancelButton: true,
         confirmButtonColor: '#d33',
         confirmButtonText: 'Sí, eliminar'
      }).then((result) => {
         if (result.isConfirmed) {
            window.location = "index.php?txID=" + id + "&mes=" + mes + "&anio=" + anio + "&periodo=" + periodo;
         }
      });
   }

   function confirmarGeneracion() {
      Swal.fire({
         title: 'Generar Gastos Fijos',
         text: "Se crearán los gastos predeterminados para <?php echo $mes_filtro; ?>.",
         icon: 'info',
         showCancelButton: true,
         confirmButtonText: 'Generar'
      }).then((result) => {
         if (result.isConfirmed) {
            window.location = "?generar_gastos=1&mes=<?php echo $mes_filtro; ?>&anio=<?php echo $anio_filtro; ?>&periodo=15";
         }
      });
   }

   const modalEdit = document.getElementById('modalEditarGasto');
   if (modalEdit) {
      modalEdit.addEventListener('show.bs.modal', event => {
         const btn = event.relatedTarget;
         const labels = <?php echo json_encode(array_map(function ($v) {
                           return $v['label'];
                        }, $tipos_gastos)); ?>;
         document.getElementById('gasto_id').value = btn.getAttribute('data-id');
         document.getElementById('gasto_detalles').value = btn.getAttribute('data-detalles');
         document.getElementById('gasto_monto').value = btn.getAttribute('data-monto');
         document.getElementById('gasta_quincena').value = btn.getAttribute('data-quincena');
         const tipoKey = btn.getAttribute('data-tipo');
         document.getElementById('gasto_tipo_display').value = labels[tipoKey] || tipoKey;
      });
   }
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php include("../../templates/footer.php"); ?>