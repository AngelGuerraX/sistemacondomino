<?php
require 'vendor/autoload.php';
require 'bd.php';

use PhpOffice\PhpSpreadsheet\{Spreadsheet, IOFactory};

$sql = "SELECT * FROM tbl_cargar_camion";
$resultado = $conexion->prepare($sql);
$resultado->execute();

$excel = new Spreadsheet();
$hojaActiva = $excel->getActiveSheet();
$hojaActiva->setTitle("cargar_camion");

$hojaActiva->getColumnDimension('A')->setWidth(15);
$hojaActiva->setCellValue('A1', 'fecha');
$hojaActiva->getColumnDimension('B')->setWidth(10);
$hojaActiva->setCellValue('B1', 'hora');
$hojaActiva->getColumnDimension('C')->setWidth(20);
$hojaActiva->setCellValue('C1', 'usuario');
$hojaActiva->getColumnDimension('D')->setWidth(15);
$hojaActiva->setCellValue('D1', 'operador');
$hojaActiva->getColumnDimension('E')->setWidth(15);
$hojaActiva->setCellValue('E1', 'camion_mmu');
$hojaActiva->getColumnDimension('F')->setWidth(15);
$hojaActiva->setCellValue('F1', 'cant_requerida');
$hojaActiva->getColumnDimension('G')->setWidth(10);
$hojaActiva->setCellValue('G1', 't_inicio');
$hojaActiva->getColumnDimension('H')->setWidth(10);
$hojaActiva->setCellValue('H1', 't_final');
$hojaActiva->getColumnDimension('I')->setWidth(11);
$hojaActiva->setCellValue('I1', 'rango_t');
$hojaActiva->getColumnDimension('J')->setWidth(15);
$hojaActiva->setCellValue('J1', 'inv_inicial_pulg');
$hojaActiva->getColumnDimension('K')->setWidth(15);
$hojaActiva->setCellValue('K1', 'inv_inicial_mt');
$hojaActiva->getColumnDimension('L')->setWidth(15);
$hojaActiva->setCellValue('L1', 'entrada_mt');
$hojaActiva->getColumnDimension('M')->setWidth(15);
$hojaActiva->setCellValue('M1', 'entrada_pulg');
$hojaActiva->getColumnDimension('N')->setWidth(15);
$hojaActiva->setCellValue('N1', 'inv_final_pulg');
$hojaActiva->getColumnDimension('O')->setWidth(15);
$hojaActiva->setCellValue('O1', 'inv_final_mt');
$hojaActiva->getColumnDimension('P')->setWidth(15);
$hojaActiva->setCellValue('P1', 'despacho_mt');
$hojaActiva->getColumnDimension('Q')->setWidth(15);
$hojaActiva->setCellValue('Q1', 'restante_en_silo');
$hojaActiva->getColumnDimension('R')->setWidth(30);
$hojaActiva->setCellValue('R1', 'descripcion');
$hojaActiva->getColumnDimension('S')->setWidth(8);
$hojaActiva->setCellValue('S1', 'id');

$fila = 2;

while ($rows = $resultado->fetch(PDO::FETCH_ASSOC)) {
    $hojaActiva->setCellValue('A' . $fila, $rows['fecha']);
    $hojaActiva->setCellValue('B' . $fila, $rows['hora']);
    $hojaActiva->setCellValue('C' . $fila, $rows['usuario']);
    $hojaActiva->setCellValue('D' . $fila, $rows['operador']);
    $hojaActiva->setCellValue('E' . $fila, $rows['camion_mmu']);
    $hojaActiva->setCellValue('F' . $fila, $rows['cant_requerida']);
    $hojaActiva->setCellValue('G' . $fila, $rows['t_inicio']);
    $hojaActiva->setCellValue('H' . $fila, $rows['t_final']);
    $hojaActiva->setCellValue('I' . $fila, $rows['rango_t']);
    $hojaActiva->setCellValue('J' . $fila, $rows['inv_inicial_pulg']);
    $hojaActiva->setCellValue('K' . $fila, $rows['inv_inicial_mt']);
    $hojaActiva->setCellValue('L' . $fila, $rows['entrada_mt']);
    $hojaActiva->setCellValue('M' . $fila, $rows['entrada_pulg']);
    $hojaActiva->setCellValue('N' . $fila, $rows['inv_final_pulg']);
    $hojaActiva->setCellValue('O' . $fila, $rows['inv_final_mt']);
    $hojaActiva->setCellValue('P' . $fila, $rows['despacho_mt']);
    $hojaActiva->setCellValue('Q' . $fila, $rows['restante_en_silo']);
    $hojaActiva->setCellValue('R' . $fila, $rows['descripcion']);
    $hojaActiva->setCellValue('S' . $fila, $rows['id']);
    $fila++;
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_carga_camion.xls"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($excel, 'Xls');
$writer->save('php://output');
exit;