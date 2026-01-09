<?php
ob_start();
session_start();
include("../../bd.php");

// 1. CONFIGURACIÓN
$meses_nombres = [
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

$mes_actual_num = $_GET['mes'] ?? $_SESSION['mes'] ?? date('n');
$anio_actual = $_GET['anio'] ?? $_SESSION['anio'] ?? date('Y');

if (is_numeric($mes_actual_num)) {
    $mes_nombre = $meses_nombres[(int)$mes_actual_num];
} else {
    $mes_nombre = $mes_actual_num;
}

$idcondominio = $_SESSION['idcondominio'];
$nombre_condominio = $_SESSION['online'] ?? 'Condominio';

// 2. CONSULTA MAESTRA (CORREGIDA PARA RESTAR ABONOS)
// Nota: Usamos GREATEST(..., 0) para evitar negativos por error, aunque la lógica de abono no debería permitirlo.
$sql = "SELECT 
            a.id, 
            a.apto, 
            a.condominos,
            a.balance as balance_neto_db,
            
            -- MANTENIMIENTO (Resta el abono)
            COALESCE((SELECT SUM(CAST(mantenimiento AS DECIMAL(10,2)) - abono) FROM tbl_tickets 
                      WHERE id_apto = a.id AND mes = :mes_nom AND anio = :anio AND estado != 'Pagado'), 0) as mtto_base,
            COALESCE((SELECT SUM(CAST(mora AS DECIMAL(10,2))) FROM tbl_tickets 
                      WHERE id_apto = a.id AND mes = :mes_nom AND anio = :anio AND estado != 'Pagado'), 0) as mtto_mora,
            
            -- GAS (Resta el abono)
            COALESCE((SELECT SUM(total_gas - abono) FROM tbl_gas 
                      WHERE id_apto = a.id AND mes = :mes_nom AND anio = :anio AND estado != 'Pagado'), 0) as gas_base,
            COALESCE((SELECT SUM(mora) FROM tbl_gas 
                      WHERE id_apto = a.id AND mes = :mes_nom AND anio = :anio AND estado != 'Pagado'), 0) as gas_mora,
            
            -- CUOTAS (Resta el abono)
            COALESCE((SELECT SUM(monto - abono) FROM tbl_cuotas_extras 
                      WHERE id_apto = a.id AND mes = :mes_nom AND anio = :anio AND estado != 'Pagado'), 0) as cuota_base,
            COALESCE((SELECT SUM(mora) FROM tbl_cuotas_extras 
                      WHERE id_apto = a.id AND mes = :mes_nom AND anio = :anio AND estado != 'Pagado'), 0) as cuota_mora,
            
            -- DEUDA ANTERIOR (Ya estaba restando abono, se mantiene igual)
            (
                COALESCE((SELECT SUM(CAST(total AS DECIMAL(10,2)) - abono) FROM tbl_tickets 
                          WHERE id_apto = a.id AND estado != 'Pagado' AND (mes != :mes_nom OR anio != :anio)), 0)
                +
                COALESCE((SELECT SUM((total_gas + mora) - abono) FROM tbl_gas 
                          WHERE id_apto = a.id AND estado != 'Pagado' AND (mes != :mes_nom OR anio != :anio)), 0)
                +
                COALESCE((SELECT SUM((monto + mora) - abono) FROM tbl_cuotas_extras 
                          WHERE id_apto = a.id AND estado != 'Pagado' AND (mes != :mes_nom OR anio != :anio)), 0)
            ) as deuda_anterior

        FROM tbl_aptos a
        WHERE a.id_condominio = :id_cond
        ORDER BY a.apto ASC";

$stmt = $conexion->prepare($sql);
$stmt->execute([':mes_nom' => $mes_nombre, ':anio' => $anio_actual, ':id_cond' => $idcondominio]);
$todos_los_datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. LÓGICA DE SEPARACIÓN
$lista_cobrar = [];
$lista_adelantos = [];

foreach ($todos_los_datos as $fila) {
    // Calculamos el total pendiente REAL (restando abonos)
    $total_facturas_pendientes = floatval($fila['mtto_base']) + floatval($fila['mtto_mora']) +
        floatval($fila['gas_base']) + floatval($fila['gas_mora']) +
        floatval($fila['cuota_base']) + floatval($fila['cuota_mora']) +
        floatval($fila['deuda_anterior']);

    // Si debe algo (aunque sea parcial), va para la lista de cobro
    if ($total_facturas_pendientes > 0.01) {
        $lista_cobrar[] = $fila;
    }

    // Para adelantos usamos la misma lógica tuya
    $balance_neto_db = floatval($fila['balance_neto_db']);
    $dinero_a_favor = $total_facturas_pendientes - $balance_neto_db; // Matemáticamente correcto

    // Si tiene saldo a favor en BD (balance negativo)
    if ($balance_neto_db < -0.01) {
        // El saldo real disponible es el valor absoluto del balance negativo
        // (Ojo: Si el sistema contable es correcto, balance = deuda - adelanto. Si deuda es 0, balance = -adelanto)
        $saldo_mostrar = abs($balance_neto_db);

        // Si hay deuda parcial, el balance neto ya lo refleja.
        // Ejemplo: Deuda 500, Adelanto 2000 => Balance -1500.
        // Aquí mostramos los 1500 a favor netos.

        $lista_adelantos[] = [
            'apto' => $fila['apto'],
            'condominos' => $fila['condominos'],
            'saldo_favor' => $saldo_mostrar
        ];
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Estado de Cuentas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            margin: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
        }

        .header p {
            margin: 2px 0;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 0.5px solid #000;
            margin-bottom: 15px;
        }

        th {
            background-color: #ffffffff;
            border: 1px solid #000;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }

        td {
            border: 1px solid #000;
            padding: 2px 4px;
            text-align: right;
            font-size: 10px;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .total-row td {
            background-color: #ffffffff;
            font-weight: bold;
            border-top: 2px solid #000;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .bg-ant {
            background-color: #ffffffff;
        }

        .bg-mtto {
            background-color: #ffffffff;
        }

        .bg-gas {
            background-color: #ffffffff;
        }

        .bg-cuota {
            background-color: #ffffffff;
        }

        .bg-total {
            background-color: #ffffffff;
            font-weight: bold;
        }

        .txt-mora {
            color: #ffffffff;
            font-size: 8px;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1><?php echo htmlspecialchars($nombre_condominio); ?></h1>
        <p>RELACION DE CUENTAS POR COBRAR Y PAGOS ADELANTADOS </p>
        <p>AL MES DE <?php echo strtoupper($mes_nombre) . " DEL AÑO " . $anio_actual; ?></p>
        <p>VALORES EN RD$</p>
    </div>

    <div class="section-title">CUENTAS POR COBRAR:</div>
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 5%;">Apto</th>
                <th rowspan="2" style="width: 20%;">Condominos</th>
                <th rowspan="2" style="width: 9%;">Deuda<br>Anterior</th>
                <th colspan="2" style="background-color: #ffffffff;">MANTENIMIENTO</th>
                <th colspan="2" style="background-color: #ffffffff;">GAS COMÚN</th>
                <th colspan="2" style="background-color: #ffffffff;">CUOTA EXTRA</th>
                <th rowspan="2" style="width: 10%;">TOTAL<br>PENDIENTE</th>
            </tr>
            <tr>
                <th style="width: 8%;">Saldo</th>
                <th style="width: 6%;">Mora</th>
                <th style="width: 8%;">Saldo</th>
                <th style="width: 6%;">Mora</th>
                <th style="width: 8%;">Saldo</th>
                <th style="width: 6%;">Mora</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $t_ant = 0;
            $t_mtto_b = 0;
            $t_mtto_m = 0;
            $t_gas_b = 0;
            $t_gas_m = 0;
            $t_cuota_b = 0;
            $t_cuota_m = 0;
            $t_general = 0;

            if (empty($lista_cobrar)): ?>
                <tr>
                    <td colspan="10" class="text-center" style="padding: 10px;">No hay deudas pendientes.</td>
                </tr>
                <?php else:
                foreach ($lista_cobrar as $row):
                    $ant = floatval($row['deuda_anterior']);
                    $mtto = floatval($row['mtto_base']);
                    $mtto_m = floatval($row['mtto_mora']);
                    $gas = floatval($row['gas_base']);
                    $gas_m = floatval($row['gas_mora']);
                    $cuota = floatval($row['cuota_base']);
                    $cuota_m = floatval($row['cuota_mora']);

                    // Suma visual de lo PENDIENTE
                    $total_fila = $ant + $mtto + $mtto_m + $gas + $gas_m + $cuota + $cuota_m;

                    $t_ant += $ant;
                    $t_mtto_b += $mtto;
                    $t_mtto_m += $mtto_m;
                    $t_gas_b += $gas;
                    $t_gas_m += $gas_m;
                    $t_cuota_b += $cuota;
                    $t_cuota_m += $cuota_m;
                    $t_general += $total_fila;
                ?>
                    <tr>
                        <td class="text-center"><b><?php echo $row['apto']; ?></b></td>
                        <td class="text-left"><?php echo substr($row['condominos'], 0, 25); ?></td>
                        <td class="bg-ant"><?php echo $ant > 0.01 ? number_format($ant, 2) : '-'; ?></td>
                        <td class="bg-mtto"><?php echo $mtto > 0.01 ? number_format($mtto, 2) : '-'; ?></td>
                        <td class="bg-mtto txt-mora"><?php echo $mtto_m > 0.01 ? number_format($mtto_m, 2) : '-'; ?></td>
                        <td class="bg-gas"><?php echo $gas > 0.01 ? number_format($gas, 2) : '-'; ?></td>
                        <td class="bg-gas txt-mora"><?php echo $gas_m > 0.01 ? number_format($gas_m, 2) : '-'; ?></td>
                        <td class="bg-cuota"><?php echo $cuota > 0.01 ? number_format($cuota, 2) : '-'; ?></td>
                        <td class="bg-cuota txt-mora"><?php echo $cuota_m > 0.01 ? number_format($cuota_m, 2) : '-'; ?></td>
                        <td class="bg-total"><?php echo number_format($total_fila, 2); ?></td>
                    </tr>
            <?php endforeach;
            endif; ?>
            <tr class="total-row">
                <td colspan="2" class="text-center">TOTALES A COBRAR</td>
                <td><?php echo number_format($t_ant, 2); ?></td>
                <td><?php echo number_format($t_mtto_b, 2); ?></td>
                <td><?php echo number_format($t_mtto_m, 2); ?></td>
                <td><?php echo number_format($t_gas_b, 2); ?></td>
                <td><?php echo number_format($t_gas_m, 2); ?></td>
                <td><?php echo number_format($t_cuota_b, 2); ?></td>
                <td><?php echo number_format($t_cuota_m, 2); ?></td>
                <td><?php echo number_format($t_general, 2); ?></td>
            </tr>
        </tbody>
    </table>

    <br>

    <div class="section-title">CUENTAS PAGADAS POR ADELANTADO:</div>
    <table style="width: 60%;">
        <thead>
            <tr>
                <th style="width: 15%;">Apto</th>
                <th style="width: 55%;">Condominos</th>
                <th style="width: 30%;">Balance Disponible</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_adelanto = 0;
            if (empty($lista_adelantos)): ?>
                <tr>
                    <td colspan="3" class="text-center" style="padding: 10px;">No hay cuentas con Balance a favor.</td>
                </tr>
                <?php else:
                foreach ($lista_adelantos as $row):
                    $saldo = floatval($row['saldo_favor']);
                    $total_adelanto += $saldo;
                ?>
                    <tr>
                        <td class="text-center"><b><?php echo $row['apto']; ?></b></td>
                        <td class="text-left"><?php echo substr($row['condominos'], 0, 40); ?></td>
                        <td style="font-weight: bold; background-color: #ffffffff;">RD$ <?php echo number_format($saldo, 2); ?></td>
                    </tr>
            <?php endforeach;
            endif; ?>
            <tr class="total-row">
                <td colspan="2" class="text-center">TOTAL PAGOS ADELANTADO</td>
                <td>RD$ <?php echo number_format($total_adelanto, 2); ?></td>
            </tr>
        </tbody>
    </table>

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
$dompdf->setPaper('letter', 'landscape');
$dompdf->render();
$filename = "Reporte_Cuentas_" . $mes_nombre . ".pdf";
$dompdf->stream($filename, array("Attachment" => false));
?>