<?php include("../../templates/header.php"); ?>
<?php
include("../../bd.php");

// =============================================
// PROCESAR NUEVO GASTO (MODAL)
// =============================================
if ($_POST && isset($_POST['sel_mas_menos']) && !isset($_POST['es_predeterminado'])) {
   $tipo_gasto = isset($_POST["sel_mas_menos"]) ? $_POST["sel_mas_menos"] : "";
   $detalles = isset($_POST["detalles"]) ? $_POST["detalles"] : "";
   $monto = isset($_POST["monto"]) ? $_POST["monto"] : "";
   $quincena = isset($_POST["quincena"]) ? $_POST["quincena"] : "";
   $mes = isset($_POST["mes"]) ? $_POST["mes"] : $_SESSION['mes'];
   $anio = isset($_POST["anio"]) ? $_POST["anio"] : $_SESSION['anio'];
   $id_condominio = $_SESSION['idcondominio'];

   try {
      $sentencia = $conexion->prepare("INSERT INTO tbl_gastos (tipo_gasto, detalles, monto, quincena, mes, anio, id_condominio)
                                       VALUES (:tipo_gasto, :detalles, :monto, :quincena, :mes, :anio, :id_condominio)");

      $sentencia->bindParam(":tipo_gasto", $tipo_gasto);
      $sentencia->bindParam(":detalles", $detalles);
      $sentencia->bindParam(":monto", $monto);
      $sentencia->bindParam(":quincena", $quincena);
      $sentencia->bindParam(":mes", $mes);
      $sentencia->bindParam(":anio", $anio);
      $sentencia->bindParam(":id_condominio", $id_condominio);
      $sentencia->execute();

      $_SESSION['mensaje'] = "✅ Gasto registrado correctamente";
      $_SESSION['tipo_mensaje'] = "success";

      header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $mes . "&anio=" . $anio . "&periodo=" . $quincena);
      exit;
   } catch (Exception $e) {
      $_SESSION['mensaje'] = "❌ Error al registrar el gasto: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }
}

// =============================================
// AGREGAR GASTO PREDETERMINADO
// =============================================
if ($_POST && isset($_POST['es_predeterminado'])) {
   $tipo_gasto = isset($_POST["sel_mas_menos"]) ? $_POST["sel_mas_menos"] : "";
   $detalles = isset($_POST["detalles"]) ? $_POST["detalles"] : "";
   $monto = isset($_POST["monto"]) ? $_POST["monto"] : "";
   $quincena = '15'; // Siempre quincena 1-15 para predeterminados
   $activo = 1;

   try {
      // Verificar si ya existe un gasto predeterminado con los mismos detalles
      $sentencia_verificar = $conexion->prepare("SELECT id FROM tbl_gastos_predeterminados WHERE detalles = :detalles");
      $sentencia_verificar->bindParam(":detalles", $detalles);
      $sentencia_verificar->execute();

      if ($sentencia_verificar->fetch()) {
         $_SESSION['mensaje'] = "⚠️ Ya existe un gasto predeterminado con estos detalles";
         $_SESSION['tipo_mensaje'] = "warning";
      } else {
         $sentencia = $conexion->prepare("INSERT INTO tbl_gastos_predeterminados (tipo_gasto, detalles, monto, quincena, activo)
                                           VALUES (:tipo_gasto, :detalles, :monto, :quincena, :activo)");

         $sentencia->bindParam(":tipo_gasto", $tipo_gasto);
         $sentencia->bindParam(":detalles", $detalles);
         $sentencia->bindParam(":monto", $monto);
         $sentencia->bindParam(":quincena", $quincena);
         $sentencia->bindParam(":activo", $activo);
         $sentencia->execute();

         $_SESSION['mensaje'] = "✅ Gasto predeterminado agregado correctamente";
         $_SESSION['tipo_mensaje'] = "success";
      }

      header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $_GET['mes'] . "&anio=" . $_GET['anio'] . "&periodo=" . $_GET['periodo']);
      exit;
   } catch (Exception $e) {
      $_SESSION['mensaje'] = "❌ Error al agregar gasto predeterminado: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }
}

// =============================================
// GENERAR GASTOS DEL MES (Desde predeterminados)
// =============================================
if (isset($_GET['generar_gastos'])) {
   $mes = isset($_GET['mes']) ? $_GET['mes'] : date('F');
   $anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
   $id_condominio = $_SESSION['idcondominio'];
   $gastos_generados = 0;

   try {
      // Obtener gastos predeterminados activos
      $sentencia_predeterminados = $conexion->prepare("SELECT * FROM tbl_gastos_predeterminados WHERE activo = 1");
      $sentencia_predeterminados->execute();
      $gastos_predeterminados = $sentencia_predeterminados->fetchAll(PDO::FETCH_ASSOC);

      foreach ($gastos_predeterminados as $gasto) {
         // Verificar si ya existe este gasto en el mes actual
         $sentencia_verificar = $conexion->prepare("SELECT id FROM tbl_gastos 
                                                     WHERE detalles = :detalles 
                                                     AND mes = :mes 
                                                     AND anio = :anio 
                                                     AND quincena = :quincena");
         $sentencia_verificar->bindParam(":detalles", $gasto['detalles']);
         $sentencia_verificar->bindParam(":mes", $mes);
         $sentencia_verificar->bindParam(":anio", $anio);
         $sentencia_verificar->bindParam(":quincena", $gasto['quincena']);
         $sentencia_verificar->execute();

         if (!$sentencia_verificar->fetch()) {
            // Insertar gasto si no existe
            $sentencia_insertar = $conexion->prepare("INSERT INTO tbl_gastos (tipo_gasto, detalles, monto, quincena, mes, anio, id_condominio)
                                                        VALUES (:tipo_gasto, :detalles, :monto, :quincena, :mes, :anio, :id_condominio)");

            $sentencia_insertar->bindParam(":tipo_gasto", $gasto['tipo_gasto']);
            $sentencia_insertar->bindParam(":detalles", $gasto['detalles']);
            $sentencia_insertar->bindParam(":monto", $gasto['monto']);
            $sentencia_insertar->bindParam(":quincena", $gasto['quincena']);
            $sentencia_insertar->bindParam(":mes", $mes);
            $sentencia_insertar->bindParam(":anio", $anio);
            $sentencia_insertar->bindParam(":id_condominio", $id_condominio);
            $sentencia_insertar->execute();
            $gastos_generados++;
         }
      }

      $_SESSION['mensaje'] = "✅ Se generaron $gastos_generados gastos del mes correctamente";
      $_SESSION['tipo_mensaje'] = "success";

      header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $mes . "&anio=" . $anio . "&periodo=15");
      exit;
   } catch (Exception $e) {
      $_SESSION['mensaje'] = "❌ Error al generar gastos: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }
}

// =============================================
// TOGGLE ACTIVO GASTO PREDETERMINADO
// =============================================
if (isset($_GET['toggle_predeterminado'])) {
   $id = $_GET['toggle_predeterminado'];

   try {
      $sentencia = $conexion->prepare("UPDATE tbl_gastos_predeterminados SET activo = NOT activo WHERE id = :id");
      $sentencia->bindParam(":id", $id);
      $sentencia->execute();

      $_SESSION['mensaje'] = "✅ Estado del gasto predeterminado actualizado";
      $_SESSION['tipo_mensaje'] = "success";
   } catch (Exception $e) {
      $_SESSION['mensaje'] = "❌ Error al actualizar estado: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }

   header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $_GET['mes'] . "&anio=" . $_GET['anio'] . "&periodo=" . $_GET['periodo']);
   exit;
}

// =============================================
// ELIMINAR GASTO PREDETERMINADO
// =============================================
if (isset($_GET['eliminar_predeterminado'])) {
   $id = $_GET['eliminar_predeterminado'];

   try {
      $sentencia = $conexion->prepare("DELETE FROM tbl_gastos_predeterminados WHERE id = :id");
      $sentencia->bindParam(":id", $id);
      $sentencia->execute();

      $_SESSION['mensaje'] = "✅ Gasto predeterminado eliminado";
      $_SESSION['tipo_mensaje'] = "success";
   } catch (Exception $e) {
      $_SESSION['mensaje'] = "❌ Error al eliminar: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }

   header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $_GET['mes'] . "&anio=" . $_GET['anio'] . "&periodo=" . $_GET['periodo']);
   exit;
}

// =============================================
// ELIMINAR GASTO NORMAL
// =============================================
if (isset($_GET['txID'])) {
   $txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";

   $sentencia = $conexion->prepare("DELETE FROM tbl_gastos WHERE id=:id");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->execute();

   $_SESSION['mensaje'] = "✅ Gasto eliminado correctamente";
   $_SESSION['tipo_mensaje'] = "success";

   $mes_actual = isset($_GET['mes']) ? $_GET['mes'] : $_SESSION['mes'];
   $anio_actual = isset($_GET['anio']) ? $_GET['anio'] : $_SESSION['anio'];
   $periodo_actual = isset($_GET['periodo']) ? $_GET['periodo'] : '15';

   header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $mes_actual . "&anio=" . $anio_actual . "&periodo=" . $periodo_actual);
   exit;
}

// =============================================
// EDITAR MONTO DE GASTO
// =============================================
if ($_POST && isset($_POST['editar_gasto'])) {
   $id_gasto = isset($_POST["id_gasto"]) ? $_POST["id_gasto"] : "";
   $nuevo_monto = isset($_POST["nuevo_monto"]) ? $_POST["nuevo_monto"] : "";
   $nuevos_detalles = isset($_POST["nuevos_detalles"]) ? $_POST["nuevos_detalles"] : "";
   $nueva_quincena = isset($_POST["nueva_quincena"]) ? $_POST["nueva_quincena"] : "";

   try {
      $sentencia = $conexion->prepare("UPDATE tbl_gastos SET monto = :monto, detalles = :detalles, quincena = :quincena WHERE id = :id");
      $sentencia->bindParam(":monto", $nuevo_monto);
      $sentencia->bindParam(":detalles", $nuevos_detalles);
      $sentencia->bindParam(":quincena", $nueva_quincena);
      $sentencia->bindParam(":id", $id_gasto);
      $sentencia->execute();

      $_SESSION['mensaje'] = "✅ Gasto actualizado correctamente";
      $_SESSION['tipo_mensaje'] = "success";
   } catch (Exception $e) {
      $_SESSION['mensaje'] = "❌ Error al actualizar el gasto: " . $e->getMessage();
      $_SESSION['tipo_mensaje'] = "danger";
   }

   $mes_actual = isset($_GET['mes']) ? $_GET['mes'] : $_SESSION['mes'];
   $anio_actual = isset($_GET['anio']) ? $_GET['anio'] : $_SESSION['anio'];
   $periodo_actual = isset($_GET['periodo']) ? $_GET['periodo'] : '15';

   header("Location: " . $_SERVER['PHP_SELF'] . "?mes=" . $mes_actual . "&anio=" . $anio_actual . "&periodo=" . $periodo_actual);
   exit;
}

// =============================================
// CONFIGURACIÓN INICIAL Y FILTROS
// =============================================

// Mostrar mensajes de sesión
if (isset($_SESSION['mensaje'])) {
   echo "<div class='alert alert-{$_SESSION['tipo_mensaje']} alert-dismissible fade show' role='alert'>
            {$_SESSION['mensaje']}
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
          </div>";
   unset($_SESSION['mensaje']);
   unset($_SESSION['tipo_mensaje']);
}

// Configuración de filtros
$meses = [
   'Enero',
   'Febrero',
   'Marzo',
   'Abril',
   'Mayo',
   'Junio',
   'Julio',
   'Agosto',
   'Septiembre',
   'Octubre',
   'Noviembre',
   'Diciembre'
];

$anios = range(2025, date('Y') + 15);

// Obtener filtros o usar valores por defecto
$mes_filtro = isset($_GET['mes']) ? $_GET['mes'] : date('F');
$anio_filtro = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
$periodo_filtro = isset($_GET['periodo']) ? $_GET['periodo'] : '15';

$idcondominio = $_SESSION['idcondominio'];

// Si no hay filtros en URL, usar valores actuales
if (!isset($_GET['mes']) && !isset($_GET['anio']) && !isset($_GET['periodo'])) {
   $mes_filtro = date('F');
   $anio_filtro = date('Y');
   $periodo_filtro = '15';
}

// Obtener gastos predeterminados
$sentencia_predeterminados = $conexion->prepare("SELECT * FROM tbl_gastos_predeterminados ORDER BY tipo_gasto, detalles");
$sentencia_predeterminados->execute();
$gastos_predeterminados = $sentencia_predeterminados->fetchAll(PDO::FETCH_ASSOC);

// Contar gastos predeterminados activos
$gastos_activos = 0;
foreach ($gastos_predeterminados as $gasto) {
   if ($gasto['activo']) $gastos_activos++;
}

// ... (El resto del código de funciones y consultas permanece igual)
// FUNCIÓN PARA OBTENER GASTOS POR TIPO, MES, AÑO Y PERIODO
function obtenerGastosPorTipo($conexion, $tipo_gasto, $idcondominio, $mes, $anio, $periodo)
{
   if ($periodo == 'completo') {
      $sentencia = $conexion->prepare("SELECT * FROM tbl_gastos 
                                       WHERE id_condominio=:id 
                                       AND tipo_gasto=:tipo_gasto 
                                       AND mes=:mes 
                                       AND anio=:anio
                                       ORDER BY quincena, id DESC");
   } else {
      $sentencia = $conexion->prepare("SELECT * FROM tbl_gastos 
                                       WHERE id_condominio=:id 
                                       AND tipo_gasto=:tipo_gasto 
                                       AND mes=:mes 
                                       AND anio=:anio
                                       AND quincena=:periodo
                                       ORDER BY id DESC");
   }

   $sentencia->bindParam(":id", $idcondominio);
   $sentencia->bindParam(":tipo_gasto", $tipo_gasto);
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $anio);
   if ($periodo != 'completo') {
      $sentencia->bindParam(":periodo", $periodo);
   }
   $sentencia->execute();
   return $sentencia->fetchAll((PDO::FETCH_ASSOC));
}

// FUNCIÓN PARA CALCULAR TOTALES
function calcularTotalGastos($conexion, $idcondominio, $mes, $anio, $periodo)
{
   if ($periodo == 'completo') {
      $sentencia = $conexion->prepare("SELECT 
            SUM(CASE WHEN quincena = '15' THEN CAST(monto AS DECIMAL(10,2)) ELSE 0 END) as total_15,
            SUM(CASE WHEN quincena = '30' THEN CAST(monto AS DECIMAL(10,2)) ELSE 0 END) as total_30,
            SUM(CAST(monto AS DECIMAL(10,2))) as total_completo
            FROM tbl_gastos 
            WHERE id_condominio=:id 
            AND mes=:mes 
            AND anio=:anio");
   } else {
      $sentencia = $conexion->prepare("SELECT SUM(CAST(monto AS DECIMAL(10,2))) as total 
                                       FROM tbl_gastos 
                                       WHERE id_condominio=:id 
                                       AND mes=:mes 
                                       AND anio=:anio
                                       AND quincena=:periodo");
   }

   $sentencia->bindParam(":id", $idcondominio);
   $sentencia->bindParam(":mes", $mes);
   $sentencia->bindParam(":anio", $anio);
   if ($periodo != 'completo') {
      $sentencia->bindParam(":periodo", $periodo);
   }
   $sentencia->execute();
   return $sentencia->fetch(PDO::FETCH_ASSOC);
}

// Calcular totales
$totales = calcularTotalGastos($conexion, $idcondominio, $mes_filtro, $anio_filtro, $periodo_filtro);

// Obtener todos los tipos de gastos para las tablas
$tipos_gastos = [
   'Nomina_Empleados' => 'Nómina Empleados',
   'Servicios_Basicos' => 'Servicios Básicos',
   'Gastos_Menores_Material_Gastable' => 'Gastos Menores, Material Gastable',
   'lmprevistos' => 'Imprevistos',
   'Cargos_Bancarios' => 'Cargos Bancarios',
   'Servicios_lgualados' => 'Servicios Igualados'
];

// Obtener gastos para cada tipo
$gastos_por_tipo = [];
foreach ($tipos_gastos as $tipo_key => $tipo_nombre) {
   $gastos_por_tipo[$tipo_key] = obtenerGastosPorTipo($conexion, $tipo_key, $idcondominio, $mes_filtro, $anio_filtro, $periodo_filtro);
}
?>

<br>
<div class="card">
   <div class="card-header text-center bg-dark text-white">
      <h2>CONTROL DE GASTOS</h2>
   </div>
   <div class="card-body">

      <!-- FILTROS DE MES, AÑO Y PERIODO -->
      <div class="row mb-4">
         <div class="col-md-12">
            <div class="card">
               <div class="card-header bg-secondary text-white">
                  <h5 class="mb-0">🔍 Filtros de Búsqueda</h5>
               </div>
               <?php
               // Obtener mes y año actual
               $mes_actual = date('n'); // 1 a 12
               $anio_actual = date('Y');
               $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
               ?>
               <div class="card-body">
                  <form action="" method="GET" class="row g-3">
                     <div class="col-md-4">
                        <label for="mes" class="form-label fw-bold">Mes:</label>
                        <select name="mes" id="mes" class="form-select">

                           <?php
                           foreach ($meses as $index => $mes) {
                              $numero_mes = $index + 1;
                              $selected = ($numero_mes == $mes_actual) ? 'selected' : '';
                              echo "<option value='$mes' $selected>$mes</option>";
                           }
                           ?>
                        </select>
                     </div>
                     <div class="col-md-3">
                        <label for="anio" class="form-label fw-bold">Año:</label>
                        <select name="anio" id="anio" class="form-select">
                           <?php foreach ($anios as $anio_option): ?>
                              <option value="<?php echo $anio_option; ?>"
                                 <?php echo $anio_filtro == $anio_option ? 'selected' : ''; ?>>
                                 <?php echo $anio_option; ?>
                              </option>
                           <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="col-md-3">
                        <label for="periodo" class="form-label fw-bold">Período:</label>
                        <select name="periodo" id="periodo" class="form-select">
                           <option value="15" <?php echo $periodo_filtro == '15' ? 'selected' : ''; ?>>Quincena 1-15</option>
                           <option value="30" <?php echo $periodo_filtro == '30' ? 'selected' : ''; ?>>Quincena 16-30</option>
                           <option value="completo" <?php echo $periodo_filtro == 'completo' ? 'selected' : ''; ?>>Mes Completo</option>
                        </select>
                     </div>
                     <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>

      <!-- BOTONES PRINCIPALES -->
      <div class="row mb-4">
         <div class="col-md-12">
            <div class="d-flex gap-2 flex-wrap">
               <!-- Botón Nuevo Gasto -->
               <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoGasto">
                  ➕ NUEVO GASTO
               </button>

               <!-- Botón Gastos Predeterminados -->
               <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalGastosPredeterminados">
                  🔧 GASTOS PREDETERMINADOS
               </button>

               <!-- Botón Generar Gastos del Mes -->
               <button type="button" class="btn btn-info" onclick="confirmarGeneracion()">
                  ⚡ GENERAR GASTOS DEL MES
               </button>

               <!-- Información de gastos predeterminados -->
               <div class="ms-auto">
                  <span class="badge bg-secondary fs-6">
                     📋 <?php echo $gastos_activos; ?> gastos predeterminados activos
                  </span>
               </div>
            </div>
         </div>
      </div>

      <!-- RESUMEN DE TOTALES -->
      <div class="row mb-4">
         <div class="col-md-12">
            <div class="card">
               <div class="card-body py-2">
                  <h5 class="mb-0 text-center">
                     <?php if ($periodo_filtro == 'completo'): ?>
                        Total Mes: <span class="text-success">RD$ <?php echo number_format($totales['total_completo'] ?? 0, 2, '.', ','); ?></span>
                     <?php else: ?>
                        Total Quincena <?php echo $periodo_filtro == '15' ? '1-15' : '16-30'; ?>:
                        <span class="text-primary">RD$ <?php echo number_format($totales['total'] ?? 0, 2, '.', ','); ?></span>
                     <?php endif; ?>
                  </h5>
               </div>
            </div>
         </div>
      </div>

      <!-- ============================================= -->
      <!-- MODAL NUEVO GASTO NORMAL -->
      <!-- ============================================= -->
      <div class="modal fade" id="modalNuevoGasto" tabindex="-1" aria-labelledby="modalNuevoGastoLabel" aria-hidden="true">
         <div class="modal-dialog">
            <div class="modal-content">
               <div class="modal-header bg-dark text-white">
                  <h2 class="modal-title" id="modalNuevoGastoLabel">REGISTRAR NUEVO GASTO</h2>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
               </div>
               <div class="modal-body">
                  <form action="" method="post" id="formNuevoGasto">
                     <div class="mb-3">
                        <label for="sel_mas_menos" class="form-label fw-bold">Tipo de Gasto:</label>
                        <select name="sel_mas_menos" id="sel_mas_menos" class="form-select" required>
                           <option value="">Seleccione un tipo...</option>
                           <?php foreach ($tipos_gastos as $key => $nombre): ?>
                              <option value="<?php echo $key; ?>"><?php echo $nombre; ?></option>
                           <?php endforeach; ?>
                        </select>
                     </div>

                     <div class="mb-3">
                        <label for="quincena" class="form-label fw-bold">Quincena:</label>
                        <select name="quincena" id="quincena" class="form-select" required>
                           <option value="15">Quincena 1-15</option>
                           <option value="30">Quincena 16-30</option>
                        </select>
                     </div>

                     <div class="mb-3">
                        <label for="detalles" class="form-label fw-bold">Detalles:</label>
                        <input type="text" class="form-control" name="detalles" id="detalles"
                           placeholder="Introducir detalles del gasto" required>
                     </div>

                     <div class="mb-3">
                        <label for="monto" class="form-label fw-bold">Monto (RD$):</label>
                        <input type="number" step="0.01" class="form-control" name="monto" id="monto"
                           placeholder="0.00" required>
                     </div>

                     <!-- Campos ocultos para mes y año actual del filtro -->
                     <input type="hidden" name="mes" value="<?php echo $mes_filtro; ?>">
                     <input type="hidden" name="anio" value="<?php echo $anio_filtro; ?>">
                  </form>
               </div>
               <div class="modal-footer bg-dark text-white">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <button type="submit" form="formNuevoGasto" class="btn btn-success">💾 Guardar Gasto</button>
               </div>
            </div>
         </div>
      </div>

      <!-- ============================================= -->
      <!-- MODAL GASTOS PREDETERMINADOS -->
      <!-- ============================================= -->
      <div class="modal fade" id="modalGastosPredeterminados" tabindex="-1" aria-labelledby="modalGastosPredeterminadosLabel" aria-hidden="true">
         <div class="modal-dialog modal-lg">
            <div class="modal-content">
               <div class="modal-header bg-warning text-dark">
                  <h2 class="modal-title" id="modalGastosPredeterminadosLabel">🔧 GASTOS PREDETERMINADOS</h2>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
               </div>
               <div class="modal-body">
                  <!-- Formulario para agregar nuevo gasto predeterminado -->
                  <div class="card mb-4">
                     <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">➕ Agregar Nuevo Gasto Predeterminado</h5>
                     </div>
                     <div class="card-body">
                        <form action="" method="post" id="formNuevoPredeterminado">
                           <input type="hidden" name="es_predeterminado" value="1">
                           <div class="row">
                              <div class="col-md-4">
                                 <label for="sel_mas_menos_pred" class="form-label fw-bold">Tipo de Gasto:</label>
                                 <select name="sel_mas_menos" id="sel_mas_menos_pred" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($tipos_gastos as $key => $nombre): ?>
                                       <option value="<?php echo $key; ?>"><?php echo $nombre; ?></option>
                                    <?php endforeach; ?>
                                 </select>
                              </div>
                              <div class="col-md-4">
                                 <label for="detalles_pred" class="form-label fw-bold">Detalles:</label>
                                 <input type="text" class="form-control" name="detalles" id="detalles_pred"
                                    placeholder="Detalles del gasto" required>
                              </div>
                              <div class="col-md-3">
                                 <label for="monto_pred" class="form-label fw-bold">Monto (RD$):</label>
                                 <input type="number" step="0.01" class="form-control" name="monto" id="monto_pred"
                                    placeholder="0.00" required>
                              </div>
                              <div class="col-md-1 d-flex align-items-end">
                                 <button type="submit" class="btn btn-success">➕</button>
                              </div>
                           </div>
                        </form>
                     </div>
                  </div>
                  <!-- Lista de gastos predeterminados -->
                  <div class="card">
                     <div class="card-header bg-info text-white">
                        <h5 class="mb-0">📋 Lista de Gastos Predeterminados</h5>
                     </div>
                     <div class="card-body">
                        <?php if (count($gastos_predeterminados) > 0): ?>
                           <div class="table-responsive">
                              <table class="table table-striped">
                                 <thead>
                                    <tr>
                                       <th>Activo</th>
                                       <th>Tipo</th>
                                       <th>Detalles</th>
                                       <th>Monto</th>
                                       <th>Quincena</th>
                                       <th>Acciones</th>
                                    </tr>
                                 </thead>
                                 <tbody>
                                    <?php foreach ($gastos_predeterminados as $gasto): ?>
                                       <tr>
                                          <td>
                                             <a href="?toggle_predeterminado=<?php echo $gasto['id']; ?>&mes=<?php echo $mes_filtro; ?>&anio=<?php echo $anio_filtro; ?>&periodo=<?php echo $periodo_filtro; ?>"
                                                class="btn btn-sm <?php echo $gasto['activo'] ? 'btn-success' : 'btn-secondary'; ?>">
                                                <?php echo $gasto['activo'] ? '✅' : '❌'; ?>
                                             </a>
                                          </td>
                                          <td><?php echo $tipos_gastos[$gasto['tipo_gasto']] ?? $gasto['tipo_gasto']; ?></td>
                                          <td><?php echo $gasto['detalles']; ?></td>
                                          <td><strong>RD$ <?php echo number_format($gasto['monto'], 2, '.', ','); ?></strong></td>
                                          <td>
                                             <span class="badge bg-info"><?php echo $gasto['quincena'] == '15' ? '1-15' : '16-30'; ?></span>
                                          </td>
                                          <td>
                                             <a href="?eliminar_predeterminado=<?php echo $gasto['id']; ?>&mes=<?php echo $mes_filtro; ?>&anio=<?php echo $anio_filtro; ?>&periodo=<?php echo $periodo_filtro; ?>"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('¿Estás seguro de eliminar este gasto predeterminado?')">
                                                🗑️
                                             </a>
                                          </td>
                                       </tr>
                                    <?php endforeach; ?>
                                 </tbody>
                              </table>
                           </div>
                        <?php else: ?>
                           <div class="text-center text-muted py-4">
                              <p>No hay gastos predeterminados configurados</p>
                              <p class="small">Agrega gastos predeterminados para generarlos automáticamente cada mes</p>
                           </div>
                        <?php endif; ?>
                     </div>
                  </div>
               </div>
               <div class="modal-footer bg-warning text-dark">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
               </div>
            </div>
         </div>
      </div>

      <!-- ============================================= -->
      <!-- MODAL EDITAR GASTO -->
      <!-- ============================================= -->
      <div class="modal fade" id="modalEditarGasto" tabindex="-1" aria-labelledby="modalEditarGastoLabel" aria-hidden="true">
         <div class="modal-dialog">
            <div class="modal-content">
               <div class="modal-header bg-warning text-dark">
                  <h2 class="modal-title" id="modalEditarGastoLabel">✏️ EDITAR GASTO</h2>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
               </div>
               <div class="modal-body">
                  <form action="" method="post" id="formEditarGasto">
                     <input type="hidden" name="editar_gasto" value="1">
                     <input type="hidden" name="id_gasto" id="gasto_id">

                     <div class="mb-3">
                        <label class="form-label fw-bold">Tipo de Gasto:</label>
                        <input type="text" class="form-control" id="gasto_tipo_display" readonly>
                        <small class="text-muted">El tipo de gasto no se puede modificar</small>
                     </div>

                     <div class="mb-3">
                        <label for="nuevos_detalles" class="form-label fw-bold">Detalles:</label>
                        <input type="text" class="form-control" name="nuevos_detalles" id="gasto_detalles"
                           placeholder="Detalles del gasto" required>
                     </div>

                     <div class="mb-3">
                        <label for="nuevo_monto" class="form-label fw-bold">Monto (RD$):</label>
                        <input type="number" step="0.01" class="form-control" name="nuevo_monto" id="gasto_monto"
                           placeholder="0.00" required>
                     </div>

                     <div class="mb-3">
                        <label for="nueva_quincena" class="form-label fw-bold">Quincena:</label>
                        <select name="nueva_quincena" id="gasta_quincena" class="form-select" required>
                           <option value="15">Quincena 1-15</option>
                           <option value="30">Quincena 16-30</option>
                        </select>
                     </div>
                  </form>
               </div>
               <div class="modal-footer bg-warning text-dark">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <button type="submit" form="formEditarGasto" class="btn btn-success">💾 Guardar Cambios</button>
               </div>
            </div>
         </div>
      </div>
      <!-- INFORMACIÓN DEL FILTRO ACTUAL -->
      <div class="alert alert-info">
         <strong>
            📅 Mostrando gastos de:
            <?php echo $mes_filtro . ' ' . $anio_filtro; ?> -
            <?php
            if ($periodo_filtro == '15') {
               echo 'Quincena 1-15';
            } elseif ($periodo_filtro == '30') {
               echo 'Quincena 16-30';
            } else {
               echo 'Mes Completo (1-15 + 16-30)';
            }
            ?>
         </strong>
      </div>

      <!-- TABLAS SEPARADAS POR TIPO DE GASTO -->
      <?php foreach ($tipos_gastos as $tipo_key => $tipo_nombre):
         $gastos = $gastos_por_tipo[$tipo_key];
         if (count($gastos) > 0 || $periodo_filtro == 'completo'): ?>
            <div class="card mb-4">
               <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                  <h4 class="mb-0"><?php echo $tipo_nombre; ?></h4>
                  <?php
                  $subtotal = 0;
                  foreach ($gastos as $gasto) {
                     $subtotal += floatval($gasto['monto']);
                  }
                  if ($subtotal > 0): ?>
                     <span class="badge bg-primary fs-6">RD$ <?php echo number_format($subtotal, 2, '.', ','); ?></span>
                  <?php endif; ?>
               </div>
               <div class="card-body">
                  <?php if (count($gastos) > 0): ?>
                     <div class="table-responsive-sm">
                        <table class="table table-striped">
                           <thead>
                              <tr>
                                 <th scope="col">Detalles</th>
                                 <th scope="col">Monto</th>
                                 <th scope="col">Quincena</th>
                                 <th scope="col">Acciones</th>
                              </tr>
                           </thead>
                           <tbody>
                              <?php foreach ($gastos as $registro): ?>
                                 <tr>
                                    <td><?php echo $registro['detalles']; ?></td>
                                    <td><strong>RD$ <?php echo number_format(floatval($registro['monto']), 2, '.', ','); ?></strong></td>
                                    <td>
                                       <span class="badge bg-<?php echo $registro['quincena'] == '15' ? 'info' : 'warning'; ?>">
                                          <?php echo $registro['quincena'] == '15' ? '1-15' : '16-30'; ?>
                                       </span>
                                    </td>
                                    <td>
                                       <div class="btn-group btn-group-sm" role="group">
                                          <!-- Botón Editar -->
                                          <button class="btn btn-warning btn-sm"
                                             data-bs-toggle="modal"
                                             data-bs-target="#modalEditarGasto"
                                             data-id="<?php echo $registro['id']; ?>"
                                             data-detalles="<?php echo htmlspecialchars($registro['detalles']); ?>"
                                             data-monto="<?php echo $registro['monto']; ?>"
                                             data-quincena="<?php echo $registro['quincena']; ?>"
                                             data-tipo="<?php echo $registro['tipo_gasto']; ?>"
                                             title="Editar gasto">
                                             ✏️
                                          </button>

                                          <!-- Botón Eliminar -->
                                          <a class="btn btn-danger btn-sm"
                                             href="javascript:borrar(<?php echo $registro['id']; ?>, '<?php echo $mes_filtro; ?>', '<?php echo $anio_filtro; ?>', '<?php echo $periodo_filtro; ?>');"
                                             role="button"
                                             title="Eliminar gasto">
                                             ❌
                                          </a>
                                       </div>
                                    </td>
                                 </tr>
                              <?php endforeach; ?>
                           </tbody>
                        </table>
                     </div>
                  <?php else: ?>
                     <div class="text-center text-muted py-3">
                        <p>No hay gastos registrados para <?php echo $tipo_nombre; ?> en el período seleccionado</p>
                     </div>
                  <?php endif; ?>
               </div>
            </div>
         <?php endif; ?>
      <?php endforeach; ?>

   </div>
</div>
<br>

<script>
   function borrar(id, mes, anio, periodo) {
      Swal.fire({
         title: '¿Quieres borrar este gasto?',
         text: "Esta acción no se puede deshacer",
         icon: 'warning',
         showCancelButton: true,
         confirmButtonColor: '#d33',
         cancelButtonColor: '#3085d6',
         confirmButtonText: 'Sí, borrar',
         cancelButtonText: 'Cancelar'
      }).then((result) => {
         if (result.isConfirmed) {
            window.location = "index.php?txID=" + id + "&mes=" + mes + "&anio=" + anio + "&periodo=" + periodo;
         }
      });
   }

   function confirmarGeneracion() {
      Swal.fire({
         title: '¿Generar gastos del mes?',
         html: `Esta acción insertará todos los gastos predeterminados <strong>activos</strong> para:<br>
                  <strong><?php echo $mes_filtro . ' ' . $anio_filtro; ?> - Quincena 1-15</strong><br><br>
                  <small class="text-muted">Solo se insertarán los gastos que no existan previamente en este mes</small>`,
         icon: 'question',
         showCancelButton: true,
         confirmButtonColor: '#3085d6',
         cancelButtonColor: '#d33',
         confirmButtonText: 'Sí, generar gastos',
         cancelButtonText: 'Cancelar'
      }).then((result) => {
         if (result.isConfirmed) {
            window.location = "?generar_gastos=1&mes=<?php echo $mes_filtro; ?>&anio=<?php echo $anio_filtro; ?>&periodo=15";
         }
      });
   }

   // Script para el modal de edición de gastos
   document.addEventListener('DOMContentLoaded', function() {
      const modalEditarGasto = document.getElementById('modalEditarGasto');

      if (modalEditarGasto) {
         modalEditarGasto.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;

            // Obtener los datos del gasto
            const gastoId = button.getAttribute('data-id');
            const gastoDetalles = button.getAttribute('data-detalles');
            const gastoMonto = button.getAttribute('data-monto');
            const gastoQuincena = button.getAttribute('data-quincena');
            const gastoTipo = button.getAttribute('data-tipo');

            // Obtener el nombre del tipo de gasto
            const tipoGastoNombre = getTipoGastoNombre(gastoTipo);

            // Llenar el formulario
            document.getElementById('gasto_id').value = gastoId;
            document.getElementById('gasto_detalles').value = gastoDetalles;
            document.getElementById('gasto_monto').value = gastoMonto;
            document.getElementById('gasta_quincena').value = gastoQuincena;
            document.getElementById('gasto_tipo_display').value = tipoGastoNombre;
         });
      }

      // Función para obtener el nombre del tipo de gasto
      function getTipoGastoNombre(tipoKey) {
         const tipos = {
            'Nomina_Empleados': 'Nómina Empleados',
            'Servicios_Basicos': 'Servicios Básicos',
            'Gastos_Menores_Material_Gastable': 'Gastos Menores, Material Gastable',
            'lmprevistos': 'Imprevistos',
            'Cargos_Bancarios': 'Cargos Bancarios',
            'Servicios_lgualados': 'Servicios Igualados'
         };
         return tipos[tipoKey] || tipoKey;
      }
   });

   function borrar(id, mes, anio, periodo) {
      Swal.fire({
         title: '¿Eliminar este gasto?',
         text: "Esta acción no se puede deshacer",
         icon: 'warning',
         showCancelButton: true,
         confirmButtonColor: '#d33',
         cancelButtonColor: '#3085d6',
         confirmButtonText: 'Sí, eliminar',
         cancelButtonText: 'Cancelar',
         focusCancel: true
      }).then((result) => {
         if (result.isConfirmed) {
            window.location = "index.php?txID=" + id + "&mes=" + mes + "&anio=" + anio + "&periodo=" + periodo;
         }
      });
   }

   // Auto-seleccionar quincena según fecha actual en el modal
   document.addEventListener('DOMContentLoaded', function() {
      const diaActual = new Date().getDate();
      const quincenaSelect = document.getElementById('quincena');
      if (quincenaSelect) {
         quincenaSelect.value = diaActual <= 15 ? '15' : '30';
      }
   });
</script>

<?php include("../../templates/footer.php"); ?>