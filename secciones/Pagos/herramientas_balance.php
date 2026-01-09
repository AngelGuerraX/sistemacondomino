<?php
include("../../templates/header.php");
include("../../bd.php");

if (session_status() === PHP_SESSION_NONE) session_start();
$id_condominio = $_SESSION['idcondominio'];

// Incluir la lógica de cálculo actualizada
include 'actualizar_balance.php';

$mensaje = "";

// =================================================================================
// PROCESO DE ACTUALIZACIÓN MASIVA
// =================================================================================
if (isset($_POST['accion']) && $_POST['accion'] == 'recalcular_todo') {
  try {
    // 1. Obtener TODOS los apartamentos
    $stmt = $conexion->prepare("SELECT id FROM tbl_aptos WHERE id_condominio = :id");
    $stmt->execute([':id' => $id_condominio]);
    $apartamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $contador = 0;

    // 2. Recalcular uno por uno
    foreach ($apartamentos as $apto) {
      actualizarBalanceApto($apto['id'], $id_condominio);
      $contador++;
    }

    $mensaje = "✅ Se han unificado y recalculado los balances de <strong>$contador</strong> apartamentos.";
    $tipo_mensaje = "success";
  } catch (Exception $e) {
    $mensaje = "❌ Error: " . $e->getMessage();
    $tipo_mensaje = "danger";
  }
}

// =================================================================================
// CONSULTA PARA VER LOS RESULTADOS
// =================================================================================
// bal_total ahora contiene la suma de todo (Dueño + Inq)
$sql_vista = "SELECT 
                a.apto, 
                a.condominos, 
                a.balance as bal_total, 
                a.tiene_inquilino,
                i.nombre as nombre_inq,
                COALESCE(i.balance, 0) as bal_inq
              FROM tbl_aptos a
              LEFT JOIN tbl_inquilinos i ON a.id = i.id_apto AND i.activo = 1
              WHERE a.id_condominio = :id
              ORDER BY a.apto ASC";
$stmt_vista = $conexion->prepare($sql_vista);
$stmt_vista->execute([':id' => $id_condominio]);
$lista_actualizada = $stmt_vista->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>🛠️ Herramienta de Saldos</h2>
    <a href="index.php" class="btn btn-secondary">⬅ Volver al Inicio</a>
  </div>

  <div class="card shadow mb-4 border-primary">
    <div class="card-body text-center bg-light">
      <h5 class="card-title text-primary">Unificar Balances (Dueño + Inquilino)</h5>
      <p class="card-text text-muted">
        Esta acción sumará las deudas del inquilino al balance general del apartamento.
        <br>Se mostrará el total adeudado por la unidad.
      </p>

      <form method="POST">
        <input type="hidden" name="accion" value="recalcular_todo">
        <button type="submit" class="btn btn-primary btn-lg px-5">
          🔄 ACTUALIZAR SALDOS TOTALES
        </button>
      </form>

      <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> mt-3 mb-0">
          <?php echo $mensaje; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card shadow">
    <div class="card-header bg-dark text-white">
      Estado de Cuentas por Apartamento
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
          <thead class="table-secondary text-center">
            <tr>
              <th>Apto</th>
              <th>Ocupantes</th>
              <th>Deuda Propietario</th>
              <th>Deuda Inquilino</th>
              <th class="bg-light border-start">DEUDA TOTAL APTO</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($lista_actualizada as $fila):
              // Cálculo para desglosar visualmente
              $deuda_total = floatval($fila['bal_total']);
              $deuda_inq = floatval($fila['bal_inq']);
              $deuda_prop = $deuda_total - $deuda_inq; // Restamos para saber lo del dueño
            ?>
              <tr>
                <td class="fw-bold text-center"><?php echo $fila['apto']; ?></td>

                <td>
                  <div>🏠 <?php echo $fila['condominos']; ?></div>
                  <?php if ($fila['tiene_inquilino']): ?>
                    <div class="text-primary small">👤 <?php echo $fila['nombre_inq']; ?></div>
                  <?php endif; ?>
                </td>

                <td class="text-end text-muted">
                  RD$ <?php echo number_format($deuda_prop, 2); ?>
                </td>

                <td class="text-end text-muted">
                  <?php if ($fila['tiene_inquilino'] && $deuda_inq > 0): ?>
                    <span class="text-warning text-dark fw-bold">RD$ <?php echo number_format($deuda_inq, 2); ?></span>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>

                <td class="text-end bg-light border-start">
                  <span class="fs-5 <?php echo $deuda_total > 0.01 ? 'text-danger fw-bold' : 'text-success fw-bold'; ?>">
                    RD$ <?php echo number_format($deuda_total, 2); ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php include("../../templates/footer.php"); ?>