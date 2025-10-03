<?php
require 'vendor/autoload.php';
require 'bd.php';

use PhpOffice\PhpSpreadsheet\{Spreadsheet, IOFactory};

$desde = $_POST['Desde'];
$hasta = $_POST['Hasta'];

$sql = "SELECT * FROM tbl_cargar_camion WHERE fecha BETWEEN '$desde' AND '$hasta' ";
$resultado = $conexion->prepare($sql);
$resultado->execute();

$excel = new Spreadsheet();
$hojaActiva = $excel->getActiveSheet();
$hojaActiva->setTitle("cargar_camion");

$hojaActiva->getStyle('A1:S1')->getFont()->setBold(true);
$hojaActiva->getStyle('A1:S1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('40CBFF');

$hojaActiva->getColumnDimension('A')->setWidth(15);
$hojaActiva->setCellValue('A1', 'FECHA');
$hojaActiva->getColumnDimension('B')->setWidth(10);
$hojaActiva->setCellValue('B1', 'HORA');
$hojaActiva->getColumnDimension('C')->setWidth(20);
$hojaActiva->setCellValue('C1', 'USUARIO');
$hojaActiva->getColumnDimension('D')->setWidth(15);
$hojaActiva->setCellValue('D1', 'OPERADOR');
$hojaActiva->getColumnDimension('E')->setWidth(15);
$hojaActiva->setCellValue('E1', 'CAMION MMU');
$hojaActiva->getColumnDimension('F')->setWidth(19);
$hojaActiva->setCellValue('F1', 'CANT. REQUERIDA');
$hojaActiva->getColumnDimension('G')->setWidth(10);
$hojaActiva->setCellValue('G1', 'T INICIO');
$hojaActiva->getColumnDimension('H')->setWidth(10);
$hojaActiva->setCellValue('H1', 'T FINAL');
$hojaActiva->getColumnDimension('I')->setWidth(11);
$hojaActiva->setCellValue('I1', 'T RANGO');
$hojaActiva->getColumnDimension('J')->setWidth(17);
$hojaActiva->setCellValue('J1', 'INV.INICIAL(PULG)');
$hojaActiva->getColumnDimension('K')->setWidth(15);
$hojaActiva->setCellValue('K1', 'INV.INICIAL(MT)');
$hojaActiva->getColumnDimension('L')->setWidth(15);
$hojaActiva->setCellValue('L1', 'ENTRADA(MT)');
$hojaActiva->getColumnDimension('M')->setWidth(17);
$hojaActiva->setCellValue('M1', 'ENTRADA(PULG)');
$hojaActiva->getColumnDimension('N')->setWidth(17);
$hojaActiva->setCellValue('N1', 'INV.FINAL(PULG)');
$hojaActiva->getColumnDimension('O')->setWidth(15);
$hojaActiva->setCellValue('O1', 'INV.FINAL(MT)');
$hojaActiva->getColumnDimension('P')->setWidth(16);
$hojaActiva->setCellValue('P1', 'DESPACHO (MT)');
$hojaActiva->getColumnDimension('Q')->setWidth(16);
$hojaActiva->setCellValue('Q1', 'RESTANTE (MT)');
$hojaActiva->getColumnDimension('R')->setWidth(30);
$hojaActiva->setCellValue('R1', 'DESCRIPCION');
$hojaActiva->getColumnDimension('S')->setWidth(8);
$hojaActiva->setCellValue('S1', 'ID');

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

