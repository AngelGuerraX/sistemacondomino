<?php
include("../../templates/header.php");
include("../../bd.php");

// 1. VALIDACIÓN DE SESIÓN
if (session_status() === PHP_SESSION_NONE) session_start();
$id_condominio = $_SESSION['idcondominio'];

// 2. CONFIGURACIÓN DE FILTROS
// Usamos el mes y año actual si no vienen en la URL
$mes_actual = $_GET['mes'] ?? $_SESSION['mes'] ?? date('n');
$anio_actual = $_GET['anio'] ?? $_SESSION['anio'] ?? date('Y');

// Array auxiliar para convertir nombres de meses a números y viceversa
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

// Lógica para determinar el nombre del mes (para la BD)
// Si $mes_actual es numérico (ej: "12"), buscamos "Diciembre". Si ya es texto, lo dejamos igual.
$mes_nombre = is_numeric($mes_actual) ? array_search($mes_actual, $meses_array) : $mes_actual;
if (!$mes_nombre) $mes_nombre = 'Enero'; // Valor por defecto por seguridad

// =================================================================================
// 3. PROCESAR GUARDADO MASIVO (POST)
// =================================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'guardar_todo') {
    try {
        $conexion->beginTransaction();

        $tickets_data = $_POST['tickets']; // Array con todos los datos enviados

        $sql = "UPDATE tbl_tickets SET 
                mantenimiento = :mant,
                mora = :mora,
                total = :total,
                estado = :estado
                WHERE id = :id AND id_condominio = :id_cond";

        $stmt = $conexion->prepare($sql);

        $contador = 0;
        foreach ($tickets_data as $id_ticket => $datos) {
            // Convertimos a float para asegurar que se guardan números válidos
            $mant = floatval($datos['mantenimiento']);
            $mora = floatval($datos['mora']);

            // Recalculamos el total matemáticamente antes de guardar
            $total = $mant + $mora;

            $stmt->execute([
                ':mant' => $mant,
                ':mora' => $mora,
                ':total' => $total,
                ':estado' => $datos['estado'],
                ':id' => $id_ticket,
                ':id_cond' => $id_condominio
            ]);
            $contador++;
        }

        $conexion->commit();
        $_SESSION['mensaje'] = "✅ Se actualizaron $contador tickets correctamente.";
        $_SESSION['tipo_mensaje'] = "success";
    } catch (Exception $e) {
        $conexion->rollBack();
        $_SESSION['mensaje'] = "❌ Error al actualizar: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
    }

    // Recargar la página (usando el mismo nombre de archivo actual)
    // Ajusta 'editar_ticket.php' si tu archivo tiene otro nombre real
    header("Location: editar_ticket.php?mes=$mes_nombre&anio=$anio_actual");
    exit;
}

// =================================================================================
// 4. CONSULTAR DATOS (JOIN con Apartamentos para ver nombre)
// =================================================================================
$sql_lista = "SELECT t.*, a.apto, a.condominos 
              FROM tbl_tickets t
              INNER JOIN tbl_aptos a ON t.id_apto = a.id
              WHERE t.mes = :mes AND t.anio = :anio AND t.id_condominio = :id_cond
              ORDER BY a.apto ASC";

$stmt = $conexion->prepare($sql_lista);
$stmt->execute([
    ':mes' => $mes_nombre,
    ':anio' => $anio_actual,
    ':id_cond' => $id_condominio
]);
$lista_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$meses_select = array_keys($meses_array);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Edición Masiva de Tickets</title>
    <style>
        .input-dinero {
            width: 100px;
            text-align: right;
        }

        .input-concepto {
            width: 100%;
            min-width: 150px;
        }

        .table-responsive {
            max-height: 70vh;
            overflow-y: auto;
        }

        thead th {
            position: sticky;
            top: 0;
            background: #0080ffff !important;
            color: white;
            z-index: 10;
        }

        tbody tr:hover {
            background-color: #f1f1f1;
        }

        /* Colores para los estados */
        .estado-pagado {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .estado-pendiente {
            background-color: #f8d7da;
            color: #842029;
        }

        .estado-abonado {
            background-color: #fff3cd;
            color: #664d03;
        }
    </style>
</head>

<body>

    <div class="container-fluid p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2>✏️ Edición Masiva de Tickets</h2>
                <p class="text-muted">Modifica múltiples facturas al mismo tiempo.</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline-secondary">Volver al Inicio</a>
            </div>
        </div>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show">
                <?php echo $_SESSION['mensaje'];
                unset($_SESSION['mensaje']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4 bg-light">
            <div class="card-body py-2">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="fw-bold small">Mes</label>
                        <select name="mes" class="form-select">
                            <?php foreach ($meses_select as $m): ?>
                                <option value="<?php echo $m; ?>" <?php echo ($m == $mes_nombre) ? 'selected' : ''; ?>>
                                    <?php echo $m; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="fw-bold small">Año</label>
                        <input type="number" name="anio" class="form-control" value="<?php echo $anio_actual; ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">🔍 Buscar</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($lista_tickets)): ?>
            <div class="alert alert-info text-center">
                No se encontraron tickets generados para <strong><?php echo $mes_nombre . ' ' . $anio_actual; ?></strong>.
                <br>
                <a href="index.php" class="btn btn-sm btn-success mt-2">Volver</a>
            </div>
        <?php else: ?>

            <form method="POST" id="formMasivo">
                <input type="hidden" name="accion" value="guardar_todo">

                <div class="d-flex justify-content-between mb-2">
                    <span class="badge bg-secondary fs-6 align-self-center">
                        <?php echo count($lista_tickets); ?> Registros encontrados
                    </span>
                    <button type="submit" class="btn btn-success fw-bold px-5" onclick="return confirm('¿Guardar todos los cambios realizados en la tabla?');">
                        💾 GUARDAR TODOS LOS CAMBIOS
                    </button>
                </div>

                <div class="table-responsive shadow border rounded text-white bg-dark ">
                    <table class="table table-bordered table-sm mb-0 align-middle text-white bg-dark">
                        <thead class="text-center text-white bg-dark">
                            <tr class="text-center text-white bg-dark">
                                <th>Apto</th>
                                <th>Mantenimiento</th>
                                <th>Mora</th>
                                <th>Total (Calc.)</th>
                                <th>Abonado</th>
                                <th>Estado</th>
                                <th>Concepto / Nota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_tickets as $t):
                                $id = $t['id'];
                                $bg_row = ($t['estado'] == 'Pagado') ? 'bg-light' : '';
                            ?>
                                <tr class="<?php echo $bg_row; ?>" id="row_<?php echo $id; ?>">

                                    <td class="fw-bold text-center bg-light">
                                        <?php echo $t['apto']; ?>
                                        <input type="hidden" name="tickets[<?php echo $id; ?>][id]" value="<?php echo $id; ?>">
                                    </td>

                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01"
                                                name="tickets[<?php echo $id; ?>][mantenimiento]"
                                                class="form-control input-dinero calc-trigger"
                                                data-id="<?php echo $id; ?>"
                                                value="<?php echo $t['mantenimiento']; ?>">
                                        </div>
                                    </td>

                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01"
                                                name="tickets[<?php echo $id; ?>][mora]"
                                                class="form-control input-dinero calc-trigger"
                                                data-id="<?php echo $id; ?>"
                                                value="<?php echo $t['mora']; ?>">
                                        </div>
                                    </td>

                                    <td class="text-center bg-light fw-bold text-primary">
                                        RD$ <span id="total_view_<?php echo $id; ?>">
                                            <?php echo number_format((float)$t['total'], 2); ?>
                                        </span>
                                    </td>

                                    <td class="text-center text-muted">
                                        <?php echo number_format((float)$t['abono'], 2); ?>
                                    </td>

                                    <td>
                                        <select name="tickets[<?php echo $id; ?>][estado]"
                                            class="form-select form-select-sm fw-bold 
                                        <?php echo ($t['estado'] == 'Pagado' ? 'estado-pagado' : ($t['estado'] == 'Abonado' ? 'estado-abonado' : 'estado-pendiente')); ?>"
                                            onchange="cambiarColorSelect(this)">
                                            <option value="Pendiente" <?php echo ($t['estado'] == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                            <option value="Pagado" <?php echo ($t['estado'] == 'Pagado') ? 'selected' : ''; ?>>Pagado</option>
                                            <option value="Abonado" <?php echo ($t['estado'] == 'Abonado') ? 'selected' : ''; ?>>Abonado</option>
                                        </select>
                                    </td>

                                    <td>
                                        <input type="text" name="tickets[<?php echo $id; ?>][concepto]"
                                            class="form-control form-control-sm input-concepto"
                                            value="<?php echo htmlspecialchars($t['concepto'] ?? ''); ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 mb-5">
                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold" onclick="return confirm('¿Guardar todos los cambios realizados en la tabla?');">
                        💾 GUARDAR CAMBIOS MASIVOS
                    </button>
                </div>
            </form>

        <?php endif; ?>

    </div>

    <script>
        // 1. Calcular total automáticamente
        document.querySelectorAll('.calc-trigger').forEach(input => {
            input.addEventListener('input', function() {
                let id = this.getAttribute('data-id');

                let mant = parseFloat(document.querySelector(`input[name="tickets[${id}][mantenimiento]"]`).value) || 0;
                let mora = parseFloat(document.querySelector(`input[name="tickets[${id}][mora]"]`).value) || 0;
                let otros = parseFloat(document.querySelector(`input[name="tickets[${id}][otros]"]`).value) || 0;

                let total = mant + mora + otros;

                document.getElementById(`total_view_${id}`).innerText = total.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            });
        });

        // 2. Colores del Select
        function cambiarColorSelect(select) {
            select.className = 'form-select form-select-sm fw-bold';
            if (select.value === 'Pagado') select.classList.add('estado-pagado');
            else if (select.value === 'Abonado') select.classList.add('estado-abonado');
            else select.classList.add('estado-pendiente');
        }
    </script>

    <?php include("../../templates/footer.php"); ?>
</body>

</html>