<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- <title>Bootstrap Example</title> -->
  <!-- <meta charset="utf-8"> -->
  <!-- <meta name="viewport" content="width=device-width, initial-scale=1"> -->
  <!-- <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet"> -->
  <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script> -->
  
  <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
  <script type="text/javascript" src="https://fastly.jsdelivr.net/npm/echarts@5.4.2/dist/echarts.min.js"></script>
  <script src="https://unpkg.com/qrious@4.0.2/dist/qrious.js"></script>
  
  <style>
body {
  font-family: 'Lato', sans-serif;
   background-color: rgba(241, 238, 237);
   font-family: Arial Narrow;
   font-size: 28px;

}


.img-container {
        text-align: right;
      }
	  

</style>
  <meta charset="UTF-8">
</head>

<body >
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  
<p><a href="Demo_SistemaComedor.html">Menu principal</a></p>
<h2>Genera tu código QR para tu comida</h2>               
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
	 <label for="html">Comida</label>
	<select name="Comida" id="Comida">
	<option value="Desayuno">Desayuno</option>
	<option value="Comida">Comida</option>
	</select>
  <br>		
  <br>
  	<label for="html">Usuario</label>
<input type="Usuar" placeholder="Ingresa tu usuario"  id="Usuar" name="Usuar" required>
	<br>
	<br>
<label for="html">Contraseña</label>
<input type="password" placeholder="Ingresa tu contraseña"  id="contrase" name="contrase" required>
	<br>
	<br>
  <label for="fname">Nombre completo:</label>
  <input type="text" id="fname" name="fname"><br><br>
    <label for="fname">Id_Empleado:</label>
  <input type="text" id="Empleado" name="Empleado"><br><br>

    <button  type="submit onclick="insertgen();">Registrarme</button>
	
	   <a href="http://192.168.100.95/Comedor/GenerarQR.php">Generar QR</a>.
</body>
</html>
</form>
<?php



function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}




////// Código insertar 

$pedido1 = $pedido = $name = $email = $gender = $comment = $website = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name = test_input($_POST["Mes"]);
  $email = test_input($_POST["Semana"]);
  $website = test_input($_POST["Anio"]);
  $comment = test_input($_POST["Comida"]);
  $gender = test_input($_POST["Usuar"]);
  $pedido = test_input($_POST["contrase"]);
  $pedido1 = test_input($_POST["Empleado"]);
  
  
// echo "<h2>Datos enviados:</h2>";
// echo $name;
// echo "<br>";
// echo $email;
// echo "<br>";
// echo $website;
// echo "<br>";
// echo $comment;
// echo "<br>";
// echo $gender;
// echo "<br>";
// echo $pedido;


////////////////// Update

////////////////// Insert
require_once __DIR__ . '/config/database.php';
$conn = getComedorConnection();

// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }

// $dayofweek = date('w', strtotime('$website'+'-'+'$name'+'-'+'$email'));
// echo $dayofweek ;
//Our YYYY-MM-DD date string.
$date = "$website-$name-$email";

//Get the day of the week using PHP's date function.
$dayOfWeek = date("l", strtotime($date));
// echo $dayOfWeek; 

// $dayOfWeek1;
// $dayOfWeek2;
// $dayOfWeek3;
// $dayOfWeek4;
// $dayOfWeek5;


// $comment


///// Dias de la semana
// Monday
// Tuesday
// Wednesday
// Thursday
// Friday


$firstday = date('d', strtotime("this week"));
$firstday1 = date('m', strtotime("this week"));

// echo "First day of this week: ", $firstday;
// echo "First day of this week: ", $firstday1;

// echo $firstday;

if ($firstday > 26) {
///// 1
if ($dayOfWeek == 'Monday') {
  $dayOfWeek1= $comment;
} 

if ($dayOfWeek == 'Tuesday') {
  $dayOfWeek2= $comment;
  
  $email = $firstday;
  $name = $firstday1;
} 

if ($dayOfWeek == 'Wednesday') {
  $dayOfWeek3= $comment;
  $name = $firstday1;
   $email = $firstday;
} 

if ($dayOfWeek == 'Thursday') {
  $dayOfWeek4= $comment;
  $name = $firstday1;
   $email = $firstday;
} 

if ($dayOfWeek == 'Friday') {
  $dayOfWeek5 = $comment;
  $name = $firstday1;
  $email = $firstday;
} 

} else {///// 1
if ($dayOfWeek == 'Monday') {
  $dayOfWeek1= $comment;
} 

if ($dayOfWeek == 'Tuesday') {
  $dayOfWeek2= $comment;
  
  $email = $email -1 ;
} 

if ($dayOfWeek == 'Wednesday') {
  $dayOfWeek3= $comment;
   $email = $email -2 ;
} 

if ($dayOfWeek == 'Thursday') {
  $dayOfWeek4= $comment;
   $email = $email - 3 ;
} 

if ($dayOfWeek == 'Friday') {
  $dayOfWeek5 = $comment;
   $email = $email - 4 ;
} 
} 





//// Nuevo if 31


//// Nuevo if 31




////// email mayor que dos 

/// Insertar NombrID Procesos insertar datos.........

$sql = "Insert into [dbo].[PedidosComida](Id_Empleado,Nom_Pedido,Usuario,Contrasena,Fecha,Lunes,Martes,Miercoles,Jueves,Viernes,Costo) Values('$pedido1','','$gender','$pedido','$website'+'-'+'$name'+'-'+'$email','$dayOfWeek1','$dayOfWeek2','$dayOfWeek3','$dayOfWeek4','$dayOfWeek5','30')";

$stmt = sqlsrv_query( $conn, $sql );
if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}

sqlsrv_free_stmt( $stmt);


}



?>

<script type="text/javascript">
var datCamp1 = <?php echo json_encode($array_Camp1);?>;
var datCamp2 = <?php echo json_encode($array_Camp2);?>;
var datCamp3 = <?php echo json_encode($array_Camp3);?>;
var datCamp4 = <?php echo json_encode($array_Camp4);?>;
var datCamp5 = <?php echo json_encode($array_Camp5);?>;
var datCamp6 = <?php echo json_encode($array_Camp6);?>;
var datCamp7 = <?php echo json_encode($array_Camp7);?>;
var datCamp8 = <?php echo json_encode($array_Camp8);?>;


function insertgen() {
  var x = document.getElementById("Semana").value;	
  var x1 = document.getElementById("Mes").value;
  var x2 = document.getElementById("Anio").value;
  var x3 = document.getElementById("fname").value;
  var x4 = document.getElementById("Comida").value;
  var x5 = document.getElementById("Usuar").value;
  var x6 = document.getElementById("contrase").value;
  
 /////////////////// Fecha 
 /////alert( (x2+"-"+x1+"-"+x) )

}	
	

</script>