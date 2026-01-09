<?php
$thetitle = "Historial de Correos";
include("../../templates/header.php");
include("../../bd.php");

// 1. Consulta SQL simplificada: Trae TODO sin filtros
$sql = "SELECT h.*, u.usuario as nombre_usuario 
        FROM tbl_historial_correos h 
        LEFT JOIN tbl_usuarios u ON h.enviado_por = u.id 
        ORDER BY h.fecha_envio DESC";

$sentencia = $conexion->prepare($sql);
$sentencia->execute();
$lista_correos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4 mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mt-4"><i class="fas fa-envelope-open-text me-2 text-primary"></i>Historial de Correos</h1>
            <p class="text-muted">Registro completo de notificaciones enviadas.</p>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5 class="m-0"><i class="fas fa-list me-2"></i> Bandeja de Salida (Todos)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="tablaCorreos">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Destinatario</th>
                            <th>Asunto</th>
                            <th>Enviado Por</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_correos as $correo): ?>
                            <tr>
                                <td class="small">
                                    <?php echo date('d/m/Y', strtotime($correo['fecha_envio'])); ?>
                                    <br>
                                    <small class="text-muted"><?php echo date('h:i A', strtotime($correo['fecha_envio'])); ?></small>
                                </td>
                                <td><?php echo $correo['destinatario']; ?></td>
                                <td><?php echo $correo['asunto']; ?></td>
                                <td>
                                    <?php
                                    // Si no hay usuario (es NULL o 0), asumimos que fue el Sistema
                                    echo !empty($correo['nombre_usuario']) ? $correo['nombre_usuario'] : '<span class="badge bg-secondary">Sistema</span>';
                                    ?>
                                </td>
                                <td class="text-center align-middle">
                                    <?php if ($correo['estado'] == 'Enviado'): ?>
                                        <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i> Enviado</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill"><i class="fas fa-times"></i> Error</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle">
                                    <?php if ($correo['estado'] == 'Error'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="<?php echo $correo['mensaje_error']; ?>">
                                            <i class="fas fa-exclamation-circle"></i> Ver Error
                                        </button>
                                    <?php else: ?>
                                        <?php if (isset($correo['id_pago']) && $correo['id_pago'] > 0): ?>
                                            <a href="../pagos/ver_recibo.php?id_pago=<?php echo $correo['id_pago']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-print"></i> Recibo
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#tablaCorreos').DataTable({
            "order": [
                [0, "desc"]
            ], // Ordenar por la primera columna (Fecha) descendente
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
            },
            "pageLength": 25
        });

        // Activar tooltips de Bootstrap (para ver el mensaje de error al pasar el mouse)
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>

<?php include("../../templates/footer.php"); ?>