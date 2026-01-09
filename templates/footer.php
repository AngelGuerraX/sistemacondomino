</main>
<footer style="background-color: #000; color: white;">
  <span class="navbar-brand mb-0 h1" style="  color: white;"> ALPLAME 2025</span>
</footer>
<!-- Bootstrap JavaScript Libraries -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"
  integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous">
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.min.js"
  integrity="sha384-7VPbUDkoPSGFnVtYi0QogXtr74QeVeeIs99Qfg5YCF+TidwNdjvaKZX19NZ/e6oz" crossorigin="anonymous">
</script>


<script>
  var dropcondominios = document.getElementById("dropcondominios");
  var text_condominio = document.getElementById("text_condominio");

  dropcondominios.addEventListener("change", function() {
    var seleccion = dropcondominios.value;
    text_condominio.value = seleccion;
    nombre_condominio_online.value = seleccion;


    var seleccion2 = dropcondominios.value;
    text_condominio.value = seleccion;
    nombre_condominio_online.value = seleccion;

  });

  var myModal = document.getElementById('myModal')
  var myInput = document.getElementById('myInput')

  myModal.addEventListener('shown.bs.modal', function() {
    myInput.focus()
  })
</script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var cargo_bancario = document.getElementById('cargo_bancario');
    var balanceconciliado = document.getElementById('balanceconciliado');
    var balanceslibro = document.getElementById('balanceslibro');
    var transito = document.getElementById('transito');

    cargo_bancario.addEventListener('input', calcularRango);

    function calcularRango() {
      var balancesbanco = parseFloat(document.getElementById('balancesbanco').value);
      var cargo_bancarion = parseFloat(cargo_bancario.value);
      var transiton = parseFloat(transito.value);

      var rbc = balancesbanco - transiton;
      var rbsl = rbc + cargo_bancarion;

      balanceconciliado.value = rbc.toFixed(2);
      balanceslibro.value = rbsl.toFixed(2);
    }
  });
</script>



</script>

<script>
  hamburger = document.querySelector(".hamburger");
  nav = document.querySelector("nav");
  hamburger.onclick = function() {
    nav.classList.toggle("active");
  }
</script>

<script>
  $(document).ready(function() {
    $("#tabla_id").DataTable({
      "pageLength": 50,
      lengthMenu: [
        [5, 10, 30, 50],
        [5, 10, 30, 50]
      ],
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json"
      }

    })
  });
</script>


</body>

</html>