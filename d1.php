
<?php


$pedido = $name = $email = $gender = $comment = $website = "";



require_once __DIR__ . '/config/database.php';
$conn = getComedorConnection();

// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }



//////////////////////////////////////////////////Prueba nuevo query
// $sql14 = "Select * from ConPed";

// $stmt14 = sqlsrv_query( $conn, $sql14 );

// while( $row = sqlsrv_fetch_array( $stmt14, SQLSRV_FETCH_ASSOC) ) {

// echo $row['Id_Empleado'];

// }





//////////////////////////////////////////////////Prueba nuevo query
$sql = "Select Fecha,
Sum(CLunes) as CLunes,Sum(DLunes) as DLunes,
Sum(CMartes) as CMartes,Sum(DMartes) as DMartes,
Sum(CMiercoles) as CMiercoles,Sum(DMiercoles) as DMiercoles,
Sum(CJueves) as CJueves,Sum(DJueves) as DJueves,
Sum(CViernes) as CViernes,Sum(DViernes) as DViernes
from (
SELECT Fecha,fecha_dia,ISNULL(CLunes, 0) as CLunes,ISNULL(DLunes, 0) as DLunes ,ISNULL(CMartes, 0) as CMartes ,
ISNULL(DMartes, 0) as DMartes,ISNULL(CMiercoles, 0)  as CMiercoles,ISNULL(DMiercoles, 0)  as DMiercoles,ISNULL(CJueves, 0) as CJueves,ISNULL(DJueves, 0)  as DJueves
,ISNULL(CViernes, 0) as CViernes,ISNULL(DViernes, 0) as DViernes
FROM  
(
Select * from (
Select * , left(descripcion,1)+D as Clave_Uni from  (
Select Fecha,Lunes as descripcion ,left(DATEADD(day, 0, Fecha),12)  as fecha_dia,Count(Lunes) as Total,D='Lunes' from [dbo].[PedidosComida] 
Where  not Lunes = ''
Group  by Fecha,Lunes
union all
Select Fecha,Martes as descripcion,left(DATEADD(day, 1, Fecha),12) as fecha_dia ,Count(*) as Total,D='Martes' from [dbo].[PedidosComida] 
Where  not Martes = ''
Group  by Fecha,Martes
union all
Select Fecha,Miercoles as descripcion,left(DATEADD(day, 2, Fecha),12) as fecha_dia ,Count(*) as Total,D='Miercoles' from [dbo].[PedidosComida] 
Where  not Miercoles = ''
Group  by Fecha,Miercoles
union all
Select Fecha,Jueves as descripcion,left(DATEADD(day, 3, Fecha),12) as fecha_dia ,Count(*) as Total ,D='Jueves' from [dbo].[PedidosComida] 
Where  not Jueves = ''
Group  by Fecha,Jueves
union all
Select Fecha,Viernes as descripcion,left(DATEADD(day, 4, Fecha),12) as fecha_dia ,Count(*) as Total, D='Viernes' from [dbo].[PedidosComida] 
Where  not Viernes = ''
Group  by Fecha,Viernes ) as n
) as f
) AS TableToPivot 
PIVOT  
(  
  SUM(Total)  
  FOR Clave_Uni  IN (CLunes,DLunes,CMartes,DMartes,CMiercoles,DMiercoles,CJueves,DJueves,CViernes,DViernes)  
) AS PivotTable ) as M
Group by Fecha";


$sql1 = "Select Fecha,c.Id_Empleado, Nombre, ISNULL(Lunes, '') as Lunes, ISNULL(Martes, '') as Martes, ISNULL(Miercoles, '') as Miercoles
,ISNULL(Jueves, '') as Jueves,ISNULL(Viernes, '')  as Viernes
from (Select Id_Empleado,Nombre,Area from [dbo].[Catalogo_EmpArea]) as a
left join
(Select * from (Select *  from [dbo].[PedidosComida] ) as b) as c
on a.Id_Empleado = c.Id_Empleado";



$stmt = sqlsrv_query( $conn, $sql );


$stmt1 = sqlsrv_query( $conn, $sql1);


if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}

/////////////////// Variables dias 


$array_tot1 = [];
$array_tot2 = [];
$array_tot3 = [];
$array_tot4 = [];


/////////////////////////// Variables arreglos querys nuevos.
$array_Q1 = [];
$array_Q2 = [];
$array_Q3 = [];
$array_Q4 = [];
$array_Q5 = [];
$array_Q6 = [];
$array_Q7 = [];
$array_Q8 = [];
$array_Q9 = [];
$array_Q10 = [];
$array_Q11 = [];

while( $row = sqlsrv_fetch_array($stCliente ,SQLSRV_FETCH_NUMERIC) ) {
echo $row[0];
}


while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {


/////////////////////////////////////////////////// Array nuevas variables
array_push($array_Q1,$row['Fecha']);

echo $row['Fecha'];
array_push($array_Q2,$row['CLunes']);
array_push($array_Q3,$row['DLunes']);
array_push($array_Q4,$row['CMartes']);
array_push($array_Q5,$row['DMartes']);
array_push($array_Q6,$row['CMiercoles']);
array_push($array_Q7,$row['DMiercoles']);
array_push($array_Q8,$row['CJueves']);
array_push($array_Q9,$row['DJueves']);
array_push($array_Q10,$row['CViernes']);
array_push($array_Q11,$row['DViernes']);

}

////////////////////////////////////// while query tabla

$FechaT = [];
$Id_EmpleadoT = [];
$NombreT = [];
$LunesT = [];
$MartesT = [];
$MiercolesT = [];
$JuevesT = [];
$ViernesT = [];


while( $row = sqlsrv_fetch_array( $stmt1, SQLSRV_FETCH_ASSOC) ) {
	array_push($FechaT,$row['Fecha']);
	array_push($Id_EmpleadoT,$row['Id_Empleado']);
	array_push($NombreT,$row['Nombre']);
	array_push($LunesT,$row['Lunes']);
	array_push($MartesT,$row['Martes']);
	array_push($MiercolesT,$row['Miercoles']);
	array_push($JuevesT,$row['Jueves']);
	array_push($ViernesT,$row['Viernes']);
	

}



sqlsrv_free_stmt( $stmt);
sqlsrv_free_stmt( $stmt1);



// if ($_SERVER["REQUEST_METHOD"] == "POST") {

// }

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

?>
