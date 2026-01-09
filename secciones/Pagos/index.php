<?php
$thetitle = "INGRESOS";
include("../../templates/header.php"); ?>
<?php
include("../../bd.php");

// 1. OBTENER VARIABLES
$mes_actual = isset($_GET['mes']) ? $_GET['mes'] : (isset($_SESSION['mes']) ? $_SESSION['mes'] : date('n'));
$anio_actual = isset($_GET['anio']) ? $_GET['anio'] : (isset($_SESSION['anio']) ? $_SESSION['anio'] : date('Y'));
$idcondominio = $_SESSION['idcondominio'];

// 2. CONVERSIÓN DE MES
$meses_nombres = [1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril", 5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto", 9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"];
$mes_para_buscar = is_numeric($mes_actual) ? $meses_nombres[intval($mes_actual)] : $mes_actual;
$mes_numero_filtro = is_numeric($mes_actual) ? $mes_actual : array_search($mes_actual, $meses_nombres);

// Borrar
if (isset($_GET['borrar_id'])) {
   $sentencia = $conexion->prepare("DELETE FROM tbl_aptos WHERE id=:id");
   $sentencia->execute([':id' => $_GET['borrar_id']]);
   header("Location: index.php");
   exit;
}

// CONSULTA SQL
$query = "SELECT 
            A.id, A.apto, A.condominos, A.fecha_ultimo_pago, T.estado,
            A.balance as balance_total_apto, 
            
            I.nombre as nombre_inquilino, 
            COALESCE(I.balance, 0) as balance_inquilino,
            
            (SELECT COALESCE(SUM(monto), 0) FROM tbl_pagos P WHERE P.id_apto = A.id AND P.mes_ingreso = :mes AND P.anio_ingreso = :anio) as pagado_propietario,
            (SELECT COALESCE(SUM(pi.monto), 0) FROM tbl_pagos_inquilinos pi INNER JOIN tbl_inquilinos i ON pi.id_inquilino = i.id WHERE i.id_apto = A.id AND pi.mes_gas = :mes AND pi.anio_gas = :anio) as pagado_inquilino
             
          FROM tbl_aptos A
          LEFT JOIN tbl_tickets T ON A.id = T.id_apto AND T.mes = :mes AND T.anio = :anio
          LEFT JOIN tbl_inquilinos I ON A.id = I.id_apto AND I.activo = 1
          WHERE A.id_condominio = :idcondominio
          ORDER BY A.apto ASC";

$sentencia = $conexion->prepare($query);
$sentencia->execute([':idcondominio' => $idcondominio, ':mes' => $mes_para_buscar, ':anio' => $anio_actual]);
$Lista_tbl_aptos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

$sum_total_deuda_calle = 0;
$sum_total_recaudado = 0;
?>

<br>
<div class="container-fluid">
   <div class="card shadow mb-3">
      <div class="card-header bg-dark text-white p-3">
         <div class="row align-items-center">
            <div class="col-md-4">
               <h3 class="mb-0">INGRESOS: <span class="text-warning"><?php echo strtoupper($mes_para_buscar) . " " . $anio_actual; ?></span></h3>
            </div>
            <div class="col-md-5">
               <form action="" method="GET" class="d-flex gap-2">
                  <select name="mes" class="form-select form-select-sm fw-bold text-uppercase">
                     <?php foreach ($meses_nombres as $num => $nombre): ?>
                        <option value="<?php echo $num; ?>" <?php echo ($num == $mes_numero_filtro) ? 'selected' : ''; ?>><?php echo $nombre; ?></option>
                     <?php endforeach; ?>
                  </select>
                  <select name="anio" class="form-select form-select-sm fw-bold">
                     <?php for ($y = 2024; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $anio_actual) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                     <?php endfor; ?>
                  </select>
                  <button type="submit" class="btn btn-warning btn-sm fw-bold">🔎 Buscar</button>
               </form>
            </div>
            <div class="col-md-3 text-end">
               <div class="btn-group">
                  <a href="<?php echo $url_base; ?>secciones/pagos/crear_anios.php" class="btn btn-success btn-sm">➕ Cargos</a>
                  <a href="<?php echo $url_base; ?>secciones/pagos/herramientas_balance.php" class="btn btn-outline-light btn-sm">🛠️</a>
                  <a href="<?php echo $url_base; ?>secciones/pagos/reporte_pagos_global.php" class="btn btn-outline-light btn-sm">📊</a>
               </div>
            </div>
         </div>
      </div>

      <div class="card-body p-0">
         <div class="table-responsive">
            <table class="table table-hover table-striped align-middle table-bordered mb-0" id="tabla_id">
               <thead class="table-light text-center">
                  <tr>
                     <th width="12%">Acción</th>
                     <th width="25%">Ocupantes</th>
                     <th width="25%">Estado de Cuentas (Deuda Total)</th>
                     <th width="15%">Pagado en <?php echo substr($mes_para_buscar, 0, 3); ?></th>
                     <th>Último Mov.</th>
                  </tr>
               </thead>
               <tbody class=".table-striped">
                  <?php foreach ($Lista_tbl_aptos as $registro) {

                     // 1. OBTENER DATOS CRUDOS
                     $bal_total_db = floatval($registro['balance_total_apto']);
                     $bal_inq = floatval($registro['balance_inquilino']);

                     // 2. CÁLCULO INTELIGENTE DEL PROPIETARIO
                     $bal_prop_crudo = $bal_total_db - $bal_inq;

                     // LIMPIEZA: Si la deuda es menor a 1 peso (basura de decimales), es 0.
                     if (abs($bal_prop_crudo) < 1.00) {
                        $bal_prop = 0.00;
                     } else {
                        $bal_prop = $bal_prop_crudo;
                     }

                     // 3. RECALCULAR TOTAL VISUAL 
                     // AQUÍ ESTÁ EL TRUCO: Ignoramos el total de la BD (1062) y sumamos lo que mostramos (0 + 1061.50)
                     // Así visualmente siempre cuadra: Prop + Inq = Total
                     $bal_total_visual = $bal_prop + $bal_inq;

                     // ACUMULADORES (Usamos el visual para que el reporte final cuadre)
                     $sum_total_deuda_calle += $bal_total_visual;

                     $total_pagado_mes = floatval($registro['pagado_propietario']) + floatval($registro['pagado_inquilino']);
                     $sum_total_recaudado += $total_pagado_mes;

                     $btn_color = ($registro['estado'] === 'Pagado') ? 'btn-success' : 'btn-danger';

                     // Definir colores
                     $clase_prop = ($bal_prop > 0.01) ? 'text-danger' : 'text-success';
                     $clase_inq  = ($bal_inq > 0.01) ? 'text-danger' : 'text-success';
                     $clase_total = ($bal_total_visual > 0.01) ? 'text-danger fw-bold' : 'text-success fw-bold';
                  ?>
                     <tr>
                        <td class="text-center">
                           <div class="d-grid gap-1">
                              <a class="btn <?php echo $btn_color; ?> btn-sm" href="gestion_pagos.php?txID=<?php echo $registro['id'] ?>"><?php echo $registro['apto'] ?> 💰</a>
                              <div class="btn-group btn-group-sm">
                                 <a class="btn btn-dark text-white" href="historial_pagos.php?id_apto=<?php echo $registro['id'] ?>">📜</a>
                                 <a class="btn btn-dark text-dark" href="estado_cuenta.php?id_apto=<?php echo $registro['id'] ?>" target="_blank">📄</a>
                              </div>
                           </div>
                        </td>
                        <td>
                           <div class="d-flex justify-content-between"><span><strong>Propietario: </strong></span><span><?php echo $registro['condominos'] ?></span></div>
                           <?php if (!empty($registro['nombre_inquilino'])): ?>
                              <div class="d-flex justify-content-between text-primary mt-1 border-top pt-1"><span>👤 <strong>Inquilinos:</strong></span><span><?php echo $registro['nombre_inquilino']; ?></span></div>
                           <?php endif; ?>
                        </td>
                        <td>
                           <div class="d-flex justify-content-between small">
                              <span class="text-muted">Prop:</span>
                              <span class="<?php echo $clase_prop; ?>">
                                 RD$ <?php echo number_format($bal_prop, 2); ?>
                              </span>
                           </div>

                           <?php if (!empty($registro['nombre_inquilino'])): ?>
                              <div class="d-flex justify-content-between small">
                                 <span class="text-muted">Inq:</span>
                                 <span class="<?php echo $clase_inq; ?>">
                                    RD$ <?php echo number_format($bal_inq, 2); ?>
                                 </span>
                              </div>
                           <?php endif; ?>

                           <div class="d-flex justify-content-between border-top mt-1 pt-1 bg-light px-1">
                              <strong>TOTAL:</strong>
                              <span class="<?php echo $clase_total; ?>" style="font-size: 1.1em;">
                                 RD$ <?php echo number_format($bal_total_visual, 2); ?>
                              </span>
                           </div>
                        </td>
                        <td class="text-end">
                           <?php if ($total_pagado_mes > 0): ?>
                              <span class="text-primary fw-bold">RD$ <?php echo number_format($total_pagado_mes, 2); ?></span><br>
                              <small class="text-muted" style="font-size: 0.75rem;">(P: <?php echo number_format($registro['pagado_propietario'], 0); ?> | I: <?php echo number_format($registro['pagado_inquilino'], 0); ?>)</small>
                           <?php else: ?>
                              <span class="text-muted">-</span>
                           <?php endif; ?>
                        </td>
                        <td class="text-center small text-muted"><?php echo ($registro['fecha_ultimo_pago']) ? date('d/m/Y', strtotime($registro['fecha_ultimo_pago'])) : 'N/A'; ?></td>
                     </tr>
                  <?php } ?>
               </tbody>
               <tfoot class="table-dark">
                  <tr>
                     <td colspan="2" class="text-end fw-bold">TOTALES (<?php echo substr($mes_para_buscar, 0, 3); ?>):</td>
                     <td class="text-end fw-bold text-warning">Deuda Total: RD$ <?php echo number_format($sum_total_deuda_calle, 2); ?></td>
                     <td class="text-end fw-bold text-success">Recaudado: RD$ <?php echo number_format($sum_total_recaudado, 2); ?></td>
                     <td></td>
                  </tr>
               </tfoot>
            </table>
         </div>
      </div>
   </div>
</div>
<?php include("../../templates/footer.php"); ?>