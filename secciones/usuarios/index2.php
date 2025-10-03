
<br>
<h4>Usuarios</h4>

<div class="card">
   <div class="card-header">
      <a name="" id="" class="btn btn-primary" href="crear.php" role="button">Agregar Usuarios</a>
   </div>
   <div class="card-body">
      
<div class="table-responsive-sm">
   
   <table class="table">
      <thead>
         <tr>
            <th scope="col">ID</th>
            <th scope="col">Nombre del usuario</th>
            <th scope="col">Contraseña</th>
            <th scope="col">Correo</th>
            <th scope="col">Acciones</th>
         </tr>
      </thead>
      <tbody>      
         <?php foreach ($lista_tbl_usuarios as $registro) { ?>
         <tr class="">
            <td scope="row"><?php echo $registro['id']?></td>
            <td><?php echo $registro['usuario']?></td>
            <td>******</td>
            <td><?php echo $registro['correo']?></td>
            <td>
            <a class="btn btn-success" href="editar.php?txID=<?php echo $registro['id']?>" role="button">Editar</a>
             |
            <a class="btn btn-danger" href="index.php?txID=<?php echo $registro['id']?>" role="button">Eliminar</a>
            </td>
         </tr>         
         <?php } ?>
      </tbody>
   </table>
</div>
   </div>
</div>