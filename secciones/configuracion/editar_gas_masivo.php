<?php
include("../../templates/header.php");
include("../../bd.php");

// 1. VALIDACIÓN DE SESIÓN
if (session_status() === PHP_SESSION_NONE) session_start();
$id_condominio = $_SESSION['idcondominio'];

// 2. CONFIGURACIÓN DE FILTROS
$mes_actual = $_GET['mes'] ?? $_SESSION['mes'] ?? date('n');
$anio_actual = $_GET['anio'] ?? $_SESSION['anio'] ?? date('Y');

// Array de meses para el select y para convertir nombres
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

// Determinar nombre del mes para la BD
$mes_nombre = is_numeric($mes_actual) ? array_search($mes_actual, $meses_array) : $mes_actual;
if (!$mes_nombre) $mes_nombre = 'Enero';

// =================================================================================
// 3. PROCESAR GUARDADO MASIVO (POST)
// =================================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'guardar_gas') {
    try {
        $conexion->beginTransaction();

        $gas_data = $_POST['gas']; // Array con todos los datos

        $sql = "UPDATE tbl_gas SET 
                lectura_anterior = :lec_ant,
                lectura_actual = :lec_act,
                consumo_galones = :consumo,
                precio_galon = :precio,
                total_gas = :total,
                estado = :estado
                WHERE id = :id AND id_condominio = :id_cond";

        $stmt = $conexion->prepare($sql);

        $contador = 0;
        foreach ($gas_data as $id_registro => $datos) {
            // Conversiones a float para evitar errores y hacer cálculos
            $lec_ant = floatval($datos['lectura_anterior']);
            $lec_act = floatval($datos['lectura_actual']);
            $precio  = floatval($datos['precio_galon']);

            // Recálculo en Backend por seguridad
            // 1. Consumo = Actual - Anterior (Si es negativo, lo dejamos en 0)
            $consumo = $lec_act - $lec_ant;
            if ($consumo < 0) $consumo = 0;

            // 2. Total = Consumo * Precio
            $total = $consumo * $precio;

            $stmt->execute([
                ':lec_ant' => $lec_ant,
                ':lec_act' => $lec_act,
                ':consumo' => $consumo,
                ':precio'  => $precio,
                ':total'   => $total,
                ':estado'  => $datos['estado'],
                ':id'      => $id_registro,
                ':id_cond' => $id_condominio
            ]);
            $contador++;
        }

        $conexion->commit();
        $_SESSION['mensaje'] = "✅ Se actualizaron $contador registros de gas correctamente.";
        $_SESSION['tipo_mensaje'] = "success";
    } catch (Exception $e) {
        $conexion->rollBack();
        $_SESSION['mensaje'] = "❌ Error al actualizar: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
    }

    // Recargar la página manteniendo filtros
    header("Location: editar_gas_masivo.php?mes=$mes_nombre&anio=$anio_actual");
    exit;
}

// =================================================================================
// 4. CONSULTAR DATOS (JOIN con Apartamentos)
// =================================================================================
$sql_lista = "SELECT g.*, a.apto 
              FROM tbl_gas g
              INNER JOIN tbl_aptos a ON g.id_apto = a.id
              WHERE g.mes = :mes AND g.anio = :anio AND g.id_condominio = :id_cond
              ORDER BY a.apto ASC";

$stmt = $conexion->prepare($sql_lista);
$stmt->execute([
    ':mes' => $mes_nombre,
    ':anio' => $anio_actual,
    ':id_cond' => $id_condominio
]);
$lista_gas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$meses_select = array_keys($meses_array);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Edición Masiva de Gas</title>
    <style>
        .input-corto {
            width: 90px;
            text-align: center;
        }

        .input-dinero {
            width: 100px;
            text-align: right;
        }

        .table-responsive {
            max-height: 75vh;
            overflow-y: auto;
        }

        /* Encabezado fijo */
        thead th {
            position: sticky;
            top: 0;
            background: #e67e22 !important;
            color: white;
            z-index: 10;
            border-bottom: 3px solid #d35400;
        }

        tbody tr:hover {
            background-color: #fcece4;
        }

        /* Color naranja muy suave al pasar mouse */

        /* Estados */
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

        /* Resaltar campos de cálculo */
        .bg-calc {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #555;
        }

        .text-total {
            color: #d35400;
            font-weight: bold;
            font-size: 1.05em;
        }
    </style>
</head>

<body>

    <div class="container-fluid p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2>🔥 Edición Masiva de Gas</h2>
                <p class="text-muted">Lecturas y facturación de gas común.</p>
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

        <div class="card mb-4 bg-light border-warning">
            <div class="card-body py-2">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="fw-bold small">Mes</label>
                        <select name="mes" class="form-select border-warning">
                            <?php foreach ($meses_select as $m): ?>
                                <option value="<?php echo $m; ?>" <?php echo ($m == $mes_nombre) ? 'selected' : ''; ?>>
                                    <?php echo $m; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="fw-bold small">Año</label>
                        <input type="number" name="anio" class="form-control border-warning" value="<?php echo $anio_actual; ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-warning text-white fw-bold w-100">🔍 Buscar</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($lista_gas)): ?>
            <div class="alert alert-warning text-center">
                No se encontraron registros de gas para <strong><?php echo $mes_nombre . ' ' . $anio_actual; ?></strong>.
                <br>
                <a href="index.php" class="btn btn-sm btn-dark mt-2">Volver</a>
            </div>
        <?php else: ?>

            <form method="POST" id="formGas">
                <input type="hidden" name="accion" value="guardar_gas">

                <div class="d-flex justify-content-between mb-2">
                    <span class="badge bg-secondary fs-6 align-self-center">
                        <?php echo count($lista_gas); ?> Registros encontrados
                    </span>
                    <button type="submit" class="btn btn-success fw-bold px-5" onclick="return confirm('¿Guardar todos los cambios de lecturas?');">
                        💾 GUARDAR CAMBIOS
                    </button>
                </div>

                <div class="table-responsive shadow border rounded">
                    <table class="table table-bordered table-sm mb-0 align-middle">
                        <thead>
                            <tr class="text-center">
                                <th>Apto</th>
                                <th>Lect. Anterior</th>
                                <th>Lect. Actual</th>
                                <th>Consumo (Gal)</th>
                                <th>Precio / Gal</th>
                                <th>Total (RD$)</th>
                                <th>Abonado</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_gas as $g):
                                $id = $g['id'];
                                $bg_row = ($g['estado'] == 'Pagado') ? 'bg-light' : '';
                            ?>
                                <tr class="<?php echo $bg_row; ?>" id="row_<?php echo $id; ?>">

                                    <td class="fw-bold text-center bg-light">
                                        <?php echo $g['apto']; ?>
                                        <input type="hidden" name="gas[<?php echo $id; ?>][id]" value="<?php echo $id; ?>">
                                    </td>

                                    <td class="text-center">
                                        <input type="number" step="0.01"
                                            name="gas[<?php echo $id; ?>][lectura_anterior]"
                                            class="form-control form-control-sm input-corto mx-auto calc-trigger"
                                            data-id="<?php echo $id; ?>"
                                            value="<?php echo $g['lectura_anterior']; ?>">
                                    </td>

                                    <td class="text-center">
                                        <input type="number" step="0.01"
                                            name="gas[<?php echo $id; ?>][lectura_actual]"
                                            class="form-control form-control-sm input-corto mx-auto calc-trigger"
                                            data-id="<?php echo $id; ?>"
                                            value="<?php echo $g['lectura_actual']; ?>">
                                    </td>

                                    <td class="text-center bg-calc">
                                        <span id="view_consumo_<?php echo $id; ?>">
                                            <?php echo number_format((float)$g['consumo_galones'], 2); ?>
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="input-group input-group-sm" style="width: 130px; margin: 0 auto;">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01"
                                                name="gas[<?php echo $id; ?>][precio_galon]"
                                                class="form-control input-dinero calc-trigger"
                                                data-id="<?php echo $id; ?>"
                                                value="<?php echo $g['precio_galon']; ?>">
                                        </div>
                                    </td>

                                    <td class="text-center bg-calc text-total">
                                        RD$ <span id="view_total_<?php echo $id; ?>">
                                            <?php echo number_format((float)$g['total_gas'], 2); ?>
                                        </span>
                                    </td>

                                    <td class="text-center text-muted small">
                                        <?php echo number_format((float)$g['abono'], 2); ?>
                                    </td>

                                    <td>
                                        <select name="gas[<?php echo $id; ?>][estado]"
                                            class="form-select form-select-sm fw-bold 
                                        <?php echo ($g['estado'] == 'Pagado' ? 'estado-pagado' : ($g['estado'] == 'Abonado' ? 'estado-abonado' : 'estado-pendiente')); ?>"
                                            onchange="cambiarColorSelect(this)">
                                            <option value="Pendiente" <?php echo ($g['estado'] == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                            <option value="Pagado" <?php echo ($g['estado'] == 'Pagado') ? 'selected' : ''; ?>>Pagado</option>
                                            <option value="Abonado" <?php echo ($g['estado'] == 'Abonado') ? 'selected' : ''; ?>>Abonado</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 mb-5">
                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold" onclick="return confirm('¿Guardar todos los cambios de lecturas?');">
                        💾 GUARDAR CAMBIOS
                    </button>
                </div>
            </form>

        <?php endif; ?>

    </div>

    <script>
        document.querySelectorAll('.calc-trigger').forEach(input => {
            input.addEventListener('input', function() {
                let id = this.getAttribute('data-id');

                // 1. Obtener valores
                let anterior = parseFloat(document.querySelector(`input[name="gas[${id}][lectura_anterior]"]`).value) || 0;
                let actual = parseFloat(document.querySelector(`input[name="gas[${id}][lectura_actual]"]`).value) || 0;
                let precio = parseFloat(document.querySelector(`input[name="gas[${id}][precio_galon]"]`).value) || 0;

                // 2. Calcular Consumo (Actual - Anterior)
                let consumo = actual - anterior;
                if (consumo < 0) consumo = 0; // Evitar negativos visuales

                // 3. Calcular Total (Consumo * Precio)
                let total = consumo * precio;

                // 4. Actualizar HTML
                document.getElementById(`view_consumo_${id}`).innerText = consumo.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                document.getElementById(`view_total_${id}`).innerText = total.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            });
        });

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