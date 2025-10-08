<?php
// Iniciar sesión primero
if (session_status() === PHP_SESSION_NONE) {
   session_start();
}

include("../../bd.php");

$id_condominio = $_SESSION['idcondominio'];

// Obtener información del condominio
$sentencia_condominio = $conexion->prepare("SELECT nombre, ubicacion, telefono FROM tbl_condominios WHERE id = :id");
$sentencia_condominio->bindParam(":id", $id_condominio);
$sentencia_condominio->execute();
$condominio = $sentencia_condominio->fetch(PDO::FETCH_ASSOC);

// Obtener todos los apartamentos con sus balances
$sentencia = $conexion->prepare("
    SELECT 
        a.apto,
        a.condominos,
        a.balance,
        a.telefono,
        a.correo,
        a.fecha_ultimo_pago,
        (SELECT SUM(monto) FROM tbl_pagos p WHERE p.id_apto = a.id AND p.id_condominio = a.id_condominio) as total_pagos,
        (SELECT SUM(mantenimiento + mora + gas + cuota) FROM tbl_tickets t WHERE t.id_apto = a.id AND t.id_condominio = a.id_condominio AND t.estado = 'Pendiente') as deuda_pendiente
    FROM tbl_aptos a
    WHERE a.id_condominio = :id_condominio
    ORDER BY a.balance ASC, a.apto ASC
");
$sentencia->bindParam(":id_condominio", $id_condominio);
$sentencia->execute();
$apartamentos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

// Calcular estadísticas
$total_aptos = count($apartamentos);
$total_pagos_general = array_sum(array_column($apartamentos, 'total_pagos'));
$total_deuda_general = array_sum(array_column($apartamentos, 'deuda_pendiente'));
$total_balance_general = array_sum(array_column($apartamentos, 'balance'));
$aptos_al_dia = count(array_filter($apartamentos, function ($a) {
   return $a['balance'] >= 0;
}));
$aptos_deudores = count(array_filter($apartamentos, function ($a) {
   return $a['balance'] < 0;
}));

// Configurar headers para Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="reporte_balances_' . ($condominio['nombre'] ?? 'condominio') . '_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM para UTF-8
echo "\xEF\xBB\xBF";
?>
<html>

<head>
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
   <style>
      table {
         border-collapse: collapse;
         width: 100%;
         font-family: Arial, sans-serif;
      }

      th {
         background-color: #4CAF50;
         color: white;
         font-weight: bold;
         padding: 10px;
         text-align: center;
         border: 1px solid #ddd;
      }

      td {
         padding: 8px;
         border: 1px solid #ddd;
      }

      .header {
         background-color: #2c3e50;
         color: white;
         font-size: 16px;
         font-weight: bold;
      }

      .subheader {
         background-color: #34495e;
         color: white;
      }

      .positive {
         background-color: #d4edda;
         color: #155724;
      }

      .negative {
         background-color: #f8d7da;
         color: #721c24;
      }

      .warning {
         background-color: #fff3cd;
         color: #856404;
      }

      .info {
         background-color: #d1ecf1;
         color: #0c5460;
      }

      .total-row {
         background-color: #e9ecef;
         font-weight: bold;
      }

      .stat-box {
         background-color: #f8f9fa;
         border: 1px solid #dee2e6;
         padding: 10px;
         margin: 5px 0;
      }

      .text-right {
         text-align: right;
      }

      .text-center {
         text-align: center;
      }

      .text-success {
         color: #28a745;
      }

      .text-danger {
         color: #dc3545;
      }

      .text-warning {
         color: #ffc107;
      }
   </style>
</head>

<body>
   <table>
      <!-- ENCABEZADO PRINCIPAL -->
      <tr>
         <td colspan="11" class="header text-center" style="font-size: 18px; padding: 15px;">
            REPORTE COMPLETO DE BALANCES - <?= strtoupper($condominio['nombre'] ?? 'CONDOMINIO') ?>
         </td>
      </tr>

      <!-- INFORMACIÓN DEL CONDOMINIO -->
      <tr>
         <td colspan="11" class="subheader" style="padding: 10px;">
            <strong>Dirección:</strong> <?= $condominio['direccion'] ?? 'No especificada' ?> |
            <strong>Teléfono:</strong> <?= $condominio['telefono'] ?? 'No especificado' ?> |
            <strong>Generado:</strong> <?= date('d/m/Y H:i:s') ?>
         </td>
      </tr>

      <!-- ESPACIO -->
      <tr>
         <td colspan="11" style="height: 10px;"></td>
      </tr>

      <!-- ESTADÍSTICAS RÁPIDAS -->
      <tr>
         <td colspan="11" style="background-color: #f8f9fa; padding: 15px;">
            <table style="width: 100%; border: none;">
               <tr>
                  <td style="border: none; width: 25%;" class="stat-box text-center">
                     <div style="font-size: 14px; color: #6c757d;">TOTAL APARTAMENTOS</div>
                     <div style="font-size: 20px; font-weight: bold; color: #007bff;"><?= $total_aptos ?></div>
                  </td>
                  <td style="border: none; width: 25%;" class="stat-box text-center">
                     <div style="font-size: 14px; color: #6c757d;">AL DÍA</div>
                     <div style="font-size: 20px; font-weight: bold; color: #28a745;"><?= $aptos_al_dia ?></div>
                     <div style="font-size: 12px;">(<?= $total_aptos > 0 ? number_format(($aptos_al_dia / $total_aptos) * 100, 1) : 0 ?>%)</div>
                  </td>
                  <td style="border: none; width: 25%;" class="stat-box text-center">
                     <div style="font-size: 14px; color: #6c757d;">CON DEUDA</div>
                     <div style="font-size: 20px; font-weight: bold; color: #dc3545;"><?= $aptos_deudores ?></div>
                     <div style="font-size: 12px;">(<?= $total_aptos > 0 ? number_format(($aptos_deudores / $total_aptos) * 100, 1) : 0 ?>%)</div>
                  </td>
                  <td style="border: none; width: 25%;" class="stat-box text-center">
                     <div style="font-size: 14px; color: #6c757d;">BALANCE GENERAL</div>
                     <div style="font-size: 16px; font-weight: bold; color: <?= $total_balance_general >= 0 ? '#28a745' : '#dc3545' ?>;">
                        RD$ <?= number_format($total_balance_general, 2) ?>
                     </div>
                  </td>
               </tr>
            </table>
         </td>
      </tr>

      <!-- ESPACIO -->
      <tr>
         <td colspan="11" style="height: 15px;"></td>
      </tr>

      <!-- COLUMNAS DE LA TABLA -->
      <tr>
         <th>#</th>
         <th>APARTAMENTO</th>
         <th>CONDÓMINO</th>
         <th>TELÉFONO</th>
         <th>CORREO</th>
         <th>TOTAL PAGOS</th>
         <th>DEUDA PENDIENTE</th>
         <th>BALANCE</th>
         <th>ESTADO</th>
         <th>ÚLTIMO PAGO</th>
         <th>OBSERVACIONES</th>
      </tr>

      <!-- DATOS DE APARTAMENTOS -->
      <?php
      $contador = 1;
      foreach ($apartamentos as $apto):
         $estado = $apto['balance'] >= 0 ? 'AL DÍA' : 'DEUDOR';
         $clase_fila = $apto['balance'] >= 0 ? 'positive' : 'negative';
         $observaciones = '';

         if ($apto['balance'] < -1000) {
            $observaciones = 'MOROSO';
            $clase_fila = 'warning';
         }

         if (!$apto['fecha_ultimo_pago'] || $apto['fecha_ultimo_pago'] == '0000-00-00') {
            $observaciones .= $observaciones ? ' | ' : '';
            $observaciones .= 'SIN PAGOS';
         }
      ?>
         <tr class="<?= $clase_fila ?>">
            <td class="text-center"><?= $contador++ ?></td>
            <td><strong><?= $apto['apto'] ?></strong></td>
            <td><?= $apto['condominos'] ?></td>
            <td><?= $apto['telefono'] ?></td>
            <td><?= $apto['correo'] ?></td>
            <td class="text-right">RD$ <?= number_format($apto['total_pagos'], 2) ?></td>
            <td class="text-right">RD$ <?= number_format($apto['deuda_pendiente'], 2) ?></td>
            <td class="text-right"><strong>RD$ <?= number_format($apto['balance'], 2) ?></strong></td>
            <td class="text-center"><?= $estado ?></td>
            <td class="text-center">
               <?= $apto['fecha_ultimo_pago'] && $apto['fecha_ultimo_pago'] != '0000-00-00' ?
                  date('d/m/Y', strtotime($apto['fecha_ultimo_pago'])) : 'NUNCA' ?>
            </td>
            <td class="text-center"><?= $observaciones ?></td>
         </tr>
      <?php endforeach; ?>

      <!-- FILA DE TOTALES -->
      <tr class="total-row">
         <td colspan="5" class="text-center"><strong>TOTALES GENERALES</strong></td>
         <td class="text-right text-success"><strong>RD$ <?= number_format($total_pagos_general, 2) ?></strong></td>
         <td class="text-right text-danger"><strong>RD$ <?= number_format($total_deuda_general, 2) ?></strong></td>
         <td class="text-right <?= $total_balance_general >= 0 ? 'text-success' : 'text-danger' ?>">
            <strong>RD$ <?= number_format($total_balance_general, 2) ?></strong>
         </td>
         <td colspan="3" class="text-center">
            <?= $total_balance_general >= 0 ? 'SALDO A FAVOR' : 'SALDO DEUDOR' ?>
         </td>
      </tr>

      <!-- ESPACIO -->
      <tr>
         <td colspan="11" style="height: 20px;"></td>
      </tr>

      <!-- RESUMEN ESTADÍSTICO DETALLADO -->
      <tr>
         <td colspan="11" class="header text-center">
            RESUMEN ESTADÍSTICO DETALLADO
         </td>
      </tr>

      <!-- ESTADÍSTICAS DETALLADAS -->
      <tr>
         <td colspan="11">
            <table style="width: 100%; border: none; font-size: 12px;">
               <tr>
                  <td style="border: none; width: 33%; padding: 8px;">
                     <strong>📊 DISTRIBUCIÓN:</strong><br>
                     • Total Apartamentos: <?= $total_aptos ?><br>
                     • Al Día: <?= $aptos_al_dia ?> (<?= $total_aptos > 0 ? number_format(($aptos_al_dia / $total_aptos) * 100, 1) : 0 ?>%)<br>
                     • Con Deuda: <?= $aptos_deudores ?> (<?= $total_aptos > 0 ? number_format(($aptos_deudores / $total_aptos) * 100, 1) : 0 ?>%)<br>
                     • Morosos (> RD$ 1,000): <?= count(array_filter($apartamentos, function ($a) {
                                                   return $a['balance'] < -1000;
                                                })) ?>
                  </td>
                  <td style="border: none; width: 33%; padding: 8px;">
                     <strong>💰 MOVIMIENTOS:</strong><br>
                     • Total Pagado: RD$ <?= number_format($total_pagos_general, 2) ?><br>
                     • Deuda Pendiente: RD$ <?= number_format($total_deuda_general, 2) ?><br>
                     • Balance General: RD$ <?= number_format($total_balance_general, 2) ?><br>
                     • Deuda Acumulada: RD$ <?= number_format(abs($total_balance_general), 2) ?>
                  </td>
                  <td style="border: none; width: 33%; padding: 8px;">
                     <strong>📈 PROMEDIOS:</strong><br>
                     • Pago Promedio: RD$ <?= number_format($total_aptos > 0 ? $total_pagos_general / $total_aptos : 0, 2) ?><br>
                     • Deuda Promedio: RD$ <?= number_format($total_aptos > 0 ? $total_deuda_general / $total_aptos : 0, 2) ?><br>
                     • Balance Promedio: RD$ <?= number_format($total_aptos > 0 ? $total_balance_general / $total_aptos : 0, 2) ?><br>
                     • Tasa de Cumplimiento: <?= $total_aptos > 0 ? number_format(($aptos_al_dia / $total_aptos) * 100, 1) : 0 ?>%
                  </td>
               </tr>
            </table>
         </td>
      </tr>

      <!-- TOP 5 DEUDORES -->
      <tr>
         <td colspan="11" style="height: 15px;"></td>
      </tr>
      <tr>
         <td colspan="11" class="subheader text-center">
            🚨 TOP 5 DEUDORES
         </td>
      </tr>
      <?php
      $top_deudores = array_slice(array_filter($apartamentos, function ($a) {
         return $a['balance'] < 0;
      }), 0, 5);
      if (count($top_deudores) > 0):
      ?>
         <tr>
            <td colspan="11">
               <table style="width: 100%; border: none; font-size: 11px;">
                  <tr style="background-color: #f8d7da;">
                     <th style="border: 1px solid #ddd; padding: 5px;">#</th>
                     <th style="border: 1px solid #ddd; padding: 5px;">Apartamento</th>
                     <th style="border: 1px solid #ddd; padding: 5px;">Condómino</th>
                     <th style="border: 1px solid #ddd; padding: 5px;">Deuda</th>
                     <th style="border: 1px solid #ddd; padding: 5px;">Teléfono</th>
                  </tr>
                  <?php foreach ($top_deudores as $index => $deudor): ?>
                     <tr style="background-color: #f8d7da;">
                        <td style="border: 1px solid #ddd; padding: 5px; text-align: center;"><?= $index + 1 ?></td>
                        <td style="border: 1px solid #ddd; padding: 5px;"><?= $deudor['apto'] ?></td>
                        <td style="border: 1px solid #ddd; padding: 5px;"><?= $deudor['condominos'] ?></td>
                        <td style="border: 1px solid #ddd; padding: 5px; text-align: right; color: #dc3545; font-weight: bold;">
                           RD$ <?= number_format($deudor['balance'], 2) ?>
                        </td>
                        <td style="border: 1px solid #ddd; padding: 5px;"><?= $deudor['telefono'] ?></td>
                     </tr>
                  <?php endforeach; ?>
               </table>
            </td>
         </tr>
      <?php else: ?>
         <tr>
            <td colspan="11" class="text-center" style="padding: 10px; background-color: #d4edda;">
               ✅ No hay deudores en el condominio
            </td>
         </tr>
      <?php endif; ?>

      <!-- INFORMACIÓN ADICIONAL -->
      <tr>
         <td colspan="11" style="height: 15px;"></td>
      </tr>
      <tr>
         <td colspan="11" class="info" style="padding: 10px; font-size: 11px;">
            <strong>💡 INFORMACIÓN DEL REPORTE:</strong><br>
            • <strong>Fórmula de cálculo:</strong> BALANCE = TOTAL PAGOS - DEUDA PENDIENTE<br>
            • <strong>Deuda Pendiente:</strong> Suma de todos los tickets con estado "Pendiente"<br>
            • <strong>Total Pagos:</strong> Suma de todos los pagos registrados en el sistema<br>
            • <strong>Moroso:</strong> Apartamento con deuda mayor a RD$ 1,000<br>
            • <strong>Generado por:</strong> Sistema de Gestión Condominial | <?= date('d/m/Y H:i:s') ?>
         </td>
      </tr>

      <!-- PIE DE PÁGINA -->
      <tr>
         <td colspan="11" style="text-align: center; padding: 10px; font-size: 10px; color: #6c757d; border-top: 2px solid #dee2e6;">
            Este reporte fue generado automáticamente por el Sistema de Gestión Condominial.<br>
            Para más información, contacte al administrador del condominio.
         </td>
      </tr>
   </table>
</body>

</html>