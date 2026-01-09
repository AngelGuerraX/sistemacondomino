 <?php
    include("../../bd.php");
    include("../../templates/header.php");

    // Variables de sesión
    $idcondominio = $_SESSION['idcondominio'];
    $usuario_registro = $_SESSION['usuario'];

    // Procesar pagos de gas
    if ($_POST && isset($_POST['pagos_seleccionados'])) {
        $pagos_procesados = 0;
        $errores = [];

        foreach ($_POST['pagos_seleccionados'] as $id_gas) {
            // Obtener información del gas a pagar
            $sentencia_info = $conexion->prepare("
            SELECT g.id, g.id_apto, g.mes, g.anio, g.total_gas, 
                   a.apto, i.id AS id_inquilino, i.nombre, i.balance
            FROM tbl_gas g
            INNER JOIN tbl_aptos a ON g.id_apto = a.id AND g.id_condominio = a.id_condominio
            INNER JOIN tbl_inquilinos i ON a.id = i.id_apto AND i.activo = 1
            WHERE g.id = :id_gas 
            AND g.id_condominio = :id_condominio
        ");
            $sentencia_info->bindParam(":id_gas", $id_gas);
            $sentencia_info->bindParam(":id_condominio", $idcondominio);
            $sentencia_info->execute();
            $gas_info = $sentencia_info->fetch(PDO::FETCH_ASSOC);

            if ($gas_info) {
                // Verificar que no esté ya pagado
                $verificar_pago = $conexion->prepare("
                SELECT COUNT(*) AS existe 
                FROM tbl_pagos_inquilinos 
                WHERE id_inquilino = :id_inquilino
            ");
                $verificar_pago->bindParam(":id_inquilino", $gas_info['id_inquilino']);
                $verificar_pago->execute();
                $ya_pagado = $verificar_pago->fetch(PDO::FETCH_ASSOC)['existe'];

                if ($ya_pagado == 0) {
                    // Verificar balance suficiente
                    if ($gas_info['balance'] >= $gas_info['total_gas']) {
                        try {
                            $conexion->beginTransaction();

                            // 1. Registrar el pago
                            $sentencia_pago = $conexion->prepare("
                            INSERT INTO tbl_pagos_inquilinos 
                            (id_inquilino, id_condominio, monto, concepto, forma_pago, 
                             fecha_pago, mes_gas, anio_gas, usuario_registro, fecha_registro)
                            VALUES (:id_inquilino, :id_condominio, :monto, :concepto, :forma_pago,
                                    :fecha_pago, :mes_gas, :anio_gas, :usuario_registro, NOW())
                        ");

                            $forma_pago = "Descuento de Balance";
                            $concepto = "Pago de Gas - " . $gas_info['mes'] . " " . $gas_info['anio'] . " - " . $gas_info['apto'];

                            $sentencia_pago->bindParam(":id_inquilino", $gas_info['id_inquilino']);
                            $sentencia_pago->bindParam(":id_condominio", $idcondominio);
                            $sentencia_pago->bindParam(":monto", $gas_info['total_gas']);
                            $sentencia_pago->bindParam(":concepto", $concepto);
                            $sentencia_pago->bindParam(":forma_pago", $forma_pago);
                            $sentencia_pago->bindParam(":fecha_pago", date('Y-m-d'));
                            $sentencia_pago->bindParam(":mes_gas", $gas_info['mes']);
                            $sentencia_pago->bindParam(":anio_gas", $gas_info['anio']);
                            $sentencia_pago->bindParam(":usuario_registro", $usuario_registro);
                            $sentencia_pago->execute();

                            // 2. Actualizar balance del inquilino
                            $sentencia_balance = $conexion->prepare("
                            UPDATE tbl_inquilinos 
                            SET balance = balance - :monto 
                            WHERE id = :id_inquilino 
                            AND activo = 1
                        ");
                            $sentencia_balance->bindParam(":monto", $gas_info['total_gas']);
                            $sentencia_balance->bindParam(":id_inquilino", $gas_info['id_inquilino']);
                            $sentencia_balance->execute();

                            $conexion->commit();
                            $pagos_procesados++;
                        } catch (Exception $e) {
                            $conexion->rollBack();
                            $errores[] = "Error al procesar pago para " . $gas_info['apto'] . " - " . $gas_info['mes'] . ": " . $e->getMessage();
                        }
                    } else {
                        $errores[] = "Balance insuficiente para " . $gas_info['apto'] . " - " . $gas_info['mes'] . " (Balance: $" . $gas_info['balance'] . ", Gas: $" . $gas_info['total_gas'] . ")";
                    }
                } else {
                    $errores[] = "El gas de " . $gas_info['apto'] . " - " . $gas_info['mes'] . " ya estaba pagado";
                }
            }
        }

        // Mostrar mensajes
        if ($pagos_procesados > 0) {
            $mensaje = "✅ Se procesaron " . $pagos_procesados . " pagos correctamente";
        }
        if (!empty($errores)) {
            $mensaje_error = "❌ Errores:<br>" . implode("<br>", $errores);
        }
    }

    // Obtener gas pendiente de pago para inquilinos
    $sentencia_gas_pendiente = $conexion->prepare("
    SELECT g.id AS id_gas, g.mes, g.anio, g.total_gas, g.fecha_registro,
           a.id AS id_apto, a.apto, 
           i.id AS id_inquilino, i.nombre AS inquilino, i.balance,
           CASE 
               WHEN i.balance >= g.total_gas THEN 'suficiente'
               ELSE 'insuficiente'
           END AS estado_balance
    FROM tbl_gas g
    INNER JOIN tbl_aptos a ON g.id_apto = a.id AND g.id_condominio = a.id_condominio
    INNER JOIN tbl_inquilinos i ON a.id = i.id_apto AND i.activo = 1
    WHERE a.tiene_inquilino = 1
    AND g.id_condominio = :id_condominio
    AND NOT EXISTS (
        SELECT 1 FROM tbl_pagos_inquilinos p 
        WHERE p.mes_gas = g.mes 
        AND p.anio_gas = g.anio 
        AND p.id_inquilino = i.id
    )
    ORDER BY a.apto, g.anio DESC, 
    FIELD(g.mes, 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre') DESC
");
    $sentencia_gas_pendiente->bindParam(":id_condominio", $idcondominio);
    $sentencia_gas_pendiente->execute();
    $gas_pendiente = $sentencia_gas_pendiente->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar por inquilino para mejor visualización
    $gas_por_inquilino = [];
    foreach ($gas_pendiente as $gas) {
        $key = $gas['id_inquilino'] . '_' . $gas['inquilino'];
        if (!isset($gas_por_inquilino[$key])) {
            $gas_por_inquilino[$key] = [
                'id_inquilino' => $gas['id_inquilino'],
                'inquilino' => $gas['inquilino'],
                'apto' => $gas['apto'],
                'balance' => $gas['balance'],
                'gas_pendiente' => []
            ];
        }
        $gas_por_inquilino[$key]['gas_pendiente'][] = $gas;
    }
    ?>

 <!DOCTYPE html>
 <html lang="es">

 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Pago de Gas - Inquilinos</title>
     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
     <style>
         .suficiente {
             background-color: #d4edda !important;
         }

         .insuficiente {
             background-color: #f8d7da !important;
         }

         .badge-suficiente {
             background-color: #28a745;
         }

         .badge-insuficiente {
             background-color: #dc3545;
         }

         .table-responsive {
             max-height: 70vh;
         }

         .inquilino-header {
             background-color: #e9ecef;
             font-weight: bold;
         }

         .checkbox-cell {
             width: 50px;
             text-align: center;
         }
     </style>
 </head>

 <body>

     <div class="container mt-4">
         <div class="card">
             <div class="card-header bg-primary text-white text-center">
                 <h4>💳 Pago de Gas - Inquilinos</h4>
                 <small class="fw-light">Solo se muestran apartamentos con inquilinos activos</small>
             </div>
             <div class="card-body">

                 <?php if (isset($mensaje)): ?>
                     <div class="alert alert-success text-center"><?= $mensaje ?></div>
                 <?php endif; ?>

                 <?php if (isset($mensaje_error)): ?>
                     <div class="alert alert-danger"><?= $mensaje_error ?></div>
                 <?php endif; ?>

                 <?php if (empty($gas_por_inquilino)): ?>
                     <div class="alert alert-info text-center">
                         🎉 No hay gas pendiente de pago para inquilinos
                     </div>
                 <?php else: ?>
                     <form method="post" id="formPagosGas">
                         <div class="table-responsive">
                             <table class="table table-bordered table-striped">
                                 <thead class="table-dark">
                                     <tr>
                                         <th class="checkbox-cell">
                                             <input type="checkbox" id="selectAll">
                                         </th>
                                         <th>Apartamento</th>
                                         <th>Inquilino</th>
                                         <th>Mes/Año</th>
                                         <th>Monto Gas</th>
                                         <th>Balance Inquilino</th>
                                         <th>Estado</th>
                                         <th>Fecha Registro Gas</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     <?php foreach ($gas_por_inquilino as $grupo): ?>
                                         <tr class="inquilino-header">
                                             <td colspan="8" class="fw-bold">
                                                 🏠 <?= $grupo['apto'] ?> -
                                                 👤 <?= $grupo['inquilino'] ?> -
                                                 💰 Balance: $<?= number_format($grupo['balance'], 2) ?>
                                             </td>
                                         </tr>
                                         <?php foreach ($grupo['gas_pendiente'] as $gas):
                                                $clase_fila = $gas['estado_balance'] == 'suficiente' ? 'suficiente' : 'insuficiente';
                                                $estado_texto = $gas['estado_balance'] == 'suficiente' ? '✅ Suficiente' : '❌ Insuficiente';
                                                $badge_class = $gas['estado_balance'] == 'suficiente' ? 'badge-suficiente' : 'badge-insuficiente';
                                            ?>
                                             <tr class="<?= $clase_fila ?>">
                                                 <td class="checkbox-cell">
                                                     <?php if ($gas['estado_balance'] == 'suficiente'): ?>
                                                         <input type="checkbox" name="pagos_seleccionados[]"
                                                             value="<?= $gas['id_gas'] ?>"
                                                             class="checkbox-pago"
                                                             data-monto="<?= $gas['total_gas'] ?>"
                                                             data-inquilino="<?= $grupo['inquilino'] ?>">
                                                     <?php else: ?>
                                                         <input type="checkbox" disabled title="Balance insuficiente">
                                                     <?php endif; ?>
                                                 </td>
                                                 <td class="fw-bold"><?= $gas['apto'] ?></td>
                                                 <td><?= $grupo['inquilino'] ?></td>
                                                 <td><?= $gas['mes'] ?> <?= $gas['anio'] ?></td>
                                                 <td class="fw-bold">$<?= number_format($gas['total_gas'], 2) ?></td>
                                                 <td>$<?= number_format($grupo['balance'], 2) ?></td>
                                                 <td>
                                                     <span class="badge <?= $badge_class ?>">
                                                         <?= $estado_texto ?>
                                                     </span>
                                                 </td>
                                                 <td><?= $gas['fecha_registro'] ?></td>
                                             </tr>
                                         <?php endforeach; ?>
                                     <?php endforeach; ?>
                                 </tbody>
                             </table>
                         </div>

                         <div class="mt-4 p-3 bg-light rounded">
                             <div class="row">
                                 <div class="col-md-6">
                                     <h5>Resumen de selección:</h5>
                                     <p id="resumen-seleccion">No hay pagos seleccionados</p>
                                     <p id="total-seleccionado" class="fw-bold h5">Total: $0.00</p>
                                 </div>
                                 <div class="col-md-6 text-end">
                                     <button type="submit" class="btn btn-success btn-lg" id="btnProcesarPagos">
                                         💾 Guardar Pagos Seleccionados
                                     </button>
                                     <br>
                                     <small class="text-muted">Solo se procesarán los pagos con balance suficiente</small>
                                 </div>
                             </div>
                         </div>
                     </form>
                 <?php endif; ?>
             </div>
         </div>
     </div>

     <script>
         $(document).ready(function() {
             let totalSeleccionado = 0;
             let cantidadSeleccionada = 0;

             // Seleccionar/deseleccionar todos (solo los habilitados)
             $('#selectAll').change(function() {
                 $('.checkbox-pago:enabled').prop('checked', this.checked).trigger('change');
             });

             // Actualizar resumen cuando cambia un checkbox
             $('.checkbox-pago').change(function() {
                 actualizarResumen();
             });

             function actualizarResumen() {
                 totalSeleccionado = 0;
                 cantidadSeleccionada = 0;
                 let resumenText = '';

                 $('.checkbox-pago:checked').each(function() {
                     const monto = parseFloat($(this).data('monto'));
                     const inquilino = $(this).data('inquilino');
                     totalSeleccionado += monto;
                     cantidadSeleccionada++;

                     if (resumenText.length < 100) { // Limitar longitud del resumen
                         if (resumenText) resumenText += ', ';
                         resumenText += inquilino + ' ($' + monto.toFixed(2) + ')';
                     }
                 });

                 // Actualizar displays
                 if (cantidadSeleccionada > 0) {
                     $('#resumen-seleccion').text(resumenText + (cantidadSeleccionada > 2 ? '...' : ''));
                     $('#total-seleccionado').text('Total: $' + totalSeleccionado.toFixed(2) + ' (' + cantidadSeleccionada + ' pago(s))');
                 } else {
                     $('#resumen-seleccion').text('No hay pagos seleccionados');
                     $('#total-seleccionado').text('Total: $0.00');
                 }
             }

             // Validar formulario antes de enviar
             $('#formPagosGas').submit(function(e) {
                 if (cantidadSeleccionada === 0) {
                     e.preventDefault();
                     alert('❌ Por favor selecciona al menos un pago para procesar.');
                     return false;
                 }

                 if (!confirm(`¿Estás seguro de que quieres procesar ${cantidadSeleccionada} pago(s) por un total de $${totalSeleccionado.toFixed(2)}?`)) {
                     e.preventDefault();
                     return false;
                 }
             });

             // Inicializar resumen
             actualizarResumen();
         });
     </script>

     <?php include("../../templates/footer.php"); ?>