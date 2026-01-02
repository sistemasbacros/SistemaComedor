<?php
$arrayRecibido=json_decode($_POST["arrayDeValores"], true );
 
echo "Hemos recibido en el PHP un array de ".count($arrayRecibido)." elementos de luis123232";
foreach($arrayRecibido as $valor)
{
	echo "\n- ".$valor;

}


$serverName = "DESAROLLO-BACRO\SQLEXPRESS"; //serverName\instanceName
$connectionInfo = array("Database"=>"Comedor", "UID"=>"Larome03", "PWD"=>"Larome03","CharacterSet" => "UTF-8");
$conn = sqlsrv_connect( $serverName, $connectionInfo);

// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }

 $fechaActualA = date('Y-m-d');
$fechaActualA1 = date('H:i:s', time()+3600);	


$sql = "delete  cancelaciones where NOMBRE like '%romero%'";
$stmt = sqlsrv_query( $conn, $sql );

// if (isset($_GET["newpwd"])) {
    // $phpVar1 = $_GET["newpwd"];
// } else {
// }

// $phpVar1 =  str_replace("?","",$phpVar1);
// $name10 = $phpVar1; /// folio

// $sql1 = "Insert into Modificar(Usuario,Tabla,Fecha,Hora) Values('$arrayRecibido[10]','Elimino el contrato'+' '+'$arrayRecibido[2]','$fechaActualA','$fechaActualA1')";
// $stmt1 = sqlsrv_query( $conn, $sql1 );



// $sql = "Delete CatologodeContratos where Id='$arrayRecibido[0]'";
// $stmt = sqlsrv_query( $conn, $sql );
// if( $stmt === false) {
    // die( print_r( sqlsrv_errors(), true) );
// }

// // while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
      // // echo $row['Usuario'].", ".$row['Contrasena']."<br />";
// // }



?>