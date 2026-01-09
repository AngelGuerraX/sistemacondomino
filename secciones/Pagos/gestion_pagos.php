<?php
$thetitle = "Gestión de Pagos";
include("../../templates/header.php");
include("../../bd.php");

// 1. INICIALIZACIÓN Y DEPENDENCIAS
if (session_status() === PHP_SESSION_NONE) session_start();
$id_condominio = $_SESSION['idcondominio'];
$txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";
$usuario_registro = $_SESSION['usuario'];

// Recalcular balance al entrar
include 'actualizar_balance.php';
actualizarBalanceApto($txtID, $id_condominio);

// OBTENER DATOS DEL APARTAMENTO (Aquí ya viene fecha_ultimo_pago)
$stmt = $conexion->prepare("SELECT * FROM tbl_aptos WHERE id=:id");
$stmt->execute([':id' => $txtID]);
$apto = $stmt->fetch(PDO::FETCH_ASSOC);
$tiene_inquilino = ($apto['tiene_inquilino'] == 1);

// =========================================================
// OBTENER BALANCES
// =========================================================

$balance_total_apto = floatval($apto['balance']);

$inquilino = null;
$balance_inquilino = 0;
$id_inquilino_db = 0;

if ($tiene_inquilino) {
  $stmt_i = $conexion->prepare("SELECT * FROM tbl_inquilinos WHERE id_apto=:id AND activo=1");
  $stmt_i->execute([':id' => $txtID]);
  $inquilino = $stmt_i->fetch(PDO::FETCH_ASSOC);
  if ($inquilino) {
    $balance_inquilino = floatval($inquilino['balance']);
    $id_inquilino_db = $inquilino['id'];
  }
}

// Balance Propietario
$balance_propietario = round($balance_total_apto - $balance_inquilino, 2);

// --- OBTENER SALDO A FAVOR REAL ---
// 1. Saldo Real Propietario
$s_real_prop = $conexion->prepare("
    SELECT COALESCE(SUM(d.monto), 0) 
    FROM tbl_pagos_detalle d
    JOIN tbl_pagos p ON d.id_pago = p.id_pago
    WHERE p.id_apto = :id 
    AND d.tipo_deuda = 'adelanto' 
    AND d.tipo_pagador = 'propietario'
");
$s_real_prop->execute([':id' => $txtID]);
$saldo_favor_prop = floatval($s_real_prop->fetchColumn());

// 2. Saldo Real Inquilino
$saldo_favor_inq = 0;
if ($tiene_inquilino && $id_inquilino_db > 0) {
  $s_real_inq = $conexion->prepare("
        SELECT COALESCE(SUM(d.monto), 0) 
        FROM tbl_pagos_detalle d
        JOIN tbl_pagos_inquilinos p ON d.id_pago = p.id
        WHERE p.id_inquilino = :id 
        AND d.tipo_deuda = 'adelanto' 
        AND d.tipo_pagador = 'inquilino'
    ");
  $s_real_inq->execute([':id' => $id_inquilino_db]);
  $saldo_favor_inq = floatval($s_real_inq->fetchColumn());
}


// =================================================================================
// 2. PROCESAR EL PAGO (BACKEND)
// =================================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'procesar_pago') {
  try {
    $conexion->beginTransaction();

    $tipo_pagador = $_POST['tipo_pagador_form'];

    // Recibimos montos
    $monto_recibido_cash = floatval($_POST['monto_recibido']);
    $saldo_favor_usado   = floatval($_POST['saldo_usado']);

    // CAPTURAMOS EL INPUT QUE VIENE DE LA BD (fecha_ultimo_pago)
    $fecha_ultimo_pago_input = $_POST['fecha_ultimo_pago'] ?? '';

    // Validación
    $total_transaccion = $monto_recibido_cash + $saldo_favor_usado;
    if ($total_transaccion <= 0) throw new Exception("Debe ingresar un monto o seleccionar saldo a favor.");

    $fecha_pago = $_POST['fecha_pago'];
    $mes_ingreso = $_POST['mes_ingreso'];
    $anio_ingreso = $_POST['anio_ingreso'];
    $forma_pago = $_POST['forma_pago'];

    $items = json_decode($_POST['items_seleccionados'], true);
    $id_ref = ($tipo_pagador == 'propietario') ? $txtID : $inquilino['id'];

    // 1. REGISTRAR CABECERA DEL PAGO
    if ($tipo_pagador == 'propietario') {
      $sql = "INSERT INTO tbl_pagos (id_apto, id_condominio, monto, concepto, forma_pago, fecha_pago, mes_ingreso, anio_ingreso, FechaDeIngreso, usuario_registro) VALUES (:ref, :cond, :monto, '...', :forma, :fecha, :mes, :anio, NOW(), :user)";
    } else {
      $sql = "INSERT INTO tbl_pagos_inquilinos (id_inquilino, id_condominio, monto, concepto, forma_pago, fecha_pago, mes_gas, anio_gas, fecha_registro, usuario_registro) VALUES (:ref, :cond, :monto, '...', :forma, :fecha, :mes, :anio, NOW(), :user)";
    }

    $stmt = $conexion->prepare($sql);
    $stmt->execute([':ref' => $id_ref, ':cond' => $id_condominio, ':monto' => $monto_recibido_cash, ':forma' => $forma_pago, ':fecha' => $fecha_pago, ':mes' => $mes_ingreso, ':anio' => $anio_ingreso, ':user' => $usuario_registro]);
    $id_pago = $conexion->lastInsertId();

    $descripciones = [];
    $total_aplicado_a_deudas = 0;

    // 2. APLICAR PAGOS A LAS DEUDAS
    if (!empty($items)) {
      foreach ($items as $item) {
        $monto_a_pagar = floatval($item['monto_aplicar']);

        if ($monto_a_pagar > 0) {
          $total_aplicado_a_deudas += $monto_a_pagar;

          // Guardar detalle
          $stmt_d = $conexion->prepare("INSERT INTO tbl_pagos_detalle (id_pago, tipo_pagador, tipo_deuda, id_deuda, monto) VALUES (:idp, :tipo, :td, :idd, :monto)");
          $stmt_d->execute([':idp' => $id_pago, ':tipo' => $tipo_pagador, ':td' => $item['tipo'], ':idd' => $item['id'], ':monto' => $monto_a_pagar]);

          // Definir tabla y columna
          if ($item['tipo'] == 'ticket') {
            $tabla = 'tbl_tickets';
            $col_total_sql = "(CAST(mantenimiento AS DECIMAL(10,2)) + CAST(mora AS DECIMAL(10,2)))";
          } elseif ($item['tipo'] == 'gas') {
            $tabla = 'tbl_gas';
            $col_total_sql = "(CAST(total_gas AS DECIMAL(10,2)) + CAST(mora AS DECIMAL(10,2)))";
          } else {
            $tabla = 'tbl_cuotas_extras';
            $col_total_sql = "(CAST(monto AS DECIMAL(10,2)) + CAST(mora AS DECIMAL(10,2)))";
          }

          // UPDATE MAESTRO
          $sql_upd = "UPDATE $tabla SET 
                      estado = CASE 
                                  WHEN (abono + :pago) >= ($col_total_sql - 0.99) THEN 'Pagado' 
                                  ELSE 'Abonado' 
                               END,
                      abono = abono + :pago
                      WHERE id = :id";

          $conexion->prepare($sql_upd)->execute([':pago' => $monto_a_pagar, ':id' => $item['id']]);

          $info_extra = ($item['es_abono']) ? "(Abono)" : "";
          $descripciones[] = ucfirst($item['tipo']) . " #" . $item['id'] . " $info_extra";
        }
      }
    }

    // 3. DESCONTAR SALDO A FAVOR USADO
    if ($saldo_favor_usado > 0.01) {
      $monto_negativo = $saldo_favor_usado * -1;
      $conexion->prepare("INSERT INTO tbl_pagos_detalle (id_pago, tipo_pagador, tipo_deuda, id_deuda, monto) VALUES (:idp, :tipo, 'adelanto', 0, :monto)")
        ->execute([':idp' => $id_pago, ':tipo' => $tipo_pagador, ':monto' => $monto_negativo]);

      $descripciones[] = "Crédito Usado: RD$ " . number_format($saldo_favor_usado, 2);
    }

    // 4. GENERAR NUEVO ADELANTO (SI SOBRA EFECTIVO)
    $remanente = round($total_transaccion - $total_aplicado_a_deudas, 2);

    if ($remanente > 0.01) {
      $conexion->prepare("INSERT INTO tbl_pagos_detalle (id_pago, tipo_pagador, tipo_deuda, id_deuda, monto) VALUES (:idp, :tipo, 'adelanto', 0, :monto)")
        ->execute([':idp' => $id_pago, ':tipo' => $tipo_pagador, ':monto' => $remanente]);
      $descripciones[] = "Nuevo Saldo a Favor: RD$ " . number_format($remanente, 2);
    }

    // Finalizar registro con el concepto
    $concepto_final = "Pago: " . implode(", ", $descripciones);
    // Agregamos la referencia al concepto si se desea (opcional)
    if (!empty($fecha_ultimo_pago_input)) {
      $concepto_final .= " | Ref: " . $fecha_ultimo_pago_input;
    }

    $tbl_m = ($tipo_pagador == 'propietario') ? 'tbl_pagos' : 'tbl_pagos_inquilinos';
    $pk = ($tipo_pagador == 'propietario') ? 'id_pago' : 'id';
    $conexion->prepare("UPDATE $tbl_m SET concepto = ? WHERE $pk = ?")->execute([$concepto_final, $id_pago]);

    // ==========================================================
    // 5. ACTUALIZAR LA COLUMNA fecha_ultimo_pago EN TBL_APTOS
    // ==========================================================
    // Guardamos lo que el usuario dejó o editó en el input
    $stmt_fecha = $conexion->prepare("UPDATE tbl_aptos SET fecha_ultimo_pago = :texto WHERE id = :id");
    $stmt_fecha->execute([':texto' => $fecha_ultimo_pago_input, ':id' => $txtID]);

    // RECALCULAR FINAL Y CONFIRMAR
    actualizarBalanceApto($txtID, $id_condominio);
    $conexion->commit();

    header("Location: historial_pagos.php?id_apto=" . $txtID);
    exit;
  } catch (Exception $e) {
    $conexion->rollBack();
    $_SESSION['mensaje'] = "Error: " . $e->getMessage();
    $_SESSION['tipo_mensaje'] = "danger";
    header("Location: gestion_pagos.php?txID=" . $txtID);
    exit;
  }
}

// =================================================================================
// 3. CONSULTAR DEUDAS PENDIENTES
// =================================================================================
$pendientes_prop = [];
$pendientes_inq = [];

$sql_t = "SELECT id, mes, anio, (CAST(mantenimiento AS DECIMAL(10,2)) + CAST(mora AS DECIMAL(10,2))) as original, abono, ((CAST(mantenimiento AS DECIMAL(10,2)) + CAST(mora AS DECIMAL(10,2))) - abono) as saldo, fecha_actual as fecha FROM tbl_tickets WHERE id_apto=:id AND estado != 'Pagado'";
$s = $conexion->prepare($sql_t);
$s->execute([':id' => $txtID]);
while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
  if ($r['saldo'] > 0.01) {
    $r['tipo'] = 'ticket';
    $r['desc'] = "Mant. " . $r['mes'] . " " . $r['anio'];
    $pendientes_prop[] = $r;
  }
}

$sql_c = "SELECT id, descripcion as descr, (monto + mora) as original, abono, ((monto + mora) - abono) as saldo, fecha_registro as fecha FROM tbl_cuotas_extras WHERE id_apto=:id AND estado != 'Pagado'";
$s = $conexion->prepare($sql_c);
$s->execute([':id' => $txtID]);
while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
  if ($r['saldo'] > 0.01) {
    $r['tipo'] = 'cuota';
    $r['desc'] = "Cuota: " . $r['descr'];
    $pendientes_prop[] = $r;
  }
}

$sql_g = "SELECT id, mes, anio, (total_gas + mora) as original, abono, ((total_gas + mora) - abono) as saldo, fecha_registro as fecha FROM tbl_gas WHERE id_apto=:id AND estado != 'Pagado'";
$s = $conexion->prepare($sql_g);
$s->execute([':id' => $txtID]);
while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
  if ($r['saldo'] > 0.01) {
    $r['tipo'] = 'gas';
    $r['desc'] = "Gas " . $r['mes'] . " " . $r['anio'];
    if ($tiene_inquilino) $pendientes_inq[] = $r;
    else $pendientes_prop[] = $r;
  }
}

usort($pendientes_prop, function ($a, $b) {
  return strtotime($a['fecha']) - strtotime($b['fecha']);
});
usort($pendientes_inq, function ($a, $b) {
  return strtotime($a['fecha']) - strtotime($b['fecha']);
});

$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <title>Caja de Cobro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f4f6f9;
    }

    .card-deuda {
      border-left: 5px solid #dc3545;
      background-color: white;
      transition: all 0.2s ease;
      cursor: pointer;
      margin-bottom: 8px;
    }

    .card-deuda:hover {
      transform: translateX(5px);
      background-color: #f8f9fa;
    }

    .seleccionado-total {
      border-left-color: #198754 !important;
      background-color: #d1e7dd !important;
    }

    .seleccionado-parcial {
      border-left-color: #ffc107 !important;
      background-color: #fff3cd !important;
    }

    .bg-prop {
      background: linear-gradient(45deg, #0d6efd, #0043a8);
      color: white;
    }

    .bg-inq {
      background: linear-gradient(45deg, #fd7e14, #c45700);
      color: white;
    }

    .nav-pills .nav-link.active {
      font-weight: bold;
      background-color: #343a40;
    }

    .sticky-col {
      position: sticky;
      top: 20px;
      z-index: 100;
    }

    .saldo-favor-box {
      background-color: #d1e7dd;
      border: 1px solid #0f5132;
      color: #0f5132;
    }
  </style>
</head>

<body>

  <div class="container-fluid p-4">

    <div class="d-flex justify-content-between bg-dark p-4 b-3 text-white align-items-center mb-3 border border-3 border-dark rounded">
      <div>
        <h2 class="mb-0">🪙 Caja de Cobro</h2>
        <h5 class="text-secondary">APARTAMENTO: <strong><?php echo $apto['apto']; ?></strong></h5>
      </div>
      <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-danger">X</a>
        <a class="btn btn-light text-dark" href="historial_pagos.php?id_apto=<?php echo $apto['id'] ?>">Historial</a>
        <button type="button" class="btn btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#modalPDF">📄 Cuentas por cobrar</button>
      </div>

      <div class="modal fade" id="modalPDF" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header bg-warning">
              <h5 class="modal-title text-dark">Generar Estado de Cuenta</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="estado_cuenta.php" method="POST" target="_blank">
              <div class="modal-body">
                <input type="hidden" name="id_apto" value="<?php echo $apto['id']; ?>">
                <div class="mb-3">
                  <label class="form-label fw-bold text-dark">Nota al pie:</label>
                  <textarea class="form-control" name="nota_reporte" rows="5"></textarea>
                </div>
              </div>
              <div class="modal-footer"><button type="submit" class="btn btn-dark">🖨️ Generar PDF</button></div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <ul class="nav nav-pills mb-4" id="pills-tab">
      <li class="nav-item">
        <button class="nav-link active px-4" id="tab-prop" data-bs-toggle="pill" data-bs-target="#content-prop" type="button" onclick="setPagador('propietario')">🏠 PROPIETARIO</button>
      </li>
      <?php if ($tiene_inquilino): ?>
        <li class="nav-item">
          <button class="nav-link px-4" id="tab-inq" data-bs-toggle="pill" data-bs-target="#content-inq" type="button" onclick="setPagador('inquilino')">👤 INQUILINO</button>
        </li>
      <?php endif; ?>
    </ul>

    <form id="formPago" method="POST">
      <input type="hidden" name="accion" value="procesar_pago">
      <input type="hidden" name="tipo_pagador_form" id="tipo_pagador_form" value="propietario">
      <input type="hidden" name="items_seleccionados" id="input_items_seleccionados">
      <input type="hidden" id="saldo_favor_prop" value="<?php echo $saldo_favor_prop; ?>">
      <input type="hidden" id="saldo_favor_inq" value="<?php echo $saldo_favor_inq; ?>">
      <input type="hidden" name="saldo_usado" id="input_saldo_usado" value="0">

      <div class="row">
        <div class="col-md-4">
          <div class="card shadow border-0 sticky-col">
            <div class="card-body">

              <div id="bal_prop_div" class="p-3 rounded text-center bg-prop mb-3">
                <small>DEUDA TOTAL PROPIETARIO</small>
                <h3>RD$ <?php echo number_format($balance_propietario, 2); ?></h3>
              </div>
              <?php if ($tiene_inquilino): ?>
                <div id="bal_inq_div" class="p-3 rounded text-center bg-inq mb-3" style="display:none;">
                  <small>DEUDA TOTAL INQUILINO</small>
                  <h3>RD$ <?php echo number_format($balance_inquilino, 2); ?></h3>
                </div>
              <?php endif; ?>

              <div class="mb-2"><label class="fw-bold small">Fecha Pago</label><input type="date" name="fecha_pago" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>

              <div class="row mb-2">
                <div class="col-6">
                  <label class="fw-bold small">Mes</label>
                  <select name="mes_ingreso" class="form-select form-select-sm">
                    <?php foreach ($meses as $i => $m) echo "<option " . (($i + 1) == date('n') ? 'selected' : '') . ">$m</option>"; ?>
                  </select>
                </div>
                <div class="col-6">
                  <label class="fw-bold small">Año</label>
                  <select name="anio_ingreso" class="form-select form-select-sm">
                    <?php
                    $anio_actual = date('Y');
                    for ($i = $anio_actual - 5; $i <= $anio_actual + 5; $i++) {
                      $selected = ($i == $anio_actual) ? 'selected' : '';
                      echo "<option value='$i' $selected>$i</option>";
                    }
                    ?>
                  </select>
                </div>
              </div>

              <div class="mb-3">
                <label class="fw-bold small text-primary"><i class="fas fa-history"></i> Último mes pagado (Ref):</label>
                <input type="text" name="fecha_ultimo_pago" class="form-control form-control-sm border-primary"
                  value="<?php echo $apto['fecha_ultimo_pago']; ?>" placeholder="Ej: Marzo 2024">
              </div>

              <div class="card bg-light border mb-3">
                <div class="card-body p-2">
                  <label class="fw-bold text-muted small">💵 Efectivo / Transferencia</label>
                  <div class="input-group mb-2">
                    <span class="input-group-text">RD$</span>
                    <input type="number" step="0.01" name="monto_recibido" id="monto_recibido" class="form-control fw-bold text-success" placeholder="0.00" oninput="calcularDistribucion()">
                  </div>

                  <div id="div_saldo_favor" style="display:none;">
                    <div class="form-check form-switch p-2 rounded saldo-favor-box">
                      <input class="form-check-input" type="checkbox" id="chk_usar_saldo" onchange="calcularDistribucion()">
                      <label class="form-check-label fw-bold small" for="chk_usar_saldo">
                        Usar Saldo a Favor <br>
                        (Disp: RD$ <span id="txt_disp_saldo">0.00</span>)
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mb-3"><select name="forma_pago" class="form-select">
                  <option>Efectivo</option>
                  <option>Transferencia</option>
                  <option>Cheque</option>
                </select></div>

              <div class="alert alert-light border small">
                <div class="d-flex justify-content-between"><span>Total a Aplicar:</span><strong class="text-primary" id="txt_total_aplicar">RD$ 0.00</strong></div>
                <hr class="m-1">
                <div class="d-flex justify-content-between"><span>Asignado a Deudas:</span><strong class="text-dark" id="txt_asignado">RD$ 0.00</strong></div>
                <div class="d-flex justify-content-between text-success mt-1" id="row_adelanto" style="display:none;"><span>Queda a Favor:</span><strong id="txt_adelanto"></strong></div>
                <div class="d-flex justify-content-between text-danger mt-1" id="row_falta" style="display:none;"><span>Falta para cubrir:</span><strong id="txt_falta"></strong></div>
              </div>

              <button type="submit" id="btn_procesar" class="btn btn-success w-100 py-2 fw-bold" disabled>✅ CONFIRMAR PAGO</button>
            </div>
          </div>
        </div>

        <div class="col-md-8">
          <div class="tab-content">
            <div class="tab-pane fade show active" id="content-prop">
              <div class="card shadow border-0">
                <div class="card-header bg-dark text-white">Deudas Propietario</div>
                <div class="card-body p-2 bg-light">
                  <?php if (empty($pendientes_prop)) echo "<div class='p-4 text-center text-muted'>Al día</div>"; ?>
                  <div class="list-group"><?php foreach ($pendientes_prop as $p) echo renderCardDeuda($p, 'prop'); ?></div>
                </div>
              </div>
            </div>
            <div class="tab-pane fade" id="content-inq">
              <div class="card shadow border-0">
                <div class="card-header bg-warning text-dark">Deudas Inquilino</div>
                <div class="card-body p-2 bg-light">
                  <?php if (empty($pendientes_inq)) echo "<div class='p-4 text-center text-muted'>Sin deuda</div>"; ?>
                  <div class="list-group"><?php foreach ($pendientes_inq as $p) echo renderCardDeuda($p, 'inq'); ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>

  <?php function renderCardDeuda($p, $tipo_lista)
  {
    $info_abono = ($p['abono'] > 0) ? "<span class='badge bg-info text-dark ms-2'>Abonado: " . number_format($p['abono'], 0) . "</span>" : "";
    return "<label class='list-group-item card-deuda p-3' id='card_{$tipo_lista}_{$p['tipo']}_{$p['id']}'>
        <div class='d-flex align-items-center'>
            <div class='me-3'><input class='form-check-input chk-deuda' type='checkbox' style='transform: scale(1.3);' data-tipo='{$p['tipo']}' data-id='{$p['id']}' data-saldo='{$p['saldo']}' data-listatipo='{$tipo_lista}' onchange='calcularDistribucion()'></div>
            <div class='flex-grow-1'>
                <div class='d-flex justify-content-between align-items-center'><h6 class='mb-0 fw-bold'>{$p['desc']} $info_abono</h6><span class='fs-5 fw-bold text-danger'>RD$ " . number_format($p['saldo'], 2) . "</span></div>
                <div class='d-flex justify-content-between small text-muted mt-1'><span>Vence: " . date('d/m/Y', strtotime($p['fecha'])) . "</span><span class='badge border text-secondary'>" . strtoupper($p['tipo']) . "</span></div>
                <div class='info-pago mt-2 d-none'><div class='progress' style='height: 6px;'><div class='progress-bar bg-warning' style='width: 0%'></div></div><div class='text-end mt-1'><span class='badge bg-warning text-dark txt-abono-monto'></span></div></div>
            </div>
        </div></label>";
  } ?>

  <script>
    let pagadorActual = 'propietario';

    function setPagador(tipo) {
      pagadorActual = tipo;
      document.getElementById('tipo_pagador_form').value = tipo;

      const saldoProp = parseFloat(document.getElementById('saldo_favor_prop').value);
      const saldoInq = parseFloat(document.getElementById('saldo_favor_inq').value);
      const saldoDisp = (tipo === 'propietario') ? saldoProp : saldoInq;

      const divSaldo = document.getElementById('div_saldo_favor');
      const txtSaldo = document.getElementById('txt_disp_saldo');
      const chkSaldo = document.getElementById('chk_usar_saldo');

      if (saldoDisp > 0) {
        divSaldo.style.display = 'block';
        txtSaldo.innerText = saldoDisp.toLocaleString('en-US', {
          minimumFractionDigits: 2
        });
      } else {
        divSaldo.style.display = 'none';
        chkSaldo.checked = false;
      }

      document.getElementById('monto_recibido').value = "";
      document.querySelectorAll('.chk-deuda').forEach(c => c.checked = false);

      document.querySelectorAll('.card-deuda').forEach(card => {
        card.classList.remove('seleccionado-total', 'seleccionado-parcial');
        card.querySelector('.info-pago').classList.add('d-none');
      });

      document.getElementById('bal_prop_div').style.display = (tipo === 'propietario') ? 'block' : 'none';
      document.getElementById('bal_inq_div').style.display = (tipo === 'inquilino') ? 'block' : 'none';
      calcularDistribucion();
    }

    function calcularDistribucion() {
      let efectivo = parseFloat(document.getElementById('monto_recibido').value) || 0;

      let saldoFavorDisponible = 0;
      let usarSaldo = document.getElementById('chk_usar_saldo').checked;
      if (usarSaldo) {
        const sP = parseFloat(document.getElementById('saldo_favor_prop').value);
        const sI = parseFloat(document.getElementById('saldo_favor_inq').value);
        saldoFavorDisponible = (pagadorActual === 'propietario') ? sP : sI;
      }

      let dineroTotalDisponible = efectivo + saldoFavorDisponible;
      document.getElementById('txt_total_aplicar').innerText = "RD$ " + dineroTotalDisponible.toLocaleString('en-US', {
        minimumFractionDigits: 2
      });

      let dineroRestante = dineroTotalDisponible;

      const checkboxes = document.querySelectorAll('.chk-deuda');
      let itemsAProcesar = [];
      let totalAsignado = 0;
      let haySeleccion = false;

      checkboxes.forEach(chk => {
        const card = chk.closest('.card-deuda');
        if (chk.dataset.listatipo === ((pagadorActual === 'propietario') ? 'prop' : 'inq')) {
          card.classList.remove('seleccionado-total', 'seleccionado-parcial');
          card.querySelector('.info-pago').classList.add('d-none');
        }
      });

      checkboxes.forEach(chk => {
        if (chk.dataset.listatipo !== ((pagadorActual === 'propietario') ? 'prop' : 'inq')) return;
        if (chk.checked) {
          haySeleccion = true;
          const card = chk.closest('.card-deuda');
          const saldoDeuda = parseFloat(chk.dataset.saldo);

          let montoPagar = 0;
          let esAbono = false;

          if (dineroRestante > 0) {
            if (dineroRestante >= saldoDeuda) {
              montoPagar = saldoDeuda;
              dineroRestante -= saldoDeuda;
              card.classList.add('seleccionado-total');
            } else {
              montoPagar = dineroRestante;
              dineroRestante = 0;
              esAbono = true;
              card.classList.add('seleccionado-parcial');
              card.querySelector('.info-pago').classList.remove('d-none');
              card.querySelector('.txt-abono-monto').innerText = "Abonará: RD$ " + montoPagar.toLocaleString('en-US', {
                minimumFractionDigits: 2
              });
            }
          }

          if (montoPagar > 0) {
            totalAsignado += montoPagar;
            itemsAProcesar.push({
              id: chk.dataset.id,
              tipo: chk.dataset.tipo,
              monto_aplicar: montoPagar,
              es_abono: esAbono
            });
          }
        }
      });

      let saldoRealmenteUsado = 0;
      if (usarSaldo) {
        if (totalAsignado <= saldoFavorDisponible) {
          saldoRealmenteUsado = totalAsignado;
        } else {
          saldoRealmenteUsado = saldoFavorDisponible;
        }
      }
      document.getElementById('input_saldo_usado').value = saldoRealmenteUsado;

      document.getElementById('txt_asignado').innerText = "RD$ " + totalAsignado.toLocaleString('en-US', {
        minimumFractionDigits: 2
      });

      const rowAdelanto = document.getElementById('row_adelanto');
      const rowFalta = document.getElementById('row_falta');

      let remanenteCash = 0;
      if (dineroRestante > 0) {
        remanenteCash = dineroRestante;
      }

      if (remanenteCash > 0.01) {
        rowAdelanto.style.setProperty('display', 'flex', 'important');
        document.getElementById('txt_adelanto').innerText = "RD$ " + remanenteCash.toLocaleString('en-US', {
          minimumFractionDigits: 2
        });
      } else {
        rowAdelanto.style.setProperty('display', 'none', 'important');
      }

      let costoTotalSeleccion = 0;
      checkboxes.forEach(c => {
        if (c.checked && c.dataset.listatipo === ((pagadorActual === 'propietario') ? 'prop' : 'inq')) costoTotalSeleccion += parseFloat(c.dataset.saldo);
      });
      let falta = costoTotalSeleccion - (efectivo + saldoFavorDisponible);

      if (falta > 0.01) {
        rowFalta.style.setProperty('display', 'flex', 'important');
        document.getElementById('txt_falta').innerText = "RD$ " + falta.toLocaleString('en-US', {
          minimumFractionDigits: 2
        });
      } else {
        rowFalta.style.setProperty('display', 'none', 'important');
      }

      let hayDinero = (efectivo > 0) || (usarSaldo && saldoFavorDisponible > 0);
      document.getElementById('btn_procesar').disabled = !(hayDinero);
      document.getElementById('input_items_seleccionados').value = JSON.stringify(itemsAProcesar);
    }

    window.onload = function() {
      setPagador('propietario');
    };
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php include("../../templates/footer.php"); ?>