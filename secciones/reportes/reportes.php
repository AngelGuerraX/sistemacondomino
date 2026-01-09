<?php

$thetitle = "Reportes - Sistema de Condominio";
include("../../templates/header.php");
include("../../bd.php");

// Configuración de meses y años
$meses = [
   'Enero',
   'Febrero',
   'Marzo',
   'Abril',
   'Mayo',
   'Junio',
   'Julio',
   'Agosto',
   'Septiembre',
   'Octubre',
   'Noviembre',
   'Diciembre'
];

$anios = range(date('Y') - 1, date('Y') + 1);

// Obtener filtros o usar valores por defecto (mes y año actuales)
$mes_filtro = isset($_GET['mes']) ? $_GET['mes'] : $meses[date('n') - 1];
$anio_filtro = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
$quincena_filtro = isset($_GET['quincena']) ? $_GET['quincena'] : (date('j') <= 15 ? '15' : '30');

$idcondominio = $_SESSION['idcondominio'];
?>

<br>
<div class="card">
   <div class="card-header text-center bg-dark text-white">
      <h2>📊 CENTRO DE REPORTES</h2>
      <p class="mb-0">Genera y visualiza todos los reportes del sistema</p>
   </div>
   <div class="card-body">

      <!-- ============================================= -->
      <!-- FILTROS DE REPORTES -->
      <!-- ============================================= -->
      <div class="row mb-4">
         <div class="col-md-12">
            <div class="card">
               <div class="card-header bg-secondary text-white">
                  <h5 class="mb-0">🔍 Filtros de Reportes</h5>
                  <small class="text-warning">Los filtros se aplicarán a todos los reportes</small>
               </div>
               <div class="card-body">
                  <form action="" method="GET" class="row g-3" id="formFiltrosReportes">
                     <div class="col-md-4">
                        <label for="mes" class="form-label fw-bold">Mes:</label>
                        <select name="mes" id="mes" class="form-select" onchange="actualizarEnlaces()">
                           <?php foreach ($meses as $mes): ?>
                              <option value="<?php echo $mes; ?>" <?php echo $mes_filtro == $mes ? 'selected' : ''; ?>>
                                 <?php echo $mes; ?>
                              </option>
                           <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="col-md-3">
                        <label for="anio" class="form-label fw-bold">Año:</label>
                        <select name="anio" id="anio" class="form-select" onchange="actualizarEnlaces()">
                           <?php foreach ($anios as $anio_option): ?>
                              <option value="<?php echo $anio_option; ?>" <?php echo $anio_filtro == $anio_option ? 'selected' : ''; ?>>
                                 <?php echo $anio_option; ?>
                              </option>
                           <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="col-md-3">
                        <label for="quincena" class="form-label fw-bold">Quincena:</label>
                        <select name="quincena" id="quincena" class="form-select" onchange="actualizarEnlaces()">
                           <option value="15" <?php echo $quincena_filtro == '15' ? 'selected' : ''; ?>>Quincena 1-15</option>
                           <option value="30" <?php echo $quincena_filtro == '30' ? 'selected' : ''; ?>>Quincena 16-30</option>
                        </select>
                     </div>
                     <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                           <i class="fas fa-filter"></i> Aplicar
                        </button>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>

      <!-- ============================================= -->
      <!-- INFORMACIÓN DEL PERIODO SELECCIONADO -->
      <!-- ============================================= -->
      <div class="alert alert-info">
         <div class="row">
            <div class="col-md-8">
               <strong>
                  📅 Período seleccionado: <?php echo $mes_filtro . ' ' . $anio_filtro; ?> -
                  Quincena <?php echo $quincena_filtro == '15' ? '1-15' : '16-30'; ?>
               </strong>
            </div>
            <div class="col-md-4 text-end">
               <small class="text-muted">
                  <i class="fas fa-info-circle"></i>
                  Todos los reportes usarán estos filtros
               </small>
            </div>
         </div>
      </div>

      <!-- ============================================= -->
      <!-- TARJETAS DE ACCESO RÁPIDO -->
      <!-- ============================================= -->
      <div class="row mb-5">
         <!-- Nómina -->
         <div class="col-md-4 mb-4">
            <div class="card h-100 border-success">
               <div class="card-header bg-success text-white">
                  <h5 class="mb-0">👥 Nómina</h5>
               </div>
               <div class="card-body">
                  <p class="card-text">Reporte detallado de nómina de empleados con desglose de pagos y deducciones.</p>
                  <div class="mt-3">
                     <a href="#" class="btn btn-success btn-sm" id="btnNomina" target="_blank">
                        <i class="fas fa-file-pdf"></i> Generar PDF
                     </a>
                     <a href="#" class="btn btn-success btn-sm" id="btnNominaCheques" target="_blank">
                        <i class="fas fa-file-pdf"></i> Generar cheques PDF
                     </a>
                  </div>
               </div>
            </div>
         </div>

         <!-- Estado de Cuentas -->
         <div class="col-md-4 mb-4">
            <div class="card h-100 border-info">
               <div class="card-header bg-info text-white">
                  <h5 class="mb-0">💰 Estado de Cuentas</h5>
               </div>
               <div class="card-body">
                  <p class="card-text">Ir a Ingresos Seleccionar Apto y click en cuentas por cobrar.</p>
                  <div class="mt-3">
                     <a href="#" class="btn btn-info btn-sm text-white" id="btnEstadoCuentas" target="_blank">
                        Seleccionar Apto
                     </a>
                  </div>
               </div>
            </div>
         </div>

         <!-- Solicitud de Cheques -->
         <div class="col-md-4 mb-4">
            <div class="card h-100 border-warning">
               <div class="card-header bg-warning text-white">
                  <h5 class="mb-0">🏦 Solicitud de Cheques</h5>
               </div>
               <div class="card-body">
                  <p class="card-text">Reporte de solicitudes de cheques con detalle de gastos y montos.</p>
                  <div class="mt-3">
                     <a href="#" class="btn btn-warning btn-sm text-white" id="btnSolicitudCheques" target="_blank">
                        <i class="fas fa-file-pdf"></i> Generar PDF
                     </a>
                  </div>
               </div>
            </div>
         </div>

         <!-- Conciliación Bancaria -->
         <div class="col-md-4 mb-4">
            <div class="card h-100 border-primary">
               <div class="card-header bg-primary text-white">
                  <h5 class="mb-0">🏛️ Conciliación Bancaria</h5>
               </div>
               <div class="card-body">
                  <p class="card-text">Conciliación entre los registros contables y los estados bancarios.</p>
                  <div class="mt-3">
                     <a href="#" class="btn btn-primary btn-sm" id="btnConciliacion" target="_blank">
                        <i class="fas fa-file-pdf"></i> Generar PDF
                     </a>
                  </div>
               </div>
            </div>
         </div>

         <!-- Cuentas Por Cobrar -->
         <div class="col-md-4 mb-4">
            <div class="card h-100 border-danger">
               <div class="card-header bg-danger text-white">
                  <h5 class="mb-0">📈 Cuentas Por Cobrar</h5>
               </div>
               <div class="card-body">
                  <p class="card-text">Reporte de cuentas por cobrar con aging y estado de cobranza.</p>
                  <div class="mt-3">
                     <a href="#" class="btn btn-danger btn-sm" id="btnCuentasCobrar" target="_blank">
                        <i class="fas fa-file-pdf"></i> Generar PDF
                     </a>
                  </div>
               </div>
            </div>
         </div>

         <!-- Estado de Resultado -->
         <div class="col-md-4 mb-4">
            <div class="card h-100 border-dark">
               <div class="card-header bg-dark text-white">
                  <h5 class="mb-0">📊 Estado de Resultado</h5>
               </div>
               <div class="card-body">
                  <p class="card-text">Click en Calcular y guardar para ver PDF.</p>
                  <div class="mt-3">
                     <a href="#" class="btn btn-info btn-sm text-white" id="btnEstadoResultado" target="_blank">
                        Ir a Estado de Resultado
                     </a>
                  </div>
               </div>
            </div>
         </div>
      </div>

      <!-- ============================================= -->
      <!-- LISTA DETALLADA DE REPORTES -->
      <!-- ============================================= -->
      <div class="card">
         <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">📋 Lista Completa de Reportes</h5>
         </div>
         <div class="card-body">
            <div class="table-responsive">
               <table class="table table-striped">
                  <thead>
                     <tr>
                        <th width="50">#</th>
                        <th>Reporte</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Acciones</th>
                     </tr>
                  </thead>
                  <tbody>
                     <!-- Nómina -->
                     <tr>
                        <td>1</td>
                        <td>
                           <i class="fas fa-users text-success me-2"></i>
                           <strong>Nómina</strong>
                        </td>
                        <td>Reporte detallado de nómina de empleados</td>
                        <td><span class="badge bg-success">Personal</span></td>
                        <td>
                           <a href="#" class="btn btn-sm btn-outline-success" id="linkNomina" target="_blank">
                              <i class="fas fa-external-link-alt"></i> Abrir
                           </a>
                        </td>
                     </tr>

                     <!-- Estado de Cuentas -->
                     <tr>
                        <td>2</td>
                        <td>
                           <i class="fas fa-file-invoice-dollar text-info me-2"></i>
                           <strong>Estado de Cuentas</strong>
                        </td>
                        <td>Estado de cuentas de condóminos</td>
                        <td><span class="badge bg-info">Condominio</span></td>
                        <td>
                           <a href="#" class="btn btn-sm btn-outline-info" id="linkEstadoCuentas" target="_blank">
                              <i class="fas fa-external-link-alt"></i> Abrir
                           </a>
                        </td>
                     </tr>

                     <!-- Solicitud de Cheques -->
                     <tr>
                        <td>3</td>
                        <td>
                           <i class="fas fa-money-check text-warning me-2"></i>
                           <strong>Solicitud de Cheques</strong>
                        </td>
                        <td>Reporte de solicitudes de cheques</td>
                        <td><span class="badge bg-warning">Bancario</span></td>
                        <td>
                           <a href="#" class="btn btn-sm btn-outline-warning" id="linkSolicitudCheques" target="_blank">
                              <i class="fas fa-external-link-alt"></i> Abrir
                           </a>
                        </td>
                     </tr>

                     <!-- Conciliación Bancaria -->
                     <tr>
                        <td>4</td>
                        <td>
                           <i class="fas fa-balance-scale text-primary me-2"></i>
                           <strong>Conciliación Bancaria</strong>
                        </td>
                        <td>Conciliación bancaria</td>
                        <td><span class="badge bg-primary">Bancario</span></td>
                        <td>
                           <a href="#" class="btn btn-sm btn-outline-primary" id="linkConciliacion" target="_blank">
                              <i class="fas fa-external-link-alt"></i> Abrir
                           </a>
                        </td>
                     </tr>

                     <!-- Cuentas Por Cobrar -->
                     <tr>
                        <td>5</td>
                        <td>
                           <i class="fas fa-receipt text-danger me-2"></i>
                           <strong>Cuentas Por Cobrar</strong>
                        </td>
                        <td>Reporte de cuentas por cobrar</td>
                        <td><span class="badge bg-danger">Financiero</span></td>
                        <td>
                           <a href="#" class="btn btn-sm btn-outline-danger" id="linkCuentasCobrar" target="_blank">
                              <i class="fas fa-external-link-alt"></i> Abrir
                           </a>
                        </td>
                     </tr>

                     <!-- Estado de Resultado -->
                     <tr>
                        <td>6</td>
                        <td>
                           <i class="fas fa-chart-line text-dark me-2"></i>
                           <strong>Estado de Resultado</strong>
                        </td>
                        <td>Estado de resultados del período</td>
                        <td><span class="badge bg-dark">Financiero</span></td>
                        <td>
                           <a href="#" class="btn btn-sm btn-outline-dark" id="linkEstadoResultado" target="_blank">
                              <i class="fas fa-external-link-alt"></i> Abrir
                           </a>
                        </td>
                     </tr>
                  </tbody>
               </table>
            </div>
         </div>
      </div>

   </div>
</div>
<br>

<script>
   // Función para actualizar todos los enlaces con los filtros actuales
   function actualizarEnlaces() {
      const mes = document.getElementById('mes').value;
      const anio = document.getElementById('anio').value;
      const quincena = document.getElementById('quincena').value;

      const params = `?mes=${mes}&anio=${anio}&quincena=${quincena}`;

      // Actualizar enlaces de tarjetas
      document.getElementById('btnNomina').href = `<?php echo $url_base; ?>secciones/reportes/pdf_nomina.php${params}`;
      document.getElementById('btnEstadoCuentas').href = `<?php echo $url_base; ?>secciones/pagos/index.php`; // Cambiar por la ruta correcta
      document.getElementById('btnSolicitudCheques').href = `<?php echo $url_base; ?>secciones/reportes/pdf_solicitud_cheque.php${params}`;
      document.getElementById('btnConciliacion').href = `<?php echo $url_base; ?>secciones/reportes/pdf_conciliacion.php${params}`;
      document.getElementById('btnCuentasCobrar').href = `<?php echo $url_base; ?>secciones/reportes/pdf_cuentas_por_cobrar.php${params}`;
      document.getElementById('btnEstadoResultado').href = `<?php echo $url_base; ?>secciones/estado_de_resultado/index.php`;
      document.getElementById('btnNominaCheques').href = `<?php echo $url_base; ?>secciones/reportes/pdf_cheques_empleados.php${params}`;

      // Actualizar enlaces de la lista
      document.getElementById('linkNomina').href = `<?php echo $url_base; ?>secciones/reportes/pdf_nomina.php${params}`;
      document.getElementById('linkEstadoCuentas').href = `<?php echo $url_base; ?>secciones/pagos/index.php${params}`; // Cambiar por la ruta correcta
      document.getElementById('linkSolicitudCheques').href = `<?php echo $url_base; ?>secciones/reportes/pdf_solicitud_cheque.php${params}`;
      document.getElementById('linkChequesEmpleados').href = `<?php echo $url_base; ?>secciones/reportes/pdf_cheques_empleados.php${params}`;
      document.getElementById('linkConciliacion').href = `<?php echo $url_base; ?>secciones/reportes/pdf_conciliacion.php${params}`;
      document.getElementById('linkCuentasCobrar').href = `<?php echo $url_base; ?>secciones/reportes/pdf_cuentas_por_cobrar.php${params}`;
      document.getElementById('linkEstadoResultado').href = `<?php echo $url_base; ?>secciones/estado_de_resultado/index.php`;
   }

   // Función para mostrar información del período seleccionado
   function mostrarInfoPeriodo() {
      const mes = document.getElementById('mes').value;
      const anio = document.getElementById('anio').value;
      const quincena = document.getElementById('quincena').value;

      const periodoInfo = `📅 Período seleccionado: ${mes} ${anio} - Quincena ${quincena == '15' ? '1-15' : '16-30'}`;

      // Puedes mostrar esta información en un toast o alert si lo deseas
      console.log(periodoInfo);
   }

   // Inicializar enlaces al cargar la página
   document.addEventListener('DOMContentLoaded', function() {
      actualizarEnlaces();

      // Agregar evento a los filtros para actualizar enlaces en tiempo real
      document.getElementById('mes').addEventListener('change', actualizarEnlaces);
      document.getElementById('anio').addEventListener('change', actualizarEnlaces);
      document.getElementById('quincena').addEventListener('change', actualizarEnlaces);
   });

   // Función para abrir reporte con confirmación
   function abrirReporte(nombreReporte, url) {
      Swal.fire({
         title: `Generar ${nombreReporte}`,
         html: `¿Estás seguro de que quieres generar el reporte de <strong>${nombreReporte}</strong>?<br>
              <small class="text-muted">El reporte se abrirá en una nueva pestaña.</small>`,
         icon: 'question',
         showCancelButton: true,
         confirmButtonColor: '#3085d6',
         cancelButtonColor: '#d33',
         confirmButtonText: 'Sí, generar',
         cancelButtonText: 'Cancelar'
      }).then((result) => {
         if (result.isConfirmed) {
            window.open(url, '_blank');
         }
      });
   }
</script>

<?php include("../../templates/footer.php"); ?>