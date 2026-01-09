<?php
ob_start();
session_start();
include("../../bd.php");

// 1. OBTENER PARÁMETROS DEL MODAL
$idcondominio = $_SESSION['idcondominio'];
$mes = $_GET['mes'] ?? date('n');
$anio = $_GET['anio'] ?? date('Y');

// Fuentes dinámicas
$font_size_title = $_GET['font_title'] ?? '11px';
$font_size_td = $_GET['font_td'] ?? '11px';
$font_size_value = $_GET['font_td'] ?? '11px';

// Convertir mes numérico a nombre
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
$mes_nombre = $meses[$mes] ?? $meses[date('n')];

// Obtener configuración del condominio
$sentencia_condominio = $conexion->prepare("SELECT mora, gas, cuota FROM tbl_condominios WHERE id = :id");
$sentencia_condominio->bindParam(":id", $idcondominio);
$sentencia_condominio->execute();
$config_condominio = $sentencia_condominio->fetch(PDO::FETCH_ASSOC);

$mostrar_mora = $config_condominio['mora'] == 'si';
$mostrar_gas = $config_condominio['gas'] == 'si';
$mostrar_cuota = $config_condominio['cuota'] == 'si';

//////////////////////////////////////////////////////////////////////////
////////   INGRESOS - DATOS CALCULADOS DESDE PAGOS REALES   ///////
//////////////////////////////////////////////////////////////////////////

$sql_ingresos = "
    SELECT 
        a.id, a.apto, a.condominos, a.fecha_ultimo_pago,
        
        -- 1. TOTAL MANTENIMIENTO
        (SELECT COALESCE(SUM(d.monto), 0) FROM tbl_pagos_detalle d
         JOIN tbl_pagos p ON d.id_pago = p.id_pago
         WHERE p.id_apto = a.id AND d.tipo_deuda = 'ticket' AND d.tipo_pagador = 'propietario'
           AND p.mes_ingreso = :mes_nombre AND p.anio_ingreso = :anio
        ) as pago_mantenimiento,

        -- 2. TOTAL GAS
        (
            (SELECT COALESCE(SUM(d.monto), 0) FROM tbl_pagos_detalle d
             JOIN tbl_pagos p ON d.id_pago = p.id_pago
             WHERE p.id_apto = a.id AND d.tipo_deuda = 'gas' AND d.tipo_pagador = 'propietario'
               AND p.mes_ingreso = :mes_nombre AND p.anio_ingreso = :anio
            ) +
            (SELECT COALESCE(SUM(d.monto), 0) FROM tbl_pagos_detalle d
             JOIN tbl_pagos_inquilinos p ON d.id_pago = p.id
             JOIN tbl_inquilinos i ON p.id_inquilino = i.id
             WHERE i.id_apto = a.id AND d.tipo_deuda = 'gas' AND d.tipo_pagador = 'inquilino'
               AND p.mes_gas = :mes_nombre AND p.anio_gas = :anio
            )
        ) as pago_gas,

        -- 3. TOTAL CUOTAS EXTRAS
        (SELECT COALESCE(SUM(d.monto), 0) FROM tbl_pagos_detalle d
         JOIN tbl_pagos p ON d.id_pago = p.id_pago
         WHERE p.id_apto = a.id AND d.tipo_deuda = 'cuota'
           AND p.mes_ingreso = :mes_nombre AND p.anio_ingreso = :anio
        ) as pago_cuota,

        -- 4. TOTAL ADELANTOS
        (SELECT COALESCE(SUM(d.monto), 0) FROM tbl_pagos_detalle d
         JOIN tbl_pagos p ON d.id_pago = p.id_pago
         WHERE p.id_apto = a.id AND d.tipo_deuda = 'adelanto'
           AND p.mes_ingreso = :mes_nombre AND p.anio_ingreso = :anio
        ) as pago_adelanto

    FROM tbl_aptos a
    WHERE a.id_condominio = :idcondominio
    ORDER BY a.apto ASC
";

$sentencia = $conexion->prepare($sql_ingresos);
$sentencia->bindParam(":idcondominio", $idcondominio);
$sentencia->bindParam(":mes_nombre", $mes_nombre);
$sentencia->bindParam(":anio", $anio);
$sentencia->execute();
$rows_aptos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

//////////////////////////////////////////
////////   GASTOS DEL MES ACTUAL   ///////
//////////////////////////////////////////

function obtenerGastos($conexion, $id, $tipo, $mes, $anio)
{
  $s = $conexion->prepare("SELECT * FROM tbl_gastos WHERE id_condominio = :id AND tipo_gasto = :tipo AND mes = :mes AND anio = :anio");
  $s->execute([':id' => $id, ':tipo' => $tipo, ':mes' => $mes, ':anio' => $anio]);
  return $s->fetchAll(PDO::FETCH_ASSOC);
}

$lista_gasto_Nomina = obtenerGastos($conexion, $idcondominio, "Nomina_Empleados", $mes_nombre, $anio);
$lista_gasto_Servicios = obtenerGastos($conexion, $idcondominio, "Servicios_Basicos", $mes_nombre, $anio);
$lista_gasto_Material = obtenerGastos($conexion, $idcondominio, "Gastos_Menores_Material_Gastable", $mes_nombre, $anio);
$lista_gasto_Imprevistos = obtenerGastos($conexion, $idcondominio, "Imprevistos", $mes_nombre, $anio);
$lista_gasto_Cargos = obtenerGastos($conexion, $idcondominio, "Cargos_Bancarios", $mes_nombre, $anio);
$lista_gasto_Igualados = obtenerGastos($conexion, $idcondominio, "Servicios_Igualados", $mes_nombre, $anio);

// BUSCAR MES ANTERIOR
$meses_array = ["ENERO", "FEBRERO", "MARZO", "ABRIL", "MAYO", "JUNIO", "JULIO", "AGOSTO", "SEPTIEMBRE", "OCTUBRE", "NOVIEMBRE", "DICIEMBRE"];
$indice_mes_actual = array_search(strtoupper($mes_nombre), $meses_array);
$indice_mes_anterior = ($indice_mes_actual - 1 + 12) % 12;
$anio_anterior = $anio;
if ($indice_mes_anterior === 11) {
  $anio_anterior--;
}
$mes_anterior = $meses_array[$indice_mes_anterior];

$sentencia = $conexion->prepare("SELECT * FROM tbl_estado_resultado WHERE id_condominio = :id AND mes = :mes AND anio = :anio");
$sentencia->execute([':id' => $idcondominio, ':mes' => $mes_anterior, ':anio' => $anio_anterior]);
$estado_anterior = $sentencia->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
  <title>Estado de Resultado</title>
  <style>
    * {
      font-family: Arial, sans-serif;
    }

    .con1 {
      text-transform: uppercase;
      text-align: center;
      font-size: 15px;
      font-weight: 600;
    }

    .con1p {
      margin: -2px;
    }

    .con2p {
      margin: 0px;
      background-color: lightgray;
    }

    table {
      border-collapse: collapse;
      width: 670px;
      text-align: left;
      margin-left: 0;
    }

    th,
    td {
      font-size: <?php echo $font_size_td; ?>;
      padding-left: 8px;
      padding-right: 8px;
      white-space: nowrap;
    }

    .con3 {
      margin: 0px;
      font-weight: 100;
      text-align: right;
    }

    .titlecontent {
      padding-left: 37px;
    }

    .text-right {
      text-align: right;
    }

    .section-title {
      font-size: <?php echo $font_size_title; ?>;
      margin: 0px;
      margin-top: 15px;
    }

    .section-value {
      font-size: <?php echo $font_size_value; ?>;
      margin-right: -10px;
      margin-top: -11px;
      float: right;
    }

    .col-apto {
      width: 8%;
    }

    .col-condominos {
      width: 15%;
    }

    .col-ultimo-pago {
      width: 12%;
    }

    .col-mantenimiento {
      width: 10%;
    }

    .col-gas {
      width: 8%;
    }

    .col-mora {
      width: 8%;
    }

    .col-cuota {
      width: 10%;
    }

    .col-total {
      width: 10%;
    }
  </style>
</head>

<body>

  <div class="con1">
    <p class="con2p">Condominio <?php echo $_SESSION['online']; ?></p>
    <p class="con1p">ESTADO DE RESULTADO</p>
    <p class="con1p">AL 30 DE <?php echo strtoupper($mes_nombre); ?> DEL AÑO <?php echo $anio; ?></p>
  </div>

  <?php
  $total_ingresos = 0;
  foreach ($rows_aptos as $row) {
    $mantenimiento = floatval($row['pago_mantenimiento']);
    $gas_val = floatval($row['pago_gas']);
    $cuota_val = floatval($row['pago_cuota']);
    $adelanto_val = floatval($row['pago_adelanto']);
    $mant_final = $mantenimiento + $adelanto_val;
    $mora_val = 0;
    $total_apto = $mant_final + $gas_val + $mora_val + $cuota_val;
    $total_ingresos += $total_apto;
  }
  ?>

  <h6 class="section-title">INGRESOS...................................................................................................................................................................................</h6>
  <h6 class="section-value"><?php echo number_format($total_ingresos, 2, '.', ','); ?></h6>

  <table>
    <tr>
      <th class="col-apto" align="left">Apto</th>
      <th class="col-condominos" align="left">Condominos</th>
      <th class="col-ultimo-pago" align="right">Ult. Pago</th>
      <th class="col-mantenimiento" align="right">Mant.</th>
      <?php if ($mostrar_gas): ?> <th class="col-gas" align="right">Gas</th> <?php endif; ?>
      <?php if ($mostrar_mora): ?> <th class="col-mora" align="right">Mora</th> <?php endif; ?>
      <?php if ($mostrar_cuota): ?> <th class="col-cuota" align="right">Cuota Extra</th> <?php endif; ?>
      <th class="col-total" align="right">Total</th>
    </tr>
    <?php foreach ($rows_aptos as $row):
      $mantenimiento = floatval($row['pago_mantenimiento']);
      $gas_val = floatval($row['pago_gas']);
      $cuota_val = floatval($row['pago_cuota']);
      $adelanto_val = floatval($row['pago_adelanto']);
      $mora_val = 0;
      $mant_mostrar = $mantenimiento + $adelanto_val;
      $total_apto = $mant_mostrar + $gas_val + $mora_val + $cuota_val;

      // MODIFICACIÓN AQUI: Usar la fecha_ultimo_pago de tbl_aptos
      $fecha_raw = $row['fecha_ultimo_pago'];
      $mes_a_mostrar = (!empty($fecha_raw) && $fecha_raw != '0000-00-00') ? date('d/m/Y', strtotime($fecha_raw)) : '';
    ?>
      <tr>
        <td><?php echo htmlspecialchars($row['apto']); ?></td>
        <td><?php echo htmlspecialchars($row['condominos']); ?></td>

        <td align="right"><?php echo $fecha_raw; ?></td>

        <td align="right" class="text-right moneda"><?php echo number_format($mant_mostrar, 2, '.', ','); ?></td>
        <?php if ($mostrar_gas): ?> <td align="right" class="text-right moneda"><?php echo number_format($gas_val, 2, '.', ','); ?></td> <?php endif; ?>
        <?php if ($mostrar_mora): ?> <td align="right" class="text-right moneda"><?php echo number_format($mora_val, 2, '.', ','); ?></td> <?php endif; ?>
        <?php if ($mostrar_cuota): ?> <td align="right" class="text-right moneda"><?php echo number_format($cuota_val, 2, '.', ','); ?></td> <?php endif; ?>
        <td align="right" class="text-right moneda"><?php echo number_format($total_apto, 2, '.', ','); ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <?php
  $totalGastos = 0;
  $categorias_gastos = [
    "Nomina Empleados" => $lista_gasto_Nomina,
    "Servicios Básicos" => $lista_gasto_Servicios,
    "Gastos Menores, Material Gastable" => $lista_gasto_Material,
    "Imprevistos" => $lista_gasto_Imprevistos,
    "Cargos Bancarios" => $lista_gasto_Cargos,
    "Servicios Igualados" => $lista_gasto_Igualados
  ];

  foreach ($categorias_gastos as $lista) {
    foreach ($lista as $g) $totalGastos += floatval($g['monto']);
  }

  $res_anterior = $estado_anterior['resultado_actual'] ?? 0;
  ?>

  <h6 class="section-title">GASTOS........................................................................................................................................................................................</h6>
  <h6 class="section-value"><?php echo number_format($totalGastos, 2, '.', ','); ?></h6>

  <?php foreach ($categorias_gastos as $titulo => $lista): ?>
    <table>
      <tr>
        <th align="left"><?php echo $titulo; ?></th>
        <th></th>
      </tr>
      <?php foreach ($lista as $row): ?>
        <tr>
          <td class="titlecontent"><?php echo htmlspecialchars($row['detalles']); ?></td>
          <td class="text-right moneda"><?php echo number_format(floatval($row['monto']), 2, '.', ','); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($lista)): ?>
        <tr>
          <td class="titlecontent">No hay gastos registrados</td>
          <td class="text-right moneda">0.00</td>
        </tr>
      <?php endif; ?>
    </table>
  <?php endforeach; ?>

  <br>

  <?php $cierre = $total_ingresos - $totalGastos; ?>
  <h6 class="section-title">CIERRE DEL MES......................................................................................................................................................................</h6>
  <h6 class="section-value"><?php echo number_format($cierre, 2, '.', ','); ?></h6>

  <h6 style="font-size: 12px; margin: 0px; margin-top: 15px; font-weight: 100;margin-left: 20px; font-family: Arial, sans-serif;">Resultado de las Operaciones del Mes Anterior</h6>
  <h6 class="section-value"><?php echo number_format($res_anterior, 2, '.', ','); ?></h6>

  <?php $res_actual = $cierre + $res_anterior; ?>
  <h6 class="section-title">Resultado Actual de las Operaciones.....................................................................................................................................</h6>
  <h6 class="section-value"><?php echo number_format($res_actual, 2, '.', ','); ?></h6>

</body>

</html>

<?php
$html = ob_get_clean();
require_once '../../libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->set(array('isRemoteEnabled' => true));
$dompdf->setOptions($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('letter');
$dompdf->render();
$filename = "estado_resultado_" . $_SESSION['online'] . "_" . $mes_nombre . "_" . $anio . ".pdf";
$dompdf->stream($filename, array("Attachment" => false));
?>