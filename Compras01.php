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
    <img src="Logo2.png" width="100" height="75"> </div>
	<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  
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
 <br><br>
  Carnes: <input type="text" name="Carnes">
  Frutas: <input type="text" name="Frutas">
  Verduras: <input type="text" name="Verduras">
  Lácteos: <input type="text" name="Lacteos">
  Accesorios: <input type="text" name="Accesorios">
  <br><br>
	<button type="submit" >Guardar</button>
		<br>		
	<br>



</body>
</html>
</form>
<?php

$pedido = $name = $email = $gender = $comment = $website = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $semana = test_input($_POST["Semana"]);
  $mes = test_input($_POST["Mes"]);
  $an = test_input($_POST["Anio"]);
  $carne = test_input($_POST["Carnes"]);
  $fruta = test_input($_POST["Frutas"]);
  $verdu = test_input($_POST["Verduras"]);
  $lacte = test_input($_POST["Lacteos"]);
  $acce = test_input($_POST["Accesorios"]);
  
  
  
  
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

$sql = "Insert into [dbo].[Compras_Costos] (Fecha,Carnes,Frutas,Verduras,Lacteos,Accesorios) Values('$semana-$mes-$an','$carne','$fruta','$verdu','$lacte','$acce')";
$stmt = sqlsrv_query( $conn, $sql );
if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}

// while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
      // echo $row['Usuario'].", ".$row['Contrasena']."<br />";
// }

sqlsrv_free_stmt( $stmt);

////////////////// Select


// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }

// $sql = "Select *  from [dbo].[Usuarios]";
// $stmt = sqlsrv_query( $conn, $sql );
// if( $stmt === false) {
    // die( print_r( sqlsrv_errors(), true) );
// }

// while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
      // echo $row['Usuario'].", ".$row['Contrasena']."<br />";
// }

// sqlsrv_free_stmt( $stmt);


}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
?>
