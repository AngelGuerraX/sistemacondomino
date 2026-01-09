<?php
// 1. INICIAR SESIÓN Y CONEXIÓN
include("../../bd.php");
include("../../templates/header.php");
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../../login.php");
    exit;
}

$usuario_actual = $_SESSION['usuario'];
$id_usuario_actual = $_SESSION['id'];
$thetitle = "Mi Actividad Reciente";

// 2. CONSULTA UNIFICADA Y MEZCLADA
// La estructura "SELECT * FROM ( ... UNION ... ) AS tabla ORDER BY fecha"
// garantiza que se mezclen todos los registros sin importar de qué tabla vienen.

$sql = "
SELECT * FROM (
    /* 1. PAGOS DE PROPIETARIOS */
    SELECT 
        'Ingreso' as tipo,
        'Cobro Propietario' as accion,
        concepto as descripcion,
        monto,
        fecha_pago as fecha
    FROM tbl_pagos 
    WHERE usuario_registro = :usuario

    UNION ALL

    /* 2. PAGOS DE INQUILINOS */
    SELECT 
        'Ingreso' as tipo,
        'Cobro Inquilino' as accion,
        concepto as descripcion,
        monto,
        fecha_pago as fecha
    FROM tbl_pagos_inquilinos 
    WHERE usuario_registro = :usuario

    UNION ALL

    /* 3. TICKETS GENERADOS */
    SELECT 
        'Sistema' as tipo,
        'Generación Ticket' as accion,
        CONCAT('Mantenimiento ', mes, ' ', anio) as descripcion,
        mantenimiento as monto,
        fecha_actual as fecha
    FROM tbl_tickets
    WHERE usuario_registro = :usuario

    UNION ALL

    /* 4. CORREOS ENVIADOS */
    SELECT 
        'Correo' as tipo,
        CONCAT('Email ', estado) as accion,
        CONCAT('A: ', destinatario, ' - ', asunto) as descripcion,
        0 as monto,
        fecha_envio as fecha
    FROM tbl_historial_correos
    WHERE enviado_por = :id_usuario

) AS historial_combinado

ORDER BY fecha DESC
LIMIT 100
";

try {
    $conexion->exec("SET lc_time_names = 'es_ES'");

    $sentencia = $conexion->prepare($sql);
    $sentencia->bindParam(":usuario", $usuario_actual);
    $sentencia->bindParam(":id_usuario", $id_usuario_actual);
    $sentencia->execute();
    $lista_actividad = $sentencia->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = "Error técnico: " . $e->getMessage();
    $lista_actividad = [];
}

// Contadores rápidos
$total_movimientos = count($lista_actividad);
$total_ingresado = 0;
$total_correos = 0;

foreach ($lista_actividad as $mov) {
    if ($mov['tipo'] == 'Ingreso') $total_ingresado += floatval($mov['monto']);
    if ($mov['tipo'] == 'Correo') $total_correos++;
}

?>

<div class="container-fluid px-4 mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mt-4"><i class="fas fa-history me-2 text-primary"></i>Mi Actividad</h1>
            <p class="text-muted">Historial unificado de <strong><?php echo $usuario_actual; ?></strong></p>
        </div>

        <div class="d-flex gap-3">
            <div class="bg-white p-3 rounded shadow-sm border text-center">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Movimientos</small>
                <div class="h5 mb-0 fw-bold text-dark"><?php echo $total_movimientos; ?></div>
            </div>
            <div class="bg-white p-3 rounded shadow-sm border text-center">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Correos</small>
                <div class="h5 mb-0 fw-bold text-info"><?php echo $total_correos; ?></div>
            </div>
            <div class="bg-white p-3 rounded shadow-sm border text-center">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Cobrado</small>
                <div class="h5 mb-0 fw-bold text-success">RD$ <?php echo number_format($total_ingresado); ?></div>
            </div>
        </div>
    </div>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger shadow-sm">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header bg-white py-3">
            <i class="fas fa-stream me-1"></i> Línea de Tiempo
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-striped" id="tablaActividad" width="100%" cellspacing="0">
                    <thead class="table-dark">
                        <tr>
                            <th width="15%">Fecha</th>
                            <th width="10%" class="text-center">Tipo</th>
                            <th width="20%">Acción</th>
                            <th>Detalle</th>
                            <th width="15%" class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_actividad as $log) {
                            $badge = "bg-secondary";
                            $icon = "fa-circle";
                            $color_texto = "text-dark";

                            if ($log['tipo'] == 'Ingreso') {
                                $badge = "bg-success";
                                $icon = "fa-arrow-up";
                                $color_texto = "text-success fw-bold";
                            } elseif ($log['tipo'] == 'Sistema') {
                                $badge = "bg-info text-dark";
                                $icon = "fa-cogs";
                            } elseif ($log['tipo'] == 'Correo') {
                                $badge = "bg-warning text-dark";
                                $icon = "fa-envelope";
                            }
                        ?>
                            <tr>
                                <td class="align-middle small">
                                    <?php
                                    // Detectar si la fecha incluye hora (datetime) o solo fecha (date)
                                    $time = strtotime($log['fecha']);
                                    echo date('d/m/Y', $time);

                                    // Si la hora es 00:00:00, probablemente era solo DATE en la BD, no mostramos hora
                                    if (date('H:i:s', $time) != '00:00:00') {
                                        echo "<br><small class='text-muted'>" . date('h:i A', $time) . "</small>";
                                    }
                                    ?>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge <?php echo $badge; ?> rounded-pill">
                                        <i class="fas <?php echo $icon; ?> me-1"></i> <?php echo strtoupper($log['tipo']); ?>
                                    </span>
                                </td>
                                <td class="align-middle fw-bold">
                                    <?php echo $log['accion']; ?>
                                </td>
                                <td class="align-middle text-muted small">
                                    <?php echo $log['descripcion']; ?>
                                </td>
                                <td class="align-middle text-end <?php echo $color_texto; ?>">
                                    <?php echo ($log['monto'] > 0) ? "RD$ " . number_format(floatval($log['monto']), 2) : "-"; ?>
                                </td>
                            </tr>
                        <?php } ?>

                        <?php if (empty($lista_actividad) && !isset($error_msg)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No hay registros recientes asociados a tu usuario.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#tablaActividad').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
            },
            // IMPORTANTE: Desactivar el ordenamiento por JS inicial para respetar el orden SQL (que ya viene mezclado por fecha)
            "order": [],
            "pageLength": 25
        });
    });
</script>

<?php include("../../templates/footer.php"); ?>