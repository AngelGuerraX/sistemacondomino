<?php
include("../../bd.php");
session_start();
$idcondominio = $_SESSION['idcondominio'];

$mes = $_GET['mes'];
$anio = $_GET['anio'];

$sentencia = $conexion->prepare("SELECT descripcion,monto FROM tbl_cuotas_extras WHERE id_condominio=:idcondominio AND mes=:mes AND anio=:anio");
$sentencia->execute([':idcondominio' => $idcondominio, ':mes' => $mes, ':anio' => $anio]);

echo json_encode($sentencia->fetchAll(PDO::FETCH_ASSOC));
