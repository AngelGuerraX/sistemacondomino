<?php
include("../../bd.php");

if (session_status() === PHP_SESSION_NONE) session_start();

// 1. VALIDAR Y OBTENER ID
if (!isset($_GET['id_pago'])) {
  echo "Error: No se especificó el pago.";
  exit;
}

$id_pago = $_GET['id_pago'];
$tipo_pagador = isset($_GET['tipo']) ? $_GET['tipo'] : 'propietario'; // 'propietario' o 'inquilino'

// 2. SELECCIONAR TABLAS SEGÚN EL TIPO
if ($tipo_pagador == 'propietario') {
  $tabla_pagos = "tbl_pagos";
  $col_id = "id_pago";

  // Consulta Segura con JOIN (Evita el error de subquery)
  $sql_cabecera = "SELECT 
                        p.*, 
                        a.apto, 
                        a.condominos as nombre_cliente,
                        c.nombre as nombre_condominio,
                        c.ubicacion,
                        c.telefono as tel_condominio
                     FROM tbl_pagos p
                     INNER JOIN tbl_aptos a ON p.id_apto = a.id
                     INNER JOIN tbl_condominios c ON p.id_condominio = c.id
                     WHERE p.id_pago = :id";
} else {
  $tabla_pagos = "tbl_pagos_inquilinos";
  $col_id = "id";

  // Consulta para Inquilino
  $sql_cabecera = "SELECT 
                        p.*, 
                        a.apto, 
                        i.nombre as nombre_cliente,
                        c.nombre as nombre_condominio,
                        c.ubicacion,
                        c.telefono as tel_condominio
                     FROM tbl_pagos_inquilinos p
                     INNER JOIN tbl_inquilinos i ON p.id_inquilino = i.id
                     INNER JOIN tbl_aptos a ON i.id_apto = a.id
                     INNER JOIN tbl_condominios c ON p.id_condominio = c.id
                     WHERE p.id = :id";
}

// 3. EJECUTAR CONSULTA DE CABECERA
$stmt = $conexion->prepare($sql_cabecera);
$stmt->execute([':id' => $id_pago]);
$recibo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recibo) {
  echo "<h3>❌ Error: Recibo no encontrado o eliminado.</h3>";
  exit;
}

// 4. OBTENER DETALLES (QUÉ SE PAGÓ EXACTAMENTE)
// Aquí hacemos JOIN con las tablas de deudas para saber el mes/año de cada item
$sql_detalle = "SELECT 
                    d.monto, 
                    d.tipo_deuda,
                    -- Detalles si es Ticket
                    t.mes as mes_ticket, t.anio as anio_ticket,
                    -- Detalles si es Gas
                    g.mes as mes_gas, g.anio as anio_gas,
                    -- Detalles si es Cuota
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
  <title>Recibo de Pago #<?php echo $id_pago; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #525659;
    }

    .page {
      background: white;
      width: 21cm;
      min-height: 29.7cm;
      margin: 20px auto;
      padding: 40px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    }

    .header-title {
      color: #0d6efd;
      font-weight: bold;
    }

    .table-items th {
      background-color: #f8f9fa;
    }

    .text-condominio {
      font-size: 0.9rem;
      color: #555;
    }

    @media print {
      body {
        background: white;
        margin: 0;
      }

      .page {
        box-shadow: none;
        margin: 0;
        width: 100%;
      }

      .no-print {
        display: none !important;
      }
    }
  </style>
</head>

<body>

  <div class="container mt-3 mb-3 no-print text-center">
    <button onclick="window.print()" class="btn btn-primary btn-lg">🖨️ Imprimir Recibo</button>

    <a href="gestion_pagos.php?txID=<?php echo isset($_GET['txID']) ? $_GET['txID'] : ''; ?>" class="btn btn-secondary btn-lg">⬅ Volver</a>
  </div>

  <div class="page">

    <div class="row mb-4 border-bottom pb-3">
      <div class="col-8">
        <h2 class="header-title"><?php echo strtoupper($recibo['nombre_condominio']); ?></h2>
        <div class="text-condominio">
          <?php echo $recibo['ubicacion']; ?><br>
          Tel: <?php echo $recibo['tel_condominio']; ?>
        </div>
      </div>
      <div class="col-4 text-end">
        <h3 class="text-danger">RECIBO #<?php echo str_pad($id_pago, 6, "0", STR_PAD_LEFT); ?></h3>
        <small class="text-muted">Fecha: <?php echo date('d/m/Y', strtotime($recibo['fecha_pago'])); ?></small><br>
        <span class="badge bg-success">PAGADO</span>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-md-6">
        <h6 class="fw-bold text-uppercase text-secondary">Recibimos de:</h6>
        <div class="fs-5"><?php echo $recibo['nombre_cliente']; ?></div>
        <div>Apartamento: <strong><?php echo $recibo['apto']; ?></strong></div>
        <div>Tipo: <?php echo ucfirst($tipo_pagador); ?></div>
      </div>
      <div class="col-md-6 text-end">
        <h6 class="fw-bold text-uppercase text-secondary">Forma de Pago:</h6>
        <div class="fs-5"><?php echo $recibo['forma_pago']; ?></div>
        <small class="text-muted">Registrado por: <?php echo $recibo['usuario_registro']; ?></small>
      </div>
    </div>

    <table class="table table-bordered table-items mb-4">
      <thead>
        <tr>
          <th width="70%">Concepto / Descripción</th>
          <th width="30%" class="text-end">Monto Aplicado</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $total_calculado = 0;
        if (count($detalles) > 0):
          foreach ($detalles as $d):
            $descripcion = "";

            // Lógica para armar la descripción bonita
            if ($d['tipo_deuda'] == 'ticket') {
              $descripcion = "Mantenimiento " . $d['mes_ticket'] . " " . $d['anio_ticket'];
            } elseif ($d['tipo_deuda'] == 'gas') {
              $descripcion = "Consumo Gas " . $d['mes_gas'] . " " . $d['anio_gas'];
            } elseif ($d['tipo_deuda'] == 'cuota') {
              $descripcion = "Cuota Extra: " . $d['desc_cuota'];
            } elseif ($d['tipo_deuda'] == 'adelanto') {
              if ($d['monto'] < 0) {
                $descripcion = "Uso de Saldo a Favor (Crédito Aplicado)";
              } else {
                $descripcion = "Abono a Cuenta (Saldo a Favor)";
              }
            } else {
              $descripcion = ucfirst($d['tipo_deuda']);
            }

            $monto = floatval($d['monto']);
            // Si es uso de saldo (negativo), no lo sumamos al total del recibo visualmente 
            // porque el recibo muestra CUANTO PAGÓ EL CLIENTE. 
            // Pero matemáticamente: Total Recibo = Suma de items.
            $total_calculado += $monto;
        ?>
            <tr>
              <td><?php echo $descripcion; ?></td>
              <td class="text-end <?php echo ($monto < 0) ? 'text-danger' : ''; ?>">
                RD$ <?php echo number_format($monto, 2); ?>
              </td>
            </tr>
          <?php endforeach;
        else: ?>
          <tr>
            <td colspan="2" class="text-center text-muted">Sin detalles registrados</td>
          </tr>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr class="table-light">
          <td class="text-end fw-bold">TOTAL PAGADO:</td>
          <td class="text-end fw-bold fs-5 bg-warning">
            RD$ <?php echo number_format($recibo['monto'], 2); ?>
          </td>
        </tr>
      </tfoot>
    </table>

    <div class="text-center mt-5 text-muted small">
      <p>Gracias por su pago.</p>
      <p>Generado el <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

  </div>

</body>

</html>