<?php include("../../templates/header.php"); ?>
<?php include("../../bd.php");

$id_condominio = $_SESSION['idcondominio'] ?? 0;

// Eliminación de tickets si viene txID
if (isset($_GET['txID'])) {
    $txtID = $_GET['txID'] ?? "";
    $fecha_creacion = $_GET['fecha_creacion'] ?? "";

    $sentencia = $conexion->prepare("DELETE FROM tbl_tickets_realizados WHERE fecha=:fecha_creacion");
    $sentencia->bindParam(":fecha_creacion", $fecha_creacion);
    $sentencia->execute();

    $sentencia = $conexion->prepare("DELETE FROM tbl_tickets WHERE fecha=:fecha_creacion");
    $sentencia->bindParam(":fecha_creacion", $fecha_creacion);
    $sentencia->execute();
}

// Obtener tickets realizados
$statement = $conexion->prepare("SELECT * FROM tbl_tickets_realizados WHERE id_condominio = :id_condominio");
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
        echo "<div class='alert alert-warning'>Ya se generaron tickets para $el_mes/$el_anio.</div>";
    } else {
        // Obtener apartamentos del condominio
        $stmtAptos = $conexion->prepare("SELECT id, mantenimiento FROM tbl_aptos WHERE id_condominio = :id_condominio");
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
                $sentencia = $conexion->prepare("INSERT INTO tbl_tickets (id_apto, mantenimiento, mora, gas, cuota, mes, anio, estado, id_condominio)
                                                 VALUES (:id_apto, :mantenimiento, '0', '0', '0', :mes, :anio, 'Pendiente', :id_condominio)");
                $sentencia->bindParam(':id_apto', $row['id']);
                $sentencia->bindParam(':mantenimiento', $row['mantenimiento']);
                $sentencia->bindParam(':mes', $el_mes);
                $sentencia->bindParam(':anio', $el_anio);
                $sentencia->bindParam(':id_condominio', $id_condominio);
                $sentencia->execute();
            }

            // Registrar mes/año generado
            $stmtInsert = $conexion->prepare("INSERT INTO tbl_tickets_realizados (mes, anio, id_condominio, fecha) VALUES (:mes, :anio, :id_condominio, :fecha)");
            $stmtInsert->bindParam(':mes', $el_mes);
            $stmtInsert->bindParam(':anio', $el_anio);
            $stmtInsert->bindParam(':id_condominio', $id_condominio);
            $stmtInsert->bindParam(':fecha', $fecha_actual);
            $stmtInsert->execute();

            header("Location: crear_anios.php");
            exit;
        }
    }
}
?>

<!-- FORMULARIO -->
<div class="center">
    <div class="card">
        <div class="card-header text-center bg-dark text-white">
            <h2>GENERACIÓN DE TICKETS</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="mb-3 col">
                        <label class="form-label">Selecciona un mes:</label>
                        <select class="form-select form-select-lg mb-3" name="el_mes">
                            <?php
                            $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                            foreach ($meses as $mes) {
                                echo "<option value='$mes'>$mes</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3 col">
                        <label class="form-label">Selecciona un año:</label>
                        <select class="form-select form-select-lg mb-3" name="el_anio">
                            <?php for ($i = 2025; $i <= 2046; $i++) {
                                echo "<option value='$i'>$i</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <input class="btn btn-secondary" type="submit" name="boton" value="Enviar">
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
                        <th>Fecha</th>
                        <th>ID Condominio</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_ti_re as $registro): ?>
                        <tr>
                            <td><?= $registro['id'] ?></td>
                            <td><?= $registro['mes'] ?></td>
                            <td><?= $registro['anio'] ?></td>
                            <td><?= $registro['fecha'] ?></td>
                            <td><?= $registro['id_condominio'] ?></td>
                            <td>
                                <a class="btn btn-danger" href="javascript:borrar(<?= $registro['id'] ?>,'<?= $registro['fecha'] ?>')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function borrar(id, fecha_creacion) {
        Swal.fire({
            title: '¿Eliminar los tickets del día ' + fecha_creacion + '?',
            showCancelButton: true,
            confirmButtonText: 'Sí, borrar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = "crear_anios.php?txID=" + id + "&fecha_creacion=" + fecha_creacion;
            }
        });
    }
</script>

<?php include("../../templates/footer.php"); ?>