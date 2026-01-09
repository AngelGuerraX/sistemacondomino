<?php
$thetitle = "Cambio de Estado de Cuenta PDF";
include("../../templates/header.php"); ?>
<?php include("../../bd.php");

$id_condominio = $_SESSION['idcondominio'];

if (isset($_GET['txID'])) {
    $txtID = (isset($_GET['txID'])) ? $_GET['txID'] : "";
    $fecha_creacion = (isset($_GET['fecha_creacion'])) ? $_GET['fecha_creacion'] : "";

    $sentencia = $conexion->prepare("DELETE FROM tbl_tickets_realizados WHERE fecha=:fecha_creacion");
    $sentencia->bindParam(":fecha_creacion", $fecha_creacion);
    $sentencia->execute();

    $sentencia = $conexion->prepare("DELETE FROM tbl_tickets WHERE fecha=:fecha_creacion");
    $sentencia->bindParam(":fecha_creacion", $fecha_creacion);
    $sentencia->execute();
}

// Obtener los registros de la tabla tbl_aptos filtrados por id_condominio
$query = "SELECT * FROM tbl_condominios where id=:id_condominio";
$statement = $conexion->prepare($query);
$statement->bindParam(':id_condominio', $id_condominio);
$statement->execute();
$lista_condominio = $statement->fetchAll();


if (isset($_POST['boton'])) {
    //recoleccion de datos
    $id = $id_condominio;
    // Verificación del checkbox y asignación del valor correspondiente
    $mora = isset($_POST['mora']) ? 'si' : 'no';
    $gas = isset($_POST['gas']) ? 'si' : 'no';
    $cuota = isset($_POST['cuota']) ? 'si' : 'no';

    //preparar insercion
    $sentencia = $conexion->prepare("UPDATE tbl_condominios SET gas=:gas, mora=:mora, cuota=:cuota 
    WHERE id=:id");

    //Asignando los valores de metodo post(del formulario)
    $sentencia->bindParam(":gas", $gas);
    $sentencia->bindParam(":mora", $mora);
    $sentencia->bindParam(":cuota", $cuota);
    $sentencia->bindParam(":id", $id);
    $sentencia->execute();

    header("Location:edit_pdf_estado.php");
};

?>

<br>
<div class="center">
    <a name="" id="" class="btn btn-primary" href="../../index.php" role="button">Atras</a>
    <h2 style="color: white; font-size: 35px; text-align: center; margin-top: -40px;">Estado de Resultado </h2>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                Habilita o Deshabilita
            </h5>
        </div>
        <div class="card-body">

            <p class="card-text">Si tiene habilitada algunas de estas opciones en este condominio, Tenga en cuenta de que no aparecera ningun monto con referencia al mismo.</p>


            <form action="" method="post" enctype="multipart/form-data">
                <?php foreach ($lista_condominio as $registro) { ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="mora" name="mora" <?php if ($registro['mora'] === 'si') {
                                                                                                            echo "checked";
                                                                                                        } ?>>
                        <label class="form-check-label" for="flexCheckDefault">Moras</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="gas" name="gas" <?php if ($registro['gas'] === 'si') {
                                                                                                            echo "checked";
                                                                                                        } ?>>
                        <label class="form-check-label" for="flexCheckDefault1">Gas</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="cuota" name="cuota" <?php if ($registro['cuota'] === 'si') {
                                                                                                                echo "checked";
                                                                                                            } ?>>
                        <label class="form-check-label" for="flexCheckDefault2">Cuotas Extras</label>
                    </div>
                    <br>
                    <input class="btn btn-success" type="submit" name="boton" value="Enviar">
                <?php } ?>
            </form>
        </div>
    </div>


</div>
<br>

<script>
    function borrar(id, fecha_creacion) {
        Swal.fire({
            title: '¿Se Eliminara los tickets del dia ' + fecha_creacion + '?',
            showCancelButton: true,
            confirmButtonText: 'Si, borrar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Pasa el segundo dato también a través del enlace
                window.location = "crear_anios.php?txID=" + id + "&fecha_creacion=" + fecha_creacion;
            }
        });
    }
</script>

<?php include("../../templates/footer.php"); ?>