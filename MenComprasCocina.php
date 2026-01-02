<!-- Carniceria, Frutas, Verduras,Lácteos,Accesorios -->
<!DOCTYPE html>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<html>
<head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

  <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
  <script type="text/javascript" src="https://fastly.jsdelivr.net/npm/echarts@5.4.2/dist/echarts.min.js"></script>
  
  <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
<style>
* {
  box-sizing: border-box;
  
}

body {
  margin: 0;
    background-color: rgba(241, 238, 237);
	font-family: Arial Narrow;
    font-size: 18px;
}

.navbar {
  overflow: hidden;
  background-color: rgba(241, 238, 237);
overflow: hidden;
font-family: Arial Narrow;
font-size: 18px;
}

.button {
  background-color: #4CAF50; /* Green */
  border: none;
  color: black;
  padding: 15px 32px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 16px;
  margin: 4px 2px;
  cursor: pointer;
}


.button2 {background-color: #008CBA;} /* Blue */
.button3 {background-color: #f44336;} /* Red */ 
.button4 {background-color: #e7e7e7; color: black;} /* Gray */ 
.button5 {background-color: rgba(241, 238, 237);} /* Black */

.navbar a {
  float: left;
  font-size: 16px;
  color: black;
  text-align: center;
  padding: 14px 16px;
  text-decoration: none;
  font-family: Arial Narrow;
    font-size: 18px
 
}

.dropdown {
  float: left;
  overflow: hidden;
}


.img-container {
        text-align: right;
      }


.dropdown .dropbtn {
  font-size: 16px;  
  border: none;
  outline: none;
  color: white;
  padding: 14px 16px;
  background-color: inherit;
  font: inherit;
  margin: 0;
}

.navbar a:hover, .dropdown:hover .dropbtn {
  background-color: gray;
  font-family: Arial Narrow;
    font-size: 18px
}

.dropdown-content {
  display: none;
  position: absolute;
  background-color: #f9f9f9;
  width: 100%;
  left: 0;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
  z-index: 1;
}

.dropdown-content .header {
  <!-- background: red; -->
  padding: 16px;
  background-color: rgba(241, 238, 237);
}

.dropdown:hover .dropdown-content {
  display: block;
}

/* Create three equal columns that floats next to each other */
.column {
  float: left;
  width: 33.33%;
  padding: 10px;
   background-color: rgba(241, 238, 237);
  height: 250px;
}

.column a {
  float: none;
   background-color: rgba(241, 238, 237);
  padding: 16px;
  text-decoration: none;
  display: block;
  text-align: left;
}

.column a:hover {
   background-color: rgba(241, 238, 237);
}

/* Clear floats after the columns */
.row:after {
  content: "";
  display: table;
  clear: both;
}

/* Responsive layout - makes the three columns stack on top of each other instead of next to each other */
@media screen and (max-width: 600px) {
  .column {
    width: 100%;
    height: auto;
  }
}
</style>
  <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<div class="navbar">
      <div class="header">
<p><a href="Demo_SistemaComedor.html">Menu principal</a></p>	  
        <h2>Menu de compras semanal</h2>
      </div>  <div class="img-container"> 
    <img src="Logo2.png" width="80" height="80"> </div>
		<!-- <table id="example" class="display" width="50%"></table>	   -->
      <div class="row">
        <div class="column">
          <h3>Carnes</h3>
          <a href="#">
  <textarea name="Carnes" id="Carnes" rows="5" cols="40"></textarea>
  <br><br>
		  </a>
  
  </div>
        <div class="column">
          <h3>Frutas</h3>
          <a href="#"><textarea name="Frutas"  id="Frutas" rows="5" cols="40"></textarea>
  <br><br></a>
        </div>
        <div class="column">
          <h3>Verduras</h3>
          <a href="#"><textarea name="Verduras" id="Verduras" rows="5" cols="40"></textarea>
  <br><br></a>
        </div>
	<div class="column">
          <h3>Lácteos</h3>
          <a href="#"><textarea name="Lacteos" id="Lacteos" rows="5" cols="40"></textarea>
  <br><br></a>
        </div>
		
			<div class="column">
          <h3>Accesorios</h3>
          <a href="#"><textarea name="Accesorios" id="Accesorios" rows="5" cols="40"></textarea>
  <br><br></a>
        </div>

<div>
<button type="button" class="button button5" id="addRow" onclick="Prueba();">Agregar</button>
</div>
 
  <table  class="column" id="example" class="display" width="50%"></table>
  <button onclick="ExportToExcel('xlsx')">Exporta tu tabla a excel</button>
	<div class="column">
	
<label for="html">Fecha</label>
<input type="date" placeholder="Ingresa tu Fecha"  id="Fecha" name="Fecha" required>
<button  type="submit" name="submit">Pedir</button>

<!-- Modificar style button -->

        </div>
		
      </div>
    </div>
  </div> 
</div>



<!-- <div style="padding:16px"> -->
  <!-- <h3>Responsive Mega Menu (Full-width dropdown in navbar)</h3> -->
  <!-- <p>Hover over the "Dropdown" link to see the mega menu.</p> -->
  <!-- <p>Resize the browser window to see the responsive effect.</p> -->
<!-- </div> -->

</body>
</html>
</form>



<script>
window.onload = function(){
  var fecha = new Date(); //Fecha actual
  var mes = fecha.getMonth()+1; //obteniendo mes
  var dia = fecha.getDate(); //obteniendo dia
  var ano = fecha.getFullYear(); //obteniendo año
  if(dia<10)
    dia='0'+dia; //agrega cero si el menor de 10
  if(mes<10)
    mes='0'+mes //agrega cero si el menor de 10
  document.getElementById('Fecha').value=ano+"-"+mes+"-"+dia;
}
 </script> 
 
 
 

<script>
var dataSet = [
];
 
$(document).ready(function () {
    $('#example').DataTable({
        columns: [
            { title: 'Departamento' },
            { title: 'Descripción' },
            { title: 'Cantidad' },
            { title: 'Fecha'},
        ],
    });
});







function Prueba() {

    var t = $('#example').DataTable();
    var counter = 0;
	
	
	
	

	var x = document.getElementById("Carnes").value;
	var x1 = document.getElementById("Frutas").value;
	var x2 = document.getElementById("Verduras").value;
	var x3 = document.getElementById("Lacteos").value;
	var x4 = document.getElementById("Accesorios").value;
	var x5 = document.getElementById("Fecha").value;
	
	
	
var xe = [];
var xe1 = [];
var xe2 = [];
var xe3 = [];
var xe4 = [];


///////////////////////////////////////////////////////// Array push javascript to php

var xep = [];
var xep1 = [];
var xe2p = [];
var xe3p = [];
var xe4p = [];


var xepx = [];

///////////////////////////////////////////////////////// Array push javascript to php



/////////////////////////////////Array carnes 
 xe  = x.split(',');
 xe1 = x1.split(',');
 xe2 = x2.split(',');
 xe3 = x3.split(',');
 xe4 = x4.split(',');
 
	
/////////////////////////////////Array carnes 
for (var i = 0; i < xe.length; i++) {
  t.row.add(['Carneceria',xe[i], '', x5]).draw(false); 
  //xep.push(['Carneceria',xe[i], '', x5]);
 ///// alert(i)
  
}

for (var i = 0; i < xe1.length; i++) {
  t.row.add(['Frutas',xe1[i], '', x5]).draw(false); 
}

for (var i = 0; i < xe2.length; i++) {
  t.row.add(['Verduras',xe2[i], '', x5]).draw(false); 
}


for (var i = 0; i < xe3.length; i++) {
  t.row.add(['Lácteos',xe3[i], '', x5]).draw(false); 
}

for (var i = 0; i < xe4.length; i++) {
  t.row.add(['Accesorios',xe4[i], '', x5]).draw(false); 
}
	
///////////////////////////////////////////////////////Prueba array 
        counter++;
				
    }
 
 




function ExportToExcel(type, fn, dl) {
       var elt = document.getElementById('example');
       var wb = XLSX.utils.table_to_book(elt, { sheet: "Orden de compra" });
       return dl ?
         XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }):
         XLSX.writeFile(wb, fn || ('Orden_Compra.' + (type || 'xlsx')));
    }


</script>




<?php

$mes = $mes1 = $mes2 = $mes3 = $mes4 = $mes5 = $prueba = $prueba1 = $prueba2 = $prueba3 = $prueba4  = $prueba5;




///////////////////////////////////////// Variables


if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $mes = test_input($_POST["Carnes"]);
  
  $mes1 = test_input($_POST["Frutas"]);
  $mes2 = test_input($_POST["Verduras"]);
  $mes3 = test_input($_POST["Lacteos"]);
  $mes4 = test_input($_POST["Accesorios"]);
  $mes5 = test_input($_POST["Fecha"]);
  
  /// $semana = test_input($_POST["Semana"]);
  /// $anio = test_input($_POST["Anio"]);
  
  

//////////////////////////////// Agregar data javascript a array PHP  


	
  ////Frutas
	 // $prueba1 = (explode(",",$mes1));
	///// Verduras
	// $prueba2 = (explode(",",$mes2));
	//////////// Látecos
	 // $prueba3 = (explode(",",$mes3));
	 //////////// Accesorios
	 // $prueba4 = (explode(",",$mes4));
	 //////////// Fecha
 
	 
//////////////////////////////// Agregar data javascript a array PHP


////////////////// Select

// $serverName = "LUISROMERO\SQLEXPRESS"; //serverName\instanceName
// $connectionInfo = array( "Database"=>"Comedor", "UID"=>"larome02", "PWD"=>"larome02","CharacterSet" => "UTF-8");
// $conn = sqlsrv_connect( $serverName, $connectionInfo);

$serverName = "DESAROLLO-BACRO\SQLEXPRESS"; //serverName\instanceName
$connectionInfo = array( "Database"=>"Comedor", "UID"=>"Larome03", "PWD"=>"Larome03","CharacterSet" => "UTF-8");
$conn = sqlsrv_connect( $serverName, $connectionInfo);

// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }




 //// Carne
 
    $prueba = (explode(",",$mes));
	$prueba5 = (explode(",",$mes5));
	
 $prueba1 = (explode(",",$mes1));
	///// Verduras
	$prueba2 = (explode(",",$mes2));
 //////////// Látecos
	 $prueba3 = (explode(",",$mes3));
 //////////// Accesorios
	 $prueba4 = (explode(",",$mes4));
	
	
/////////////Sample
$i = 0;

$i1 = 0;
$i2 = 0;
$i2 = 0;
$i4 = 0;

//////////////// while array

while($i < count($prueba))
{
$sql = "Insert into ListComprasCocina (Departamento,Descripcion,Cantidad,Fecha) Values('Carnes','$prueba[$i]','','$mes5')";
$stmt = sqlsrv_query( $conn, $sql );

	////echo $prueba[$i]."\n";
	$i++;
}
//////////////// while array


//////////////// while array Frutas
while($i1 < count($prueba1))
{
$sql1 = "Insert into ListComprasCocina (Departamento,Descripcion,Cantidad,Fecha) Values('Frutas','$prueba1[$i1]','','$mes5')";
$stmt1 = sqlsrv_query( $conn, $sql1);
	////echo $prueba1[$i]."\n";
	$i1++;
}

// //////////////// while array Verduras
while($i2 < count($prueba2))
{
$sql2 = "Insert into ListComprasCocina (Departamento,Descripcion,Cantidad,Fecha) Values('Verduras','$prueba2[$i2]','','$mes5')";
$stmt2 = sqlsrv_query( $conn, $sql2 );

	///echo $prueba2[$i2]."\n";
	$i2++;
}

// //////////////// while array Lácteos
while($i3 < count($prueba3))
{
$sql3 = "Insert into ListComprasCocina (Departamento,Descripcion,Cantidad,Fecha) Values('Lácteos','$prueba3[$i3]','','$mes5')";
$stmt3 = sqlsrv_query( $conn, $sql3 );

	////echo $prueba3[$i3]."\n";
	$i3++;
}


// //////////////// while array Accesorios
while($i4 < count($prueba4))
{
$sql4 = "Insert into ListComprasCocina (Departamento,Descripcion,Cantidad,Fecha) Values('Carnes','$prueba4[$i4]','','$mes5')";
$stmt4 = sqlsrv_query( $conn, $sql4 );

	////////////////////////////////////////////////echo $prueba4[$i4]."\n";
	$i4++;
}


//////////////// while array Accesorios




// if( $stmt === false) {
    // die( print_r( sqlsrv_errors(), true) );
// }



// while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
     // ///echo $row['Usuario'].", ".$row['Contrasena']."<br />";
// }


sqlsrv_free_stmt( $stmt);

}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}



?>