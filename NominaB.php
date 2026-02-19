<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css"></script>
<script src="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap.min.css"></script>

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
<script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
  
  <style>
body {
font-family: Arial Narrow;
 background-color: rgba(241, 238, 237);
   font-size:19px;
}

.img-container {
        text-align:Right;
 }
</style>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">  
</head>

<body >
<p><a href="Demo_SistemaComedor.html">Menú principal</a></p>  
<p><a href="http://192.168.100.95/Comedor/Desglosechecador.php">Checador</a></p>   
	    <div class="img-container"> <!-- Block parent element -->
    <img src="Logo2.png" width="75" height="50"> </div>
<form method="post" action="NominaB1.php">
   <label for="html">Mes inicio</label>
	<select name="Mes" id="Mes">
    <option value="Jan">Enero</option>
    <option value="Feb">Febrero</option>
	 <option value="Mar">Marzo</option>
	  <option value="Apr">Abril</option>
	   <option value="May">Mayo</option>
	    <option value="Jun">Junio</option>
		 <option value="Jul">Julio</option>
		  <option value="Aug">Agosto</option>
		   <option value="Sep">Septiembre</option>
		    <option value="Oct">Octubre</option>
			<option value="Nov">Noviembre</option>
<option value="Dec">Diciembre</option>
</select>
<br>
<br>
   <label for="html">Día inicio</label>
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
   <label for="html">Mes final</label>
	<select name="Mesfin" id="Mesfin">
    <option value="Jan">Enero</option>
    <option value="Feb">Febrero</option>
	 <option value="Mar">Marzo</option>
	  <option value="Apr">Abril</option>
	   <option value="May">Mayo</option>
	    <option value="Jun">Junio</option>
		 <option value="Jul">Julio</option>
		  <option value="Aug">Agosto</option>
		   <option value="Sep">Septiembre</option>
		    <option value="Oct">Octubre</option>
			<option value="Nov">Noviembre</option>
<option value="Dec">Diciembre</option>
</select>
<br>
<br>			
   <label for="html">Día final</label>
	<select name="Semana1" id="Semana1">
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
   <label for="html">Año_Inicio</label>
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
		<br>		
		<br>
		
		
		
<input type="submit" name="submit" value="Consultar" >		
		</form>		
		
			
<button onclick="ExportToExcel('xlsx')">Exporta tu tabla a excel</button>

<div id="main" style="height:45%"></div>
<table id="example" class="display" width="50%"></table>

</body>
</html>


<?php
//////onclick="AgregaCampos()"
$mes = $semana = $anio;

 /////echo $_POST["Mes"];

// if ( true === ( isset( $mesini ) ? $mesini : null ) ) {
    // echo "no tiene valor"
// }


if ($_SERVER["REQUEST_METHOD"] == "POST") {

///////////////////////////////////////////////////// Variables html
$mesini = test_input($_POST["Mes"]);
$semana = test_input($_POST["Semana"]);	
$semana1 = test_input($_POST["Semana1"]);
$year = test_input($_POST["Anio"]);
///////////////////////////////////////////////////// Variables html

//////////////////77echo $mesini;
// echo "<br>";
// echo $semana;
// echo "<br>";
// echo $semana1;
// echo "<br>";
// echo $semana1-$semana;
// echo "<br>";
// echo $year;
// echo "<br>";


   
require_once __DIR__ . '/config/database.php';
$conn = getComedorConnection();


//////////////////// Query tabla 
$sql = "DECLARE @columns nvarchar(MAX);
DECLARE @sql nvarchar(MAX)
 
 SELECT @columns = STUFF(
 (
SELECT 
   ',' + QUOTENAME(LTRIM(fecha_dia))
 FROM
   (Select distinct fecha_dia,Fecha,d,D1  from (
Select Id_Empleado,Fecha,fecha_dia,d,D1 ,sum(Total) as Total_Comidas from (
Select Id_Empleado,Fecha,Lunes as descripcion ,replace(rtrim(ltrim(left(DATEADD(day, 0, Fecha),12))),'  ',' ')  as fecha_dia,Count(Lunes) as Total,D=Month(Fecha),D1='1' from [dbo].[PedidosComida] 
Where  not Lunes = ''
Group  by Id_Empleado,Fecha,Lunes
union all
Select Id_Empleado,Fecha,Martes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 1, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1='2' from [dbo].[PedidosComida] 
Where  not Martes = ''
Group  by Id_Empleado,Fecha,Martes
union all
Select Id_Empleado,Fecha,Miercoles as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 2, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1='3' from [dbo].[PedidosComida] 
Where  not Miercoles = ''
Group  by Id_Empleado,Fecha,Miercoles
union all
Select Id_Empleado,Fecha,Jueves as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 3, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total ,D=Month(Fecha),D1='4' from [dbo].[PedidosComida] 
Where  not Jueves = ''
Group  by Id_Empleado,Fecha,Jueves
union all
Select Id_Empleado,Fecha,Viernes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 4, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total, D=Month(Fecha) ,D1='5'from [dbo].[PedidosComida] 
Where  not Viernes = ''
Group  by Id_Empleado,Fecha,Viernes ) as a
Group by Id_Empleado,Fecha,fecha_dia,d,D1 ) as a
WHERE fecha_dia like '$mesini%' and ltrim(rtrim(Substring(fecha_dia,Charindex(' ',fecha_dia),3))) <= $semana and  right(fecha_dia,4) = '$year'

   ) AS T
 ORDER BY
 Fecha,d,D1 
 FOR XML PATH('')
 ), 1, 1, ''); 

Set @sql = N'

Select * from (
Select * from (
Select Id_Empleado,Fecha,fecha_dia,d,D1 ,sum(Total) as Total_Comidas from (
Select Id_Empleado,Fecha,Lunes as descripcion ,replace(rtrim(ltrim(left(DATEADD(day, 0, Fecha),12))),''  '','' '')  as fecha_dia,Count(Lunes) as Total,D=Month(Fecha),D1=''1'' from [dbo].[PedidosComida] 
Where  not Lunes = ''''
Group  by Id_Empleado,Fecha,Lunes
union all
Select Id_Empleado,Fecha,Martes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 1, Fecha),12))),''  '','' '')  as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1=''2'' from [dbo].[PedidosComida] 
Where  not Martes = ''''
Group  by Id_Empleado,Fecha,Martes
union all
Select Id_Empleado,Fecha,Miercoles as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 2, Fecha),12))),''  '','' '')  as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1=''3'' from [dbo].[PedidosComida] 
Where  not Miercoles = ''''
Group  by Id_Empleado,Fecha,Miercoles
union all
Select Id_Empleado,Fecha,Jueves as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 3, Fecha),12))),''  '','' '')  as fecha_dia ,Count(*) as Total ,D=Month(Fecha),D1=''4'' from [dbo].[PedidosComida] 
Where  not Jueves = ''''
Group  by Id_Empleado,Fecha,Jueves
union all
Select Id_Empleado,Fecha,Viernes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 4, Fecha),12))),''  '','' '')  as fecha_dia ,Count(*) as Total, D=Month(Fecha) ,D1=''5'' from [dbo].[PedidosComida] 
Where  not Viernes = ''''
Group  by Id_Empleado,Fecha,Viernes ) as a
-- fecha_dia like
Group by Id_Empleado,Fecha,fecha_dia,d,D1 ) as a
-----Order by Fecha,d,D1
) AS TableToPivot 
PIVOT  
(  
  SUM(Total_comidas)  
  FOR fecha_dia  IN ('+@columns+')  
) AS PivotTable;'
EXEC sp_executesql @sql;";

//////////////////// Query tabla


////////////////// Prueba query dos


$sql1 = "DECLARE @columns nvarchar(MAX);
DECLARE @sql nvarchar(MAX)
 
 SELECT @columns = STUFF(
 (
SELECT 
   ',' + QUOTENAME(LTRIM(fecha_dia))
 FROM
   (Select distinct fecha_dia,Fecha,d,D1  from (
Select Id_Empleado,Fecha,fecha_dia,d,D1 ,sum(Total) as Total_Comidas from (
Select Id_Empleado,Fecha,Lunes as descripcion ,replace(rtrim(ltrim(left(DATEADD(day, 0, Fecha),12))),'  ',' ')  as fecha_dia,Count(Lunes) as Total,D=Month(Fecha),D1='1' from [dbo].[PedidosComida] 
Where  not Lunes = ''
Group  by Id_Empleado,Fecha,Lunes
union all
Select Id_Empleado,Fecha,Martes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 1, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1='2' from [dbo].[PedidosComida] 
Where  not Martes = ''
Group  by Id_Empleado,Fecha,Martes
union all
Select Id_Empleado,Fecha,Miercoles as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 2, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1='3' from [dbo].[PedidosComida] 
Where  not Miercoles = ''
Group  by Id_Empleado,Fecha,Miercoles
union all
Select Id_Empleado,Fecha,Jueves as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 3, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total ,D=Month(Fecha),D1='4' from [dbo].[PedidosComida] 
Where  not Jueves = ''
Group  by Id_Empleado,Fecha,Jueves
union all
Select Id_Empleado,Fecha,Viernes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 4, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total, D=Month(Fecha) ,D1='5'from [dbo].[PedidosComida] 
Where  not Viernes = ''
Group  by Id_Empleado,Fecha,Viernes ) as a
Group by Id_Empleado,Fecha,fecha_dia,d,D1 ) as a
WHERE fecha_dia like '$mesini%' and ltrim(rtrim(Substring(fecha_dia,Charindex(' ',fecha_dia),3))) > $semana and  right(fecha_dia,4) = '$year'
   ) AS T
 ORDER BY
 Fecha,d,D1 
 FOR XML PATH('')
 ), 1, 1, ''); 

Set @sql = N'

Select * from (
Select * from (
Select Id_Empleado,Fecha,fecha_dia,d,D1 ,sum(Total) as Total_Comidas from (
Select Id_Empleado,Fecha,Lunes as descripcion ,replace(rtrim(ltrim(left(DATEADD(day, 0, Fecha),12))),''  '','' '')  as fecha_dia,Count(Lunes) as Total,D=Month(Fecha),D1=''1'' from [dbo].[PedidosComida] 
Where  not Lunes = ''''
Group  by Id_Empleado,Fecha,Lunes
union all
Select Id_Empleado,Fecha,Martes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 1, Fecha),12))),''  '','' '')  as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1=''2'' from [dbo].[PedidosComida] 
Where  not Martes = ''''
Group  by Id_Empleado,Fecha,Martes
union all
Select Id_Empleado,Fecha,Miercoles as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 3, Fecha),12))),''  '','' '')  as fecha_dia  ,Count(*) as Total,D=Month(Fecha),D1=''3'' from [dbo].[PedidosComida] 
Where  not Miercoles = ''''
Group  by Id_Empleado,Fecha,Miercoles
union all
Select Id_Empleado,Fecha,Jueves as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 3, Fecha),12))),''  '','' '')  as fecha_dia ,Count(*) as Total ,D=Month(Fecha),D1=''4'' from [dbo].[PedidosComida] 
Where  not Jueves = ''''
Group  by Id_Empleado,Fecha,Jueves
union all
Select Id_Empleado,Fecha,Viernes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 4, Fecha),12))),''  '','' '')  as fecha_dia ,Count(*) as Total, D=Month(Fecha) ,D1=''5'' from [dbo].[PedidosComida] 
Where  not Viernes = ''''
Group  by Id_Empleado,Fecha,Viernes ) as a
-- fecha_dia like ''May%''
Group by Id_Empleado,Fecha,fecha_dia,d,D1 ) as a
-----Order by Fecha,d,D1
) AS TableToPivot 
PIVOT  
(  
  SUM(Total_comidas)  
  FOR fecha_dia  IN ('+@columns+')  
) AS PivotTable;'
EXEC sp_executesql @sql;";

/////////////////  Prueba Query dos


/////$sql1 = "";


$stmt = sqlsrv_query( $conn, $sql );
$stmt1 = sqlsrv_query( $conn, $sql1 );

//////////////////////////////////////////////////////////////////$stmt1 = sqlsrv_query( $conn, $sql1 );



///$stmt1 = sqlsrv_query( $conn, $sql1 );

////////////////////////////////////////////Ejecutar Querys
if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}


///// Array gráfica mensual
$array_tabla1 = [];
$array_tabla2 = [];
$array_tabla3 = [];
$array_tabla4 = [];
$array_tabla5 = [];
$array_tabla6 = [];
///// Array gráfica mensual




///////////////// array datos dias
$array_d1 = [];
$array_d2 = [];
$array_d3 = [];
$array_d4 = [];
$array_d5 = [];
$array_d6 = [];
$array_d7 = [];
$array_d8 = [];
$array_d9 = [];
$array_d10 = [];
$array_d11 = [];
$array_d12 = [];
$array_d13 = [];
$array_d14 = [];
$array_d15 = [];
$array_d16 = [];
$array_d17 = [];
$array_d18 = [];
$array_d19 = [];
$array_d20 = [];
$array_d21 = [];
$array_d22 = [];
$array_d23 = [];
$array_d24 = [];
$array_d25 = [];
$array_d26 = [];
$array_d27 = [];
$array_d28 = [];
$array_d29 = [];
$array_d30 = [];
$array_d31 = [];

///////////////// array datos dias

//////echo $semana1-$semana+1;


/////////////////////////////////// Variables PHP con numero
/////////////////////////////////// Variables PHP CON NUMERO

while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC))  
{  

array_push($array_d1,$row[0]);
array_push($array_d2,$row[1]);
array_push($array_d3,$row[2]);
array_push($array_d4,$row[3]);
array_push($array_d5,$row[4]);
array_push($array_d6,$row[5]);
array_push($array_d7,$row[6]);
array_push($array_d8,$row[7]);
array_push($array_d9,$row[8]);
array_push($array_d10,$row[9]);
array_push($array_d11,$row[10]);
array_push($array_d12,$row[11]);
array_push($array_d13,$row[12]);
array_push($array_d14,$row[13]);
array_push($array_d15,$row[14]);

///echo $row[0];
// echo $row[1];
// echo $row[2];
// echo $row[3];
// echo $row[4];
// echo $row[5];
// echo $row[6];
// echo $row[7];
// echo $row[8];
// echo $row[9];
// echo $row[10];
// echo $row[11];
// echo $row[12];
// echo $row[13];
// echo $row[14];
// echo $row[15];
// echo $row[16];
// echo $row[17];

}  




while( $row = sqlsrv_fetch_array( $stmt1, SQLSRV_FETCH_NUMERIC))  
{ 

////// echo $row[1].", ".$row[4]."<br />";
	  
array_push($array_d16,$row[0]);
array_push($array_d17,$row[1]);
array_push($array_d18,$row[2]);
array_push($array_d19,$row[3]);
array_push($array_d20,$row[4]);
array_push($array_d21,$row[5]);
array_push($array_d22,$row[6]);
array_push($array_d23,$row[7]);
array_push($array_d24,$row[8]);
array_push($array_d25,$row[9]);
array_push($array_d26,$row[10]);
array_push($array_d27,$row[11]);
array_push($array_d28,$row[12]);
array_push($array_d29,$row[13]);
array_push($array_d30,$row[14]);
array_push($array_d31,$row[15]);


// echo $row[0];
// echo $row[1];
// echo $row[2];
// echo $row[3];
// echo $row[4];
// echo $row[5];
// echo $row[6];
// echo $row[7];
// echo $row[8];
// echo $row[9];
// echo $row[10];
// echo $row[11];
// echo $row[12];
// echo $row[13];
// echo $row[17];
// echo $row[14];
// echo $row[15];
// echo $row[16];

}  






// $array_int = [];

// while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
// array_push($array_tabla1,$row['Id_Empleado']);
// array_push($array_tabla2,$row['Fecha']);
// array_push($array_tabla3,$row['fecha_dia']);
// array_push($array_tabla4,$row['d']);
// array_push($array_tabla5,$row['D1']);
// array_push($array_tabla6,$row['Total_Comidas']);

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


<script type="text/javascript">
/////////////////// Pasar varibles javascript
var dataT1 = <?php echo json_encode($array_tabla1);?>;
var dataT2 = <?php echo json_encode($array_tabla2);?>;
var dataT3 = <?php echo json_encode($array_tabla3);?>;
var dataT4 = <?php echo json_encode($array_tabla4);?>;
var dataT5 = <?php echo json_encode($array_tabla5);?>; 
var dataT6 = <?php echo json_encode($array_tabla6);?>;
 

////////////alert(dataJS1)
  var x; 
  var x1;
  var x2;
  var x4;
  ///var dataJS1;

 
 //////////////////////////////////////////////////////////// Function javascipt agregar columnas
 function  AgregaCampos() {
	 
///////////////////////////////////////////////////////////variabbles PHP

var dataJS1 = <?php echo json_encode($array_d1);?>;
var dataJS2 = <?php echo json_encode($array_d2);?>;
var dataJS3 = <?php echo json_encode($array_d3);?>;
var dataJS4 = <?php echo json_encode($array_d4);?>;
var dataJS5 = <?php echo json_encode($array_d5);?>;
var dataJS6 = <?php echo json_encode($array_d6);?>;
var dataJS7 = <?php echo json_encode($array_d7);?>;
var dataJS8 = <?php echo json_encode($array_d8);?>;
var dataJS9 = <?php echo json_encode($array_d9);?>;
var dataJS10 = <?php echo json_encode($array_d10);?>;
var dataJS11 = <?php echo json_encode($array_d11);?>;
var dataJS12 = <?php echo json_encode($array_d12);?>;
var dataJS13 = <?php echo json_encode($array_d13);?>;
var dataJS14 = <?php echo json_encode($array_d14);?>;
var dataJS15 = <?php echo json_encode($array_d15);?>;
var dataJS16 = <?php echo json_encode($array_d16);?>;
var dataJS17 = <?php echo json_encode($array_d17);?>;
var dataJS18 = <?php echo json_encode($array_d18);?>;
var dataJS19 = <?php echo json_encode($array_d19);?>;
var dataJS20 = <?php echo json_encode($array_d20);?>;
var dataJS21 = <?php echo json_encode($array_d21);?>;
var dataJS22 = <?php echo json_encode($array_d22);?>;
var dataJS23 = <?php echo json_encode($array_d23);?>;
var dataJS24 = <?php echo json_encode($array_d24);?>;
var dataJS25 = <?php echo json_encode($array_d25);?>;
var dataJS26 = <?php echo json_encode($array_d26);?>;
var dataJS27 = <?php echo json_encode($array_d27);?>;
var dataJS28 = <?php echo json_encode($array_d28);?>;
var dataJS29 = <?php echo json_encode($array_d29);?>;
var dataJS30 = <?php echo json_encode($array_d30);?>;
var dataJS31 = <?php echo json_encode($array_d31);?>;

///alert(dataJS17)


////////////////////////////////////////////////////////////variables PHP

	
	 
var table = $('#example').DataTable();
table.destroy();



  x = document.getElementById("Semana").value;  //// dia inicio
  x1 = document.getElementById("Semana1").value;  ////////// dia  final
  x2 = document.getElementById("Mes").value; //// año
  x4 = document.getElementById("Anio").value;


var dataSet = [];
///////////////////['721','Luis Antonio Romero López','1','1','1','1','1','1','1','','','','','8','','','','',''],



if (x1>15) {
    
for (var i = 0; i < dataJS21.length; i++) {
	
////alert(dataJS21[i])	
dataSet.push([dataJS21[i],'','','','','','','','','','','','','','','','','','']);
///t.row.add(dataJS21[i],'','','','','','','','','','','','','','','','','','').draw(false); 
}
}
if (x1<=15) {
    for (var i = 0; i < dataJS1.length; i++) {
dataSet.push(dataJS1[i],'','','','','','','','','','','','','','','','','','');
////t.row.add(dataJS21[i],'','','','','','','','','','','','','','','','','','').draw(false); 
		
}
}
// alert(dataSet)

// ////////////////////////////////// Variables html javascript
  x = document.getElementById("Semana").value;  //// dia inicio
  x1 = document.getElementById("Semana1").value;  ////////// dia  final
  x2 = document.getElementById("Mes").value; //// año
  x4 = document.getElementById("Anio").value;


var header1 = ["Id_Empleado","Nombre",x2+' '+'1'+' '+x4,x2+' '+'2'+' '+x4,x2+' '+'3'+' '+x4,x2+' '+'4'+' '+x4,x2+' '+'5'+' '+x4,x2+' '+'6'+' '+x4,x2+' '+'7'+' '+x4,x2+' '+'8'+' '+x4,x2+' '+'9'+' '+x4,x2+' '+'10'+' '+x4,x2+' '+'11'+' '+x4,x2+' '+'12'+' '+x4,x2+' '+'13'+' '+x4,x2+' '+'14'+' '+x4,x2+' '+'15'+' '+x4];

var header2 = ["Id_Empleado","Nombre",x2+' '+'16'+' '+x4,x2+' '+'17'+' '+x4,x2+' '+'18'+' '+x4,x2+' '+'19'+' '+x4,x2+' '+'20'+' '+x4,x2+' '+'21'+' '+x4,x2+' '+'22'+' '+x4,x2+' '+'23'+' '+x4,x2+' '+'23'+' '+x4,x2+' '+'24'+' '+x4,x2+' '+'25'+' '+x4,x2+' '+'26'+' '+x4,x2+' '+'27'+' '+x4,x2+' '+'28'+' '+x4,x2+' '+'29'+' '+x4,x2+' '+'30'+' '+x4,x2+' '+'31'+' '+x4];

///alert(header2)

var headerb =[];

///////////////////////////////////////////////////////////////////////////////////////////// ciclo encabezados


///////////////////////If encabezados			{ title: '' },

if (x1>15) {
    
for (var i = 0; i < header2.length; i++) {
	headerb.push({ title: header2[i] })
}
}
if (x1<=15) {
    for (var i = 0; i < header1.length; i++) {
	headerb.push({ title: header1[i] })
}
}

///////////////////////If encabezados


// for (var i = 0; i < header.length; i++) {
	// header2.push({ title: header[i] })
// }


	
  $('#example').DataTable({
        data: dataSet,
        columns:headerb,retrieve: true,destroy: true
    });



// $(document).ready(function () {
	
  // $('#example').DataTable({
        // data: dataSet,
        // columns:headerb,retrieve: true,destroy: true
    // });
// });







// // if ($.fn.DataTable.isDataTable('#example')) {
    // // $('#dataTable').DataTable().destroy();
// // }
// ///$('#example').empty();
	 
	 

  
  // //// var id_mes controles
  
  
  // //// var id_mes controles
  
  
// ////////////////////////////////// Variables html	 	 
// ///////////////////////////////////////variables PHP
// // dataT1
// // dataT2
// // dataT3
// // dataT4
// // dataT5
// // dataT6	 
// ////////////////////////////////////////variables PHP

// var descmes1;
// var descmes2;


// if ( x2 === '01') {	
// descmes1='Jan'
// }
// if ( x2 === '02') {	
// descmes1='Feb'
// }
// if ( x2 === '03') {	
// descmes1='Mar'
// }

// if ( x2 === '04') {	
// descmes1='Apr'
// }

// if ( x2 === '05') {	
// descmes1='May'
// }

// if ( x2 === '06') {	
// descmes1='Jun'
// }

// if ( x2 === '07') {	
// descmes1='Jul'
// }

// if ( x2 === '08') {	
// descmes1='Aug'
// }

// if ( x2 === '09') {	
// descmes1='Sep'
// }

// if ( x2 === '10') {	
// descmes1='Oct'
// }

// if ( x2 === '11') {	
// descmes1='Nov'
// }

// if ( x2 === '12') {	
// descmes1='Dec'
// }

// ///////////////////////////// Mes fin

// if ( x3 === '01') {	
// descmes2='Jan'
// }
// if ( x3 === '02') {	
// descmes2='Feb'
// }
// if ( x3 === '03') {	
// descmes2='Mar'
// }

// if ( x3 === '04') {	
// descmes2='Apr'
// }

// if ( x3 === '05') {	
// descmes2='May'
// }

// if ( x3 === '06') {	
// descmes2='Jun'
// }

// if ( x3 === '07') {	
// descmes2='Jul'
// }

// if ( x3 === '08') {	
// descmes2='Aug'
// }

// if ( x3 === '09') {	
// descmes2='Sep'
// }

// if ( x3 === '10') {	
// descmes2='Oct'
// }

// if ( x3 === '11') {	
// descmes2='Nov'
// }

// if ( x3 === '12') {	
// descmes2='Dec'
// }


// function onlyUnique(value, index, array) {
  // return array.indexOf(value) === index;
// }


// var unique = dataT3.filter(onlyUnique);


// ////////////////////alert('Fecha Inicio:'+descmes1+' '+(x*1)+' '+x4)


// /////////////////////alert('Fecha Final:'+descmes2+' '+(x1*1)+' '+x4)

// var Titulos = [];

// var numI;
// //////////////////////////////////////////////////For agregar data in array para encabezados

// for (var i = 0; i < unique.length; i++) {

// if ( (descmes1+' '+(x*1)+' '+x4) === unique[i].trim().replace("  ", " ")) {	
// numI= i
// ///////Titulos.push(unique[i])

// }
// }

 // for (var i = numI; i < unique.length; i++) {
  // if ((descmes2+' '+(x1*1)+' '+x4) === unique[i].trim().replace("  ", " ")) { break; }
  // Titulos.push(unique[i])
 // }


// ///////////////////////////// ejemplo generar headers json

// var header = Titulos


// var header2 = [];

// for (var i = 0; i < header.length; i++) {
	// header2.push({ title: header[i] })
// }


// ///////////////////////////////////////// Mandar datos a inicio de array
// header2.unshift({ title: 'Nombre' });
// ///////////////////////////////////////// Mandar datos a finales del array
// header2.push({ title: 'Total' })




///////////////////////////////////////////////////Array agregar datos en la tabla 05/06/2023
///alert(header)



// function onlyUnique(value, index, array) {
  // return array.indexOf(value) === index;
// }

// var uniqueID = dataT1.filter(onlyUnique);

// /////alert(uniqueID)


// for (var t = 0; t < dataT3.length; t++) {

// for (var r = 0; r < header.length; r++) {
	
// if ( dataT3[t]===header[r]) {	
// alert(header[r])
// }
 // }

  // }

////////////For agregas rows a tabla
// dataT1 ///7 Id_Empelado
// dataT2 /// Fecha
// dataT3 /// fecha_dia
// dataT4 /// d
// dataT5 /// D1
// dataT6 /// Total_Comidas 

// var PruebaTa = [];

 // for (var i = 0; i < 10; i++) {

// for (var x = 0; i < dataT3.length; x++) {
	// alert(unique[i])
// ///PruebaTa.push(unique[i])
 // }
 // }
 ////alert(PruebaTa) 
 
// function onlyUnique(value, index, array) {
  // return array.indexOf(value) === index;
// }

// var uniqueID = dataT1.filter(onlyUnique);
// alert(uniqueID)
 


////////////For agregas rows a tabla

///////////////////////////////////////////////////Array agregar datos en la tabla 05/06/2023

// var t = $('#example').DataTable();
  
    // var counter = 0;
	
	

// if (x1>15) {
    
// for (var i = 0; i < dataJS21.length; i++) {

// t.row.add(dataJS21[i],'','','','','','','','','','','','','','','','','','').draw(false); 
// }
// }
// if (x1<=15) {
    // for (var i = 0; i < dataJS1.length; i++) {


// t.row.add(dataJS21[i],'','','','','','','','','','','','','','','','','','').draw(false); 
		
// }
// }

}	 


// function AgCampos1() {
	
// }


 
 
var app = {};

var chartDom = document.getElementById('main');
var myChart = echarts.init(chartDom);
var option;

const posList = [
  'left',
  'right',
  'top',
  'bottom',
  'inside',
  'insideTop',
  'insideLeft',
  'insideRight',
  'insideBottom',
  'insideTopLeft',
  'insideTopRight',
  'insideBottomLeft',
  'insideBottomRight'
];
app.configParameters = {
  rotate: {
    min: -90,
    max: 90
  },
  align: {
    options: {
      left: 'left',
      center: 'center',
      right: 'right'
    }
  },
  verticalAlign: {
    options: {
      top: 'top',
      middle: 'middle',
      bottom: 'bottom'
    }
  },
  position: {
    options: posList.reduce(function (map, pos) {
      map[pos] = pos;
      return map;
    }, {})
  },
  distance: {
    min: 0,
    max: 100
  }
};
app.config = {
  rotate: 90,
  align: 'left',
  verticalAlign: 'middle',
  position: 'insideBottom',
  distance: 15,
  onChange: function () {
    const labelOption = {
      rotate: app.config.rotate,
      align: app.config.align,
      verticalAlign: app.config.verticalAlign,
      position: app.config.position,
      distance: app.config.distance
    };
    myChart.setOption({
      series: [
        {
          label: labelOption
        },
        {
          label: labelOption
        },
        {
          label: labelOption
        },
        {
          label: labelOption
        }
      ]
    });
  }
};
const labelOption = {
  show: true,
  position: app.config.position,
  distance: app.config.distance,
  align: app.config.align,
  verticalAlign: app.config.verticalAlign,
  rotate: app.config.rotate,
  formatter: '{c}  {name|{a}}',
  fontSize: 16,
  rich: {
    name: {}
  }
};
option = {
  tooltip: {
    trigger: 'axis',
    axisPointer: {
      type: 'shadow'
    }
  },
  legend: {
    data: ['Día 1', 'Día 2', 'Día 3', 'Día 4','Día 5']
  },
  toolbox: {
    show: true,
    orient: 'vertical',
    left: 'right',
    top: 'center',
    feature: {
      mark: { show: true },
      <!-- dataView: { show: true, readOnly: false }, -->
      <!-- magicType: { show: true, type: ['line', 'bar', 'stack'] }, -->
      restore: { show: true },
      saveAsImage: { show: true }
    }
  },
  xAxis: [
    {
      type: 'category',
      axisTick: { show: false },
      data: ['Semana 1', 'Semana 2', 'Semana 3', 'Semena 4']
    }
  ],
  yAxis: [
    {
      type: 'value'
    }
  ],
  series: [
    {
      name: 'Día 1',
      type: 'bar',
      barGap: 0,
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: [320, 332, 301, 334, 390]
    },
    {
      name: 'Día 2',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: [220, 182, 191, 234, 290]
    },
    {
      name: 'Día 3',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: [150, 232, 201, 154, 190]
    },
    {
      name: 'Día 4',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: [98, 77, 101, 99, 40]
    },
    {
      name: 'Día 5',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: [98, 77, 101, 99, 40]
    }
  ]
};

option && myChart.setOption(option);
</script>


<script>

function Borrar() {
var table = $('#example').DataTable();
 table.destroy();
}


// var dataSet = [
// ['ANLEX NIETO','1','1','1','1','1','1','1','1','','','','','8',''],
// ['MIGUEL ANGEL  LUNA  CASTRO','','','1','1','2','2','1','1','','','','','8','14'],
// ['ROSA CARMONA NAVA','','','','','0','0','0','2','','','','','2','78'],
// ['MARIA DEL CARMEN CASTILLO COLIN','','','','','1','0','0','','','','','','1',''],
// ['MIRIAM CIENEGA JASSO','','','1','1','0','0','0','2','','','','','4','22'],
// ['LUIS ALBERTO DIONISIO ESPINOZA','','','','1','0','2','1','1','','','','','5',''],
// ['REBECA GONZALEZ AVILES','','','','1','1','','1','','','','','','3','16'],
// ['EDGAR GUTIERREZ ESQUIVEL   (2 COMIDAS CONDONADAS)','','','','1','0','0','0','1','','','','','2',''],
// ['ENRIQUE MEDINA DE JESUS','','','','','1','0','0','','','','','','1',''],
// ['JOSE LUIS MEDINA DE JESUS','','','','','1','0','0','1','','','','','2',''],
// ['NORMA ANGELICA REYES FONSECA (SUBSIDIADO POR EL ING. MIGUEL)','','','2','','0','0','0','','','','','','2',''],
// ['MARTIN SANTOS SAAVEDRA PEREZ','','','','','0','2','0','','','','','','2',''],
// ['ISMAEL SOTO DEL HOYO','','','','','1','0','0','','','','','','1',''],
// ['DIANA VALDEZ AGUIRRE','','','1','','0','0','0','','','','','','1','60'],
// ['JAZMIN AVILES AGUIRRE','','','','','1','2','2','1','','','','','6','8'],
// ['DANIELA ALVAREZ REYES','','','','','1','1','1','2','','','','','5','8'],
// ['MARISOL BERNAL JUAREZ','','','','','1','','2','1','','','','','4','40'],
// ['ROJER MILTON PEREZ MIRANDA','','','','','1','1','1','1','','','','','4','40'],
// ['MANUEL ALEJANDRO GARCIA  TERAN','','','','','1','','2','2','','','','','5',''],
// ['OFELIA AGUIRRE FLORES','','','1','','0','2','0','2','','','','','5',''],
// ['ING. MIGUEL CRUZ BARRAGAN','','','','','0','0','0','','','','','','0',''],
// ['ANA VIANEY PINEDA JUAREZ ','','','','','0','0','0','','','','','','0',''],
// ['OSCAR DANIEL BARRIOS SANCHEZ','','','1','','1','0','0','','','','','','2',''],
// ['SERGIO ALEGRIA RITO','','','','','2','0','0','','','','','','2',''],
// ['LIC. MIGUEL CRUZ RODRIGUEZ','','','','','0','0','0','','','','','','0',''],
// ['LEONARDO CRUZ BARRAGAN','','','','','0','0','0','','','','','','0',''],
// ['THOMAS IVAN MUNDO','','','','','0','0','0','','','','','','0',''],
// ['ROSA ESPERANZA BARRIOS SANCHEZ','','','2','','2','2','1','2','2','','','','11',''],
// ['LORENA GONZALEZ','','','','','2','2','2','','','','','','6',''],
// ['MISAEL HERNANDEZ','','','','','2','2','2','2','','','','','8',''],
// ['ULISES RUIZ JIMENEZ','','','','','2','','1','','','','','','3',''],
// ['ROQUE LEON PEREIDA','','','','','2','2','2','2','','','','','8',''],
// ['ALEJANDRO CRUZ RODRIGUEZ','','','','','0','0','0','','','','','','0',''],
// ['ENRIQUE TORRES','','','','','2','2','2','2','','','','','8',''],
// ['BEATRIZ GONZALEZ CERNA','','','','','2','','1','1','','','','','4','12'],
// ['CONCEPCION ELIZABETH FABELA  QUINTERO  (SUBSIDIADO POR EL LIC. MIGUEL 50%)','','','','','0','0','0','','','','','','0','26'],
// ['EDUARDO RAMIREZ (SUBSIDIADO 100% LIC. MIGUEL)','','','','','0','0','0','','','','','','0',''],
// ['MIGUELANGEL RAMIREZ (SUBSISDIADO 100 LIC. MIGUEL)','','','','','0','0','0','','','','','','0',''],
// ['MARIANA CITLALI HERRERA HERNANDEZ','','','1','','1','0','0','2','','','','','4',''],
// ['LINNETT BENITEZ','','','','','2','2','2','2','','','','','8',''],
// ['ROGELIO NAVA DE JESUS','','','','','1','0','2','2','','','','','5',''],
// ['OSCAR FABILA GARDUÑO','','','','','0','0','0','','','','','','0',''],
// ['ULISES FLORES BUSTAMANTE','','','','','0','0','0','','','','','','0',''],
// ['HILDA REYES QUIROZ','0','0','0','0','','0','0','','','','','','0',''],
// ['ARMANDO CARBAJAL','','','','','1','1','0','','','','','','2',''],
// ['RUBEN JESUS PIÑA ','','','','','2','2','1','','','','','','5',''],
// ['HECTOR EMPORO','2','2','2','2','2','2','2','2','','','','','16',''],
// ['ALEJANDRO SANCHEZ VARGAS','','','','','0','0','0','','','','','','0',''],
// ['ALEXIS SALVADOR GARCIA PEREZ','','','1','','1','2','0','1','','','','','5',''],
// ['IVAN SANCHEZ MENDOZA','','','1','2','2','2','2','2','','','','','11',''],
// ['AURORA LIZBETH MEDINA MARTINEZ','','','','','1','1','2','1','','','','','5','24'],
// ['DULCE VIOLETA FLORES FIGUEROA','','','','','1','1','1','2','','','','','5',''],
// ['RICARDO ROSALES','','','','','1','0','0','','','','','','1',''],
// ['GUILLERMO PEÑA ADAME','','','','','0','0','0','','','','','','0',''],
// ['JOVITA ','','','','','1','1','0','','','','','','2',''],
// ['JOSELYN PICHARDO','','','','','0','0','0','','','','','','0',''],
// ['ANTONIO HERNANDEZ','','','1','','1','2','1','1','','','','','6',''],
// ['LUIS ENRIQUE MEDINA','','','','1','1','1','0','1','','','','','4',''],
// ['JOSE MANUEL YAXI','','','1','','2','1','1','','','','','','5',''],
// ['DAVID CASTILLO','','','','','2','2','2','','','','','','6','2'],
// ['JUANA DANIELA JASSO HUERTA','','','','','1','1','1','1','','','','','4',''],
// ['JOSE LUIS CRUZ','','','','1','0','0','0','','','','','','1',''],
// ['GABRIELA CRUZ','','','','','0','0','0','','','','','','0',''],
// ['JORGE VALDEZ','','','','','0','0','0','','','','','','0',''],
// ['BRENDA MORENO GUTIERREZ','','','','','1','0','0','','','','','','1','26'],
// ['FERNANDO BRITO','','','','','1','2','1','','','','','','4',''],
// ['LUIS ANTONIO','','','','','1','2','2','2','','','','','7',''],
// ['JESUS REYES DE JESUS','','','','','2','2','1','2','','','','','7','18'],
// ['BECARIOS','','','','','','','','','','','','','0',''],
// ['LUIS MIGUEL BECERRIL GARCIA ','','','','','2','1','1','','','','','','4',''],
// ['VICTOR HUGO JUAREZ GUADARRAMA','','','','','2','2','2','','','','','','6',''],
// ['ALISON HERNANDEZ HERNANDEZ','','','','','2','2','2','','','','','','6',''],
// ['MARLON GONZALEZ CAMPOS','','','','','2','2','2','','','','','','6',''],
// ['ALONDRA VAZQUEZ','','','2','','2','2','2','','','','','','8',''],
// ['EXTERNOS','','','','','','','','','','','','','0',''],
// ['SISTEMAS','','','2','','7','4','6','','','','','','19',''],
// ['C.P VICTOR','','','','','0','1','','','','','','','1',''],
// ['CONSULTORIA','','','','','0','','','','','','','','0',''],
// ['ESCUELA','','','','','0','','','','','','','','0',''],
// ];



$(document).ready(function () {
	////alert(<?php echo json_encode($array_d1);?>)
  $('#example').DataTable({
        data: '',
        columns: [
            { title: '' },
            { title: '' },
            { title: '' },
            { title: '' },
            { title: '' },
            { title: '' },
		    { title: '' },
			{ title: '' },
			{ title: '' },
			{ title: '' },
			{ title: '' },
			{ title: '' },
			{ title: '' },
		    { title: '' },
		    { title: '' },
			{ title: '' },
			{ title: '' },
			{ title: '' },
			{ title: '' },
			{ title: '' },			
			////{ title: 'COMPLEMENTOS' },
        ],retrieve: true,
    });
});


// function Borrar() {
// var table = $('#example').DataTable();
// table.destroy();
// }
// table.clear()



function ExportToExcel(type, fn, dl) {
       var elt = document.getElementById('example');
       var wb = XLSX.utils.table_to_book(elt, { sheet: "sheet1" });
       return dl ?
         XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }):
         XLSX.writeFile(wb, fn || ('MySheetName.' + (type || 'xlsx')));
    }


</script>
