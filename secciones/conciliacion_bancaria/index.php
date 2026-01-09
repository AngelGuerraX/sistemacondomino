<?php include("../../templates/header.php"); ?>
<?php
include("../../bd.php");

// =============================================
// CONFIGURACIÓN DE FILTROS
// =============================================

// Configurar meses en español
$meses_espanol = [
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

$anios = range(date('Y') - 1, date('Y') + 1);

// Obtener filtros o usar valores por defecto
$mes_filtro = isset($_GET['mes']) ? $_GET['mes'] : $meses_espanol[date('n') - 1];
$anio_filtro = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

$idcondominio = $_SESSION['idcondominio'];

// =============================================
// ELIMINAR CHEQUE
// =============================================

if (isset($_GET['txID'])) {
   $txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";
   $sentencia = $conexion->prepare("DELETE FROM tbl_cheques WHERE id=:id");
   $sentencia->bindParam(":id", $txtID);
   $sentencia->execute();

   $_SESSION['mensaje'] = "Cheque eliminado exitosamente";
   $_SESSION['tipo_mensaje'] = "success";
   header("Location: index.php?mes=" . $mes_filtro . "&anio=" . $anio_filtro);
   exit();
}

// =============================================
// OBTENER DATOS CON FILTROS
// =============================================

// Cheques Mas (+) Depositos en Transito
$sentencia = $conexion->prepare("SELECT * FROM tbl_cheques where idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='mas'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes_filtro);
$sentencia->bindParam(":anio", $anio_filtro);
$sentencia->execute();
$lista_cheque = $sentencia->fetchAll((PDO::FETCH_ASSOC));

// Cheques Menos (-) Cheques en Transito
$sentencia = $conexion->prepare("SELECT * FROM tbl_cheques where idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='menos'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes_filtro);
$sentencia->bindParam(":anio", $anio_filtro);
$sentencia->execute();
$lista_cheque2 = $sentencia->fetchAll((PDO::FETCH_ASSOC));

// Cheques Nulos
$sentencia = $conexion->prepare("SELECT * FROM tbl_cheques where idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='nulo'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes_filtro);
$sentencia->bindParam(":anio", $anio_filtro);
$sentencia->execute();
$lista_chequenulo = $sentencia->fetchAll((PDO::FETCH_ASSOC));

// Conciliación Bancaria
$sentencia = $conexion->prepare("SELECT * FROM tbl_conciliacion_bancaria WHERE id_condominio=:idcondominio and mes=:mes and anio=:anio");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes_filtro);
$sentencia->bindParam(":anio", $anio_filtro);
$sentencia->execute();
$lista_cheque3 = $sentencia->fetchAll((PDO::FETCH_ASSOC));

// Suma de cheques Menos (-)
$sentencia = $conexion->prepare("SELECT SUM(monto) as montosum FROM tbl_cheques WHERE idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='menos'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes_filtro);
$sentencia->bindParam(":anio", $anio_filtro);
$sentencia->execute();
$registro = $sentencia->fetch(PDO::FETCH_LAZY);
$sumamas = $registro["montosum"] ?? 0;

// Suma de cheques Mas (+)
$sentencia = $conexion->prepare("SELECT SUM(monto) as montosum FROM tbl_cheques WHERE idcondominio=:idcondominio and mes=:mes and anio=:anio and tipo_cheque='mas'");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes_filtro);
$sentencia->bindParam(":anio", $anio_filtro);
$sentencia->execute();
$registro = $sentencia->fetch(PDO::FETCH_LAZY);
$sumamenos = $registro["montosum"] ?? 0;

// Datos de conciliación existente
$sentencia = $conexion->prepare("SELECT * FROM tbl_conciliacion_bancaria WHERE id_condominio=:idcondominio and mes=:mes and anio=:anio");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes", $mes_filtro);
$sentencia->bindParam(":anio", $anio_filtro);
$sentencia->execute();
$conciliacion_existente = $sentencia->fetch(PDO::FETCH_ASSOC);

// =============================================
// PROCESAR FORMULARIOS
// =============================================

if ($_POST) {

   // Procesar creación de cheque desde el modal
   if (isset($_POST['crear_cheque'])) {
      $detalle = (isset($_POST["detalle"]) ? $_POST["detalle"] : "");
      $monto = (isset($_POST["monto"]) ? $_POST["monto"] : "");
      $tipo_cheque = $_POST["sel_mas_menos"];

      $sentencia = $conexion->prepare("INSERT INTO tbl_cheques (detalle,tipo_cheque,monto,idcondominio,mes,anio)
      VALUES (:detalle, :tipo_cheque, :monto, :idcondominio, :mes, :anio)");

      $sentencia->bindParam(":detalle", $detalle);
      $sentencia->bindParam(":tipo_cheque", $tipo_cheque);
      $sentencia->bindParam(":monto", $monto);
      $sentencia->bindParam(":idcondominio", $idcondominio);
      $sentencia->bindParam(":mes", $mes_filtro);
      $sentencia->bindParam(":anio", $anio_filtro);
      $sentencia->execute();

      $_SESSION['mensaje'] = "Cheque creado exitosamente";
      $_SESSION['tipo_mensaje'] = "success";
      header("Location: index.php?mes=" . $mes_filtro . "&anio=" . $anio_filtro);
      exit();
   }

   // Procesar conciliación bancaria
   $sentencia = $conexion->prepare("SELECT *,count(*) as existentes
   FROM tbl_conciliacion_bancaria
   WHERE mes= :mes AND anio=:anio AND id_condominio=:id_condominio
   ");

   $sentencia->bindParam(":mes", $mes_filtro);
   $sentencia->bindParam(":anio", $anio_filtro);
   $sentencia->bindParam(":id_condominio", $idcondominio);
   $sentencia->execute();
   $registro = $sentencia->fetch(PDO::FETCH_LAZY);

   if ($registro["existentes"] > 0) {
      $balancesbanco = (isset($_POST["balancesbanco"]) ? $_POST["balancesbanco"] : "");
      $mat = (isset($_POST["mat"]) ? $_POST["mat"] : "");
      $menost = (isset($_POST["menost"]) ? $_POST["menost"] : "");
      $balanceconciliado = (isset($_POST["balanceconciliado"]) ? $_POST["balanceconciliado"] : "");
      $balanceslibro = (isset($_POST["balanceslibro"]) ? $_POST["balanceslibro"] : "");
      $cargo_bancario = (isset($_POST["cargo_bancario"]) ? $_POST["cargo_bancario"] : "");

      $sentencia = $conexion->prepare("UPDATE tbl_conciliacion_bancaria SET balance_banco=:balancesbanco, mas_en_transito=:mat, menos_en_transito=:menost, balance_conciliado=:balanceconciliado, balance_libro=:balanceslibro, menos_cargos_bancarios=:cargo_bancario 
      WHERE mes= :mes AND anio=:anio AND id_condominio=:id_condominio");

      $sentencia->bindParam(":balancesbanco", $balancesbanco);
      $sentencia->bindParam(":mat", $mat);
      $sentencia->bindParam(":menost", $menost);
      $sentencia->bindParam(":balanceconciliado", $balanceconciliado);
      $sentencia->bindParam(":balanceslibro", $balanceslibro);
      $sentencia->bindParam(":cargo_bancario", $cargo_bancario);
      $sentencia->bindParam(":mes", $mes_filtro);
      $sentencia->bindParam(":anio", $anio_filtro);
      $sentencia->bindParam(":id_condominio", $idcondominio);
      $sentencia->execute();

      $_SESSION['mensaje'] = "Conciliación actualizada exitosamente";
      $_SESSION['tipo_mensaje'] = "success";
      header("Location: index.php?mes=" . $mes_filtro . "&anio=" . $anio_filtro);
      exit();
   } else {
      $balancesbanco = (isset($_POST["balancesbanco"]) ? $_POST["balancesbanco"] : "");
      $mat = (isset($_POST["mat"]) ? $_POST["mat"] : "");
      $menost = (isset($_POST["menost"]) ? $_POST["menost"] : "");
      $balanceconciliado = (isset($_POST["balanceconciliado"]) ? $_POST["balanceconciliado"] : "");
      $balanceslibro = (isset($_POST["balanceslibro"]) ? $_POST["balanceslibro"] : "");
      $cargo_bancario = (isset($_POST["cargo_bancario"]) ? $_POST["cargo_bancario"] : "");

      $sentencia = $conexion->prepare("INSERT INTO tbl_conciliacion_bancaria (balance_banco,mas_en_transito,menos_en_transito,balance_conciliado,balance_libro,menos_cargos_bancarios,id_condominio,mes,anio)
      VALUES (:balancesbanco, :mat, :menost, :balanceconciliado, :balanceslibro, :cargo_bancario, :id_condominio, :mes, :anio)");

      $sentencia->bindParam(":balancesbanco", $balancesbanco);
      $sentencia->bindParam(":mat", $mat);
      $sentencia->bindParam(":menost", $menost);
      $sentencia->bindParam(":balanceconciliado", $balanceconciliado);
      $sentencia->bindParam(":balanceslibro", $balanceslibro);
      $sentencia->bindParam(":cargo_bancario", $cargo_bancario);
      $sentencia->bindParam(":id_condominio", $idcondominio);
      $sentencia->bindParam(":mes", $mes_filtro);
      $sentencia->bindParam(":anio", $anio_filtro);
      $sentencia->execute();

      $_SESSION['mensaje'] = "Conciliación creada exitosamente";
      $_SESSION['tipo_mensaje'] = "success";
      header("Location: index.php?mes=" . $mes_filtro . "&anio=" . $anio_filtro);
      exit();
   }
}

// =============================================
// MOSTRAR MENSAJES DE SESIÓN
// =============================================

if (isset($_SESSION['mensaje'])) {
   echo "<div class='alert alert-{$_SESSION['tipo_mensaje']} alert-dismissible fade show' role='alert'>
            {$_SESSION['mensaje']}
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
          </div>";
   unset($_SESSION['mensaje']);
   unset($_SESSION['tipo_mensaje']);
}
?>

<br>

<!-- ============================================= -->
<!-- FILTROS DE MES Y AÑO -->
<!-- ============================================= -->
<div class="card">
   <div class="card-header bg-secondary text-white">
      <h5 class="mb-0">🔍 Filtros de Búsqueda</h5>
   </div>
   <div class="card-body">
      <form action="" method="GET" class="row g-3">
         <div class="col-md-6">
            <label for="mes" class="form-label fw-bold">Mes:</label>
            <select name="mes" id="mes" class="form-select">
               <?php foreach ($meses_espanol as $mes): ?>
                  <option value="<?php echo $mes; ?>" <?php echo $mes_filtro == $mes ? 'selected' : ''; ?>>
                     <?php echo $mes; ?>
                  </option>
               <?php endforeach; ?>
            </select>
         </div>
         <div class="col-md-4">
            <label for="anio" class="form-label fw-bold">Año:</label>
            <select name="anio" id="anio" class="form-select">
               <?php foreach ($anios as $anio_option): ?>
                  <option value="<?php echo $anio_option; ?>" <?php echo $anio_filtro == $anio_option ? 'selected' : ''; ?>>
                     <?php echo $anio_option; ?>
                  </option>
               <?php endforeach; ?>
            </select>
         </div>
         <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
         </div>
      </form>
   </div>
</div>

<!-- ============================================= -->
<!-- INFORMACIÓN DEL PERIODO SELECCIONADO -->
<!-- ============================================= -->
<div class="alert alert-info mt-3">
   <strong>
      📅 Mostrando datos de: <?php echo $mes_filtro . ' ' . $anio_filtro; ?>
   </strong>
</div>

<!-- ============================================= -->
<!-- CONCILIACIÓN BANCARIA -->
<!-- ============================================= -->
<div class="card mt-4">
   <div class="card-header bg-dark text-white">
      <h4>Conciliacion Bancaria -
         <a name="" id="" class="btn btn-light"
            href="<?php echo $ruta_base ?>secciones/conciliacion_bancaria/pdf_conciliacion.php?mes=<?php echo $mes_filtro; ?>&anio=<?php echo $anio_filtro; ?>"
            role="button">Generar PDF</a>
      </h4>
   </div>
   <div class="card-body">
      <div class="card-body bg-secondary text-white">
         <form action="" method="post" enctype="multipart/form-data">
            <div class="row">
               <!-- Primera fila -->
               <div class="col-md-6 mb-3">
                  <label for="balancesbanco" class="form-label">Balance Segun Banco:</label>
                  <input type="text" class="form-control" name="balancesbanco" id="balancesbanco"
                     value="<?php echo $conciliacion_existente['balance_banco'] ?? ''; ?>" required>
               </div>
               <div class="col-md-6 mb-3">
                  <label for="cargo_bancario" class="form-label">Cargos Bancarios:</label>
                  <input type="text" class="form-control" name="cargo_bancario" id="cargo_bancario"
                     value="<?php echo $conciliacion_existente['menos_cargos_bancarios'] ?? ''; ?>" required>
               </div>
            </div>

            <div class="row">
               <!-- Segunda fila -->
               <div class="col-md-6 mb-3">
                  <label for="balanceconciliado" class="form-label">
                     Balance Conciliado a <?php echo $mes_filtro; ?> del <?php echo $anio_filtro; ?>:
                  </label>
                  <input type="text" class="form-control" name="balanceconciliado" id="balanceconciliado"
                     value="<?php echo $conciliacion_existente['balance_conciliado'] ?? ''; ?>" readonly>
               </div>
               <div class="col-md-6 mb-3">
                  <label for="balanceslibro" class="form-label">Balance Segun Libro:</label>
                  <input type="text" class="form-control" name="balanceslibro" id="balanceslibro"
                     value="<?php echo $conciliacion_existente['balance_libro'] ?? ''; ?>" readonly>
               </div>
            </div>

            <div class="row">
               <!-- Tercera fila - Depósitos y Cheques en tránsito -->
               <div class="col-md-4 mb-3">
                  <label for="mat" class="form-label">Mas (+) Depositos en transito:</label>
                  <input type="text" class="form-control" name="mat" id="mat"
                     value="<?php
                              $sumamasshow = number_format(floatval($sumamas), 2, '.', ',');
                              echo $sumamasshow;
                              ?>" readonly>
               </div>

               <div class="col-md-4 mb-3">
                  <label for="menost" class="form-label">Menos (-) Cheques en transito:</label>
                  <input type="text" class="form-control" name="menost" id="menost"
                     value="<?php
                              $sumamenosshow = number_format(floatval($sumamenos), 2, '.', ',');
                              echo $sumamenosshow;
                              ?>" readonly>
               </div>

               <div class="col-md-4 mb-3">
                  <label for="transito" class="form-label">En transito:</label>
                  <input type="text" class="form-control" name="transito" id="transito"
                     value="<?php
                              $res = $sumamas - $sumamenos;
                              $resshow = number_format(floatval($res), 2, '.', ',');
                              echo $resshow;
                              ?>" readonly>
               </div>
            </div>

            <!-- Botones de acción -->
            <div class="row mt-4">
               <div class="col-md-12">
                  <button type="submit" class="btn btn-success me-2">
                     <i class="fas fa-save"></i> <?php echo $conciliacion_existente ? 'Actualizar' : 'Guardar'; ?>
                  </button>
                  <a name="" id="" class="btn btn-danger" href="index.php?mes=<?php echo $mes_filtro; ?>&anio=<?php echo $anio_filtro; ?>" role="button">
                     <i class="fas fa-times"></i> Cancelar
                  </a>
               </div>
            </div>
         </form>
      </div>
   </div>
</div>

<!-- ============================================= -->
<!-- RESUMEN CONCILIACIÓN BANCARIA -->
<!-- ============================================= -->
<div class="card mt-4">
   <div class="card-header bg-dark text-white">
      <h4>Resumen Conciliacion Bancaria</h4>
   </div>
   <div class="card-body">
      <div class="table-responsive-sm">
         <table class="table">
            <thead>
               <tr>
                  <th scope="col">Balance Segun Banco</th>
                  <th scope="col">Cargos Bancarios</th>
                  <th scope="col">Balance Conciliado</th>
                  <th scope="col">Balance Segun Libro</th>
               </tr>
            </thead>
            <tbody>
               <?php if (!empty($lista_cheque3)): ?>
                  <?php foreach ($lista_cheque3 as $registro) { ?>
                     <tr class="">
                        <td scope="row"><?php $Balancesbshow = number_format(floatval($registro['balance_banco']), 2, '.', ',');
                                          echo $Balancesbshow; ?></td>
                        <td scope="row"><?php $menos_cargos_bancariosshow = number_format(floatval($registro['menos_cargos_bancarios']), 2, '.', ',');
                                          echo $menos_cargos_bancariosshow; ?></td>
                        <td scope="row"><?php $balance_conciliadoshow = number_format(floatval($registro['balance_conciliado']), 2, '.', ',');
                                          echo $balance_conciliadoshow; ?></td>
                        <td scope="row"><?php $balance_libroshow = number_format(floatval($registro['balance_libro']), 2, '.', ',');
                                          echo $balance_libroshow; ?></td>
                     </tr>
                  <?php } ?>
               <?php else: ?>
                  <tr>
                     <td colspan="4" class="text-center text-muted">No hay datos de conciliación para este período</td>
                  </tr>
               <?php endif; ?>
            </tbody>
         </table>
      </div>
   </div>
</div>

<!-- ============================================= -->
<!-- BOTÓN PARA CREAR CHEQUE -->
<!-- ============================================= -->
<div class="card mt-4">
   <div class="card-body text-center">
      <button type="button" class="btn btn-dark btn-lg" data-bs-toggle="modal" data-bs-target="#modalCrearCheque">
         <i class="fas fa-plus"></i> CREAR CHEQUE
      </button>
   </div>
</div>

<!-- ============================================= -->
<!-- MODAL PARA CREAR CHEQUE -->
<!-- ============================================= -->
<div class="modal fade" id="modalCrearCheque" tabindex="-1" aria-labelledby="modalCrearChequeLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header bg-dark text-white">
            <h5 class="modal-title" id="modalCrearChequeLabel">Crear Nuevo Cheque - <?php echo $mes_filtro . ' ' . $anio_filtro; ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body bg-light text-dark">
            <form action="" method="post" enctype="multipart/form-data">
               <input type="hidden" name="crear_cheque" value="1">

               <div class="mb-3">
                  <label for="sel_mas_menos" class="form-label">Tipo de Cheque:</label>
                  <select name="sel_mas_menos" id="sel_mas_menos" class="form-select" required>
                     <option value="mas">Mas (+) Depositos en Transito</option>
                     <option value="menos">Menos (-) Cheques en Transito</option>
                     <option value="nulo">Cheque nulo</option>
                  </select>
               </div>

               <div class="mb-3">
                  <label for="detalle" class="form-label">Detalles:</label>
                  <input type="text" class="form-control" name="detalle" id="detalle"
                     placeholder="Ej: Cheque No. 0000" value="Cheque No. 0000" required>
               </div>

               <div class="mb-3">
                  <label for="monto" class="form-label">Monto:</label>
                  <input type="number" step="0.01" class="form-control" name="monto" id="monto"
                     placeholder="0.00" value="0" required>
               </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success">Agregar Cheque</button>
            </form>
         </div>
      </div>
   </div>
</div>

<!-- ============================================= -->
<!-- TABLAS DE CHEQUES -->
<!-- ============================================= -->

<!-- Mas (+) Depositos en Transito -->
<div class="card mt-4">
   <div class="card-header bg-dark text-white">
      <h4>Mas (+) Depositos en Transito</h4>
      <small class="text-warning">Total: RD$ <?php echo number_format(floatval($sumamas), 2, '.', ','); ?></small>
   </div>
   <div class="card-body">
      <div class="table-responsive-sm">
         <table class="table">
            <thead>
               <tr>
                  <th scope="col">ID</th>
                  <th scope="col">Detalles</th>
                  <th scope="col">Monto</th>
                  <th scope="col">Acciones</th>
               </tr>
            </thead>
            <tbody>
               <?php if (!empty($lista_cheque)): ?>
                  <?php foreach ($lista_cheque as $registro) { ?>
                     <tr class="">
                        <td scope="row"><?php echo $registro['id'] ?></td>
                        <td scope="row"><?php echo $registro['detalle']; ?></td>
                        <td scope="row"><?php $precio = $registro['monto'];
                                          $precio_formateado = number_format($precio, 2, '.', ',');
                                          echo $precio_formateado ?></td>
                        <td><a class="btn btn-danger btn-sm" href="javascript:borrar(<?php echo $registro['id'] ?>);" role="button">Eliminar</a></td>
                     </tr>
                  <?php } ?>
               <?php else: ?>
                  <tr>
                     <td colspan="4" class="text-center text-muted">No hay cheques de este tipo para el período seleccionado</td>
                  </tr>
               <?php endif; ?>
            </tbody>
         </table>
      </div>
   </div>
</div>

<!-- Menos (-) Cheques en Transito -->
<div class="card mt-4">
   <div class="card-header bg-dark text-white">
      <h4>Menos (-) Cheques en Transito</h4>
      <small class="text-warning">Total: RD$ <?php echo number_format(floatval($sumamenos), 2, '.', ','); ?></small>
   </div>
   <div class="card-body">
      <div class="table-responsive-sm">
         <table class="table">
            <thead>
               <tr>
                  <th scope="col">ID</th>
                  <th scope="col">Detalles</th>
                  <th scope="col">Monto</th>
                  <th scope="col">Acciones</th>
               </tr>
            </thead>
            <tbody>
               <?php if (!empty($lista_cheque2)): ?>
                  <?php foreach ($lista_cheque2 as $registro) { ?>
                     <tr class="">
                        <td scope="row"><?php echo $registro['id'] ?></td>
                        <td scope="row"><?php echo $registro['detalle']; ?></td>
                        <td scope="row"><?php $precio = $registro['monto'];
                                          $precio_formateado = number_format($precio, 2, '.', ',');
                                          echo $precio_formateado ?></td>
                        <td><a class="btn btn-danger btn-sm" href="javascript:borrar(<?php echo $registro['id'] ?>);" role="button">Eliminar</a></td>
                     </tr>
                  <?php } ?>
               <?php else: ?>
                  <tr>
                     <td colspan="4" class="text-center text-muted">No hay cheques de este tipo para el período seleccionado</td>
                  </tr>
               <?php endif; ?>
            </tbody>
         </table>
      </div>
   </div>
</div>

<!-- Cheques Nulos -->
<div class="card mt-4">
   <div class="card-header bg-dark text-white">
      <h4>Cheques Nulos</h4>
   </div>
   <div class="card-body">
      <div class="table-responsive-sm">
         <table class="table">
            <thead>
               <tr>
                  <th scope="col">ID</th>
                  <th scope="col">Detalles</th>
                  <th scope="col">Monto</th>
                  <th scope="col">Acciones</th>
               </tr>
            </thead>
            <tbody>
               <?php if (!empty($lista_chequenulo)): ?>
                  <?php foreach ($lista_chequenulo as $registro) { ?>
                     <tr class="">
                        <td scope="row"><?php echo $registro['id'] ?></td>
                        <td scope="row"><?php echo $registro['detalle']; ?></td>
                        <td scope="row"><?php $precio = $registro['monto'];
                                          $precio_formateado = number_format($precio, 2, '.', ',');
                                          echo $precio_formateado ?></td>
                        <td><a class="btn btn-danger btn-sm" href="javascript:borrar(<?php echo $registro['id'] ?>);" role="button">Eliminar</a></td>
                     </tr>
                  <?php } ?>
               <?php else: ?>
                  <tr>
                     <td colspan="4" class="text-center text-muted">No hay cheques nulos para el período seleccionado</td>
                  </tr>
               <?php endif; ?>
            </tbody>
         </table>
      </div>
   </div>
</div>

<script>
   function borrar(id) {
      Swal.fire({
         title: '¿Quieres borrar el registro?',
         showCancelButton: true,
         confirmButtonText: 'Si, borrar',
         cancelButtonText: 'Cancelar'
      }).then((result) => {
         if (result.isConfirmed) {
            window.location = "index.php?txID=" + id + "&mes=<?php echo $mes_filtro; ?>&anio=<?php echo $anio_filtro; ?>";
         }
      })
   }

   // Limpiar formulario cuando se cierre el modal
   document.getElementById('modalCrearCheque').addEventListener('hidden.bs.modal', function() {
      document.getElementById('detalle').value = 'Cheque No. 0000';
      document.getElementById('monto').value = '0';
      document.getElementById('sel_mas_menos').selectedIndex = 0;
   });

   // Calcular balance conciliado automáticamente
   document.addEventListener('DOMContentLoaded', function() {
      const balanceBanco = document.getElementById('balancesbanco');
      const cargosBancarios = document.getElementById('cargo_bancario');
      const mat = document.getElementById('mat');
      const menost = document.getElementById('menost');
      const balanceConciliado = document.getElementById('balanceconciliado');
      const balanceLibro = document.getElementById('balanceslibro');

      function calcularBalances() {
         const balanceBancoVal = parseFloat(balanceBanco.value) || 0;
         const cargosBancariosVal = parseFloat(cargosBancarios.value) || 0;
         const matVal = parseFloat(mat.value.replace(/,/g, '')) || 0;
         const menostVal = parseFloat(menost.value.replace(/,/g, '')) || 0;

         // Balance Conciliado = Balance Banco - Cargos Bancarios + Depósitos en tránsito - Cheques en tránsito
         const balanceConciliadoVal = balanceBancoVal - cargosBancariosVal + matVal - menostVal;

         // Balance Libro = Balance Conciliado (simplificado)
         const balanceLibroVal = balanceConciliadoVal;

         balanceConciliado.value = balanceConciliadoVal.toFixed(2);
         balanceLibro.value = balanceLibroVal.toFixed(2);
      }

      if (balanceBanco && cargosBancarios) {
         balanceBanco.addEventListener('input', calcularBalances);
         cargosBancarios.addEventListener('input', calcularBalances);
      }
   });
</script>

<?php include("../../templates/footer.php"); ?>