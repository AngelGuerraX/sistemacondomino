<?php include("../../templates/header.php"); ?>
<?php
include("../../bd.php");

$idcondominio = $_SESSION['idcondominio'];

// Obtener configuración TSS del condominio
$sentencia_condominio = $conexion->prepare("SELECT porcentaje_ars, porcentaje_afp FROM tbl_condominios WHERE id = :id");
$sentencia_condominio->bindParam(":id", $idcondominio);
$sentencia_condominio->execute();
$config_tss = $sentencia_condominio->fetch(PDO::FETCH_ASSOC);

$porcentaje_ars = $config_tss['porcentaje_ars'] ?? 3.04;
$porcentaje_afp = $config_tss['porcentaje_afp'] ?? 0.87;

// ... código anterior ...

// Procesar envío del formulario
if ($_POST && isset($_POST['guardar_nomina'])) {
  $quincena = $_POST['quincena'];
  $mes = $_POST['mes'];
  $anio = $_POST['anio'];
  $fecha_pago = $_POST['fecha_pago'];
  $total_general = 0;

  // DEBUG: Verificar qué datos llegan
  error_log("Datos POST recibidos: " . print_r($_POST, true));
  error_log("Datos empleados: " . print_r($_POST['empleados'] ?? 'No hay empleados', true));

  try {
    // Iniciar transacción
    $conexion->beginTransaction();

    // Verificar si hay datos de empleados
    if (isset($_POST['empleados']) && is_array($_POST['empleados']) && count($_POST['empleados']) > 0) {

      foreach ($_POST['empleados'] as $id_empleado => $datos) {
        // DEBUG: Verificar datos de cada empleado
        error_log("Procesando empleado ID: $id_empleado - Datos: " . print_r($datos, true));

        // Validar que los datos necesarios estén presentes
        if (!isset($datos['salario_quincenal']) || empty($datos['salario_quincenal'])) {
          throw new Exception("Falta salario quincenal para el empleado ID: $id_empleado");
        }

        $salario_quincenal = floatval($datos['salario_quincenal']);
        $horas_extras = floatval($datos['horas_extras'] ?? 0);
        $incentivos = floatval($datos['incentivos'] ?? 0);
        $descuento_prestamo = floatval($datos['descuento_prestamo'] ?? 0);
        $descuento_tss = floatval($datos['descuento_tss'] ?? 0);
        $total_quincena = $salario_quincenal + $horas_extras + $incentivos - $descuento_prestamo - $descuento_tss;

        // Insertar en nómina - CORREGIDO: usar 'mes' como varchar para coincidir con la tabla
        $sentencia = $conexion->prepare("INSERT INTO tbl_nomina 
                            (id_empleado, id_condominio, quincena, mes, anio, salario_quincenal, horas_extras, incentivos, descuento_prestamo, descuento_tss, total_quincena, fecha_pago) 
                            VALUES (:id_empleado, :id_condominio, :quincena, :mes, :anio, :salario_quincenal, :horas_extras, :incentivos, :descuento_prestamo, :descuento_tss, :total_quincena, :fecha_pago)");

        $sentencia->bindParam(":id_empleado", $id_empleado);
        $sentencia->bindParam(":id_condominio", $idcondominio);
        $sentencia->bindParam(":quincena", $quincena);
        $sentencia->bindParam(":mes", $mes); // Ahora es varchar
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
          throw new Exception("Error al insertar nómina: " . $errorInfo[2]);
        }

        $total_general += $total_quincena;
      }

      // Crear gasto automático en tbl_gastos
      $mes_nombre = $meses[$mes] ?? $mes;
      $detalles_gasto = "Pago de nómina " . ($quincena == '15' ? '1-15' : '16-30') . " - " . $mes_nombre . " " . $anio;

      $sentencia_gasto = $conexion->prepare("INSERT INTO tbl_gastos 
                        (tipo_gasto, detalles, monto, quincena, mes, anio, id_condominio) 
                        VALUES ('Nomina_Empleados', :detalles, :monto, :quincena, :mes, :anio, :id_condominio)");

      $sentencia_gasto->bindParam(":detalles", $detalles_gasto);
      $sentencia_gasto->bindParam(":monto", $total_general);
      $sentencia_gasto->bindParam(":quincena", $quincena);
      $sentencia_gasto->bindParam(":mes", $mes_nombre);
      $sentencia_gasto->bindParam(":anio", $anio);
      $sentencia_gasto->bindParam(":id_condominio", $idcondominio);

      if (!$sentencia_gasto->execute()) {
        $errorInfo = $sentencia_gasto->errorInfo();
        throw new Exception("Error al crear gasto: " . $errorInfo[2]);
      }

      // Confirmar transacción
      $conexion->commit();

      $_SESSION['mensaje'] = "✅ Nómina generada correctamente - Total: RD$ " . number_format($total_general, 2, '.', ',');
      $_SESSION['tipo_mensaje'] = "success";

      header("Location: index.php?mes=" . $mes . "&anio=" . $anio . "&quincena=" . $quincena);
      exit;
    } else {
      // DEBUG más detallado
      $debug_msg = "No se recibieron datos de empleados. ";
      $debug_msg .= "POST keys: " . implode(', ', array_keys($_POST)) . ". ";
      $debug_msg .= "Empleados en POST: " . (isset($_POST['empleados']) ? 'SÍ' : 'NO') . ". ";
      $debug_msg .= "Count: " . (isset($_POST['empleados']) ? count($_POST['empleados']) : 0);

      error_log($debug_msg);
      throw new Exception($debug_msg);
    }
  } catch (Exception $e) {
    $conexion->rollBack();
    $_SESSION['mensaje'] = "❌ Error al generar nómina: " . $e->getMessage();
    $_SESSION['tipo_mensaje'] = "danger";
  }
}

// ... resto del código ...

// Obtener empleados - VERSIÓN SIMPLIFICADA sin campos nuevos
$sentencia_empleados = $conexion->prepare("
    SELECT id, nombres, apellidos, cedula_pasaporte, cargo, salario 
    FROM tbl_empleados 
    WHERE idcondominio = :id_condominio 
    ORDER BY nombres, apellidos
");
$sentencia_empleados->bindParam(":id_condominio", $idcondominio);
$sentencia_empleados->execute();
$empleados = $sentencia_empleados->fetchAll(PDO::FETCH_ASSOC);

// Meses
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
  </style>
</head>

<body>
  <div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card bg-gradient-success text-white shadow">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h1 class="h2 mb-1">💰 Generar Nómina</h1>
                <p class="mb-0 opacity-8">Complete los datos para generar la nómina de la quincena</p>
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
          <li>Consulta ejecutada: SELECT id, nombres, apellidos, cedula_pasaporte, cargo, salario FROM tbl_empleados WHERE idcondominio = '<?php echo $idcondominio; ?>'</li>
          <li>Si no hay empleados, ve a <a href="../empleados/index.php">Gestionar Empleados</a> para agregarlos</li>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Configuración General -->
    <div class="card shadow mb-4">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
          <i class="fas fa-cog me-2"></i>Configuración de la Nómina
        </h6>
      </div>
      <div class="card-body">
        <form action="" method="post" id="formNomina">
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
        </form>
      </div>
    </div>

    <!-- Lista de Empleados -->
    <div class="card shadow">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
          <i class="fas fa-users me-2"></i>Empleados
          <span class="badge bg-primary ms-2"><?php echo count($empleados); ?></span>
        </h6>
        <div>
          <button type="button" id="btnExpandirTodos" class="btn btn-outline-primary btn-sm me-2">
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

              // Por defecto, todos tienen TSS activo (hasta que agreguemos el campo)
              $tss_activo = true;
              $descuento_tss = ($salario_quincenal * $porcentaje_ars / 100) +
                ($salario_quincenal * $porcentaje_afp / 100);

              $total_quincena = $salario_quincenal - $descuento_tss;
            ?>
              <div class="empleado-card" data-empleado-id="<?php echo $empleado['id']; ?>">
                <div class="empleado-header" data-bs-toggle="collapse"
                  data-bs-target="#empleado<?php echo $empleado['id']; ?>"
                  aria-expanded="false">
                  <div class="row align-items-center">
                    <div class="col-md-4">
                      <strong><?php echo htmlspecialchars($empleado['nombres'] . ' ' . $empleado['apellidos']); ?></strong>
                    </div>
                    <div class="col-md-3">
                      <small class="text-muted"><?php echo htmlspecialchars($empleado['cargo']); ?></small>
                    </div>
                    <div class="col-md-3">
                      <small>Cédula: <?php echo htmlspecialchars($empleado['cedula_pasaporte']); ?></small>
                    </div>
                    <div class="col-md-2 text-end">
                      <span class="badge bg-success">
                        TSS: Sí
                      </span>
                      <i class="fas fa-chevron-down ms-2"></i>
                    </div>
                  </div>
                </div>

                <div class="collapse empleado-body" id="empleado<?php echo $empleado['id']; ?>">
                  <div class="row g-3">
                    <!-- Campos de solo lectura -->
                    <div class="col-md-3">
                      <label class="form-label fw-bold">Salario Mensual:</label>
                      <input type="text" class="form-control"
                        value="RD$ <?php echo number_format($empleado['salario'], 2, '.', ','); ?>"
                        readonly>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label fw-bold">Salario Quincenal:</label>
                      <input type="number" step="0.01"
                        name="empleados[<?php echo $empleado['id']; ?>][salario_quincenal]"
                        class="form-control salario-quincenal"
                        value="<?php echo number_format($salario_quincenal, 2, '.', ''); ?>"
                        readonly>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label fw-bold">Descuento TSS:</label>
                      <input type="number" step="0.01"
                        name="empleados[<?php echo $empleado['id']; ?>][descuento_tss]"
                        class="form-control descuento-tss"
                        value="<?php echo number_format($descuento_tss, 2, '.', ''); ?>"
                        readonly>
                    </div>

                    <!-- Campos editables -->
                    <div class="col-md-3">
                      <label class="form-label fw-bold">Horas Extras:</label>
                      <input type="number" step="0.01"
                        name="empleados[<?php echo $empleado['id']; ?>][horas_extras]"
                        class="form-control horas-extras"
                        value="0.00"
                        onchange="calcularEmpleado(<?php echo $empleado['id']; ?>)">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label fw-bold">Incentivos:</label>
                      <input type="number" step="0.01"
                        name="empleados[<?php echo $empleado['id']; ?>][incentivos]"
                        class="form-control incentivos"
                        value="0.00"
                        onchange="calcularEmpleado(<?php echo $empleado['id']; ?>)">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label fw-bold">Desc. Préstamo:</label>
                      <input type="number" step="0.01"
                        name="empleados[<?php echo $empleado['id']; ?>][descuento_prestamo]"
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
              </div>
              <div class="col-md-4">
                <button type="submit" name="guardar_nomina" form="formNomina"
                  class="btn btn-light btn-lg w-100">
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

    <!-- Botón Volver -->
    <div class="text-center mt-4">
      <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i> Volver al Listado
      </a>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Calcular total general inicial
      calcularTotalGeneral();

      // Expandir/Contraer todos
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

      // Recalcular todos
      document.getElementById('btnCalcularTodos').addEventListener('click', function() {
        document.querySelectorAll('.empleado-card').forEach(card => {
          const empleadoId = card.getAttribute('data-empleado-id');
          calcularEmpleado(empleadoId);
        });
      });
    });

    function calcularEmpleado(empleadoId) {
      const card = document.querySelector(`[data-empleado-id="${empleadoId}"]`);

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

      document.querySelectorAll('.total-quincena').forEach(input => {
        totalGeneral += parseFloat(input.value) || 0;
      });

      document.getElementById('totalGeneral').textContent =
        'RD$ ' + totalGeneral.toLocaleString('es-DO', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        });
    }
  </script>
</body>

</html>

<?php include("../../templates/footer.php"); ?>