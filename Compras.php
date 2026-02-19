<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
<!DOCTYPE html>
<html>
<head>
  <!-- <title>Bootstrap Example</title> -->
  <!-- <meta charset="utf-8"> -->
  <!-- <meta name="viewport" content="width=device-width, initial-scale=1"> -->
  <!-- <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet"> -->
  <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script> -->
  
  <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
  <script type="text/javascript" src="https://fastly.jsdelivr.net/npm/echarts@5.4.2/dist/echarts.min.js"></script>
  
  <style>
body {
	font-family: Arial Narrow;
 background-color: rgba(241, 238, 237);
   font-size:21px;
}


.img-container {
        text-align:Right;
 }
 

</style>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body >
<p><a href="Demo_SistemaComedor.html">Menu principal</a></p>    <div class="img-container"> <!-- Block parent element -->
    <img src="Logo2.png" width="100" height="75"> </div
   <label for="html">Mes</label>
	<select name="Mes" id="Mes">
    <option value="01">Enero</option>
    <option value="02">Febrero</option>
	 <option value="03">Marzo</option>
	  <option value="04">Abril</option>
	   <option value="05">Mayo</option>
	    <option value="06">Junio</option>
		 <option value="07">Julio</option>
		  <option value="08">Agosto</option>
		   <option value="09">Septiembre</option>
		    <option value="10">Octubre</option>
			<option value="11">Noviembre</option>
<option value="12">Diciembre</option>
</select>
	<br>
			<br>

   <label for="html">Día</label>
	<select name="Semana" id="Semana">
	<option value="01">1</option>
	<option value="02">2</option>
	<option value="03">3</option>
	<option value="04">4</option>
	<option value="05">5</option>
	<option value="06">6</option>
	<option value="07">7</option>
	<option value="08">8</option>
	<option value="09">9</option>
	<option value="10">10</option>
	<option value="11">11</option>
	<option value="12">12</option>
	<option value="13">13</option>
	<option value="14">14</option>
	<option value="15">15</option>
	<option value="16">16</option>
	<option value="17">17</option>
    <option value="18">18</option>
    <option value="19">19</option>
    <option value="20">20</option>
    <option value="21">21</option>
    <option value="22">22</option>
    <option value="23">23</option>
	<option value="24">24</option>
	<option value="25">25</option>
	<option value="26">26</option>
	<option value="27">27</option>
	<option value="28">28</option>
	<option value="29">29</option>
	<option value="30">30</option>
	<option value="31">31</option>
	
	</select>
		<br>
			<br>
   <label for="html">Año</label>
	<select name="Anio" id="Anio">
	<option value="2023">2023</option>
	<option value="2024">2024</option>
	<option value="2025">2025</option>
	<option value="2026">2026</option>
	<option value="2027">2027</option>
	<option value="2028">2028</option>
	<option value="2029">2029</option>
	<option value="2030">2030</option>
	</select>
	<button onclick="Borrar();Tabla()">Buscar</button>
	<button onclick="ExportToExcel('xlsx')">Exporta tu tabla a excel</button>
		<br>		
	<br>

<p><a href="http://192.168.100.95/Comedor/Compras01.php">Capturar costos</a></p>
<div style="height:70%;width:70%;float: left;">
<table id="example" class="display" width="50%" ></table>
</div>
</body>
</html>

<?php


// $mes = $semana = $anio;

// $mes = test_input($_POST["Mes"]);
// $semana = test_input($_POST["Semana"]);

require_once __DIR__ . '/config/database.php';
$conn = getComedorConnection();


$sql = "Select * from [dbo].[ListComprasCocina]
order by fecha";


$stmt = sqlsrv_query( $conn, $sql );


if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}


///////////////////////////////// var array
$array_Camp1 = [];
$array_Camp2 = [];
$array_Camp3 = [];
$array_Camp4 = [];
///////////////////////////////// var array

while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
// echo $row['Departamento'];
array_push($array_Camp1,$row['Departamento']);
array_push($array_Camp2,$row['Descripcion']);
array_push($array_Camp3,$row['Cantidad']);
array_push($array_Camp4,$row['Fecha']);


}



sqlsrv_free_stmt( $stmt);


function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

?>

<script type="text/javascript">
//////////////////////// Declarar variable PHP


$(document).ready(function () {
    $('#example').DataTable({
        data: '',
        columns: [
            { title: 'Departamento' },
            { title: 'Descripción' },
            { title: 'Fecha' }
        ],
    });
});


 var datCamp1 = <?php echo json_encode($array_Camp1);?>;
 var datCamp2 = <?php echo json_encode($array_Camp2);?>;
 var datCamp3 = <?php echo json_encode($array_Camp3);?>;
 var datCamp4 = <?php echo json_encode($array_Camp4);?>;
 
//////////////////////// Declarar variables PHP


function Tabla() {

    var t = $('#example').DataTable();
    var counter = 0;
//////////////////////////////////////////////////////////// Variables controles html
  var x = document.getElementById("Semana").value;	
  var x1 = document.getElementById("Mes").value;
  var x2 = document.getElementById("Anio").value;
//////////////////////////////////////////////////////////// Variables controles html

////alert(x2+'-'+x1+'-'+x)

for (var i = 0; i < datCamp4.length; i++) {
if ( (x2+'-'+x1+'-'+x) === datCamp4[i]) {

//////dataSet.push([datCamp1[i],datCamp2[i],datCamp4[i]]);

t.row.add([datCamp1[i],datCamp2[i],datCamp4[i]]).draw(false); 

}
}



}



function ExportToExcel(type, fn, dl) {
       var elt = document.getElementById('example');
       var wb = XLSX.utils.table_to_book(elt, { sheet: "sheet1" });
       return dl ?
         XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }):
         XLSX.writeFile(wb, fn || ('OrdendeCompra.' + (type || 'xlsx')));
    }


function Borrar() {
var table = $('#example').DataTable();
 
table.clear()
}
</script>