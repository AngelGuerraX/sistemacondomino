<?php
include("../../templates/header.php");
include("../../bd.php");
include 'actualizar_balance.php'; // Necesario para corregir el saldo del apto al editar/borrar

if (session_status() === PHP_SESSION_NONE) session_start();
$id_condominio = $_SESSION['idcondominio'];

// CONFIGURACIÓN DE FILTROS
$meses_array = [
    'Enero' => 1,
    'Febrero' => 2,
    'Marzo' => 3,
    'Abril' => 4,
    'Mayo' => 5,
    'Junio' => 6,
    'Julio' => 7,
    'Agosto' => 8,
    'Septiembre' => 9,
    'Octubre' => 10,
    'Noviembre' => 11,
    'Diciembre' => 12
];

$mes_actual = $_GET['mes'] ?? date('n');
// Si viene número, convertir a nombre para la BD (ej: 1 -> Enero)
$mes_nombre = is_numeric($mes_actual) ? array_search($mes_actual, $meses_array) : $mes_actual;
$anio_actual = $_GET['anio'] ?? date('Y');

$mensaje = "";
$tipo_mensaje = "";

// =================================================================================
// ACCIONES: EDITAR O ELIMINAR MORA
// =================================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {

    try {
        $id_registro = $_POST['id_registro'];
        $tipo_tabla = $_POST['tipo_tabla']; // 'ticket', 'gas', 'cuota'
        $id_apto = $_POST['id_apto'];

        $conexion->beginTransaction();

        if ($_POST['accion'] == 'eliminar') {
            $nueva_mora = 0;
            $accion_texto = "eliminada";
        } elseif ($_POST['accion'] == 'editar') {
            $nueva_mora = floatval($_POST['monto_mora']);
            $accion_texto = "actualizada";
        }

        // APLICAR CAMBIOS SEGÚN LA TABLA
        switch ($tipo_tabla) {
            case 'ticket':
                // En tickets, al cambiar la mora, debemos actualizar el TOTAL del ticket
                // Total = Mantenimiento + Nueva Mora
                $sql = "UPDATE tbl_tickets SET 
                        mora = :mora,
                        total = (CAST(mantenimiento AS DECIMAL(10,2)) + :mora)
                        WHERE id = :id";
                break;

            case 'gas':
                // En gas, la mora es una columna independiente, solo actualizamos eso
                $sql = "UPDATE tbl_gas SET mora = :mora WHERE id = :id";
                break;

            case 'cuota':
                // En cuotas, la mora es independiente
                $sql = "UPDATE tbl_cuotas_extras SET mora = :mora WHERE id = :id";
                break;
        }

        if (isset($sql)) {
            $stmt = $conexion->prepare($sql);
            $stmt->execute([':mora' => $nueva_mora, ':id' => $id_registro]);

            // CRÍTICO: Recalcular el balance del apartamento para que cuadre todo
            actualizarBalanceApto($id_apto, $id_condominio);

            $conexion->commit();
            $mensaje = "✅ Mora $accion_texto correctamente.";
            $tipo_mensaje = "success";
        }
    } catch (Exception $e) {
        $conexion->rollBack();
        $mensaje = "❌ Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// =================================================================================
// CONSULTAS: OBTENER TODAS LAS MORAS DEL MES
// =================================================================================
$lista_moras = [];

// 1. MORAS DE TICKETS (MANTENIMIENTO)
$sql_tickets = "SELECT t.id, t.id_apto, a.apto, a.condominos, t.mantenimiento as monto_base, t.mora, 'Mantenimiento' as concepto, 'ticket' as tipo_tabla 
                FROM tbl_tickets t
                INNER JOIN tbl_aptos a ON t.id_apto = a.id
                WHERE t.mes = :mes AND t.anio = :anio AND t.id_condominio = :cond 
                AND CAST(t.mora AS DECIMAL(10,2)) > 0 AND t.estado != 'Pagado'"; // Solo mostrar si tiene mora y no está pagado

// 2. MORAS DE GAS
$sql_gas = "SELECT g.id, g.id_apto, a.apto, a.condominos, g.total_gas as monto_base, g.mora, 'Gas Común' as concepto, 'gas' as tipo_tabla 
            FROM tbl_gas g
            INNER JOIN tbl_aptos a ON g.id_apto = a.id
            WHERE g.mes = :mes AND g.anio = :anio AND g.id_condominio = :cond 
            AND g.mora > 0 AND g.estado != 'Pagado'";

// 3. MORAS DE CUOTAS
$sql_cuotas = "SELECT c.id, c.id_apto, a.apto, a.condominos, c.monto as monto_base, c.mora, CONCAT('Cuota: ', c.descripcion) as concepto, 'cuota' as tipo_tabla 
               FROM tbl_cuotas_extras c
               INNER JOIN tbl_aptos a ON c.id_apto = a.id
               WHERE c.mes = :mes AND c.anio = :anio AND c.id_condominio = :cond 
               AND c.mora > 0 AND c.estado != 'Pagado'";

// EJECUTAR Y UNIR
$params = [':mes' => $mes_nombre, ':anio' => $anio_actual, ':cond' => $id_condominio];

$stmt = $conexion->prepare($sql_tickets);
$stmt->execute($params);
$lista_moras = array_merge($lista_moras, $stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $conexion->prepare($sql_gas);
$stmt->execute($params);
$lista_moras = array_merge($lista_moras, $stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $conexion->prepare($sql_cuotas);
$stmt->execute($params);
$lista_moras = array_merge($lista_moras, $stmt->fetchAll(PDO::FETCH_ASSOC));

// Ordenar por apartamento
usort($lista_moras, function ($a, $b) {
    return $a['apto'] <=> $b['apto'];
});

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Moras</title>
</head>

<body>

    <div class="container-fluid p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>🛡️ Gestión de Moras Aplicadas</h2>
            <a href="index.php" class="btn btn-danger">X</a>
            <a href="herramientas_moras.php" class="btn btn-primary">Aplicar Moras</a>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4 bg-light">
            <div class="card-body py-3">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="fw-bold">Mes</label>
                        <select name="mes" class="form-select">
                            <?php foreach ($meses_array as $nombre => $num): ?>
                                <option value="<?php echo $nombre; ?>" <?php echo ($nombre == $mes_nombre) ? 'selected' : ''; ?>>
                                    <?php echo $nombre; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="fw-bold">Año</label>
                        <input type="number" name="anio" class="form-control" value="<?php echo $anio_actual; ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">🔍 Buscar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header bg-warning text-dark fw-bold">
                Moras activas en <?php echo $mes_nombre . " " . $anio_actual; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0 align-middle">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Apto</th>
                                <th>Condómino</th>
                                <th>Concepto (Origen)</th>
                                <th>Monto Base</th>
                                <th class="text-danger">Mora Actual</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lista_moras)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No hay moras registradas para este periodo.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($lista_moras as $mora):
                                    $id_unico = $mora['tipo_tabla'] . "_" . $mora['id'];
                                ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?php echo $mora['apto']; ?></td>
                                        <td><?php echo $mora['condominos']; ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo strtoupper($mora['tipo_tabla']); ?></span>
                                            <small class="text-muted d-block"><?php echo $mora['concepto']; ?></small>
                                        </td>
                                        <td class="text-end">RD$ <?php echo number_format((float)$mora['monto_base'], 2); ?></td>
                                        <td class="text-end fw-bold text-danger fs-5">
                                            RD$ <?php echo number_format((float)$mora['mora'], 2); ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditar_<?php echo $id_unico; ?>">
                                                    ✏️ Editar
                                                </button>

                                                <form method="POST" onsubmit="return confirm('¿Seguro que deseas ELIMINAR esta mora? Se pondrá en 0.');" style="display:inline;">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input type="hidden" name="id_registro" value="<?php echo $mora['id']; ?>">
                                                    <input type="hidden" name="tipo_tabla" value="<?php echo $mora['tipo_tabla']; ?>">
                                                    <input type="hidden" name="id_apto" value="<?php echo $mora['id_apto']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">🗑️ Borrar</button>
                                                </form>
                                            </div>

                                            <div class="modal fade" id="modalEditar_<?php echo $id_unico; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-title">Editar Mora - Apto <?php echo $mora['apto']; ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body text-start">
                                                                <input type="hidden" name="accion" value="editar">
                                                                <input type="hidden" name="id_registro" value="<?php echo $mora['id']; ?>">
                                                                <input type="hidden" name="tipo_tabla" value="<?php echo $mora['tipo_tabla']; ?>">
                                                                <input type="hidden" name="id_apto" value="<?php echo $mora['id_apto']; ?>">

                                                                <div class="mb-3">
                                                                    <label class="form-label">Monto Base (Referencia)</label>
                                                                    <input type="text" class="form-control" value="RD$ <?php echo number_format((float)$mora['monto_base'], 2); ?>" disabled>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold text-danger">Nuevo Monto de Mora</label>
                                                                    <input type="number" step="0.01" name="monto_mora" class="form-control fw-bold text-danger" value="<?php echo $mora['mora']; ?>" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <?php include("../../templates/footer.php"); ?>
</body>

</html>