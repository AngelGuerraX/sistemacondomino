<?php
include("../../templates/header.php");
include("../../bd.php");
include 'actualizar_balance.php'; // Importante para recalcular al final

if (session_status() === PHP_SESSION_NONE) session_start();
$id_condominio = $_SESSION['idcondominio'];

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
$mes_actual_num = date('n');
$anio_actual_val = date('Y');

// =================================================================================
// LÓGICA CENTRAL: APLICAR MORA EN COLUMNAS SEPARADAS
// =================================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'aplicar_mora') {

    try {
        $conexion->beginTransaction();

        // Validar porcentaje
        $porcentaje = floatval($_POST['porcentaje']);
        if ($porcentaje <= 0) throw new Exception("El porcentaje debe ser mayor a 0.");
        $factor = $porcentaje / 100;

        $post_mes_nombre = $_POST['filtro_mes'];
        $post_anio = $_POST['filtro_anio'];

        $total_afectados = 0;
        $aptos_para_recalcular = [];

        // ---------------------------------------------------------
        // 1. TICKETS (MANTENIMIENTO) - CORREGIDO
        // ---------------------------------------------------------
        if (isset($_POST['check_ticket'])) {
            // CORRECCIÓN: Eliminada la columna 'otros' que no existe en tu base de datos.
            // Total = Mantenimiento + Nueva Mora.
            $sql_upd_tickets = "UPDATE tbl_tickets 
                                SET 
                                    mora = (mantenimiento * :factor), 
                                    total = (mantenimiento + (mantenimiento * :factor))
                                WHERE mes = :mes 
                                  AND anio = :anio 
                                  AND id_condominio = :cond 
                                  AND estado != 'Pagado'";

            $stmt = $conexion->prepare($sql_upd_tickets);
            $stmt->execute([
                ':factor' => $factor,
                ':mes' => $post_mes_nombre,
                ':anio' => $post_anio,
                ':cond' => $id_condominio
            ]);
            $afectados = $stmt->rowCount();
            $total_afectados += $afectados;

            if ($afectados > 0) {
                // Obtener IDs de aptos afectados para recalcular balance
                $stmt_ids = $conexion->prepare("SELECT DISTINCT id_apto FROM tbl_tickets WHERE mes=:m AND anio=:a AND id_condominio=:c AND estado!='Pagado'");
                $stmt_ids->execute([':m' => $post_mes_nombre, ':a' => $post_anio, ':c' => $id_condominio]);
                while ($row = $stmt_ids->fetch(PDO::FETCH_ASSOC)) {
                    $aptos_para_recalcular[$row['id_apto']] = true;
                }
            }
        }

        // ---------------------------------------------------------
        // 2. GAS COMÚN
        // ---------------------------------------------------------
        if (isset($_POST['check_gas'])) {
            $sql_upd_gas = "UPDATE tbl_gas 
                            SET mora = (total_gas * :factor)
                            WHERE mes = :mes 
                              AND anio = :anio 
                              AND id_condominio = :cond 
                              AND estado != 'Pagado'";

            $stmt = $conexion->prepare($sql_upd_gas);
            $stmt->execute([
                ':factor' => $factor,
                ':mes' => $post_mes_nombre,
                ':anio' => $post_anio,
                ':cond' => $id_condominio
            ]);
            $afectados = $stmt->rowCount();
            $total_afectados += $afectados;

            if ($afectados > 0) {
                $stmt_ids = $conexion->prepare("SELECT DISTINCT id_apto FROM tbl_gas WHERE mes=:m AND anio=:a AND id_condominio=:c AND estado!='Pagado'");
                $stmt_ids->execute([':m' => $post_mes_nombre, ':a' => $post_anio, ':c' => $id_condominio]);
                while ($row = $stmt_ids->fetch(PDO::FETCH_ASSOC)) {
                    $aptos_para_recalcular[$row['id_apto']] = true;
                }
            }
        }

        // ---------------------------------------------------------
        // 3. CUOTAS EXTRAS
        // ---------------------------------------------------------
        if (isset($_POST['check_cuota'])) {
            $sql_upd_cuotas = "UPDATE tbl_cuotas_extras 
                               SET mora = (monto * :factor)
                               WHERE YEAR(fecha_registro) = :anio 
                                 AND id_condominio = :cond 
                                 AND estado != 'Pagado'";

            $stmt = $conexion->prepare($sql_upd_cuotas);
            $stmt->execute([
                ':factor' => $factor,
                ':anio' => $post_anio,
                ':cond' => $id_condominio
            ]);
            $afectados = $stmt->rowCount();
            $total_afectados += $afectados;

            if ($afectados > 0) {
                $stmt_ids = $conexion->prepare("SELECT DISTINCT id_apto FROM tbl_cuotas_extras WHERE YEAR(fecha_registro)=:a AND id_condominio=:c AND estado!='Pagado'");
                $stmt_ids->execute([':a' => $post_anio, ':c' => $id_condominio]);
                while ($row = $stmt_ids->fetch(PDO::FETCH_ASSOC)) {
                    $aptos_para_recalcular[$row['id_apto']] = true;
                }
            }
        }

        // 4. RECALCULAR BALANCES GLOBALES
        if (!empty($aptos_para_recalcular)) {
            foreach (array_keys($aptos_para_recalcular) as $id_apto_upd) {
                // Como id_apto en tickets puede ser varchar, aseguramos limpieza si es necesario,
                // pero la funcion actualizarBalanceApto suele esperar el ID numérico de la tabla tbl_aptos.
                // En tu DB, tbl_tickets.id_apto parece guardar el ID numérico (ej: 86, 87).
                actualizarBalanceApto($id_apto_upd, $id_condominio);
            }
        }

        $conexion->commit();
        $_SESSION['mensaje'] = "✅ Mora del <strong>$porcentaje%</strong> aplicada correctamente. Se actualizaron $total_afectados registros y sus balances.";
        $_SESSION['tipo_mensaje'] = "success";
    } catch (Exception $e) {
        $conexion->rollBack();
        $_SESSION['mensaje'] = "❌ Error crítico: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Herramienta de Moras (Separadas)</title>
</head>

<body>

    <div class="container mt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>📉 Herramienta de Moras y Recargos</h2>
                <p class="text-muted">Aplica porcentajes de mora manteniéndolos separados del capital base.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary">Volver</a>
        </div>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show shadow-sm">
                <?php echo $_SESSION['mensaje'];
                unset($_SESSION['mensaje']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm border-danger">
                    <div class="card-header bg-danger text-white py-3">
                        <h5 class="mb-0 fw-bold">⚠️ Configurar Aplicación de Mora</h5>
                    </div>
                    <div class="card-body bg-light p-4">

                        <form method="POST" onsubmit="return confirm('ATENCIÓN:\n\nSe calculará el porcentaje ingresado sobre los montos base y se GUARDARÁ EN LA COLUMNA DE MORA correspondiente.\n\nSi ya existía una mora previa para ese mes, SERÁ REEMPLAZADA por este nuevo cálculo.\n\n¿Deseas proceder?');">
                            <input type="hidden" name="accion" value="aplicar_mora">

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark">1. Porcentaje a aplicar (%)</label>
                                    <div class="input-group input-group-lg">
                                        <input type="number" step="0.01" min="0.01" name="porcentaje" class="form-control fw-bold text-danger border-danger" placeholder="Ej: 10" required>
                                        <span class="input-group-text bg-danger text-white border-danger fw-bold">%</span>
                                    </div>
                                    <small class="text-muted">Ejemplo: Si pones 10, se calculará el 10% del monto base.</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark">2. Periodo Objetivo (Mes Vencido)</label>
                                    <div class="d-flex gap-2">
                                        <select name="filtro_mes" class="form-select form-select-lg border-secondary">
                                            <?php foreach ($meses_array as $nombre => $num): ?>
                                                <?php $mes_anterior = ($mes_actual_num == 1) ? 12 : $mes_actual_num - 1; ?>
                                                <option value="<?php echo $nombre; ?>" <?php echo ($num == $mes_anterior) ? 'selected' : ''; ?>>
                                                    <?php echo $nombre; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" name="filtro_anio" class="form-control form-select-lg border-secondary" value="<?php echo ($mes_actual_num == 1) ? $anio_actual_val - 1 : $anio_actual_val; ?>" style="max-width: 100px;">
                                    </div>
                                    <small class="text-danger fw-bold">Se aplicará a recibos "Pendientes" de esta fecha.</small>
                                </div>
                            </div>

                            <hr class="text-secondary opacity-25">

                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark mb-3">3. ¿A qué conceptos aplicar la mora?</label>
                                <div class="d-flex flex-wrap gap-4 p-3 bg-white rounded border">
                                    <div class="form-check form-switch ps-5">
                                        <input class="form-check-input" type="checkbox" name="check_ticket" id="chk1" checked style="transform: scale(1.3);">
                                        <label class="form-check-label fw-bold ms-2 pt-1" for="chk1">Mantenimiento (Tickets)</label>
                                        <div class="small text-muted ms-2">Aplica a la columna <code>mora</code> en tickets.</div>
                                    </div>
                                    <div class="form-check form-switch ps-5">
                                        <input class="form-check-input" type="checkbox" name="check_gas" id="chk2" style="transform: scale(1.3);">
                                        <label class="form-check-label fw-bold ms-2 pt-1" for="chk2">Gas Común</label>
                                        <div class="small text-muted ms-2">Aplica a la nueva columna <code>mora</code> en gas.</div>
                                    </div>
                                    <div class="form-check form-switch ps-5">
                                        <input class="form-check-input" type="checkbox" name="check_cuota" id="chk3" style="transform: scale(1.3);">
                                        <label class="form-check-label fw-bold ms-2 pt-1" for="chk3">Cuotas Extras</label>
                                        <div class="small text-muted ms-2">Aplica a la nueva columna <code>mora</code> en cuotas.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger btn-lg fw-bold py-3 text-uppercase">
                                    ⚡ Calcular y Guardar Moras Separadas
                                </button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="alert alert-info shadow-sm">
                    <h5 class="alert-heading fw-bold">ℹ️ ¿Cómo funciona esta versión?</h5>
                    <p>Esta herramienta está diseñada para cumplir con el requisito de mantener la mora visible por separado, tal como en un estado de cuenta detallado.</p>
                    <hr>
                    <ul class="ps-3 mb-0">
                        <li class="mb-2"><strong>No suma al capital:</strong> El porcentaje se calcula sobre el monto base (ej. $1,000 de mantenimiento) y el resultado (ej. $100) se guarda en una casilla aparte llamada "Mora".</li>
                        <li class="mb-2"><strong>Mantenimiento:</strong> Usa la columna <code>mora</code> que ya existía en los tickets.</li>
                        <li class="mb-2"><strong>Gas y Cuotas:</strong> Usa las NUEVAS columnas <code>mora</code> que creaste en la Base de Datos.</li>
                        <li class="mb-2"><strong>Recálculo automático:</strong> Al final, el sistema actualiza el saldo total del deudor sumando (Capital Base + Mora Separada).</li>
                    </ul>
                </div>
                <div class="alert alert-warning shadow-sm mt-3">
                    <strong>Nota Importante:</strong> Si vuelves a aplicar la mora sobre el mismo mes, el valor anterior de la mora será <strong>reemplazado</strong> por el nuevo cálculo del porcentaje actual.
                </div>
            </div>
        </div>

    </div>

    <?php include("../../templates/footer.php"); ?>
</body>

</html>