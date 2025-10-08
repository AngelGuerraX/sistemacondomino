<?php
include("../../templates/header.php");
include("../../bd.php");

// Obtener mes y año actual por defecto
$mes_actual = date("F");
$anio_actual = date("Y");

// Si el formulario fue enviado
if ($_POST) {
   $descripcion = trim($_POST['descripcion']);
   $monto = floatval($_POST['monto']);
   $mes = $_POST['mes'];
   $anio = $_POST['anio'];
   $id_condominio = 7; // Ajusta según tu sistema o sesión
   $id_usuario_registro = 1; // Ajusta según el usuario actual

   // 1️⃣ Verificar si existen tickets del condominio para ese mes
   $sentencia_tickets = $conexion->prepare("
        SELECT COUNT(*) 
        FROM tbl_tickets 
        WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio
    ");
   $sentencia_tickets->bindParam(":mes", $mes);
   $sentencia_tickets->bindParam(":anio", $anio);
   $sentencia_tickets->bindParam(":id_condominio", $id_condominio);
   $sentencia_tickets->execute();
   $tickets_existentes = $sentencia_tickets->fetchColumn();

   if ($tickets_existentes > 0) {
      // 2️⃣ Insertar la nueva cuota extra
      $sentencia = $conexion->prepare("
            INSERT INTO tbl_cuotas_extras 
            (id_condominio, id_usuario_registro, descripcion, monto, mes, anio, fecha_registro)
            VALUES (:id_condominio, :id_usuario_registro, :descripcion, :monto, :mes, :anio, NOW())
        ");
      $sentencia->bindParam(":id_condominio", $id_condominio);
      $sentencia->bindParam(":id_usuario_registro", $id_usuario_registro);
      $sentencia->bindParam(":descripcion", $descripcion);
      $sentencia->bindParam(":monto", $monto);
      $sentencia->bindParam(":mes", $mes);
      $sentencia->bindParam(":anio", $anio);
      $sentencia->execute();

      // 3️⃣ Calcular el total de cuotas extras de ese mes
      $sentencia_total = $conexion->prepare("
            SELECT SUM(monto) AS total 
            FROM tbl_cuotas_extras 
            WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio
        ");
      $sentencia_total->bindParam(":mes", $mes);
      $sentencia_total->bindParam(":anio", $anio);
      $sentencia_total->bindParam(":id_condominio", $id_condominio);
      $sentencia_total->execute();
      $total_cuotas = $sentencia_total->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

      // 4️⃣ Actualizar el campo 'cuota' en los tickets del mes
      $actualizar = $conexion->prepare("
            UPDATE tbl_ticket 
            SET cuota=:total 
            WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio
        ");
      $actualizar->bindParam(":total", $total_cuotas);
      $actualizar->bindParam(":mes", $mes);
      $actualizar->bindParam(":anio", $anio);
      $actualizar->bindParam(":id_condominio", $id_condominio);
      $actualizar->execute();

      $mensaje = "✅ Cuota extra guardada y tickets actualizados correctamente.";
   } else {
      $mensaje = "❌ No se puede guardar cuotas extras: no existen tickets para este mes.";
   }
}

// 5️⃣ Obtener las cuotas extras del mes actual
$sentencia_cuotas = $conexion->prepare("
    SELECT * FROM tbl_cuotas_extras 
    WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio 
    ORDER BY fecha_registro DESC
");
$sentencia_cuotas->bindParam(":mes", $mes_actual);
$sentencia_cuotas->bindParam(":anio", $anio_actual);
$sentencia_cuotas->bindParam(":id_condominio", $id_condominio);
$sentencia_cuotas->execute();
$lista_cuotas = $sentencia_cuotas->fetchAll(PDO::FETCH_ASSOC);

// 6️⃣ Calcular el total
$total_mes = 0;
foreach ($lista_cuotas as $cuota) {
   $total_mes += $cuota['monto'];
}
?>

<div class="container mt-4">
   <div class="card">
      <div class="card-header bg-dark text-white text-center">
         <h3>CUOTAS EXTRAS</h3>
      </div>
      <div class="card-body">
         <?php if (isset($mensaje)): ?>
            <div class="alert alert-info text-center"><?= $mensaje ?></div>
         <?php endif; ?>

         <form method="POST" action="">
            <div class="row mb-3">
               <div class="col-md-6">
                  <label class="form-label">Descripción de la cuota extra:</label>
                  <input type="text" name="descripcion" class="form-control" required>
               </div>

               <div class="col-md-3">
                  <label class="form-label">Monto:</label>
                  <input type="number" name="monto" step="0.01" class="form-control" required>
               </div>

               <div class="col-md-2">
                  <label class="form-label">Mes:</label>
                  <select name="mes" class="form-control">
                     <?php
                     $meses = [
                        "January" => "Enero",
                        "February" => "Febrero",
                        "March" => "Marzo",
                        "April" => "Abril",
                        "May" => "Mayo",
                        "June" => "Junio",
                        "July" => "Julio",
                        "August" => "Agosto",
                        "September" => "Septiembre",
                        "October" => "Octubre",
                        "November" => "Noviembre",
                        "December" => "Diciembre"
                     ];
                     foreach ($meses as $en => $es) {
                        $selected = ($en == $mes_actual) ? "selected" : "";
                        echo "<option value='$en' $selected>$es</option>";
                     }
                     ?>
                  </select>
               </div>

               <div class="col-md-1">
                  <label class="form-label">Año:</label>
                  <input type="number" name="anio" value="<?= $anio_actual ?>" class="form-control">
               </div>
            </div>

            <div class="text-center">
               <button type="submit" class="btn btn-dark px-5">Guardar Cuota Extra</button>
            </div>
         </form>
      </div>
   </div>

   <!-- Tabla -->
   <div class="card mt-4">
      <div class="card-header bg-secondary text-white text-center">
         <h4>Cuotas registradas en <?= $meses[$mes_actual] . " " . $anio_actual ?></h4>
      </div>
      <div class="card-body">
         <table class="table table-striped text-center">
            <thead class="table-dark">
               <tr>
                  <th>#</th>
                  <th>Descripción</th>
                  <th>Monto</th>
                  <th>Fecha de Registro</th>
               </tr>
            </thead>
            <tbody>
               <?php foreach ($lista_cuotas as $i => $cuota): ?>
                  <tr>
                     <td><?= $i + 1 ?></td>
                     <td><?= $cuota['descripcion'] ?></td>
                     <td><?= number_format($cuota['monto'], 2) ?></td>
                     <td><?= $cuota['fecha_registro'] ?></td>
                  </tr>
               <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
               <tr>
                  <th colspan="2" class="text-end">TOTAL:</th>
                  <th colspan="2"><?= number_format($total_mes, 2) ?></th>
               </tr>
            </tfoot>
         </table>
      </div>
   </div>
</div>

<?php include("../../templates/footer.php"); ?>