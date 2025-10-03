
<h4>
   <div class="card">
        <div class="card-header">
            Seleccione el intervalo de Fechas
        </div>
        <div class="card-body">
            <form action="../../Reporte_camion.php" method="POST" enctype="multipart/form-data">

            <div class="mb-3">
                <label for="Desde" class="form-label">Desde:</label>
                <input type="date" class="form-control" name="Desde" id="Desde">
              </div>
              <div class="mb-3">
                <label for="Hasta" class="form-label">Hasta:</label>
                <input type="date" class="form-control" name="Hasta" id="Hasta">
              </div>

                <button type="sumit" class="btn btn-success">Generar</button>
              <a name="" id="" class="btn btn-danger" href="index.php" role="button">Cancelar</a>
            </form>
        </div>
        <div class="card-footer text-muted">
        </div>
    </div>

    <br><br>

    </h4>
 