<?php
// Cargar configuración de base de datos desde .env
require_once __DIR__ . '/config/database.php';

$serverName = "DESAROLLO-BACRO\SQLEXPRESS"; //serverName\instanceName
$dbConfig = getComedorConfig(); $connectionInfo = $dbConfig['connectionOptions'];
$conn = sqlsrv_connect( $serverName, $connectionInfo);


// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }


////////////////////////// Validación Totales de cómidas
$sql1 ="Select distinct nombre from Entradas";

$stmt1 = sqlsrv_query( $conn, $sql1 );

$TotalPal="";


if (sqlsrv_has_rows($stmt1)) {
 while( $row = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_ASSOC) ) {




sqlsrv_free_stmt( $stmt);

$name1 = explode(":",$name);

// if ($name1[0]  == 'NOMBRE') {  
// $name2 = str_replace(" NSS", "",str_replace(" TEL DE EMERGENCIA","",str_replace("N.E", "",$name1[1])));
 
// }
// elseif  ($name1[0]  == 'ID'){

// $name2 = str_replace("AREA", "",$name1[2]);
	// }
	
	// else { $name2 = str_replace("NSS", "",$name1[0]);}

// // echo $name2;

// // Cambiar Nombre


// echo '<div  id="demo" style=" font-size: 30px;;;color:red">Te encuentras registrado:'.$name2.' </div>';

// } else { 
// echo "No se encuentran lugares disponibles";}

// }


function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
  }
?>