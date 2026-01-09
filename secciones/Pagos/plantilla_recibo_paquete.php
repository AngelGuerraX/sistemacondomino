<?php
// Si este archivo se llama directamente, incluye la BD. 
// Si se incluye desde enviar_paquete_facturas.php, la BD ya estará cargada.
include_once("../../bd.php");

if (session_status() === PHP_SESSION_NONE) session_start();

// 1. VALIDAR Y OBTENER ID
// Intentamos obtenerlo por GET (navegador) o usamos la variable local (script de envío)
$id_pago = $_GET['id_pago'] ?? $id_pago ?? null;

if (!$id_pago) {
    echo "Error: No se especificó el pago (ID nulo).";
    exit;
}

// 2. LÓGICA DE DATOS
// Primero verificamos si el pago existe en la tabla de PROPIETARIOS usando 'id_pago'
$stmt_check = $conexion->prepare("SELECT id_pago FROM tbl_pagos WHERE id_pago = :id");
$stmt_check->execute([':id' => $id_pago]);
$es_propietario = $stmt_check->fetch();

if ($es_propietario) {
    $tipo_pagador = 'propietario';
    // Nota: Aquí usamos 'id_pago'
    $sql_cabecera = "SELECT p.*, a.apto, a.condominos as nombre_cliente, c.nombre as nombre_condominio, c.ubicacion, c.telefono as tel_condominio
                     FROM tbl_pagos p
                     INNER JOIN tbl_aptos a ON p.id_apto = a.id
                     INNER JOIN tbl_condominios c ON p.id_condominio = c.id
                     WHERE p.id_pago = :id";
} else {
    $tipo_pagador = 'inquilino';
    // Nota: En la tabla de inquilinos la llave primaria suele ser 'id'
    $sql_cabecera = "SELECT p.*, a.apto, i.nombre as nombre_cliente, c.nombre as nombre_condominio, c.ubicacion, c.telefono as tel_condominio
                     FROM tbl_pagos_inquilinos p
                     INNER JOIN tbl_inquilinos i ON p.id_inquilino = i.id
                     INNER JOIN tbl_aptos a ON i.id_apto = a.id
                     INNER JOIN tbl_condominios c ON p.id_condominio = c.id
                     WHERE p.id = :id";
}

$stmt = $conexion->prepare($sql_cabecera);
$stmt->execute([':id' => $id_pago]);
$recibo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recibo) {
    echo "<h3>Error: Recibo no encontrado en la base de datos (ID Pago: $id_pago).</h3>";
    exit;
}

// 3. OBTENER DETALLES (Items pagados)
$sql_detalle = "SELECT d.monto, d.tipo_deuda,
                t.mes as mes_ticket, t.anio as anio_ticket,
                g.mes as mes_gas, g.anio as anio_gas,
                ce.descripcion as desc_cuota
                FROM tbl_pagos_detalle d
                LEFT JOIN tbl_tickets t ON d.id_deuda = t.id AND d.tipo_deuda = 'ticket'
                LEFT JOIN tbl_gas g ON d.id_deuda = g.id AND d.tipo_deuda = 'gas'
                LEFT JOIN tbl_cuotas_extras ce ON d.id_deuda = ce.id AND d.tipo_deuda = 'cuota'
                WHERE d.id_pago = :id_pago AND d.tipo_pagador = :tipo";

$stmt_det = $conexion->prepare($sql_detalle);
$stmt_det->execute([':id_pago' => $id_pago, ':tipo' => $tipo_pagador]);
$detalles = $stmt_det->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recibo #<?php echo $id_pago; ?></title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            color: #333;
            background-color: #f9f9f9;
            /* Fondo gris suave para pantalla */
        }

        .invoice-box {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            border: 1px solid #eee;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }

        /* Estilos de Tablas (Seguros para PDF) */
        .w-100 {
            width: 100%;
            border-collapse: collapse;
        }

        .top-table td {
            vertical-align: top;
        }

        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
            margin: 0;
        }

        .text-muted {
            color: #777;
            font-size: 11px;
            margin-top: 5px;
        }

        .receipt-number {
            font-size: 18px;
            color: #e74c3c;
            font-weight: bold;
            text-align: right;
        }

        .paid-badge {
            background-color: #27ae60;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
            margin-top: 5px;
        }

        .info-section {
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .info-label {
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            font-size: 10px;
        }

        .info-value {
            font-size: 13px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        /* Tabla de Items */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .items-table th {
            background: #f4f4f4;
            color: #333;
            font-weight: bold;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-size: 11px;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .text-right {
            text-align: right;
        }

        .total-row td {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 14px;
            padding-top: 15px;
            background-color: #fff;
        }

        /* Firmas */
        .signatures {
            margin-top: 60px;
        }

        .sign-box {
            text-align: center;
            border-top: 1px solid #333;
            width: 80%;
            margin: 0 auto;
            padding-top: 5px;
            font-size: 11px;
        }

        /* Botones (Solo pantalla) */
        .actions {
            text-align: center;
            margin: 20px 0;
        }

        .btn {
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            background-color: #34495e;
            border-radius: 4px;
            margin: 0 5px;
            display: inline-block;
            cursor: pointer;
            border: none;
            font-size: 13px;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-blue {
            background-color: #2980b9;
        }

        .btn-gray {
            background-color: #7f8c8d;
        }

        /* Ajustes para Impresión y PDF */
        @media print {
            body {
                background-color: #fff;
            }

            .invoice-box {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 0;
            }

            .actions {
                display: none;
            }
        }
    </style>
</head>

<body>

    <?php if (!isset($modo_envio) || $modo_envio === false): ?>
        <div class="actions">
            <button onclick="window.print()" class="btn btn-blue">🖨️ Imprimir</button>
            <a href="enviar_recibo.php?id_pago=<?php echo $id_pago; ?>" class="btn btn-blue">📧 Enviar Individual</a>
            <a href="javascript:history.back()" class="btn btn-gray">⬅ Volver</a>
        </div>
    <?php endif; ?>

    <div class="invoice-box">

        <table class="w-100 top-table">
            <tr>
                <td width="60%">
                    <div class="header-title"><?php echo strtoupper($recibo['nombre_condominio']); ?></div>
                    <div class="text-muted">
                        <?php echo $recibo['ubicacion']; ?><br>
                        Tel: <?php echo $recibo['tel_condominio']; ?>
                    </div>
                </td>
                <td width="40%" class="text-right">
                    <div class="receipt-number">RECIBO #<?php echo str_pad($id_pago, 6, "0", STR_PAD_LEFT); ?></div>
                    <div style="margin-top:5px;">
                        Fecha: <?php echo date('d/m/Y', strtotime($recibo['fecha_pago'])); ?><br>
                        <span class="paid-badge">PAGADO</span>
                    </div>
                </td>
            </tr>
        </table>

        <table class="w-100 info-section">
            <tr>
                <td width="50%">
                    <div class="info-label">Recibimos de:</div>
                    <div class="info-value"><?php echo strtoupper($recibo['nombre_cliente']); ?></div>
                    <div>Apto: <strong><?php echo $recibo['apto']; ?></strong></div>
                    <div class="text-muted">Tipo: <?php echo ucfirst($tipo_pagador); ?></div>
                </td>
                <td width="50%" class="text-right">
                    <div class="info-label">Forma de Pago:</div>
                    <div class="info-value"><?php echo $recibo['forma_pago']; ?></div>
                    <div class="text-muted">Registrado por: <?php echo $recibo['usuario_registro']; ?></div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Concepto / Descripción</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_calculado = 0;
                if (count($detalles) > 0):
                    foreach ($detalles as $d):
                        $descripcion = ucfirst($d['tipo_deuda']); // Fallback

                        // Personalizar descripción según el tipo
                        if ($d['tipo_deuda'] == 'ticket') {
                            $descripcion = "Mantenimiento " . ($d['mes_ticket'] ?? '') . " " . ($d['anio_ticket'] ?? '');
                        } elseif ($d['tipo_deuda'] == 'gas') {
                            $descripcion = "Consumo Gas " . ($d['mes_gas'] ?? '') . " " . ($d['anio_gas'] ?? '');
                        } elseif ($d['tipo_deuda'] == 'cuota') {
                            $descripcion = "Cuota Extra: " . ($d['desc_cuota'] ?? '');
                        } elseif ($d['tipo_deuda'] == 'adelanto') {
                            $descripcion = ($d['monto'] < 0) ? "Uso de Saldo a Favor" : "Abono a Cuenta (Saldo a Favor)";
                        }

                        $monto = floatval($d['monto']);
                        $style_monto = ($monto < 0) ? "color: red;" : "";
                        $total_calculado += $monto;
                ?>
                        <tr>
                            <td><?php echo $descripcion; ?></td>
                            <td class="text-right" style="<?php echo $style_monto; ?>">
                                RD$ <?php echo number_format($monto, 2); ?>
                            </td>
                        </tr>
                    <?php
                    endforeach;
                else:
                    ?>
                    <tr>
                        <td colspan="2" style="text-align: center; color: #999;">No hay detalles registrados para este pago.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td class="text-right">TOTAL PAGADO:</td>
                    <td class="text-right">RD$ <?php echo number_format($recibo['monto'], 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <table class="w-100 signatures">
            <tr>
                <td width="50%">
                    <div class="sign-box">Firma Recibido Conforme</div>
                </td>
                <td width="50%">
                    <div class="sign-box">Firma Administración</div>
                </td>
            </tr>
        </table>

        <div style="text-align: center; margin-top: 40px; color: #aaa; font-size: 10px;">
            Documento generado el <?php echo date('d/m/Y H:i:s'); ?>
        </div>

    </div>

</body>

</html>