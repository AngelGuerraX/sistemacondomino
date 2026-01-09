<?php
include("../../templates/header.php");
include("../../bd.php");

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) session_start();

$id_condominio = $_SESSION['idcondominio'];
$id_usuario_registro = $_SESSION['usuario'];

// Variables de fecha
$mes_actual_num = date("n");
$anio_actual = date("Y");
$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mes_actual_espanol = $meses[$mes_actual_num - 1];

// =================================================================================
// 1. PROCESAR ELIMINACIÓN DE CUOTA
// =================================================================================
if (isset($_GET['eliminar'])) {
   $id_eliminar = $_GET['eliminar'];

   // Verificar y eliminar
   $stmt = $conexion->prepare("DELETE FROM tbl_cuotas_extras WHERE id = :id AND id_condominio = :id_cond");
   $stmt->execute([':id' => $id_eliminar, ':id_cond' => $id_condominio]);

   if ($stmt->rowCount() > 0) {
      // IMPORTANTE: Aquí deberíamos llamar a actualizarBalanceApto() si tuviéramos el ID del apto
      // Como eliminamos directo, asumimos que el balance se recalculará al entrar a pagos.
      $mensaje = "✅ Cuota eliminada correctamente.";
      $tipo_mensaje = "success";
   } else {
      $mensaje = "❌ Error al eliminar la cuota.";
      $tipo_mensaje = "danger";
   }
}

// =================================================================================
// 2. PROCESAR CREACIÓN DE CUOTA
// =================================================================================
if ($_POST) {
   $descripcion = trim($_POST['descripcion']);
   $monto = floatval($_POST['monto']);
   $mes = $_POST['mes'];
   $anio = $_POST['anio'];
   $tipo_aplicacion = $_POST['tipo_aplicacion'];
   $id_apto_seleccionado = $_POST['id_apto'] ?? null;
   $fecha_registro = date('Y-m-d H:i:s');

   try {
      $conexion->beginTransaction();

      $sql_insert = "INSERT INTO tbl_cuotas_extras 
                       (id_condominio, id_usuario_registro, descripcion, monto, mes, anio, fecha_registro, id_apto, estado)
                       VALUES (:id_cond, :user, :desc, :monto, :mes, :anio, :fecha, :apto, 'Pendiente')";
      $stmt = $conexion->prepare($sql_insert);

      if ($tipo_aplicacion == 'todos') {
         // A. Aplicar a TODOS
         $stmt_aptos = $conexion->prepare("SELECT id FROM tbl_aptos WHERE id_condominio = :id");
         $stmt_aptos->execute([':id' => $id_condominio]);
         $todos = $stmt_aptos->fetchAll(PDO::FETCH_ASSOC);

         foreach ($todos as $apto) {
            $stmt->execute([
               ':id_cond' => $id_condominio,
               ':user' => $id_usuario_registro,
               ':desc' => $descripcion,
               ':monto' => $monto,
               ':mes' => $mes,
               ':anio' => $anio,
               ':fecha' => $fecha_registro,
               ':apto' => $apto['id']
            ]);
         }
         $mensaje = "✅ Cuota aplicada a " . count($todos) . " apartamentos.";
      } elseif ($tipo_aplicacion == 'individual' && $id_apto_seleccionado) {
         // B. Aplicar a INDIVIDUAL
         $stmt->execute([
            ':id_cond' => $id_condominio,
            ':user' => $id_usuario_registro,
            ':desc' => $descripcion,
            ':monto' => $monto,
            ':mes' => $mes,
            ':anio' => $anio,
            ':fecha' => $fecha_registro,
            ':apto' => $id_apto_seleccionado
         ]);
         $mensaje = "✅ Cuota aplicada al apartamento seleccionado.";
      }

      $conexion->commit();
      $tipo_mensaje = "success";
   } catch (Exception $e) {
      $conexion->rollBack();
      $mensaje = "❌ Error: " . $e->getMessage();
      $tipo_mensaje = "danger";
   }
}

// =================================================================================
// 3. CONSULTAS PARA LA VISTA
// =================================================================================

// Lista de Apartamentos para el Select
$stmt_aptos = $conexion->prepare("SELECT id, apto, condominos FROM tbl_aptos WHERE id_condominio = :id ORDER BY apto");
$stmt_aptos->execute([':id' => $id_condominio]);
$apartamentos = $stmt_aptos->fetchAll(PDO::FETCH_ASSOC);

// Filtros para la tabla
$mes_filtro = $_GET['mes_buscar'] ?? $mes_actual_espanol;
$anio_filtro = $_GET['anio_buscar'] ?? $anio_actual;

// Historial de Cuotas
$stmt_hist = $conexion->prepare("
    SELECT ce.*, a.apto, a.condominos 
    FROM tbl_cuotas_extras ce
    LEFT JOIN tbl_aptos a ON ce.id_apto = a.id 
    WHERE ce.mes=:mes AND ce.anio=:anio AND ce.id_condominio=:id 
    ORDER BY ce.fecha_registro DESC, a.apto ASC
");
$stmt_hist->execute([':mes' => $mes_filtro, ':anio' => $anio_filtro, ':id' => $id_condominio]);
$lista_cuotas = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

$total_mes = array_sum(array_column($lista_cuotas, 'monto'));
?>

<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Cuotas Extras</title>
   <style>
      .table-responsive {
         max-height: 60vh;
      }

      .bg-gradient-warning {
         background: linear-gradient(45deg, #ffc107, #ffca2c);
      }
   </style>
</head>

<body>

   <div class="container mt-4">

      <?php if (isset($mensaje)): ?>
         <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show shadow-sm" role="alert">
            <strong><?= $mensaje ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
         </div>
      <?php endif; ?>

      <div class="card shadow mb-5">
         <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📜 Historial de Cuotas</h5>

            <form method="get" class="d-flex gap-2">
               <select name="mes_buscar" class="form-select form-select-sm" style="width: auto;">
                  <?php foreach ($meses as $m) echo "<option value='$m' " . ($m == $mes_filtro ? 'selected' : '') . ">$m</option>"; ?>
               </select>
               <select name="anio_buscar" class="form-select form-select-sm" style="width: auto;">
                  <?php for ($i = 2024; $i <= 2030; $i++) echo "<option value='$i' " . ($i == $anio_filtro ? 'selected' : '') . ">$i</option>"; ?>
               </select>
               <button type="submit" class="btn btn-light btn-sm">🔍</button>
            </form>
         </div>

         <div class="card-body p-0">
            <div class="table-responsive">
               <table class="table table-striped mb-0 align-middle text-center">
                  <thead class="table-light">
                     <tr>
                        <th>Descripción</th>
                        <th>Monto</th>
                        <th>Apto</th>
                        <th>Condómino</th>
                        <th>Estado</th>
                        <th>Fecha Registro</th>
                        <th></th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($lista_cuotas as $cuota): ?>
                        <tr>
                           <td class="text-start"><?= $cuota['descripcion'] ?></td>
                           <td class="fw-bold">RD$ <?= number_format($cuota['monto'], 2) ?></td>
                           <td class="fw-bold"><?= $cuota['apto'] ?? 'N/A' ?></td>
                           <td class="small"><?= $cuota['condominos'] ?? 'N/A' ?></td>
                           <td>
                              <?php
                              $bg = empty($cuota['estado']) || $cuota['estado'] == 'Pendiente' ? 'bg-secondary' : ($cuota['estado'] == 'Pagado' ? 'bg-success' : 'bg-warning text-dark');
                              ?>
                              <span class="badge <?= $bg ?>"><?= $cuota['estado'] ?: 'Pendiente' ?></span>
                           </td>
                           <td class="small text-muted"><?= date('d/m/y H:i', strtotime($cuota['fecha_registro'])) ?></td>
                           <td>
                              <a href="?eliminar=<?= $cuota['id'] ?>&mes_buscar=<?= $mes_filtro ?>&anio_buscar=<?= $anio_filtro ?>"
                                 class="btn btn-sm btn-outline-danger border-0"
                                 onclick="return confirm('¿Eliminar esta cuota?')">🗑️</a>
                           </td>
                        </tr>
                     <?php endforeach; ?>
                     <?php if (empty($lista_cuotas)) echo "<tr><td colspan='7' class='text-center py-4 text-muted'>No hay cuotas registradas en este periodo</td></tr>"; ?>
                  </tbody>
                  <tfoot class="table-dark">
                     <tr>
                        <td colspan="2" class="text-end">Total Mes:</td>
                        <td colspan="5" class="text-start">RD$ <?= number_format($total_mes, 2) ?></td>
                     </tr>
                  </tfoot>
               </table>
            </div>
         </div>
      </div>

      <div class="card shadow mb-4 border-warning">
         <div class="card-header bg-dark text-white text-center">
            <h4 class="mb-0">⚠️ Nueva Cuota Extra</h4>
         </div>
         <div class="card-body bg-light">

            <form method="post">
               <div class="row mb-3">
                  <div class="col-md-8 mb-2">
                     <label class="form-label fw-bold">Descripción / Motivo</label>
                     <input type="text" name="descripcion" class="form-control" placeholder="Ej: Reparación Bomba de Agua" required>
                  </div>
                  <div class="col-md-4 mb-2">
                     <label class="form-label fw-bold">Monto (RD$)</label>
                     <input type="number" step="0.01" name="monto" class="form-control" placeholder="0.00" required>
                  </div>
               </div>

               <div class="row mb-4">
                  <div class="col-md-3 mb-2">
                     <label class="form-label small fw-bold">Mes Aplicación</label>
                     <select name="mes" class="form-select form-select-sm">
                        <?php foreach ($meses as $m) echo "<option value='$m' " . ($m == $mes_actual_espanol ? 'selected' : '') . ">$m</option>"; ?>
                     </select>
                  </div>
                  <div class="col-md-3 mb-2">
                     <label class="form-label small fw-bold">Año</label>
                     <select name="anio" class="form-select form-select-sm">
                        <?php for ($i = 2024; $i <= 2030; $i++) echo "<option value='$i' " . ($i == $anio_actual ? 'selected' : '') . ">$i</option>"; ?>
                     </select>
                  </div>

                  <div class="col-md-6">
                     <label class="form-label fw-bold">¿A quién aplica?</label>
                     <div class="card p-2">
                        <div class="d-flex gap-3 align-items-center">
                           <div class="form-check">
                              <input class="form-check-input" type="radio" name="tipo_aplicacion" id="todos" value="todos" checked>
                              <label class="form-check-label fw-bold" for="todos">Todos los Aptos</label>
                           </div>
                           <div class="form-check">
                              <input class="form-check-input" type="radio" name="tipo_aplicacion" id="individual" value="individual">
                              <label class="form-check-label" for="individual">Individual</label>
                           </div>
                        </div>

                        <div id="div-select-apto" class="mt-2" style="display: none;">
                           <select name="id_apto" class="form-select form-select-sm">
                              <option value="">Seleccione apartamento...</option>
                              <?php foreach ($apartamentos as $a) echo "<option value='{$a['id']}'>{$a['apto']} - {$a['condominos']}</option>"; ?>
                           </select>
                        </div>
                     </div>
                  </div>
               </div>

               <div class="text-center">
                  <button type="submit" class="btn btn-warning w-50 fw-bold shadow-sm">
                     💾 Guardar Cuota Extra
                  </button>
               </div>
            </form>

         </div>
      </div>

   </div>

   <script>
      // Lógica para mostrar/ocultar el select de apartamentos
      const radioTodos = document.getElementById('todos');
      const radioIndiv = document.getElementById('individual');
      const divSelect = document.getElementById('div-select-apto');

      function toggleSelect() {
         divSelect.style.display = radioIndiv.checked ? 'block' : 'none';
         // Si se selecciona "Todos", limpiamos el select para evitar errores
         if (radioTodos.checked) document.querySelector('select[name="id_apto"]').value = "";
      }

      radioTodos.addEventListener('change', toggleSelect);
      radioIndiv.addEventListener('change', toggleSelect);
   </script>

   <?php include("../../templates/footer.php"); ?>