<?php
include("../../templates/header.php");
include("../../bd.php");

$idcondominio = $_SESSION['idcondominio'];

// Obtener configuración TSS del condominio
$sentencia_condominio = $conexion->prepare("SELECT porcentaje_ars, porcentaje_afp FROM tbl_condominios WHERE id = :id");
$sentencia_condominio->bindParam(":id", $idcondominio);
$sentencia_condominio->execute();
$config_tss = $sentencia_condominio->fetch(PDO::FETCH_ASSOC);

$porcentaje_ars = $config_tss['porcentaje_ars'] ?? 3.04;
$porcentaje_afp = $config_tss['porcentaje_afp'] ?? 0.87;

// Función corregida para registrar gasto de nómina - COMPATIBLE CON TU BD
function registrarGastoNomina($conexion, $id_condominio, $quincena, $mes, $anio, $total_general)
{
  // Convertir mes numérico a nombre del mes
  $meses = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre'
  ];

  $mes_nombre = $meses[$mes] ?? $mes;
  $detalles_gasto = "Pago de nómina " . ($quincena == '15' ? '1-15' : '16-30') . " - " . $mes_nombre . " " . $anio;

  // Convertir monto a string (ya que tu campo monto es VARCHAR)
  $monto_str = (string)$total_general;

  // Verificar si ya existe este gasto
  $sentencia_verificar = $conexion->prepare("
        SELECT id FROM tbl_gastos 
        WHERE tipo_gasto = 'Nomina_Empleados' 
        AND quincena = :quincena 
        AND mes = :mes 
        AND anio = :anio 
        AND id_condominio = :id_condominio
    ");
  $sentencia_verificar->bindParam(":quincena", $quincena);
  $sentencia_verificar->bindParam(":mes", $mes_nombre);
  $sentencia_verificar->bindParam(":anio", $anio);
  $sentencia_verificar->bindParam(":id_condominio", $id_condominio);
  $sentencia_verificar->execute();

  if ($sentencia_verificar->fetch()) {
    // Ya existe, actualizar en lugar de insertar
    $sentencia_gasto = $conexion->prepare("
            UPDATE tbl_gastos 
            SET monto = :monto, detalles = :detalles
            WHERE tipo_gasto = 'Nomina_Empleados' 
            AND quincena = :quincena 
            AND mes = :mes 
            AND anio = :anio 
            AND id_condominio = :id_condominio
        ");
  } else {
    // Insertar nuevo gasto - COMPATIBLE CON TU ESTRUCTURA
    $sentencia_gasto = $conexion->prepare("
            INSERT INTO tbl_gastos 
            (tipo_gasto, detalles, monto, quincena, mes, anio, id_condominio) 
            VALUES ('Nomina_Empleados', :detalles, :monto, :quincena, :mes, :anio, :id_condominio)
        ");
  }

  $sentencia_gasto->bindParam(":detalles", $detalles_gasto);
  $sentencia_gasto->bindParam(":monto", $monto_str);
  $sentencia_gasto->bindParam(":quincena", $quincena);
  $sentencia_gasto->bindParam(":mes", $mes_nombre);
  $sentencia_gasto->bindParam(":anio", $anio);
  $sentencia_gasto->bindParam(":id_condominio", $id_condominio);

  return $sentencia_gasto->execute();
}

// Función para verificar si ya existe nómina para el periodo
function verificarNominaExistente($conexion, $id_condominio, $quincena, $mes, $anio)
{
  // Convertir mes numérico a nombre para la búsqueda
  $meses = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre'
  ];
  $mes_nombre = $meses[$mes] ?? $mes;

  $sentencia = $conexion->prepare("
        SELECT COUNT(*) as count 
        FROM tbl_nomina 
        WHERE id_condominio = :id_condominio 
        AND quincena = :quincena 
        AND mes = :mes 
        AND anio = :anio
    ");
  $sentencia->bindParam(":id_condominio", $id_condominio);
  $sentencia->bindParam(":quincena", $quincena);
  $sentencia->bindParam(":mes", $mes_nombre);
  $sentencia->bindParam(":anio", $anio);
  $sentencia->execute();

  $resultado = $sentencia->fetch(PDO::FETCH_ASSOC);
  return $resultado['count'] > 0;
}

// Procesar envío del formulario
if ($_POST && isset($_POST['guardar_nomina'])) {
  $quincena = $_POST['quincena'];
  $mes_numero = $_POST['mes'];
  $anio = $_POST['anio'];
  $fecha_pago = $_POST['fecha_pago'];
  $total_general = 0;

  // Convertir mes numérico a nombre para la base de datos
  $meses = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre'
  ];
  $mes_nombre = $meses[$mes_numero] ?? $mes_numero;

  // Variable para controlar el estado de la transacción
  $transaccion_activa = false;

  try {
    // Verificar si ya existe nómina para este periodo
    if (verificarNominaExistente($conexion, $idcondominio, $quincena, $mes_numero, $anio)) {
      throw new Exception("⚠️ Ya existe una nómina registrada para la quincena $quincena del mes $mes_nombre de $anio.");
    }

    // Iniciar transacción
    $conexion->beginTransaction();
    $transaccion_activa = true;

    $empleados_procesados = 0;
    $empleados_con_errores = [];

    foreach ($_POST as $key => $value) {
      if (strpos($key, 'empleado_') === 0) {
        $id_empleado = str_replace('empleado_', '', $key);

        // Verificar que el empleado está marcado para procesar
        if (isset($_POST['seleccionado_' . $id_empleado]) && $_POST['seleccionado_' . $id_empleado] == '1') {
          $salario_quincenal = floatval($_POST['salario_quincenal_' . $id_empleado] ?? 0);
          $horas_extras = floatval($_POST['horas_extras_' . $id_empleado] ?? 0);
          $incentivos = floatval($_POST['incentivos_' . $id_empleado] ?? 0);
          $descuento_prestamo = floatval($_POST['descuento_prestamo_' . $id_empleado] ?? 0);
          $descuento_tss = floatval($_POST['descuento_tss_' . $id_empleado] ?? 0);

          $total_quincena = $salario_quincenal + $horas_extras + $incentivos - $descuento_prestamo - $descuento_tss;

          // Validar datos del empleado
          if ($salario_quincenal <= 0) {
            $empleados_con_errores[] = "Empleado ID $id_empleado: Salario quincenal inválido";
            continue;
          }

          // Insertar en nómina - AJUSTADO A TU ESTRUCTURA
          $sentencia = $conexion->prepare("INSERT INTO tbl_nomina 
                                                (id_empleado, id_condominio, quincena, mes, anio, salario_quincenal, horas_extras, incentivos, descuento_prestamo, descuento_tss, total_quincena, fecha_pago, fecha_registro) 
                                                VALUES (:id_empleado, :id_condominio, :quincena, :mes, :anio, :salario_quincenal, :horas_extras, :incentivos, :descuento_prestamo, :descuento_tss, :total_quincena, :fecha_pago, NOW())");

          $sentencia->bindParam(":id_empleado", $id_empleado);
          $sentencia->bindParam(":id_condominio", $idcondominio);
          $sentencia->bindParam(":quincena", $quincena);
          $sentencia->bindParam(":mes", $mes_nombre);
          $sentencia->bindParam(":anio", $anio);
          $sentencia->bindParam(":salario_quincenal", $salario_quincenal);
          $sentencia->bindParam(":horas_extras", $horas_extras);
          $sentencia->bindParam(":incentivos", $incentivos);
          $sentencia->bindParam(":descuento_prestamo", $descuento_prestamo);
          $sentencia->bindParam(":descuento_tss", $descuento_tss);
          $sentencia->bindParam(":total_quincena", $total_quincena);
          $sentencia->bindParam(":fecha_pago", $fecha_pago);

          if (!$sentencia->execute()) {
            $errorInfo = $sentencia->errorInfo();
            $empleados_con_errores[] = "Empleado ID $id_empleado: " . $errorInfo[2];
            continue;
          }

          $total_general += $total_quincena;
          $empleados_procesados++;
        }
      }
    }

    if ($empleados_procesados > 0) {
      // Crear gasto automático en tbl_gastos usando la función corregida
      if (!registrarGastoNomina($conexion, $idcondominio, $quincena, $mes_numero, $anio, $total_general)) {
        throw new Exception("Error al registrar el gasto de nómina en el sistema de gastos");
      }

      // Confirmar transacción
      $conexion->commit();
      $transaccion_activa = false;

      $mensaje_exito = "✅ Nómina generada correctamente para $empleados_procesados empleado(s) - Total: RD$ " . number_format($total_general, 2, '.', ',');

      // Agregar advertencias si hay empleados con errores
      if (!empty($empleados_con_errores)) {
        $mensaje_exito .= "<br><small class='text-warning'><strong>Advertencia:</strong> " . count($empleados_con_errores) . " empleado(s) no pudieron procesarse.</small>";
      }

      $_SESSION['mensaje'] = $mensaje_exito;
      $_SESSION['tipo_mensaje'] = "success";

      header("Location: index.php?mes=" . $mes_numero . "&anio=" . $anio . "&quincena=" . $quincena);
      exit;
    } else {
      throw new Exception("No se pudo procesar ningún empleado. " .
        (empty($empleados_con_errores) ? "Marque al menos un empleado." : "Todos los empleados seleccionados tuvieron errores."));
    }
  } catch (Exception $e) {
    // Solo hacer rollback si hay una transacción activa
    if ($transaccion_activa) {
      $conexion->rollBack();
    }
    $_SESSION['mensaje'] = "❌ Error al generar nómina: " . $e->getMessage();
    $_SESSION['tipo_mensaje'] = "danger";
  }
}

// Obtener empleados
$sentencia_empleados = $conexion->prepare("
    SELECT id, nombres, apellidos, cedula_pasaporte, cargo, salario 
    FROM tbl_empleados 
    WHERE idcondominio = :id_condominio 
    AND activo = 'si'
    ORDER BY nombres, apellidos
");
$sentencia_empleados->bindParam(":id_condominio", $idcondominio);
$sentencia_empleados->execute();
$empleados = $sentencia_empleados->fetchAll(PDO::FETCH_ASSOC);

// Meses para el formulario
$meses = [
  1 => 'Enero',
  2 => 'Febrero',
  3 => 'Marzo',
  4 => 'Abril',
  5 => 'Mayo',
  6 => 'Junio',
  7 => 'Julio',
  8 => 'Agosto',
  9 => 'Septiembre',
  10 => 'Octubre',
  11 => 'Noviembre',
  12 => 'Diciembre'
];
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Generar Nómina</title>
  <style>
    .empleado-card {
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      transition: all 0.3s ease;
      margin-bottom: 1rem;
    }

    .empleado-card:hover {
      border-color: #0d6efd;
      box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .empleado-header {
      background-color: #f8f9fa;
      border-bottom: 1px solid #dee2e6;
      padding: 0.75rem 1rem;
      cursor: pointer;
    }

    .empleado-body {
      padding: 1rem;
    }

    .calculos-automaticos {
      background-color: #e7f3ff;
      border-radius: 0.375rem;
      padding: 0.75rem;
      margin-bottom: 1rem;
    }

    .resumen-total {
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      border-radius: 0.375rem;
      padding: 1rem;
      font-size: 1.25rem;
      font-weight: bold;
    }

    .badge-tss {
      background-color: #6f42c1;
    }

    .form-control:read-only {
      background-color: #f8f9fa;
    }

    .debug-info {
      background-color: #fff3cd;
      border: 1px solid #ffeaa7;
      border-radius: 0.375rem;
      padding: 0.75rem;
      margin-bottom: 1rem;
      font-size: 0.875rem;
    }

    .checkbox-seleccion {
      transform: scale(1.2);
      margin-right: 10px;
    }

    .empleado-seleccionado .empleado-header {
      background-color: #e8f5e8;
      border-color: #28a745;
    }

    .loading-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }

    .loading-spinner {
      color: white;
      font-size: 2rem;
    }
  </style>
</head>

<body>
  <!-- Loading Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
      <i class="fas fa-spinner fa-spin me-2"></i> Procesando nómina...
    </div>
  </div>

  <div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card bg-dark text-white shadow">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h1 class="h2 mb-1">💰 Generar Nómina</h1>
                <p class="mb-0 opacity-8">Seleccione empleados y complete los datos</p>
              </div>
              <div class="col-md-4 text-end">
                <div class="bg-white bg-opacity-20 rounded p-3 d-inline-block">
                  <i class="fas fa-calculator fa-2x"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['mensaje'])): ?>
      <div class='alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show' role='alert'>
        <?php echo $_SESSION['mensaje']; ?>
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
      </div>
      <?php
      unset($_SESSION['mensaje']);
      unset($_SESSION['tipo_mensaje']);
      ?>
    <?php endif; ?>

    <!-- Información de Debug -->
    <?php if (count($empleados) === 0): ?>
      <div class="debug-info">
        <strong>⚠️ Información de Depuración:</strong>
        <ul class="mb-0 mt-2">
          <li>Condominio ID: <?php echo $idcondominio; ?></li>
          <li>Empleados encontrados: <?php echo count($empleados); ?></li>
          <li>Si no hay empleados, ve a <a href="../empleados/index.php">Gestionar Empleados</a> para agregarlos</li>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Formulario Principal -->
    <form action="" method="post" id="formNomina" onsubmit="mostrarLoading()">
      <input type="hidden" name="guardar_nomina" value="1">

      <!-- Configuración General -->
      <div class="card shadow mb-4">
        <div class="card-header bg-dark text-white py-3">
          <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-cog me-2"></i>Configuración de la Nómina
          </h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label fw-bold">Quincena:</label>
              <select name="quincena" id="quincena" class="form-select" required>
                <option value="15">Quincena 1-15</option>
                <option value="30">Quincena 16-30</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Mes:</label>
              <select name="mes" id="mes" class="form-select" required>
                <?php foreach ($meses as $numero => $nombre): ?>
                  <option value="<?php echo $numero; ?>" <?php echo date('n') == $numero ? 'selected' : ''; ?>>
                    <?php echo $nombre; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Año:</label>
              <select name="anio" id="anio" class="form-select" required>
                <?php for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++): ?>
                  <option value="<?php echo $i; ?>" <?php echo date('Y') == $i ? 'selected' : ''; ?>>
                    <?php echo $i; ?>
                  </option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Fecha de Pago:</label>
              <input type="date" name="fecha_pago" id="fecha_pago" class="form-control"
                value="<?php echo date('Y-m-d'); ?>" required>
            </div>
          </div>

          <!-- Información TSS -->
          <div class="calculos-automaticos mt-3">
            <div class="row">
              <div class="col-md-12">
                <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Configuración TSS Automática</h6>
                <div class="row">
                  <div class="col-md-3">
                    <strong>ARS:</strong> <span class="badge badge-tss"><?php echo $porcentaje_ars; ?>%</span>
                  </div>
                  <div class="col-md-3">
                    <strong>AFP:</strong> <span class="badge badge-tss"><?php echo $porcentaje_afp; ?>%</span>
                  </div>
                  <div class="col-md-6">
                    <strong>Total TSS:</strong> <span class="badge bg-dark"><?php echo $porcentaje_ars + $porcentaje_afp; ?>%</span>
                    <small class="text-muted ms-2">(Se aplica automáticamente a todos los empleados)</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Lista de Empleados -->
      <div class="card shadow">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
          <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-users me-2"></i>Empleados
            <span class="badge bg-primary ms-2"><?php echo count($empleados); ?></span>
          </h6>
          <div>
            <button type="button" id="btnSeleccionarTodos" class="btn btn-outline-primary btn-sm me-2">
              <i class="fas fa-check-square me-1"></i> Seleccionar Todos
            </button>
            <button type="button" id="btnExpandirTodos" class="btn btn-outline-info btn-sm me-2">
              <i class="fas fa-expand me-1"></i> Expandir Todos
            </button>
            <button type="button" id="btnCalcularTodos" class="btn btn-outline-success btn-sm">
              <i class="fas fa-calculator me-1"></i> Recalcular Todos
            </button>
          </div>
        </div>
        <div class="card-body">
          <?php if (count($empleados) > 0): ?>
            <div id="listaEmpleados">
              <?php foreach ($empleados as $empleado):
                $salario_quincenal = $empleado['salario'] / 2;
                $descuento_tss = ($salario_quincenal * $porcentaje_ars / 100) + ($salario_quincenal * $porcentaje_afp / 100);
                $total_quincena = $salario_quincenal - $descuento_tss;
              ?>
                <div class="empleado-card empleado-seleccionado" id="empleado_<?php echo $empleado['id']; ?>" data-empleado-id="<?php echo $empleado['id']; ?>">
                  <div class="empleado-header" data-bs-toggle="collapse"
                    data-bs-target="#detalles<?php echo $empleado['id']; ?>"
                    aria-expanded="false">
                    <div class="row align-items-center">
                      <div class="col-md-1">
                        <input type="checkbox"
                          name="seleccionado_<?php echo $empleado['id']; ?>"
                          value="1"
                          class="checkbox-seleccion empleado-checkbox"
                          checked
                          onchange="toggleSeleccionado(<?php echo $empleado['id']; ?>)">
                      </div>
                      <div class="col-md-4">
                        <strong><?php echo htmlspecialchars($empleado['nombres'] . ' ' . $empleado['apellidos']); ?></strong>
                      </div>
                      <div class="col-md-3">
                        <small class="text-muted"><?php echo htmlspecialchars($empleado['cargo']); ?></small>
                      </div>
                      <div class="col-md-3">
                        <small>Cédula: <?php echo htmlspecialchars($empleado['cedula_pasaporte']); ?></small>
                      </div>
                      <div class="col-md-1 text-end">
                        <span class="badge bg-danger">TSS: Sí</span>
                        <i class="fas fa-chevron-down ms-2"></i>
                      </div>
                    </div>
                  </div>

                  <div class="collapse empleado-body" id="detalles<?php echo $empleado['id']; ?>">
                    <div class="row g-3">
                      <!-- Campos ocultos para identificar empleado -->
                      <input type="hidden" name="empleado_<?php echo $empleado['id']; ?>" value="<?php echo $empleado['id']; ?>">

                      <!-- Campos de solo lectura -->
                      <div class="col-md-3">
                        <label class="form-label fw-bold">Salario Mensual:</label>
                        <input type="text" class="form-control"
                          value="RD$ <?php echo number_format($empleado['salario'], 2, '.', ','); ?>">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label fw-bold">Salario Quincenal:</label>
                        <input type="number" step="0.01"
                          name="salario_quincenal_<?php echo $empleado['id']; ?>"
                          class="form-control salario-quincenal"
                          value="<?php echo number_format($salario_quincenal, 2, '.', ''); ?>">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label fw-bold">Descuento TSS:</label>
                        <input type="number" step="0.01"
                          name="descuento_tss_<?php echo $empleado['id']; ?>"
                          class="form-control descuento-tss"
                          value="<?php echo number_format($descuento_tss, 2, '.', ''); ?>">
                      </div>

                      <!-- Campos editables -->
                      <div class="col-md-3">
                        <label class="form-label fw-bold">Horas Extras:</label>
                        <input type="number" step="0.01"
                          name="horas_extras_<?php echo $empleado['id']; ?>"
                          class="form-control horas-extras"
                          value="0.00"
                          onchange="calcularEmpleado(<?php echo $empleado['id']; ?>)">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label fw-bold">Incentivos:</label>
                        <input type="number" step="0.01"
                          name="incentivos_<?php echo $empleado['id']; ?>"
                          class="form-control incentivos"
                          value="0.00"
                          onchange="calcularEmpleado(<?php echo $empleado['id']; ?>)">
                      </div>
                      <div class="col-md-3">
                        <label class="form-label fw-bold">Desc. Préstamo:</label>
                        <input type="number" step="0.01"
                          name="descuento_prestamo_<?php echo $empleado['id']; ?>"
                          class="form-control descuento-prestamo"
                          value="0.00"
                          onchange="calcularEmpleado(<?php echo $empleado['id']; ?>)">
                      </div>

                      <!-- Total -->
                      <div class="col-md-3">
                        <label class="form-label fw-bold">Total a Pagar:</label>
                        <input type="number" step="0.01"
                          class="form-control total-quincena fw-bold text-success"
                          value="<?php echo number_format($total_quincena, 2, '.', ''); ?>"
                          readonly>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Resumen General -->
            <div class="resumen-total mt-4 text-center">
              <div class="row align-items-center">
                <div class="col-md-8">
                  <h4 class="mb-0">TOTAL NÓMINA: <span id="totalGeneral">RD$ 0.00</span></h4>
                  <small class="opacity-8" id="contadorEmpleados">0 empleados seleccionados</small>
                </div>
                <div class="col-md-4">
                  <button type="submit" name="guardar_nomina"
                    class="btn btn-light btn-lg w-100"
                    id="btnGuardarNomina">
                    <i class="fas fa-save me-2"></i> Guardar Nómina
                  </button>
                </div>
              </div>
            </div>

          <?php else: ?>
            <div class="text-center py-5">
              <i class="fas fa-users-slash fa-4x text-muted mb-3"></i>
              <h4 class="text-muted">No hay empleados registrados</h4>
              <p class="text-muted">Agrega empleados para poder generar nómina</p>
              <a href="../empleados/index.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Gestionar Empleados
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </form>

    <!-- Botón Volver -->
    <div class="text-center mt-4">
      <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i> Volver al Listado
      </a>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      calcularTotalGeneral();
      actualizarContadorEmpleados();

      document.getElementById('btnSeleccionarTodos').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('.empleado-checkbox');
        const todosSeleccionados = Array.from(checkboxes).every(checkbox => checkbox.checked);
        checkboxes.forEach(checkbox => {
          checkbox.checked = !todosSeleccionados;
          const empleadoId = checkbox.name.replace('seleccionado_', '');
          toggleSeleccionado(empleadoId);
        });
        this.querySelector('i').className = todosSeleccionados ? 'fas fa-check-square me-1' : 'fas fa-minus-square me-1';
      });

      document.getElementById('btnExpandirTodos').addEventListener('click', function() {
        const collapses = document.querySelectorAll('.empleado-body');
        const icon = this.querySelector('i');
        const algunoAbierto = Array.from(collapses).some(collapse => collapse.classList.contains('show'));
        collapses.forEach(collapse => {
          if (algunoAbierto) {
            bootstrap.Collapse.getInstance(collapse)?.hide();
          } else {
            new bootstrap.Collapse(collapse).show();
          }
        });
        icon.className = algunoAbierto ? 'fas fa-expand me-1' : 'fas fa-compress me-1';
      });

      document.getElementById('btnCalcularTodos').addEventListener('click', function() {
        document.querySelectorAll('.empleado-card').forEach(card => {
          const empleadoId = card.getAttribute('data-empleado-id');
          calcularEmpleado(empleadoId);
        });
      });
    });

    function mostrarLoading() {
      document.getElementById('loadingOverlay').style.display = 'flex';
      document.getElementById('btnGuardarNomina').disabled = true;
    }

    function toggleSeleccionado(empleadoId) {
      const card = document.getElementById('empleado_' + empleadoId);
      const checkbox = document.querySelector(`[name="seleccionado_${empleadoId}"]`);
      if (checkbox.checked) {
        card.classList.add('empleado-seleccionado');
      } else {
        card.classList.remove('empleado-seleccionado');
      }
      actualizarContadorEmpleados();
      calcularTotalGeneral();
    }

    function calcularEmpleado(empleadoId) {
      const card = document.getElementById('empleado_' + empleadoId);
      const checkbox = document.querySelector(`[name="seleccionado_${empleadoId}"]`);
      if (!checkbox.checked) return;

      const salarioQuincenal = parseFloat(card.querySelector('.salario-quincenal').value) || 0;
      const horasExtras = parseFloat(card.querySelector('.horas-extras').value) || 0;
      const incentivos = parseFloat(card.querySelector('.incentivos').value) || 0;
      const descuentoPrestamo = parseFloat(card.querySelector('.descuento-prestamo').value) || 0;
      const descuentoTss = parseFloat(card.querySelector('.descuento-tss').value) || 0;

      const totalQuincena = salarioQuincenal + horasExtras + incentivos - descuentoPrestamo - descuentoTss;
      card.querySelector('.total-quincena').value = totalQuincena.toFixed(2);
      calcularTotalGeneral();
    }

    function calcularTotalGeneral() {
      let totalGeneral = 0;
      document.querySelectorAll('.empleado-card').forEach(card => {
        const empleadoId = card.getAttribute('data-empleado-id');
        const checkbox = document.querySelector(`[name="seleccionado_${empleadoId}"]`);
        if (checkbox.checked) {
          const totalInput = card.querySelector('.total-quincena');
          totalGeneral += parseFloat(totalInput.value) || 0;
        }
      });
      document.getElementById('totalGeneral').textContent = 'RD$ ' + totalGeneral.toLocaleString('es-DO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    }

    function actualizarContadorEmpleados() {
      const checkboxesSeleccionados = document.querySelectorAll('.empleado-checkbox:checked');
      document.getElementById('contadorEmpleados').textContent = checkboxesSeleccionados.length + ' empleado(s) seleccionado(s)';
    }
  </script>
</body>

</html>

<?php include("../../templates/footer.php"); ?>