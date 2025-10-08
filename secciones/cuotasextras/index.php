<?php
include("../../templates/header.php");
include("../../bd.php");

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
   session_start();
}

// Obtener mes y año actual por defecto
$mes_actual = date("n");
$meses_numeros = [
   1 => "Enero",
   2 => "Febrero",
   3 => "Marzo",
   4 => "Abril",
   5 => "Mayo",
   6 => "Junio",
   7 => "Julio",
   8 => "Agosto",
   9 => "Septiembre",
   10 => "Octubre",
   11 => "Noviembre",
   12 => "Diciembre"
];
$mes_actual_espanol = $meses_numeros[$mes_actual];
$anio_actual = date("Y");

// Obtener lista de apartamentos del condominio desde la sesión
$id_condominio = $_SESSION['idcondominio'];
$id_usuario_registro = $_SESSION['usuario'];

// Procesar eliminación de cuota
if (isset($_GET['eliminar'])) {
   $id_eliminar = $_GET['eliminar'];

   // Obtener información de la cuota a eliminar
   $sentencia_info = $conexion->prepare("
        SELECT mes, anio, id_apto, monto 
        FROM tbl_cuotas_extras 
        WHERE id = :id AND id_condominio = :id_condominio
    ");
   $sentencia_info->bindParam(":id", $id_eliminar);
   $sentencia_info->bindParam(":id_condominio", $id_condominio);
   $sentencia_info->execute();
   $cuota_info = $sentencia_info->fetch(PDO::FETCH_ASSOC);

   if ($cuota_info) {
      $mes_eliminar = $cuota_info['mes'];
      $anio_eliminar = $cuota_info['anio'];
      $id_apto_eliminar = $cuota_info['id_apto'];

      // Eliminar la cuota extra
      $sentencia_eliminar = $conexion->prepare("
            DELETE FROM tbl_cuotas_extras 
            WHERE id = :id AND id_condominio = :id_condominio
        ");
      $sentencia_eliminar->bindParam(":id", $id_eliminar);
      $sentencia_eliminar->bindParam(":id_condominio", $id_condominio);
      $sentencia_eliminar->execute();

      // Actualizar los tickets afectados
      if ($id_apto_eliminar) {
         // Cuota individual - actualizar solo ese apartamento
         $sentencia_total = $conexion->prepare("
                SELECT SUM(monto) AS total 
                FROM tbl_cuotas_extras 
                WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio 
                AND (id_apto IS NULL OR id_apto = :id_apto)
            ");
         $sentencia_total->bindParam(":mes", $mes_eliminar);
         $sentencia_total->bindParam(":anio", $anio_eliminar);
         $sentencia_total->bindParam(":id_condominio", $id_condominio);
         $sentencia_total->bindParam(":id_apto", $id_apto_eliminar);
         $sentencia_total->execute();
         $total_cuotas = $sentencia_total->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

         // Actualizar el campo 'cuota' en el ticket específico
         $actualizar = $conexion->prepare("
                UPDATE tbl_tickets 
                SET cuota = :total 
                WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio AND id_apto=:id_apto
            ");
         $actualizar->bindParam(":total", $total_cuotas);
         $actualizar->bindParam(":mes", $mes_eliminar);
         $actualizar->bindParam(":anio", $anio_eliminar);
         $actualizar->bindParam(":id_condominio", $id_condominio);
         $actualizar->bindParam(":id_apto", $id_apto_eliminar);
         $actualizar->execute();

         $mensaje = "✅ Cuota extra eliminada y ticket del apartamento $id_apto_eliminar actualizado.";
      } else {
         // Cuota para todos - actualizar todos los apartamentos
         $sentencia_aptos = $conexion->prepare("
                SELECT id_apto FROM tbl_tickets 
                WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio
            ");
         $sentencia_aptos->bindParam(":mes", $mes_eliminar);
         $sentencia_aptos->bindParam(":anio", $anio_eliminar);
         $sentencia_aptos->bindParam(":id_condominio", $id_condominio);
         $sentencia_aptos->execute();
         $apartamentos = $sentencia_aptos->fetchAll(PDO::FETCH_ASSOC);

         foreach ($apartamentos as $apto) {
            $sentencia_total = $conexion->prepare("
                    SELECT SUM(monto) AS total 
                    FROM tbl_cuotas_extras 
                    WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio 
                    AND (id_apto IS NULL OR id_apto = :id_apto)
                ");
            $sentencia_total->bindParam(":mes", $mes_eliminar);
            $sentencia_total->bindParam(":anio", $anio_eliminar);
            $sentencia_total->bindParam(":id_condominio", $id_condominio);
            $sentencia_total->bindParam(":id_apto", $apto['id_apto']);
            $sentencia_total->execute();
            $total_cuotas = $sentencia_total->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            $actualizar = $conexion->prepare("
                    UPDATE tbl_tickets 
                    SET cuota = :total 
                    WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio AND id_apto=:id_apto
                ");
            $actualizar->bindParam(":total", $total_cuotas);
            $actualizar->bindParam(":mes", $mes_eliminar);
            $actualizar->bindParam(":anio", $anio_eliminar);
            $actualizar->bindParam(":id_condominio", $id_condominio);
            $actualizar->bindParam(":id_apto", $apto['id_apto']);
            $actualizar->execute();
         }

         $mensaje = "✅ Cuota extra eliminada y todos los tickets actualizados.";
      }
   } else {
      $mensaje = "❌ No se encontró la cuota extra a eliminar.";
   }
}

// Si el formulario fue enviado (agregar nueva cuota)
if ($_POST) {
   $descripcion = trim($_POST['descripcion']);
   $monto = floatval($_POST['monto']);
   $mes = $_POST['mes'];
   $anio = $_POST['anio'];
   $tipo_aplicacion = $_POST['tipo_aplicacion'];
   $id_apto_seleccionado = isset($_POST['id_apto']) ? $_POST['id_apto'] : null;

   // Verificar si existen tickets del condominio para ese mes
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
      if ($tipo_aplicacion == 'todos') {
         // Aplicar a todos los apartamentos
         $sentencia = $conexion->prepare("
                INSERT INTO tbl_cuotas_extras 
                (id_condominio, id_usuario_registro, descripcion, monto, mes, anio, fecha_registro, id_apto)
                VALUES (:id_condominio, :id_usuario_registro, :descripcion, :monto, :mes, :anio, NOW(), NULL)
            ");
         $sentencia->bindParam(":id_condominio", $id_condominio);
         $sentencia->bindParam(":id_usuario_registro", $id_usuario_registro);
         $sentencia->bindParam(":descripcion", $descripcion);
         $sentencia->bindParam(":monto", $monto);
         $sentencia->bindParam(":mes", $mes);
         $sentencia->bindParam(":anio", $anio);
         $sentencia->execute();

         // Actualizar tickets de TODOS los apartamentos
         $sentencia_aptos = $conexion->prepare("
                SELECT id_apto FROM tbl_tickets 
                WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio
            ");
         $sentencia_aptos->bindParam(":mes", $mes);
         $sentencia_aptos->bindParam(":anio", $anio);
         $sentencia_aptos->bindParam(":id_condominio", $id_condominio);
         $sentencia_aptos->execute();
         $apartamentos = $sentencia_aptos->fetchAll(PDO::FETCH_ASSOC);

         foreach ($apartamentos as $apto) {
            // Calcular total de cuotas extras para este apartamento
            $sentencia_total = $conexion->prepare("
                    SELECT SUM(monto) AS total 
                    FROM tbl_cuotas_extras 
                    WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio 
                    AND (id_apto IS NULL OR id_apto = :id_apto)
                ");
            $sentencia_total->bindParam(":mes", $mes);
            $sentencia_total->bindParam(":anio", $anio);
            $sentencia_total->bindParam(":id_condominio", $id_condominio);
            $sentencia_total->bindParam(":id_apto", $apto['id_apto']);
            $sentencia_total->execute();
            $total_cuotas = $sentencia_total->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Actualizar el campo 'cuota' en el ticket específico
            $actualizar = $conexion->prepare("
                    UPDATE tbl_tickets 
                    SET cuota = :total 
                    WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio AND id_apto=:id_apto
                ");
            $actualizar->bindParam(":total", $total_cuotas);
            $actualizar->bindParam(":mes", $mes);
            $actualizar->bindParam(":anio", $anio);
            $actualizar->bindParam(":id_condominio", $id_condominio);
            $actualizar->bindParam(":id_apto", $apto['id_apto']);
            $actualizar->execute();
         }

         $mensaje = "✅ Cuota extra aplicada a TODOS los apartamentos y tickets actualizados correctamente.";
      } elseif ($tipo_aplicacion == 'individual' && $id_apto_seleccionado) {
         // Aplicar a un apartamento individual
         $sentencia = $conexion->prepare("
                INSERT INTO tbl_cuotas_extras 
                (id_condominio, id_usuario_registro, descripcion, monto, mes, anio, fecha_registro, id_apto)
                VALUES (:id_condominio, :id_usuario_registro, :descripcion, :monto, :mes, :anio, NOW(), :id_apto)
            ");
         $sentencia->bindParam(":id_condominio", $id_condominio);
         $sentencia->bindParam(":id_usuario_registro", $id_usuario_registro);
         $sentencia->bindParam(":descripcion", $descripcion);
         $sentencia->bindParam(":monto", $monto);
         $sentencia->bindParam(":mes", $mes);
         $sentencia->bindParam(":anio", $anio);
         $sentencia->bindParam(":id_apto", $id_apto_seleccionado);
         $sentencia->execute();

         // Calcular total de cuotas extras para este apartamento
         $sentencia_total = $conexion->prepare("
                SELECT SUM(monto) AS total 
                FROM tbl_cuotas_extras 
                WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio 
                AND (id_apto IS NULL OR id_apto = :id_apto)
            ");
         $sentencia_total->bindParam(":mes", $mes);
         $sentencia_total->bindParam(":anio", $anio);
         $sentencia_total->bindParam(":id_condominio", $id_condominio);
         $sentencia_total->bindParam(":id_apto", $id_apto_seleccionado);
         $sentencia_total->execute();
         $total_cuotas = $sentencia_total->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

         // Actualizar el campo 'cuota' en el ticket específico
         $actualizar = $conexion->prepare("
                UPDATE tbl_tickets 
                SET cuota = :total 
                WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio AND id_apto=:id_apto
            ");
         $actualizar->bindParam(":total", $total_cuotas);
         $actualizar->bindParam(":mes", $mes);
         $actualizar->bindParam(":anio", $anio);
         $actualizar->bindParam(":id_condominio", $id_condominio);
         $actualizar->bindParam(":id_apto", $id_apto_seleccionado);
         $actualizar->execute();

         $mensaje = "✅ Cuota extra aplicada al apartamento $id_apto_seleccionado y ticket actualizado correctamente.";
      }
   } else {
      $mensaje = "❌ No se puede guardar cuotas extras: no existen tickets para $mes $anio.";

      // Debug: mostrar qué meses existen
      $sentencia_debug = $conexion->prepare("
            SELECT DISTINCT mes, anio FROM tbl_tickets 
            WHERE id_condominio = :id_condominio
            ORDER BY anio, mes
        ");
      $sentencia_debug->bindParam(":id_condominio", $id_condominio);
      $sentencia_debug->execute();
      $meses_existentes = $sentencia_debug->fetchAll(PDO::FETCH_ASSOC);

      $mensaje_debug = "<br>Meses existentes en la base de datos: ";
      foreach ($meses_existentes as $mes_existente) {
         $mensaje_debug .= $mes_existente['mes'] . " " . $mes_existente['anio'] . ", ";
      }
      $mensaje .= $mensaje_debug;
   }
}

// Obtener lista de apartamentos para el select desde tbl_aptos
$sentencia_aptos = $conexion->prepare("
    SELECT id, apto, condominos 
    FROM tbl_aptos 
    WHERE id_condominio = :id_condominio 
    ORDER BY apto
");
$sentencia_aptos->bindParam(":id_condominio", $id_condominio);
$sentencia_aptos->execute();
$apartamentos = $sentencia_aptos->fetchAll(PDO::FETCH_ASSOC);

// Obtener las cuotas extras del mes actual
$sentencia_cuotas = $conexion->prepare("
    SELECT ce.*, a.condominos 
    FROM tbl_cuotas_extras ce
    LEFT JOIN tbl_aptos a ON ce.id_apto = a.apto AND a.id_condominio = :id_condominio
    WHERE ce.mes=:mes AND ce.anio=:anio AND ce.id_condominio=:id_condominio 
    ORDER BY ce.fecha_registro DESC
");
$sentencia_cuotas->bindParam(":mes", $mes_actual_espanol);
$sentencia_cuotas->bindParam(":anio", $anio_actual);
$sentencia_cuotas->bindParam(":id_condominio", $id_condominio);
$sentencia_cuotas->execute();
$lista_cuotas = $sentencia_cuotas->fetchAll(PDO::FETCH_ASSOC);

// Calcular el total
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
                     $meses_espanol = [
                        "Enero",
                        "Febrero",
                        "Marzo",
                        "Abril",
                        "Mayo",
                        "Junio",
                        "Julio",
                        "Agosto",
                        "Septiembre",
                        "Octubre",
                        "Noviembre",
                        "Diciembre"
                     ];
                     foreach ($meses_espanol as $mes) {
                        $selected = ($mes == $mes_actual_espanol) ? "selected" : "";
                        echo "<option value='$mes' $selected>$mes</option>";
                     }
                     ?>
                  </select>
               </div>

               <div class="col-md-1">
                  <label class="form-label">Año:</label>
                  <input type="number" name="anio" value="<?= $anio_actual ?>" class="form-control" required>
               </div>
            </div>

            <div class="row mb-3">
               <div class="col-md-6">
                  <label class="form-label">Aplicar a:</label>
                  <div>
                     <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="tipo_aplicacion" id="todos" value="todos" checked>
                        <label class="form-check-label" for="todos">Todos los apartamentos</label>
                     </div>
                     <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="tipo_aplicacion" id="individual" value="individual">
                        <label class="form-check-label" for="individual">Apartamento individual</label>
                     </div>
                  </div>
               </div>

               <div class="col-md-6" id="select-apartamento" style="display: none;">
                  <label class="form-label">Seleccionar apartamento:</label>
                  <select name="id_apto" class="form-control">
                     <option value="">Seleccione un apartamento</option>
                     <?php foreach ($apartamentos as $apto): ?>
                        <option value="<?= $apto['id'] ?>">
                           <?= $apto['apto'] ?> - <?= $apto['condominos'] ?>
                        </option>
                     <?php endforeach; ?>
                  </select>
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
         <h4>Cuotas registradas en <?= $mes_actual_espanol . " " . $anio_actual ?></h4>
      </div>
      <div class="card-body">
         <div class="table-responsive">
            <table class="table table-striped text-center">
               <thead class="table-dark">
                  <tr>
                     <th>Descripción</th>
                     <th>Monto</th>
                     <th>Apartamento</th>
                     <th>Fecha de Registro</th>
                     <th>Acciones</th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach ($lista_cuotas as $i => $cuota): ?>
                     <tr>
                        <td><?= $cuota['descripcion'] ?></td>
                        <td><?= number_format($cuota['monto'], 2) ?></td>
                        <td><?= $cuota['id_apto'] ? $cuota['id_apto'] : 'TODOS' ?></td>
                        <td><?= $cuota['fecha_registro'] ?></td>
                        <td>
                           <a href="?eliminar=<?= $cuota['id'] ?>"
                              class="btn btn-danger btn-sm"
                              onclick="return confirm('¿Estás seguro de que quieres eliminar esta cuota extra?')">
                              ❌ Eliminar
                           </a>
                        </td>
                     </tr>
                  <?php endforeach; ?>
               </tbody>
               <tfoot class="table-light">
                  <tr>
                     <th colspan="2" class="text-end">TOTAL:</th>
                     <th colspan="5"><?= number_format($total_mes, 2) ?></th>
                  </tr>
               </tfoot>
            </table>
         </div>
      </div>
   </div>
</div>

<script>
   document.addEventListener('DOMContentLoaded', function() {
      const radioTodos = document.getElementById('todos');
      const radioIndividual = document.getElementById('individual');
      const selectApartamento = document.getElementById('select-apartamento');

      function toggleSelectApartamento() {
         if (radioIndividual.checked) {
            selectApartamento.style.display = 'block';
         } else {
            selectApartamento.style.display = 'none';
         }
      }

      radioTodos.addEventListener('change', toggleSelectApartamento);
      radioIndividual.addEventListener('change', toggleSelectApartamento);

      // Inicializar estado
      toggleSelectApartamento();
   });
</script>

<?php include("../../templates/footer.php"); ?>