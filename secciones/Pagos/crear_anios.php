<?php
$thetitle = "Generar Tickets";
include("../../templates/header.php"); ?>
<?php include("../../bd.php");

$id_condominio = $_SESSION['idcondominio'] ?? 0;

// Eliminación de tickets si viene txID
if (isset($_GET['txID'])) {
    $txtID = $_GET['txID'] ?? "";
    $fecha_creacion = $_GET['fecha_creacion'] ?? "";
    $mes = $_GET['mes'] ?? "";
    $anio = $_GET['anio'] ?? "";

    try {
        // Primero obtener el mes y año del registro a eliminar
        if (empty($mes) || empty($anio)) {
            $sentencia_info = $conexion->prepare("SELECT mes, anio FROM tbl_tickets_realizados WHERE id=:id AND id_condominio=:id_condominio");
            $sentencia_info->bindParam(":id", $txtID);
            $sentencia_info->bindParam(":id_condominio", $id_condominio);
            $sentencia_info->execute();
            $info = $sentencia_info->fetch(PDO::FETCH_ASSOC);

            if ($info) {
                $mes = $info['mes'];
                $anio = $info['anio'];
            }
        }

        // Eliminar de tbl_tickets_realizados
        $sentencia = $conexion->prepare("DELETE FROM tbl_tickets_realizados WHERE id=:id AND id_condominio=:id_condominio");
        $sentencia->bindParam(":id", $txtID);
        $sentencia->bindParam(":id_condominio", $id_condominio);
        $sentencia->execute();

        // Eliminar de tbl_tickets usando mes y año
        $sentencia_tickets = $conexion->prepare("DELETE FROM tbl_tickets WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio");
        $sentencia_tickets->bindParam(":mes", $mes);
        $sentencia_tickets->bindParam(":anio", $anio);
        $sentencia_tickets->bindParam(":id_condominio", $id_condominio);
        $sentencia_tickets->execute();

        // También eliminar cualquier cuota extra relacionada con ese mes
        $sentencia_cuotas = $conexion->prepare("DELETE FROM tbl_cuotas_extras WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio");
        $sentencia_cuotas->bindParam(":mes", $mes);
        $sentencia_cuotas->bindParam(":anio", $anio);
        $sentencia_cuotas->bindParam(":id_condominio", $id_condominio);
        $sentencia_cuotas->execute();

        // También eliminar cualquier registro de gas relacionado con ese mes
        $sentencia_gas = $conexion->prepare("DELETE FROM tbl_gas WHERE mes=:mes AND anio=:anio AND id_condominio=:id_condominio");
        $sentencia_gas->bindParam(":mes", $mes);
        $sentencia_gas->bindParam(":anio", $anio);
        $sentencia_gas->bindParam(":id_condominio", $id_condominio);
        $sentencia_gas->execute();

        $_SESSION['mensaje'] = "✅ Tickets del mes $mes $anio eliminados correctamente";
        $_SESSION['tipo_mensaje'] = "success";
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "❌ Error al eliminar tickets: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
    }

    header("Location: crear_anios.php");
    exit;
}

// Mostrar mensajes de sesión
if (isset($_SESSION['mensaje'])) {
    echo "<div class='alert alert-{$_SESSION['tipo_mensaje']}'>{$_SESSION['mensaje']}</div>";
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}

// Obtener tickets realizados
$statement = $conexion->prepare("SELECT * FROM tbl_tickets_realizados WHERE id_condominio = :id_condominio ORDER BY anio DESC, FIELD(mes, 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre') DESC");
$statement->bindParam(':id_condominio', $id_condominio);
$statement->execute();
$lista_ti_re = $statement->fetchAll();

// Generación de tickets
if (isset($_POST['boton'])) {
    $el_mes = $_POST['el_mes'] ?? '';
    $el_anio = $_POST['el_anio'] ?? '';

    // Verificar si ya se generaron tickets para ese mes/año
    $stmtCheck = $conexion->prepare("SELECT * FROM tbl_tickets_realizados WHERE id_condominio = :id_condominio AND mes = :mes AND anio = :anio");
    $stmtCheck->bindParam(':id_condominio', $id_condominio);
    $stmtCheck->bindParam(':mes', $el_mes);
    $stmtCheck->bindParam(':anio', $el_anio);
    $stmtCheck->execute();
    $tickets_existentes = $stmtCheck->fetchAll();

    if (count($tickets_existentes) > 0) {
        echo "<div class='alert alert-warning'>Ya se generaron tickets para $el_mes $el_anio.</div>";
    } else {
        // Obtener apartamentos del condominio
        $stmtAptos = $conexion->prepare("SELECT id, apto, mantenimiento FROM tbl_aptos WHERE id_condominio = :id_condominio");
        $stmtAptos->bindParam(':id_condominio', $id_condominio);
        $stmtAptos->execute();
        $aptos = $stmtAptos->fetchAll();

        if (count($aptos) < 1) {
            echo "<div class='alert alert-danger'>No se encontraron apartamentos para este condominio.</div>";
        } else {
            date_default_timezone_set('America/Santo_Domingo');
            $fecha_actual = date('Y-m-d H:i:s');

            // Insertar tickets para cada apartamento
            foreach ($aptos as $row) {
                $mantenimiento = $row['mantenimiento'];
                $id_apto = $row['id'];
                $apto_numero = $row['apto'];

                $sentencia = $conexion->prepare("INSERT INTO tbl_tickets 
                    (id_apto, mantenimiento, mora, mes, anio, estado, id_condominio, fecha_actual, usuario_registro) 
                    VALUES (:id_apto, :mantenimiento, '0', :mes, :anio, 'Pendiente', :id_condominio, :fecha_actual, :usuario_registro)");

                $sentencia->bindParam(':id_apto', $id_apto);
                $sentencia->bindParam(':mantenimiento', $mantenimiento);
                $sentencia->bindParam(':mes', $el_mes);
                $sentencia->bindParam(':anio', $el_anio);
                $sentencia->bindParam(':id_condominio', $id_condominio);
                $sentencia->bindParam(':fecha_actual', $fecha_actual);
                $sentencia->bindParam(':usuario_registro', $_SESSION['usuario']);
                $sentencia->execute();
            }

            // Registrar mes/año generado
            $stmtInsert = $conexion->prepare("INSERT INTO tbl_tickets_realizados (mes, anio, id_condominio, fecha) VALUES (:mes, :anio, :id_condominio, :fecha)");
            $stmtInsert->bindParam(':mes', $el_mes);
            $stmtInsert->bindParam(':anio', $el_anio);
            $stmtInsert->bindParam(':id_condominio', $id_condominio);
            $stmtInsert->bindParam(':fecha', $fecha_actual);
            $stmtInsert->execute();

            $_SESSION['mensaje'] = "✅ Tickets generados correctamente para $el_mes $el_anio";
            $_SESSION['tipo_mensaje'] = "success";

            header("Location: crear_anios.php");
            exit;
        }
    }
}
?>
<br>
<!-- FORMULARIO -->
<div class="center">
    <div class="card">
        <div class="card-header text-center bg-dark text-white">
            <h2>GENERACIÓN DE TICKETS</h2>
        </div>
        <div class="card-body">
            <?php
            // Obtener mes y año actual
            $mes_actual = date('n'); // 1 a 12
            $anio_actual = date('Y');
            $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            ?>
            <form method="POST" action="">
                <div class="row">
                    <!-- Selección de mes -->
                    <div class="mb-3 col">
                        <label class="form-label">Selecciona un mes:</label>
                        <select class="form-select form-select-lg mb-3" name="el_mes" required>
                            <?php
                            foreach ($meses as $index => $mes) {
                                $numero_mes = $index + 1;
                                $selected = ($numero_mes == $mes_actual) ? 'selected' : '';
                                echo "<option value='$mes' $selected>$mes</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Selección de año -->
                    <div class="mb-3 col">
                        <label class="form-label">Selecciona un año:</label>
                        <select class="form-select form-select-lg mb-3" name="el_anio" required>
                            <?php
                            for ($i = 2024; $i <= 2046; $i++) {
                                $selected = ($i == $anio_actual) ? 'selected' : '';
                                echo "<option value='$i' $selected>$i</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="card-footer text-muted text-center">
                    <input class="btn btn-warning" type="submit" name="boton" value="GENERAR TICKETS">
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TABLA DE TICKETS GENERADOS -->
<div class="card mt-4">
    <div class="card-header text-center bg-dark text-white">
        <h2>TICKETS GENERADOS</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive-sm">
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Mes</th>
                        <th>Año</th>
                        <th>Fecha de creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_ti_re as $registro): ?>
                        <tr>
                            <td><?= $registro['id'] ?></td>
                            <td><?= $registro['mes'] ?></td>
                            <td><?= $registro['anio'] ?></td>
                            <td>
                                <?php
                                $fechaDB = $registro['fecha'];
                                $fechaFormateada = date("d/m/Y H:i", strtotime($fechaDB));
                                echo $fechaFormateada;
                                ?>
                            </td>
                            <td>
                                <a class="btn btn-danger btn-sm"
                                    href="javascript:borrar(<?= $registro['id'] ?>, '<?= $registro['mes'] ?>', '<?= $registro['anio'] ?>')">
                                    ❌ Eliminar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function borrar(id, mes, anio) {
        Swal.fire({
            title: '¿Eliminar tickets?',
            html: `Esta acción eliminará <strong>TODOS</strong> los tickets, cuotas extras y registros de gas del mes <strong>${mes} ${anio}</strong>.<br><br>¿Estás seguro?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = "crear_anios.php?txID=" + id + "&mes=" + mes + "&anio=" + anio;
            }
        });
    }
</script>

<?php include("../../templates/footer.php"); ?>