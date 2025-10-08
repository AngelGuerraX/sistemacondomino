<?php
include("../../bd.php");
include("../../templates/header.php");

// Variables de sesión
$mes_actual = $_SESSION['mes'];
$idcondominio = $_SESSION['idcondominio'];
$id_usuario_registro = $_SESSION['usuario'];

// Obtener mes y año actual
$mes_actual_num = date('n');
$anio_actual = date('Y');
$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mes_actual_espanol = $meses[$mes_actual_num - 1];

// Procesar eliminación de registro de gas
if (isset($_GET['eliminar_gas'])) {
   $id_eliminar = $_GET['eliminar_gas'];

   // Obtener información del gas a eliminar
   $sentencia_info = $conexion->prepare("
        SELECT id_apto, mes, anio, gas_insertado 
        FROM tbl_gas 
        WHERE id = :id AND id_condominio = :id_condominio
    ");
   $sentencia_info->bindParam(":id", $id_eliminar);
   $sentencia_info->bindParam(":id_condominio", $idcondominio);
   $sentencia_info->execute();
   $gas_info = $sentencia_info->fetch(PDO::FETCH_ASSOC);

   if ($gas_info) {
      $id_apto_eliminar = $gas_info['id_apto'];
      $mes_eliminar = $gas_info['mes'];
      $anio_eliminar = $gas_info['anio'];

      // Eliminar el registro de gas
      $sentencia_eliminar = $conexion->prepare("
            DELETE FROM tbl_gas 
            WHERE id = :id AND id_condominio = :id_condominio
        ");
      $sentencia_eliminar->bindParam(":id", $id_eliminar);
      $sentencia_eliminar->bindParam(":id_condominio", $idcondominio);
      $sentencia_eliminar->execute();

      // 🔥 CORRECCIÓN: Recalcular el total de gas para este apartamento después de eliminar
      $sentencia_total_gas = $conexion->prepare("
            SELECT SUM(gas_insertado) AS total_gas 
            FROM tbl_gas 
            WHERE id_apto = :id_apto 
            AND mes = :mes 
            AND anio = :anio 
            AND id_condominio = :id_condominio
        ");
      $sentencia_total_gas->bindParam(":id_apto", $id_apto_eliminar);
      $sentencia_total_gas->bindParam(":mes", $mes_eliminar);
      $sentencia_total_gas->bindParam(":anio", $anio_eliminar);
      $sentencia_total_gas->bindParam(":id_condominio", $idcondominio);
      $sentencia_total_gas->execute();
      $nuevo_total_gas = $sentencia_total_gas->fetch(PDO::FETCH_ASSOC)['total_gas'] ?? 0;

      // Actualizar el ticket con el nuevo total de gas
      $actualizar = $conexion->prepare("
            UPDATE tbl_tickets 
            SET gas = :total_gas 
            WHERE id_apto = :id_apto 
            AND mes = :mes 
            AND anio = :anio 
            AND id_condominio = :id_condominio
        ");
      $actualizar->bindParam(":total_gas", $nuevo_total_gas);
      $actualizar->bindParam(":id_apto", $id_apto_eliminar);
      $actualizar->bindParam(":mes", $mes_eliminar);
      $actualizar->bindParam(":anio", $anio_eliminar);
      $actualizar->bindParam(":id_condominio", $idcondominio);
      $actualizar->execute();

      $mensaje_gas = "✅ Registro de gas eliminado y ticket actualizado correctamente.";
   } else {
      $mensaje_gas = "❌ No se encontró el registro de gas a eliminar.";
   }
}

// 1️⃣ Traer los apartamentos
$sentencia = $conexion->prepare("
    SELECT id, apto, id_condominio, condominos 
    FROM tbl_aptos 
    WHERE id_condominio = :idcondominio 
    ORDER BY apto ASC
");
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->execute();
$lista_aptos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

// 2️⃣ Procesar formulario
if ($_POST) {
   $id_usuario_registro = $_POST['id_usuario_registro'];
   $mes = $_POST['el_mes'];
   $anio = $_POST['el_anio'];
   $fecha_registro = date('Y-m-d H:i:s');

   // 🔍 Verificar si existen tickets de ese mes/año en el condominio
   $verificarTickets = $conexion->prepare("
      SELECT COUNT(*) AS total 
      FROM tbl_tickets 
      WHERE id_condominio = :idcondominio 
        AND mes = :mes 
        AND anio = :anio
   ");
   $verificarTickets->bindParam(":idcondominio", $idcondominio);
   $verificarTickets->bindParam(":mes", $mes);
   $verificarTickets->bindParam(":anio", $anio);
   $verificarTickets->execute();
   $existeTickets = $verificarTickets->fetch(PDO::FETCH_ASSOC)['total'];

   if ($existeTickets == 0) {
      echo "<script>
         alert('⚠️ No se puede registrar gas porque no existen tickets creados para el mes de $mes $anio.');
         window.location.href = 'index.php';
      </script>";
      exit;
   }

   // Si existen tickets → registrar gas normalmente
   foreach ($_POST['gas_insertado'] as $id_apto => $valor_gas) {
      if ($valor_gas != '') {
         $sentencia = $conexion->prepare("
            INSERT INTO tbl_gas 
            (id_apto, id_condominio, id_usuario_registro, gas_insertado, mes, anio, fecha_registro)
            VALUES (:id_apto, :id_condominio, :id_usuario_registro, :gas_insertado, :mes, :anio, :fecha_registro)
            ON DUPLICATE KEY UPDATE 
               gas_insertado = :gas_insertado, 
               fecha_registro = :fecha_registro
         ");

         $id_condominio = $_POST['id_condominio'][$id_apto];
         $sentencia->bindParam(":id_apto", $id_apto);
         $sentencia->bindParam(":id_condominio", $id_condominio);
         $sentencia->bindParam(":id_usuario_registro", $id_usuario_registro);
         $sentencia->bindParam(":gas_insertado", $valor_gas);
         $sentencia->bindParam(":mes", $mes);
         $sentencia->bindParam(":anio", $anio);
         $sentencia->bindParam(":fecha_registro", $fecha_registro);
         $sentencia->execute();

         // 🔥 CORRECCIÓN: Recalcular el total de gas para este apartamento después de insertar/actualizar
         $sentencia_total_gas = $conexion->prepare("
            SELECT SUM(gas_insertado) AS total_gas 
            FROM tbl_gas 
            WHERE id_apto = :id_apto 
            AND mes = :mes 
            AND anio = :anio 
            AND id_condominio = :id_condominio
         ");
         $sentencia_total_gas->bindParam(":id_apto", $id_apto);
         $sentencia_total_gas->bindParam(":mes", $mes);
         $sentencia_total_gas->bindParam(":anio", $anio);
         $sentencia_total_gas->bindParam(":id_condominio", $id_condominio);
         $sentencia_total_gas->execute();
         $nuevo_total_gas = $sentencia_total_gas->fetch(PDO::FETCH_ASSOC)['total_gas'] ?? 0;

         // Actualizar el valor de gas en los tickets existentes con el total recalculado
         $actualizarTickets = $conexion->prepare("
            UPDATE tbl_tickets
            SET gas = :gas
            WHERE id_apto = :id_apto
              AND id_condominio = :id_condominio
              AND mes = :mes
              AND anio = :anio
         ");
         $actualizarTickets->bindParam(":gas", $nuevo_total_gas);
         $actualizarTickets->bindParam(":id_apto", $id_apto);
         $actualizarTickets->bindParam(":id_condominio", $id_condominio);
         $actualizarTickets->bindParam(":mes", $mes);
         $actualizarTickets->bindParam(":anio", $anio);
         $actualizarTickets->execute();
      }
   }

   $mensaje_gas = "✅ Gas registrado y tickets actualizados correctamente";
}

// Obtener los registros de gas del mes actual para mostrar en la tabla
$sentencia_gas_registrados = $conexion->prepare("
    SELECT g.*, a.apto, a.condominos 
    FROM tbl_gas g
    INNER JOIN tbl_aptos a ON g.id_apto = a.id AND g.id_condominio = a.id_condominio
    WHERE g.mes = :mes AND g.anio = :anio AND g.id_condominio = :id_condominio
    ORDER BY g.fecha_registro DESC
");
$sentencia_gas_registrados->bindParam(":mes", $mes_actual_espanol);
$sentencia_gas_registrados->bindParam(":anio", $anio_actual);
$sentencia_gas_registrados->bindParam(":id_condominio", $idcondominio);
$sentencia_gas_registrados->execute();
$gas_registrados = $sentencia_gas_registrados->fetchAll(PDO::FETCH_ASSOC);

// Calcular total de gas del mes
$total_gas_mes = 0;
foreach ($gas_registrados as $gas) {
   $total_gas_mes += $gas['gas_insertado'];
}
?>

<!-- 🧾 INTERFAZ -->
<div class="container mt-4">
   <!-- Formulario para registrar gas -->
   <div class="card">
      <div class="card-header bg-dark text-white text-center">
         <h4>Registrar Gas por Apartamento</h4>
      </div>
      <div class="card-body">
         <?php if (isset($mensaje_gas)): ?>
            <div class="alert alert-info text-center"><?= $mensaje_gas ?></div>
         <?php endif; ?>

         <form method="post">
            <input type="hidden" name="id_usuario_registro" value="<?= $id_usuario_registro ?>">

            <div class="row">
               <!-- Selección de mes -->
               <div class="mb-3 col">
                  <label class="form-label fw-bold">Selecciona un mes:</label>
                  <select class="form-select form-select-lg mb-3" name="el_mes" required>
                     <?php
                     foreach ($meses as $index => $mes) {
                        $numero_mes = $index + 1;
                        $selected = ($numero_mes == $mes_actual_num) ? 'selected' : '';
                        echo "<option value='$mes' $selected>$mes</option>";
                     }
                     ?>
                  </select>
               </div>

               <!-- Selección de año -->
               <div class="mb-3 col">
                  <label class="form-label fw-bold">Selecciona un año:</label>
                  <select class="form-select form-select-lg mb-3" name="el_anio" required>
                     <?php
                     for ($i = 2024; $i <= 2046; $i++) {
                        $selected = ($i == $anio_actual) ? 'selected' : '';
                        echo "<option value='$i' $selected>$i</option>";
                     }
                     ?>
                  </select>
               </div>
            </div>

            <table class="table table-bordered text-center align-middle">
               <thead class="table-dark">
                  <tr>
                     <th>Apartamento</th>
                     <th>Condómino</th>
                     <th>Gas Insertado</th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach ($lista_aptos as $apto) { ?>
                     <tr>
                        <td class="fw-bold"><?php echo $apto['apto']; ?></td>
                        <td><?php echo $apto['condominos']; ?></td>
                        <td>
                           <input type="number" step="0.01" min="0" class="form-control text-center"
                              name="gas_insertado[<?php echo $apto['id']; ?>]"
                              placeholder="0.00">
                           <input type="hidden" name="id_condominio[<?php echo $apto['id']; ?>]" value="<?php echo $apto['id_condominio']; ?>">
                        </td>
                     </tr>
                  <?php } ?>
               </tbody>
            </table>

            <div class="text-center">
               <button type="submit" class="btn btn-success btn-lg">
                  💾 Guardar Todo
               </button>
            </div>
         </form>
      </div>
   </div>

   <!-- Tabla de gas registrado -->
   <div class="card mt-4">
      <div class="card-header bg-secondary text-white text-center">
         <h4>Gas registrado en <?= $mes_actual_espanol . " " . $anio_actual ?></h4>
      </div>
      <div class="card-body">
         <div class="table-responsive">
            <table class="table table-bordered table-striped">
               <thead class="table-dark">
                  <tr>
                     <th>#</th>
                     <th>Apartamento</th>
                     <th>Condómino</th>
                     <th>Gas Insertado</th>
                     <th>Fecha de Registro</th>
                     <th>Acciones</th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach ($gas_registrados as $i => $gas): ?>
                     <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= $gas['apto'] ?></td>
                        <td><?= $gas['condominos'] ?></td>
                        <td><?= number_format($gas['gas_insertado'], 2) ?></td>
                        <td><?= $gas['fecha_registro'] ?></td>
                        <td>
                           <a href="?eliminar_gas=<?= $gas['id'] ?>"
                              class="btn btn-danger btn-sm"
                              onclick="return confirm('¿Estás seguro de que quieres eliminar este registro de gas?')">
                              ❌ Eliminar
                           </a>
                        </td>
                     </tr>
                  <?php endforeach; ?>
               </tbody>
               <tfoot class="table-light">
                  <tr>
                     <th colspan="3" class="text-end">TOTAL:</th>
                     <th colspan="3"><?= number_format($total_gas_mes, 2) ?></th>
                  </tr>
               </tfoot>
            </table>
         </div>
      </div>
   </div>
</div>

<?php include("../../templates/footer.php"); ?>