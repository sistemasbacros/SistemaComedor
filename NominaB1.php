
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
<p><a href="NominaB.php">Buscar</a></p>
			
<button onclick="ExportToExcel('xlsx')">Exporta tu tabla a excel</button>  	    <div class="img-container"> <!-- Block parent element -->
    <img src="Logo2.png" width="80" height="55"> </div>

<div id="main" style="height:45%"></div>
<table id="example" class="display" width="50%"></table>

</body>
</html>





<?php

// echo $_POST["Mes"];
// echo $_POST["Semana"];
// echo $_POST["Semana1"];
// echo $_POST["Anio"];

////////////////// Variables PHP
$mesini = $_POST["Mes"];
$mesfin = $_POST["Mesfin"];
$semana = $_POST["Semana"];	
$semana1 = $_POST["Semana1"];
$year = $_POST["Anio"];


require_once __DIR__ . '/config/database.php';
$conn = getComedorConnection();

////  querys


// //////////////////// Query tabla 
// $sql = "DECLARE @columns nvarchar(MAX);
// DECLARE @sql nvarchar(MAX)
 
 // SELECT @columns = STUFF(
 // (
// SELECT 
   // ',' + QUOTENAME(LTRIM(fecha_dia))
 // FROM
   // (Select distinct fecha_dia,Fecha,d,D1  from (
// Select Id_Empleado,Fecha,fecha_dia,d,D1 ,sum(Total) as Total_Comidas from (
// Select Id_Empleado,Fecha,Lunes as descripcion ,replace(rtrim(ltrim(left(DATEADD(day, 0, Fecha),12))),'  ',' ')  as fecha_dia,Count(Lunes) as Total,D=Month(Fecha),D1='1' from [dbo].[PedidosComida] 
// Where  not Lunes = ''
// Group  by Id_Empleado,Fecha,Lunes
// union all
// Select Id_Empleado,Fecha,Martes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 1, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1='2' from [dbo].[PedidosComida] 
// Where  not Martes = ''
// Group  by Id_Empleado,Fecha,Martes
// union all
// Select Id_Empleado,Fecha,Miercoles as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 2, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1='3' from [dbo].[PedidosComida] 
// Where  not Miercoles = ''
// Group  by Id_Empleado,Fecha,Miercoles
// union all
// Select Id_Empleado,Fecha,Jueves as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 3, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total ,D=Month(Fecha),D1='4' from [dbo].[PedidosComida] 
// Where  not Jueves = ''
// Group  by Id_Empleado,Fecha,Jueves
// union all
// Select Id_Empleado,Fecha,Viernes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 4, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total, D=Month(Fecha) ,D1='5'from [dbo].[PedidosComida] 
// Where  not Viernes = ''
// Group  by Id_Empleado,Fecha,Viernes ) as a
// Group by Id_Empleado,Fecha,fecha_dia,d,D1 ) as a
// WHERE fecha_dia like '$mesini%' and ltrim(rtrim(Substring(fecha_dia,Charindex(' ',fecha_dia),3))) <= $semana1 and  right(fecha_dia,4) = '$year'

   // ) AS T
 // ORDER BY
 // Fecha,d,D1 
 // FOR XML PATH('')
 // ), 1, 1, ''); 

// Set @sql = N'

// Select * from (
// Select * from (
// Select Id_Empleado,Fecha,fecha_dia,d,D1 ,sum(Total) as Total_Comidas from (
// Select Id_Empleado,Fecha,Lunes as descripcion ,replace(rtrim(ltrim(left(DATEADD(day, 0, Fecha),12))),''  '','' '')  as fecha_dia,Count(Lunes) as Total,D=Month(Fecha),D1=''1'' from [dbo].[PedidosComida] 
// Where  not Lunes = ''''
// Group  by Id_Empleado,Fecha,Lunes
// union all
// Select Id_Empleado,Fecha,Martes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 1, Fecha),12))),''  '','' '')  as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1=''2'' from [dbo].[PedidosComida] 
// Where  not Martes = ''''
// Group  by Id_Empleado,Fecha,Martes
// union all
// Select Id_Empleado,Fecha,Miercoles as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 2, Fecha),12))),''  '','' '')  as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1=''3'' from [dbo].[PedidosComida] 
// Where  not Miercoles = ''''
// Group  by Id_Empleado,Fecha,Miercoles
// union all
// Select Id_Empleado,Fecha,Jueves as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 3, Fecha),12))),''  '','' '')  as fecha_dia ,Count(*) as Total ,D=Month(Fecha),D1=''4'' from [dbo].[PedidosComida] 
// Where  not Jueves = ''''
// Group  by Id_Empleado,Fecha,Jueves
// union all
// Select Id_Empleado,Fecha,Viernes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 4, Fecha),12))),''  '','' '')  as fecha_dia ,Count(*) as Total, D=Month(Fecha) ,D1=''5'' from [dbo].[PedidosComida] 
// Where  not Viernes = ''''
// Group  by Id_Empleado,Fecha,Viernes ) as a
// -- fecha_dia like
// Group by Id_Empleado,Fecha,fecha_dia,d,D1 ) as a
// -----Order by Fecha,d,D1
// ) AS TableToPivot 
// PIVOT  
// (  
  // SUM(Total_comidas)  
  // FOR fecha_dia  IN ('+@columns+')  
// ) AS PivotTable;'
// EXEC sp_executesql @sql;";



// $sql1 = "DECLARE @columns nvarchar(MAX);
// DECLARE @sql nvarchar(MAX)
 
 // SELECT @columns = STUFF(
 // (
// SELECT 
   // ',' + QUOTENAME(LTRIM(fecha_dia))
 // FROM
   // (Select distinct fecha_dia,Fecha,d,D1  from (
// Select Id_Empleado,Fecha,fecha_dia,d,D1 ,sum(Total) as Total_Comidas from (
// Select Id_Empleado,Fecha,Lunes as descripcion ,replace(rtrim(ltrim(left(DATEADD(day, 0, Fecha),12))),'  ',' ')  as fecha_dia,Count(Lunes) as Total,D=Month(Fecha),D1='1' from [dbo].[PedidosComida] 
// Where  not Lunes = ''
// Group  by Id_Empleado,Fecha,Lunes
// union all
// Select Id_Empleado,Fecha,Martes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 1, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1='2' from [dbo].[PedidosComida] 
// Where  not Martes = ''
// Group  by Id_Empleado,Fecha,Martes
// union all
// Select Id_Empleado,Fecha,Miercoles as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 2, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1='3' from [dbo].[PedidosComida] 
// Where  not Miercoles = ''
// Group  by Id_Empleado,Fecha,Miercoles
// union all
// Select Id_Empleado,Fecha,Jueves as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 3, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total ,D=Month(Fecha),D1='4' from [dbo].[PedidosComida] 
// Where  not Jueves = ''
// Group  by Id_Empleado,Fecha,Jueves
// union all
// Select Id_Empleado,Fecha,Viernes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 4, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total, D=Month(Fecha) ,D1='5'from [dbo].[PedidosComida] 
// Where  not Viernes = ''
// Group  by Id_Empleado,Fecha,Viernes ) as a
// Group by Id_Empleado,Fecha,fecha_dia,d,D1 ) as a
// WHERE fecha_dia like '$mesini%' and ltrim(rtrim(Substring(fecha_dia,Charindex(' ',fecha_dia),3))) > $semana and  right(fecha_dia,4) = '$year'
   // ) AS T
 // ORDER BY
 // Fecha,d,D1 
 // FOR XML PATH('')
 // ), 1, 1, ''); 

// Set @sql = N'

// Select * from (
// Select * from (
// Select Id_Empleado,Fecha,fecha_dia,d,D1 ,sum(Total) as Total_Comidas from (
// Select Id_Empleado,Fecha,Lunes as descripcion ,replace(rtrim(ltrim(left(DATEADD(day, 0, Fecha),12))),''  '','' '')  as fecha_dia,Count(Lunes) as Total,D=Month(Fecha),D1=''1'' from [dbo].[PedidosComida] 
// Where  not Lunes = ''''
// Group  by Id_Empleado,Fecha,Lunes
// union all
// Select Id_Empleado,Fecha,Martes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 1, Fecha),12))),''  '','' '')  as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1=''2'' from [dbo].[PedidosComida] 
// Where  not Martes = ''''
// Group  by Id_Empleado,Fecha,Martes
// union all
// Select Id_Empleado,Fecha,Miercoles as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 3, Fecha),12))),''  '','' '')  as fecha_dia  ,Count(*) as Total,D=Month(Fecha),D1=''3'' from [dbo].[PedidosComida] 
// Where  not Miercoles = ''''
// Group  by Id_Empleado,Fecha,Miercoles
// union all
// Select Id_Empleado,Fecha,Jueves as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 3, Fecha),12))),''  '','' '')  as fecha_dia ,Count(*) as Total ,D=Month(Fecha),D1=''4'' from [dbo].[PedidosComida] 
// Where  not Jueves = ''''
// Group  by Id_Empleado,Fecha,Jueves
// union all
// Select Id_Empleado,Fecha,Viernes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 4, Fecha),12))),''  '','' '')  as fecha_dia ,Count(*) as Total, D=Month(Fecha) ,D1=''5'' from [dbo].[PedidosComida] 
// Where  not Viernes = ''''
// Group  by Id_Empleado,Fecha,Viernes ) as a
// -- fecha_dia like ''May%''
// Group by Id_Empleado,Fecha,fecha_dia,d,D1 ) as a
// -----Order by Fecha,d,D1
// ) AS TableToPivot 
// PIVOT  
// (  
  // SUM(Total_comidas)  
  // FOR fecha_dia  IN ('+@columns+')  
// ) AS PivotTable;'
// EXEC sp_executesql @sql;";

/////////////////  Prueba Query dos

///////////////////////////////////////////// Prueba query quincena 1-15
$sql2 = "Select * from (
Select * from (
Select Id_Empleado,Nombre,Fecha,fecha_dia,d,D1 ,sum(Total) as Total_Comidas from (
Select n1.Id_Empleado,n1.Fecha,n1.descripcion,n1.fecha_dia,n1.Total,n1.D,n1.D1,n2.Nombre
from (
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
Select Id_Empleado,Fecha,Viernes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 4, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total, D=Month(Fecha) ,D1='5' from [dbo].[PedidosComida] 
Where  not Viernes = ''
Group  by Id_Empleado,Fecha,Viernes  ) as n1
inner join 
(Select * from [dbo].[Catalogo_EmpArea]) as n2
on n1.Id_Empleado = n2.Id_Empleado
) as a
-- fecha_dia like
Group by Id_Empleado,Nombre,Fecha,fecha_dia,d,D1 ) as a
WHERE fecha_dia like '$mesini%' and ltrim(rtrim(Substring(fecha_dia,Charindex(' ',fecha_dia),3))) <= $semana1 and  right(fecha_dia,4) = '$year'
) AS TableToPivot 
PIVOT  
(  
  SUM(Total_comidas)  
  FOR fecha_dia  IN ([$mesini 1 $year],[$mesini 2 $year],[$mesini 3 $year],[$mesini 4 $year],[$mesini 5 $year],[$mesini 6 $year],[$mesini 7 $year],[$mesini 8 $year],[$mesini 9 $year],[$mesini 10 $year],[$mesini 11 $year],[$mesini 12 $year],[$mesini 13 $year],[$mesini 14 $year],[$mesini 15 $year])  
) AS PivotTable";


///////////////////////////////////////////// Prueba query quincena 1-15



///////////////////////////////////////////// Prueba query quincena 15-30
$sql3 = " Select * from (
Select * from (
Select Id_Empleado,Nombre,Fecha,fecha_dia,d,D1 ,sum(Total) as Total_Comidas from (
Select n1.Id_Empleado,n1.Fecha,n1.descripcion,n1.fecha_dia,n1.Total,n1.D,n1.D1,n2.Nombre
from (
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
Select Id_Empleado,Fecha,Viernes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 4, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total, D=Month(Fecha) ,D1='5' from [dbo].[PedidosComida] 
Where  not Viernes = ''
Group  by Id_Empleado,Fecha,Viernes  ) as n1
inner join 
(Select * from [dbo].[Catalogo_EmpArea]) as n2
on n1.Id_Empleado = n2.Id_Empleado ) as a
-- fecha_dia like
Group by Id_Empleado,Nombre,Fecha,fecha_dia,d,D1 ) as a
WHERE fecha_dia like '$mesini%' and ltrim(rtrim(Substring(fecha_dia,Charindex(' ',fecha_dia),3))) > $semana and  right(fecha_dia,4) = '$year'
) AS TableToPivot 
PIVOT  
(  
  SUM(Total_comidas)  
  FOR fecha_dia  IN ([$mesini 16 $year],[$mesini 17 $year],[$mesini 18 $year],[$mesini 19 $year],[$mesini 20 $year],[$mesini 21 $year],[$mesini 22 $year],[$mesini 23 $year],[$mesini 24 $year],[$mesini 25 $year],[$mesini 26 $year],[$mesini 27 $year],[$mesini 28 $year],[$mesini 29 $year],[$mesini 30 $year],[$mesini 31 $year])  
) AS PivotTable";


///////////////////////////////////////////// Prueba query quincena 15-30
// echo $mesini; 
// echo $semana;
// echo $year;


$valmes;

if ($mesini == 'Jan') {
  $valmes = '01';
}

if ($mesini == 'Feb') {
  $valmes = '02';
}

if ($mesini == 'Mar') {
  $valmes = '03';
}

if ($mesini == 'Apr') {
  $valmes = '04';
}

if ($mesini == 'May') {
  $valmes = '05';
}

if ($mesini == 'Jun') {
  $valmes = '06';
}

if ($mesini == 'Jul') {
  $valmes = '07';
}

if ($mesini == 'Aug') {
  $valmes = '08';
}

if ($mesini == 'Sep') {
  $valmes = '09';
}

if ($mesini == 'Oct') {
  $valmes = '10';
}

if ($mesini == 'Nov') {
  $valmes = '11';
}

if ($mesini == 'Dec') {
  $valmes = '12';
}

////////////////////////////////////////////////////  Mesfin ifssss

$valmes1;

if ($mesfin == 'Jan') {
  $valmes1 = '01';
}

if ($mesfin == 'Feb') {
  $valmes1 = '02';
}

if ($mesfin == 'Mar') {
  $valmes1 = '03';
}

if ($mesfin == 'Apr') {
  $valmes1 = '04';
}

if ($mesfin == 'May') {
  $valmes1 = '05';
}

if ($mesfin == 'Jun') {
  $valmes1 = '06';
}

if ($mesfin == 'Jul') {
  $valmes1 = '07';
}

if ($mesfin == 'Aug') {
  $valmes1 = '08';
}

if ($mesfin == 'Sep') {
  $valmes1 = '09';
}

if ($mesfin == 'Oct') {
  $valmes1 = '10';
}

if ($mesfin == 'Nov') {
  $valmes1 = '11';
}

if ($mesfin == 'Dec') {
  $valmes1 = '12';
}

//////////////

/////////// Variable día 
//////  /////////// Variable día  $valmes

/////$semana

// if ($semana >=  5) {
// for ($x = $semana; $x <= $semana1; $x++) {
  // echo "The number is: $x <br>";
// }
// }


////////////////////////// "Dos casos cuando inician el 7 y acaba los veintes" y "cuando inicia 20 y acaba en 7"

//////////////////////// Caso 1
  $valt;
  $valt1;
  
   $valt134;
  
  /////////////////////////// Variables AND OR
  
  
    /////////////////////////// Variables AND OR 
	$int_cast = (int)$semana;
	$int_cast1 = (int)$semana1;
	
// echo (int)$semana;
// // echo (int)$semana1;

// ECHO $semana;

if ($semana <= 17) {

	
	   $valt134= 'AND';
	  

for ($x = $int_cast ; $x <= $semana1; $x++) {
	
	// echo $x ;
// echo "The number is: $x <br>";
  $valt = $valt."[$mesini $x $year]" ;
}

$valt1 = str_replace("][","],[","$valt");

///echo $valt1;
  
}


////echo  $valt1;
/////////// Variable día 

//////////////////////// Caso 1
// echo $semana 

$valt4;
if ($semana >=  18 ) {

	   $valt134 = 'OR';
for ($x = $semana; $x <= 31; $x++) {
// echo "The number is: $x <br>";
  $valt4 = $valt4."[$mesini $x $year]" ;
}

for ($x = 1; $x <= $semana1 ; $x++) {
// // echo "The number is: $x <br>";
  $valt4 = $valt4."[$mesfin $x $year]" ;
 }
$valt1 = str_replace("][","],[","$valt4");
 /////echo  $valt5;
}
//////////////////////// Caso 2


/////////////////////////////////////////////  Sumar dias 


////// SUMA 
////echo $semana1-$semana;
///////////////////////////////////////////////// Asignar valores 27/07/2023
// if (($semana1-$semana) >=  15 ) {
	// echo $semana1-$semana;
 // }
////// SUMA 


// echo 


////////////////////////////////////////////////////  Mesfin ifssss


// $valt = "[$mesini 16 $year],[$mesini 17 $year],[$mesini 18 $year],[$mesini 19 $year],[$mesini 20 $year],[$mesini 21 $year],[$mesini 22 $year],[$mesini 23 $year],[$mesini 24 $year],[$mesini 25 $year],[$mesini 26 $year],[$mesini 27 $year],[$mesini 28 $year],[$mesini 29 $year],[$mesini 30 $year],[$mesini 31 $year]";

// echo $valt1;

// echo $semana;

// echo $semana1;

// echo mesini;
// echo mesfin;

$sql4p = "
Select *  From (Select * from (
Select * from (
Select Id_Empleado,Nombre,fecha_dia ,sum(Total) as Total_Comidas from (
Select n1.Id_Empleado,n1.Fecha,n1.descripcion,n1.fecha_dia,n1.Total,n1.D,n1.D1,n2.Nombre
from (
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
Select Id_Empleado,Fecha,Viernes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 4, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total, D=Month(Fecha) ,D1='5' from [dbo].[PedidosComida] 
Where  not Viernes = ''
Group  by Id_Empleado,Fecha,Viernes  ) as n1
left join 
(Select * from [dbo].[Catalogo_EmpArea]) as n2
on n1.Id_Empleado = n2.Id_Empleado ) as a
-- fecha_dia like
WHERE (fecha_dia like '%$mesini%' and day(fecha_dia)  >= $semana and  year(fecha_dia)  = '$year')
or (fecha_dia like '%$mesfin%' and day(fecha_dia)  <= $semana and  year(fecha_dia) = '$year')
Group by Id_Empleado,Nombre,fecha_dia) as a
) AS TableToPivot 
PIVOT  
(  
  SUM(Total_comidas)  
  FOR fecha_dia  IN ($valt1)  
 
                              
) AS PivotTable ) as T1
FULL OUTER JOIN
(
Select replace(IdEmpleado,'d','') as IdEmpleado,Nombre,Count(*) as Total  from  (
Select DATENAME(WEEKDAY,CONVERT(date,Hora_Entrada,103)) as Dia_Semana,  IdEmpleado= case 
when  Nombre like '%N.E%'then ltrim(rtrim(replace(substring(Nombre,CHARINDEX('N.E',Nombre),CHARINDEX('DEPARTAMENTO',Nombre)-CHARINDEX('N.E',Nombre) ) ,'N.E: ','')))
when  nombre like '%NE:%' AND nombre like '%DEPARTAMENTO%'then ltrim(rtrim(Replace(substring(Nombre,CHARINDEX('NE:',Nombre),CHARINDEX('DEPARTAMENTO',Nombre)-CHARINDEX('NE:',Nombre)),'NE:','')))
when  nombre like '%NE:%' AND nombre like '%AREA%'then ltrim(rtrim(Replace(substring(Nombre,CHARINDEX('NE:',Nombre),CHARINDEX('AREA',Nombre)-CHARINDEX('NE:',Nombre)),'NE: ','')))
when  nombre like '%NE%' AND nombre like '%AREA%'then ltrim(rtrim(Replace(substring(Nombre,CHARINDEX('NE',Nombre),CHARINDEX('AREA',Nombre)-CHARINDEX('NE: ',Nombre)),'NE: ','')))
end ,
Nombre= case 
WHEN  Nombre like 'NOMBRE:%' and Nombre like '%N.E%' then ltrim(rtrim(Replace (left(Nombre,ChaRindex('N.E',Nombre)-1),'NOMBRE:',''))) 
WHEN Nombre like 'NOMBRE:%' and  Nombre like '%NE%' then ltrim(rtrim(Replace (left(Nombre,ChaRindex('NE',Nombre)-1),'NOMBRE:',''))) 
WHEN Nombre like 'NOMBRE:%' and  Nombre like '%NE%' then ltrim(rtrim(Replace (left(Nombre,ChaRindex('NE',Nombre)-1),'NOMBRE:',''))) 
WHEN Nombre like 'Id%' then        ltrim(rtrim(Replace(Substring(Nombre,Charindex('NOMBRE:',Nombre) ,Charindex('AREA:',Nombre ) - Charindex('NOMBRE:',Nombre )),'NOMBRE:','')))
WHEN Nombre like 'NOMBRE :%' and  Nombre like '%NE%' then ltrim(rtrim(Replace (left(Nombre,ChaRindex('NE',Nombre)-1),'NOMBRE :',''))) 
WHEN  Nombre like 'NOMBRE:%'  and  Nombre not like '%NE%' or Nombre like 'NOMBRE:%'  and  Nombre not like '%N.E%' then Replace (left(Nombre,ChaRindex('NSS',Nombre)),'NOMBRE:','')
end,Hora_Entrada,Fecha, Tipo_Comida=  case when Fecha > '13:00:00' then 'Comida' else 'Desayuno' end,
Id_Semana=datepart(week,CONVERT(date,Hora_Entrada,103))
- datepart(week, dateadd(dd,-day(CONVERT(date,Hora_Entrada,103))+1,CONVERT(date,Hora_Entrada,103)))
+1 
from  [dbo].[Entradas]
where Not ltrim(rtrim(Replace(left(substring(Nombre,CHARINDEX(':',Nombre)+1,LEN(Nombre)-CHARINDEX(':',Nombre)+1),charindex(':',substring(Nombre,CHARINDEX(':',Nombre)+1,LEN(Nombre)-CHARINDEX(':',Nombre)+1))),'N.E:',''))) = ''
) as b
where (Substring(Hora_Entrada,4,2)  = '$valmes' and  right(Hora_Entrada,4) = '$year' and left(Hora_Entrada,2) >= '$semana')
and (Substring(Hora_Entrada,4,2)  = '$valmes1' and  right(Hora_Entrada,4) = '$year' and left(Hora_Entrada,2) <= '$semana1')
Group  by  replace(IdEmpleado,'d',''),Nombre) AS T2
on T1.Id_Empleado = T2.IdEmpleado";

///////////////////////////////////////////// Prueba query quincena 15-30

$stmt4p = sqlsrv_query( $conn,$sql4p);

$i = 0;

$array1530p1 = [];
$array1530p2 = [];
$array1530p3 = [];
$array1530p4 = [];
$array1530p5 = [];
$array1530p6 = [];
$array1530p7 = [];
$array1530p8 = [];
$array1530p9 = [];
$array1530p10 = [];
$array1530p11 = [];
$array1530p12 = [];
$array1530p13 = [];
$array1530p14 = [];
$array1530p15 = [];
$array1530p16 = [];
$array1530p17 = [];
$array1530p18 = [];
$array1530p19 = [];
$array1530p20 = [];
$array1530p21 = [];


//////////////////////////////////////////////////////////////////////// Variables extra
$array1530p22 = [];
$array1530p23 = [];
$array1530p24 = [];
$array1530p25 = [];

// if (($semana1-$semana) >=  15 ) {
	// echo $semana1-$semana;
 // }
 
///////////////////////////////////////// IF


///// 22 variables

// $options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
// $stmt4p1 = sqlsrv_query( $conn, $sql4p , $params, $options );

// $row_count = sqlsrv_num_rows($stmt4p1);

// echo $row_count;

// echo ($semana1-$semana);

/// Hola


$date = new DateTime('last day of this month');
$numDaysOfCurrentMonth = $date->format('d');


// echo (($numDaysOfCurrentMonth-$semana)+$semana1)+1;

// echo  ($semana1-$semana)+1 ;
// echo (($numDaysOfCurrentMonth-$semana)+$semana1)+1;
 

// echo $numDaysOfCurrentMonth;

if( $stmt4p === false) {
    die( print_r( sqlsrv_errors(), true) );
}




while( $row = sqlsrv_fetch_array($stmt4p,  SQLSRV_FETCH_NUMERIC) ) {

// echo $numDaysOfCurrentMonth;


   if (($semana1-$semana)+1 ==  13 or  (($numDaysOfCurrentMonth-$semana)+$semana1)+1 ==  13 ) {
   ////echo $semana1-$semana;
array_push($array1530p1,$row[0]);
array_push($array1530p2,$row[1]);
array_push($array1530p3,$row[2]);
array_push($array1530p4,$row[3]);
array_push($array1530p5,$row[4]);
array_push($array1530p6,$row[5]);
array_push($array1530p7,$row[6]);
array_push($array1530p8,$row[7]);
array_push($array1530p9,$row[8]);
array_push($array1530p10,$row[9]);
array_push($array1530p11,$row[10]);
array_push($array1530p12,$row[11]);
array_push($array1530p13,$row[12]);
array_push($array1530p14,$row[13]);
array_push($array1530p15,$row[14]);
array_push($array1530p16,$row[15]);
array_push($array1530p17,$row[16]);
array_push($array1530p18,$row[17]);
array_push($array1530p19,$row[18]);
array_push($array1530p20,$row[19]);

// echo " Entro al 13";

   }
 
   if (($semana1-$semana)+1 ==  14 or  (($numDaysOfCurrentMonth-$semana)+$semana1)+1 ==  14 ) {
array_push($array1530p1,$row[0]);
array_push($array1530p2,$row[1]);
array_push($array1530p3,$row[2]);
array_push($array1530p4,$row[3]);
array_push($array1530p5,$row[4]);
array_push($array1530p6,$row[5]);
array_push($array1530p7,$row[6]);
array_push($array1530p8,$row[7]);
array_push($array1530p9,$row[8]);
array_push($array1530p10,$row[9]);
array_push($array1530p11,$row[10]);
array_push($array1530p12,$row[11]);
array_push($array1530p13,$row[12]);
array_push($array1530p14,$row[13]);
array_push($array1530p15,$row[14]);
array_push($array1530p16,$row[15]);
array_push($array1530p17,$row[16]);
array_push($array1530p18,$row[17]);
array_push($array1530p19,$row[18]);
array_push($array1530p20,$row[19]);

// echo " Entro al 14";

   }
 
 if (($semana1-$semana)+1 ==  15  or  (($numDaysOfCurrentMonth-$semana)+$semana1)+1 ==  15) {
array_push($array1530p1,$row[0]);
array_push($array1530p2,$row[1]);
array_push($array1530p3,$row[2]);
array_push($array1530p4,$row[3]);
array_push($array1530p5,$row[4]);
array_push($array1530p6,$row[5]);
array_push($array1530p7,$row[6]);
array_push($array1530p8,$row[7]);
array_push($array1530p9,$row[8]);
array_push($array1530p10,$row[9]);
array_push($array1530p11,$row[10]);
array_push($array1530p12,$row[11]);
array_push($array1530p13,$row[12]);
array_push($array1530p14,$row[13]);
array_push($array1530p15,$row[14]);
array_push($array1530p16,$row[15]);
array_push($array1530p17,$row[16]);
array_push($array1530p18,$row[17]);
array_push($array1530p19,$row[18]);
array_push($array1530p20,$row[19]);
array_push($array1530p21,$row[20]);
array_push($array1530p22,$row[21]);


// echo " Entro al 15";
   }

  if (($semana1-$semana)+1 ==  16  or  (($numDaysOfCurrentMonth-$semana)+$semana1)+1 ==  16 ) {
array_push($array1530p1,$row[0]);
array_push($array1530p2,$row[1]);
array_push($array1530p3,$row[2]);
array_push($array1530p4,$row[3]);
array_push($array1530p5,$row[4]);
array_push($array1530p6,$row[5]);
array_push($array1530p7,$row[6]);
array_push($array1530p8,$row[7]);
array_push($array1530p9,$row[8]);
array_push($array1530p10,$row[9]);
array_push($array1530p11,$row[10]);
array_push($array1530p12,$row[11]);
array_push($array1530p13,$row[12]);
array_push($array1530p14,$row[13]);
array_push($array1530p15,$row[14]);
array_push($array1530p16,$row[15]);
array_push($array1530p17,$row[16]);
array_push($array1530p18,$row[17]);
array_push($array1530p19,$row[18]);
array_push($array1530p20,$row[19]);
array_push($array1530p21,$row[20]);

 // echo " Entro al 16";

   }	
 
   if (($semana1-$semana)+1 ==  17  or  (($numDaysOfCurrentMonth-$semana)+$semana1)+1 ==  17 ) {
array_push($array1530p1,$row[0]);
array_push($array1530p2,$row[1]);
array_push($array1530p3,$row[2]);
array_push($array1530p4,$row[3]);
array_push($array1530p5,$row[4]);
array_push($array1530p6,$row[5]);
array_push($array1530p7,$row[6]);
array_push($array1530p8,$row[7]);
array_push($array1530p9,$row[8]);
array_push($array1530p10,$row[9]);
array_push($array1530p11,$row[10]);
array_push($array1530p12,$row[11]);
array_push($array1530p13,$row[12]);
array_push($array1530p14,$row[13]);
array_push($array1530p15,$row[14]);
array_push($array1530p16,$row[15]);
array_push($array1530p17,$row[16]);
array_push($array1530p18,$row[17]);
array_push($array1530p19,$row[18]);
array_push($array1530p20,$row[19]);
array_push($array1530p21,$row[20]);
array_push($array1530p22,$row[21]);

 // echo " Entro al 17";

   }
 

 
   if (($semana1-$semana)+1  ==  18  or  (($numDaysOfCurrentMonth-$semana)+$semana1)+1 ==  18 ) {
array_push($array1530p1,$row[0]);
array_push($array1530p2,$row[1]);
array_push($array1530p3,$row[2]);
array_push($array1530p4,$row[3]);
array_push($array1530p5,$row[4]);
array_push($array1530p6,$row[5]);
array_push($array1530p7,$row[6]);
array_push($array1530p8,$row[7]);
array_push($array1530p9,$row[8]);
array_push($array1530p10,$row[9]);
array_push($array1530p11,$row[10]);
array_push($array1530p12,$row[11]);
array_push($array1530p13,$row[12]);
array_push($array1530p14,$row[13]);
array_push($array1530p15,$row[14]);
array_push($array1530p16,$row[15]);
array_push($array1530p17,$row[16]);
array_push($array1530p18,$row[17]);
array_push($array1530p19,$row[18]);
array_push($array1530p20,$row[19]);
array_push($array1530p21,$row[20]);
array_push($array1530p22,$row[21]);
array_push($array1530p23,$row[22]);



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
// echo $row[14];
// echo $row[15];
// echo $row[16];
// echo $row[17];
// echo $row[18];
// echo $row[19];
// echo $row[21];
// echo $row[22];

///echo "entro";


// echo " Entro al 18";
   }
 
   if (($semana1-$semana)+1  ==  19  or  (($numDaysOfCurrentMonth-$semana)+$semana1)+1 ==  19 ) {
array_push($array1530p1,$row[0]);
array_push($array1530p2,$row[1]);
array_push($array1530p3,$row[2]);
array_push($array1530p4,$row[3]);
array_push($array1530p5,$row[4]);
array_push($array1530p6,$row[5]);
array_push($array1530p7,$row[6]);
array_push($array1530p8,$row[7]);
array_push($array1530p9,$row[8]);
array_push($array1530p10,$row[9]);
array_push($array1530p11,$row[10]);
array_push($array1530p12,$row[11]);
array_push($array1530p13,$row[12]);
array_push($array1530p14,$row[13]);
array_push($array1530p15,$row[14]);
array_push($array1530p16,$row[15]);
array_push($array1530p17,$row[16]);
array_push($array1530p18,$row[17]);
array_push($array1530p19,$row[18]);
array_push($array1530p20,$row[19]);
array_push($array1530p21,$row[20]);
array_push($array1530p22,$row[21]);
array_push($array1530p23,$row[22]);
array_push($array1530p24,$row[23]);

// echo " Entro al 19";

   }

   if (($semana1-$semana) ==  20  or (($numDaysOfCurrentMonth-$semana)+$semana1)+1 ==  20 ) {
array_push($array1530p1,$row[0]);
array_push($array1530p2,$row[1]);
array_push($array1530p3,$row[2]);
array_push($array1530p4,$row[3]);
array_push($array1530p5,$row[4]);
array_push($array1530p6,$row[5]);
array_push($array1530p7,$row[6]);
array_push($array1530p8,$row[7]);
array_push($array1530p9,$row[8]);
array_push($array1530p10,$row[9]);
array_push($array1530p11,$row[10]);
array_push($array1530p12,$row[11]);
array_push($array1530p13,$row[12]);
array_push($array1530p14,$row[13]);
array_push($array1530p15,$row[14]);
array_push($array1530p16,$row[15]);
array_push($array1530p17,$row[16]);
array_push($array1530p18,$row[17]);
array_push($array1530p19,$row[18]);
array_push($array1530p20,$row[19]);
array_push($array1530p21,$row[20]);
array_push($array1530p22,$row[21]);
array_push($array1530p23,$row[22]);
array_push($array1530p24,$row[23]);
array_push($array1530p25,$row[24]);

// echo " Entro al 20";

   }
	

}

//////////////////////////////////////////////////////////
// echo $semana;
$sql5p = "Select *  From (Select * from (
Select * from (
Select Id_Empleado,Nombre,fecha_dia ,sum(Total) as Total_Comidas from (
Select n1.Id_Empleado,n1.Fecha,n1.descripcion,n1.fecha_dia,n1.Total,n1.D,n1.D1,n2.Nombre
from (
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
Select Id_Empleado,Fecha,Viernes as descripcion,replace(rtrim(ltrim(left(DATEADD(day, 4, Fecha),12))),'  ',' ')  as fecha_dia ,Count(*) as Total, D=Month(Fecha) ,D1='5' from [dbo].[PedidosComida] 
Where  not Viernes = ''
Group  by Id_Empleado,Fecha,Viernes  ) as n1
left join 
(Select * from [dbo].[Catalogo_EmpArea]) as n2
on n1.Id_Empleado = n2.Id_Empleado ) as a
-- fecha_dia like
Group by Id_Empleado,Nombre,fecha_dia) as a
WHERE fecha_dia like '$mesini%' and ltrim(rtrim(Substring(fecha_dia,Charindex(' ',fecha_dia),3))) <= $semana1 and  right(fecha_dia,4) = '$year'
) AS TableToPivot 
PIVOT  
(  
  SUM(Total_comidas)  
  FOR fecha_dia  IN ([$mesini 1 2023],[$mesini 2 2023],[$mesini 3 2023],[$mesini 4 2023],[$mesini 5 2023],[$mesini 6 2023],[$mesini 7 2023],[$mesini 8 2023],[$mesini 9 2023],[$mesini 10 2023],[$mesini 11 2023],[$mesini 12 2023],[$mesini 13 2023],[$mesini 14 2023],[$mesini 15 2023])  
                              
) AS PivotTable ) as T1
FULL OUTER JOIN
(
Select IdEmpleado,Nombre,Count(*) as Total  from  (
Select DATENAME(WEEKDAY,CONVERT(date,Hora_Entrada,103)) as Dia_Semana, Substring(Nombre,charindex('N.E: ',Nombre)+5,3) as IdEmpleado,
ltrim(rtrim(Replace(left(substring(Nombre,CHARINDEX(':',Nombre)+1,LEN(Nombre)-CHARINDEX(':',Nombre)+1),charindex(':',substring(Nombre,CHARINDEX(':',Nombre)+1,LEN(Nombre)-CHARINDEX(':',Nombre)+1))),'N.E:','')))
as Nombre,Hora_Entrada,Fecha, Tipo_Comida=  case when Fecha > '13:00:00' then 'Comida' else 'Desayuno' end,
Id_Semana=datepart(week,CONVERT(date,Hora_Entrada,103))
- datepart(week, dateadd(dd,-day(CONVERT(date,Hora_Entrada,103))+1,CONVERT(date,Hora_Entrada,103)))
+1 
from  [dbo].[Entradas]
where Not ltrim(rtrim(Replace(left(substring(Nombre,CHARINDEX(':',Nombre)+1,LEN(Nombre)-CHARINDEX(':',Nombre)+1),charindex(':',substring(Nombre,CHARINDEX(':',Nombre)+1,LEN(Nombre)-CHARINDEX(':',Nombre)+1))),'N.E:',''))) = ''
) as b
where Substring(Hora_Entrada,4,2)  = '$valmes' and  right(Hora_Entrada,4) = '$year' and left(Hora_Entrada,2) <= '$semana1'
Group  by  IdEmpleado,Nombre) AS T2
on T1.Id_Empleado = T2.IdEmpleado";


$stmt5p = sqlsrv_query( $conn,$sql5p);

////$i = 0;

$array115p1 = [];
$array115p2 = [];
$array115p3 = [];
$array115p4 = [];
$array115p5 = [];
$array115p6 = [];
$array115p7 = [];
$array115p8 = [];
$array115p9 = [];
$array115p10 = [];
$array115p11 = [];
$array115p12 = [];
$array115p13 = [];
$array115p14 = [];
$array115p15 = [];
$array115p16 = [];
$array115p17 = [];
$array115p18 = [];
$array115p19 = [];
$array115p20 = [];


$t = 0;

while( $row = sqlsrv_fetch_array($stmt5p,  SQLSRV_FETCH_NUMERIC) ) {
array_push($array115p1,$row[0]);
array_push($array115p2,$row[1]);
array_push($array115p3,$row[2]);
array_push($array115p4,$row[3]);
array_push($array115p5,$row[4]);
array_push($array115p6,$row[5]);
array_push($array115p7,$row[6]);
array_push($array115p8,$row[7]);
array_push($array115p9,$row[8]);
array_push($array115p10,$row[9]);
array_push($array115p11,$row[10]);
array_push($array115p12,$row[11]);
array_push($array115p13,$row[12]);
array_push($array115p14,$row[13]);
array_push($array115p15,$row[14]);
array_push($array115p16,$row[15]);
array_push($array115p17,$row[16]);
array_push($array115p18,$row[17]);
array_push($array115p19,$row[18]);
array_push($array115p20,$row[19]);
// echo $t;
// $t++;
}

//////////////////////// Respaldo Query 16-30 

///// Variables
// Jan
// Feb  
// Mar
// Apr 
// May
// Jun
// Jul
// Aug
// Sep 
// Oct
// Nov
// Dec



// echo $valmes;
///// Variables

////////////////////////////////////////// Query gráfica
$sql4 = "Select Anio, Mes,Id_Semana, sum(LunesT) as LunesT, sum(MartesT) as MartesT,sum(MiercolesT) as MiercolesT,sum(JuevesT) as JuevesT,sum(ViernesT) as ViernesT from (
SELECT left(Fecha,4) as Anio,Substring(cast(convert(date,fecha_dia, 101) as char),6,2) as Mes,Id_semana,ISNULL(Lunes, 0) as LunesT,ISNULL(Martes, 0) AS MartesT,
ISNULL(Miercoles, 0) AS MiercolesT,ISNULL(Jueves, 0) as JuevesT, ISNULL(Viernes, 0) as ViernesT
FROM
(
Select * ,Id_Semana=datepart(week,convert(date,fecha_dia, 101))
- datepart(week, dateadd(dd,-day(convert(date,fecha_dia, 101))+1,convert(date,fecha_dia, 101)))
+1  from (
Select Id_Empleado,Fecha,Lunes as descripcion ,left(DATEADD(day, 0, Fecha),12)  as fecha_dia,Count(Lunes) as Total,D='Lunes' from [dbo].[PedidosComida] 
Where  not Lunes = ''
Group  by Id_Empleado,Fecha,Lunes
union all
Select Id_Empleado,Fecha,Martes as descripcion,left(DATEADD(day, 1, Fecha),12) as fecha_dia ,Count(*) as Total,D='Martes' from [dbo].[PedidosComida] 
Where  not Martes = ''
Group  by Id_Empleado,Fecha,Martes
union all
Select Id_Empleado,Fecha,Miercoles as descripcion,left(DATEADD(day, 2, Fecha),12) as fecha_dia ,Count(*) as Total,D='Miercoles' from [dbo].[PedidosComida] 
Where  not Miercoles = ''
Group  by Id_Empleado,Fecha,Miercoles
union all
Select Id_Empleado,Fecha,Jueves as descripcion,left(DATEADD(day, 3, Fecha),12) as fecha_dia ,Count(*) as Total ,D='Jueves' from [dbo].[PedidosComida] 
Where  not Jueves = ''
Group  by Id_Empleado,Fecha,Jueves
union all
Select Id_Empleado,Fecha,Viernes as descripcion,left(DATEADD(day, 4, Fecha),12) as fecha_dia ,Count(*) as Total, D='Viernes' from [dbo].[PedidosComida] 
Where  not Viernes = ''
Group  by Id_Empleado,Fecha,Viernes) as a
) p
PIVOT
(
  SUM(Total)  
  FOR d IN(Lunes,Martes,Miercoles,Jueves,Viernes)
) AS upvt) as c
where anio = $year and mes = '$valmes'
Group by Anio, Mes,Id_Semana
order by Anio, Mes,Id_Semana
";


// $stmt  = sqlsrv_query( $conn, $sql);
// $stmt1 = sqlsrv_query( $conn, $sql1);
$stmt2 = sqlsrv_query( $conn, $sql2);
$stmt3 = sqlsrv_query( $conn, $sql3);


//// execute query 3 gráfica
$stmt4 = sqlsrv_query( $conn, $sql4);
//// execute query 3 gráfica



/////////////////////////////////////// Variables gráfica
$array_Se1 = [];
$array_Se2 = [];
$array_Se3 = [];
$array_Se4 = [];
$array_Se4 = [];
$array_Se5 = [];

$array_ValArra = [];
/////////////////////////////////////// Variables gráfica


$array_dP1 = [];
$array_dP2 = [];
$array_dP3 = [];
$array_dP4 = [];
$array_dP5 = [];
$array_dP6 = [];
$array_dP7 = [];
$array_dP8 = [];
$array_dP9 = [];
$array_dP10 = [];
$array_dP11 = [];
$array_dP12 = [];
$array_dP13 = [];
$array_dP14 = [];
$array_dP15 = [];
$array_dP16 = [];
$array_dP17 = [];
$array_dP18 = [];
$array_dP19 = [];
$array_dP20 = [];


//////////////////////////////////////////////  variables  quincena dos
$array_d2P1 = [];
$array_d2P2 = [];
$array_d2P3 = [];
$array_d2P4 = [];
$array_d2P5 = [];
$array_d2P6 = [];
$array_d2P7 = [];
$array_d2P8 = [];
$array_d2P9 = [];
$array_d2P10 = [];
$array_d2P11 = [];
$array_d2P12 = [];
$array_d2P13 = [];
$array_d2P14 = [];
$array_d2P15 = [];
$array_d2P16 = [];
$array_d2P17 = [];
$array_d2P18 = [];
$array_d2P19 = [];
$array_d2P20 = [];
$array_d2P21 = [];
$array_d2P22 = [];
//////////////////////////////////////////////  variables  quincena dos



// // $array_Se1 = [];
// // $array_Se2 = [];
// // $array_Se3 = [];
// // $array_Se4 = [];
// // $array_Se5 = [];

///////////////////////////////////////////////While Query gráfica

  
while( $row = sqlsrv_fetch_array( $stmt4, SQLSRV_FETCH_NUMERIC) ) {
	/////echo $row[2];
array_push($array_ValArra,$row[2]);
array_push($array_Se1,$row[3]);
array_push($array_Se2,$row[4]);
array_push($array_Se3,$row[5]);
array_push($array_Se4,$row[6]);
array_push($array_Se5,$row[7]);
}

// $clave1 = array_search('1',$array_ValArra); 
// $clave2 = array_search('2',$array_ValArra);
// $clave3 = array_search('3',$array_ValArra); 
// $clave4 = array_search('4',$array_ValArra); 
// $clave5 = array_search('5',$array_ValArra); 

 // ///////////////// empty link  https://ed.team/blog/detectar-variables-vacias-o-inexistentes-en-php

// // echo $clave1 ;


// if ($clave1==0) {
	
    // array_splice($array_Se1, 0, 0, '' );
    // array_splice($array_Se2, 0, 0, '' );
	// array_splice($array_Se3, 0, 0, '' );
	// array_splice($array_Se4, 0, 0, '' );
	// array_splice($array_Se5, 0, 0, '' );
	
// }
// if (empty($clave2) && $clave2  != 0) {
	                                     // //////echo "entro";
    // array_splice($array_Se1, 1, 1, '' );
    // array_splice($array_Se2, 1, 1, '' );
	// array_splice($array_Se3, 1, 1, '' );
	// array_splice($array_Se4, 1, 1, '' );
	// array_splice($array_Se5, 1, 1, '' );
	                                     // //////echo "entro";
// }

// if (empty($clave3) && $clave3  != 0) {
    // array_splice($array_Se1, 2, 2, '' );
    // array_splice($array_Se2, 2, 2, '' );
	// array_splice($array_Se3, 2, 2, '' );
	// array_splice($array_Se4, 2, 2, '' );
	// array_splice($array_Se5, 2, 2, '' );
// }
// if (empty($clave4) && $clave4  != 0) {
    // array_splice($array_Se1, 3, 3, '' );
    // array_splice($array_Se2, 3, 3, '' );
	// array_splice($array_Se3, 3, 3, '' );
	// array_splice($array_Se4, 3, 3, '' );
	// array_splice($array_Se5, 3, 3, '' );
// }

 // //////echo count($array_Se1);0 

// if (empty($clave5) && $clave5  != 0) {
// ////echo count($array_Se1); 
    // array_splice($array_Se1, 4, 4, '' );
    // array_splice($array_Se2, 4, 4, '' );
	// array_splice($array_Se3, 4, 4, '' );
	// array_splice($array_Se4, 4, 4, '' );
	// array_splice($array_Se5, 4, 4, '' );
// }



// if ($clave5==0) {
	
	// array_unshift($array_Se1,"", "","","");
    // array_unshift($array_Se2,"", "","","");
	// array_unshift($array_Se3,"", "","","");
    // array_unshift($array_Se4,"", "","","");
	// array_unshift($array_Se5,"", "","","");
// }




 /////////////echo count($array_ValArra);



///////////////////////////////////////////////While Query gráfica

/////////////////////////////////////////////////////////////////////////////////while quincena dos


while( $row = sqlsrv_fetch_array( $stmt3, SQLSRV_FETCH_NUMERIC) ) {
	
	
array_push($array_d2P1,$row[0]);
array_push($array_d2P2,$row[1]);
array_push($array_d2P3,$row[2]);
array_push($array_d2P4,$row[3]);
array_push($array_d2P5,$row[4]);
array_push($array_d2P6,$row[5]);
array_push($array_d2P7,$row[6]);
array_push($array_d2P8,$row[7]);
array_push($array_d2P9,$row[8]);
array_push($array_d2P10,$row[9]);
array_push($array_d2P11,$row[10]);
array_push($array_d2P12,$row[11]);
array_push($array_d2P13,$row[12]);
array_push($array_d2P14,$row[13]);
array_push($array_d2P15,$row[14]);
array_push($array_d2P16,$row[15]);
array_push($array_d2P17,$row[16]);
array_push($array_d2P18,$row[17]);
array_push($array_d2P19,$row[18]);
array_push($array_d2P20,$row[19]);
array_push($array_d2P21,$row[20]);


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
	  // echo $row[14];
	  // // echo $row[15];
	  // echo $row[16];
	  // echo $row[17];
	  // echo $row[18];	
      // echo $row[19];		  
}



/////////////////////////////////////////////////////////////////////////////////while quincena dos



/////////////////////////////////// var 

while( $row = sqlsrv_fetch_array( $stmt2, SQLSRV_FETCH_NUMERIC) ) {
	
	
array_push($array_dP1,$row[0]);
array_push($array_dP2,$row[1]);
array_push($array_dP3,$row[2]);
array_push($array_dP4,$row[3]);
array_push($array_dP5,$row[4]);
array_push($array_dP6,$row[5]);
array_push($array_dP7,$row[6]);
array_push($array_dP8,$row[7]);
array_push($array_dP9,$row[8]);
array_push($array_dP10,$row[9]);
array_push($array_dP11,$row[10]);
array_push($array_dP12,$row[11]);
array_push($array_dP13,$row[12]);
array_push($array_dP14,$row[13]);
array_push($array_dP15,$row[14]);
array_push($array_dP16,$row[15]);
array_push($array_dP17,$row[16]);
array_push($array_dP18,$row[17]);
array_push($array_dP19,$row[18]);
array_push($array_dP20,$row[19]);

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
	  // echo $row[14];
	  // echo $row[15];
	  // echo $row[16];
	  // echo $row[17];
	  // echo $row[18];	
      // echo $row[18];		  

}




if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}



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
$array_d32 = [];
$array_d33 = [];
$array_d34 = [];
$array_d34 = [];


////$i= 0;


////echo json_encode(sqlsrv_field_metadata($stmt)); 


//////////////////////
////echo json_encode(sqlsrv_num_fields($stmt));



// Jun 1 2023
// Jun 2 2023
// Jun 5 2023
// Jun 6 2023
// Jun 7 2023
// Jun 8 2023
// Jun 9 2023
// Jun 12 2023
// Jun 13 2023
// Jun 14 2023





// while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC))  
 // { 
// ///////////////////////////////////////////7echo sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC);

// array_push($array_d1,$row[0]);
// array_push($array_d2,$row[1]);
// array_push($array_d3,$row[4]); 
// array_push($array_d4,$row[5]);
// array_push($array_d5,$row[6]);
// array_push($array_d6,$row[7]);
// array_push($array_d7,$row[8]);
// array_push($array_d8,$row[9]);
// array_push($array_d9,$row[10]);
// array_push($array_d10,$row[11]);
// array_push($array_d11,$row[12]);
// array_push($array_d12,$row[13]);
// array_push($array_d13,$row[14]);
// array_push($array_d14,$row[15]);
// array_push($array_d15,$row[16]);
// array_push($array_d16,$row[17]);
// array_push($array_d17,$row[18]);


// // echo $row[0];
// // echo $row[1];
// // echo $row[2];
// // echo $row[3];
// //echo $row[4];
// // echo $row[5];
// // echo $row[6];
// // echo $row[7];
// // echo $row[8];
// // echo $row[9];
// // echo $row[10];
// // echo $row[11];
// // echo $row[12];
// // echo $row[13];
// // echo $row[14];
// // echo $row[15];
// // echo $row[16];
// // echo $row[17];

// // $i++;

// // echo $i;
// }  



// // ${'a' . 'b'} = 'hello there';
// // echo $ab; // hello there}


// // echo sqlsrv_fetch_array( $stmt1, SQLSRV_FETCH_NUMERIC);


// while( $row = sqlsrv_fetch_array( $stmt1, SQLSRV_FETCH_NUMERIC))  
// { 

// // echo sqlsrv_fetch_array( $stmt1, SQLSRV_FETCH_NUMERIC);
// // ${'a' . 'b'} = [];
// ////// echo $row[1].", ".$row[4]."<br />";
	  
// array_push($array_d18,$row[0]);
// array_push($array_d19,$row[1]);
// array_push($array_d20,$row[4]);
// array_push($array_d21,$row[5]);
// array_push($array_d22,$row[6]);
// array_push($array_d23,$row[7]);
// array_push($array_d24,$row[8]);
// array_push($array_d25,$row[9]);
// array_push($array_d26,$row[10]);
// array_push($array_d27,$row[11]);
// array_push($array_d28,$row[12]);
// array_push($array_d29,$row[13]);
// array_push($array_d30,$row[14]);
// array_push($array_d31,$row[15]);
// array_push($array_d32,$row[16]);
// array_push($array_d33,$row[17]);
// array_push($array_d34,$row[18]);
// // echo $row[0];
// // echo $row[1];
// // echo $row[2];
// // echo $row[3];
// // echo $row[4];
// // echo $row[5];
// // echo $row[6];
// // echo $row[7];
// // echo $row[8];
// // echo $row[9];
// // echo $row[10];
// // echo $row[11];
// // echo $row[12];
// // echo $row[13];
// // echo $row[17];
// // echo $row[14];
// // echo $row[15];
// // echo $row[16];

// }  

// sqlsrv_free_stmt( $stmt);


function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}



?>

<script type="text/javascript">


// var darray115p1 = <?php echo json_encode($array115p1);?>;
// var darray115p2 = <?php echo json_encode($array115p2);?>;
// var darray115p3 = <?php echo json_encode($array115p3);?>;
// var darray115p4 = <?php echo json_encode($array115p4);?>;
// var darray115p5 = <?php echo json_encode($array115p5);?>;
// var darray115p6 = <?php echo json_encode($array115p6);?>;
// var darray115p7 = <?php echo json_encode($array115p7);?>;
// var darray115p8 = <?php echo json_encode($array115p8);?>;
// var darray115p9 = <?php echo json_encode($array115p9);?>;
// var darray115p10 = <?php echo json_encode($array115p10);?>;
// var darray115p11 = <?php echo json_encode($array115p11);?>;
// var darray115p12 = <?php echo json_encode($array115p12);?>;
// var darray115p13 = <?php echo json_encode($array115p13);?>;
// var darray115p14 = <?php echo json_encode($array115p14);?>;
// var darray115p15 = <?php echo json_encode($array115p15);?>;
// var darray115p16 = <?php echo json_encode($array115p16);?>;
// var darray115p17 = <?php echo json_encode($array115p17);?>;
// var darray115p18 = <?php echo json_encode($array115p18);?>;
// var darray115p19 = <?php echo json_encode($array115p19);?>;
// var darray115p20 = <?php echo json_encode($array115p20);?>;



var darray1530p1 = <?php echo json_encode($array1530p1);?>;

// alert(darray1530p1)

var darray1530p2 = <?php echo json_encode($array1530p2);?>;
var darray1530p3 = <?php echo json_encode($array1530p3);?>;
var darray1530p4 = <?php echo json_encode($array1530p4);?>;
var darray1530p5 = <?php echo json_encode($array1530p5);?>;
var darray1530p6 = <?php echo json_encode($array1530p6);?>;
var darray1530p7 = <?php echo json_encode($array1530p7);?>;
var darray1530p8 = <?php echo json_encode($array1530p8);?>;
var darray1530p9 = <?php echo json_encode($array1530p9);?>;
var darray1530p10 = <?php echo json_encode($array1530p10);?>;
var darray1530p11 = <?php echo json_encode($array1530p11);?>;
var darray1530p12 = <?php echo json_encode($array1530p12);?>;
var darray1530p13 = <?php echo json_encode($array1530p13);?>;
var darray1530p14 = <?php echo json_encode($array1530p14);?>;
var darray1530p15 = <?php echo json_encode($array1530p15);?>;
var darray1530p16 = <?php echo json_encode($array1530p16);?>;
var darray1530p17 = <?php echo json_encode($array1530p17);?>;
var darray1530p18 = <?php echo json_encode($array1530p18);?>;
var darray1530p19 = <?php echo json_encode($array1530p19);?>;
var darray1530p20 = <?php echo json_encode($array1530p20);?>;
var darray1530p21 = <?php echo json_encode($array1530p21);?>;

// ///////////////////////////////////////////

// //////////// Hola
// //////////////////// Variables nuevas 27-07-23
var darray1530p22 = <?php echo json_encode($array1530p22);?>;
var darray1530p23 = <?php echo json_encode($array1530p23);?>;
var darray1530p24 = <?php echo json_encode($array1530p24);?>;
var darray1530p25 = <?php echo json_encode($array1530p25);?>;
// //////////////////// Variables nuevas 27-07-23

////alert(darray1530p21)

var Pruebas = <?php echo json_encode($valt1);?>;

////////////////////////// Encabezados
// alert(Pruebas)
////////////////////////// Encabezados


// alert(darray1530p1)
// alert(darray1530p2)
// alert(darray1530p3)
// alert(darray1530p4)
// alert(darray1530p5)
// alert(darray1530p6)
// alert(darray1530p7)
// alert(darray1530p8)
// alert(darray1530p9)
// alert(darray1530p10)
// alert(darray1530p11)
// alert(darray1530p12)
// alert(darray1530p13)
// alert(darray1530p14)
// alert(darray1530p15)
// alert(darray1530p16)
// alert(darray1530p17)
// alert(darray1530p18)
// alert(darray1530p19)
// alert(darray1530p20)
// alert(darray1530p21)
// alert(darray1530p22)
// alert(darray1530p23)
// alert(darray1530p24)
// alert(darray1530p25)


//////////////////////////// Variables gráfica


// // $array_Se1 = [];
// // $array_Se2 = [];
// // $array_Se3 = [];
// // $array_Se4 = [];
// // $array_Se5 = [];
// $array_ValArra
var dgrasem = <?php echo json_encode($array_ValArra);?>;
var dgra1 = <?php echo json_encode($array_Se1);?>;
var dgra2 = <?php echo json_encode($array_Se2);?>;
var dgra3 = <?php echo json_encode($array_Se3);?>;
var dgra4 = <?php echo json_encode($array_Se4);?>;
var dgra5 = <?php echo json_encode($array_Se5);?>;

// var dgrasem123 = ['2','3','4']
/////alert(dgrasem)

/////////////////////////////////  Realizar pruebas  

if (dgrasem[0] == '2') {
dgra1.splice(0,0,'')
dgra2.splice(0,0,'')
dgra3.splice(0,0,'')
dgra4.splice(0,0,'')
dgra5.splice(0,0,'')  	
}

if (dgrasem[0] == '3') {
dgra1.splice(0,0,'')
dgra2.splice(0,0,'')
dgra3.splice(0,0,'')
dgra4.splice(0,0,'')
dgra5.splice(0,0,'')

dgra1.splice(0,0,'')
dgra2.splice(0,0,'')
dgra3.splice(0,0,'')
dgra4.splice(0,0,'')
dgra5.splice(0,0,'')   	
}


if (dgrasem[0] == '4') {
dgra1.splice(0,0,'')
dgra2.splice(0,0,'')
dgra3.splice(0,0,'')
dgra4.splice(0,0,'')
dgra5.splice(0,0,'')

dgra1.splice(0,0,'')
dgra2.splice(0,0,'')
dgra3.splice(0,0,'')
dgra4.splice(0,0,'')
dgra5.splice(0,0,'')

dgra1.splice(0,0,'')
dgra2.splice(0,0,'')
dgra3.splice(0,0,'')
dgra4.splice(0,0,'')
dgra5.splice(0,0,'')   

dgra1.splice(0,0,'')
dgra2.splice(0,0,'')
dgra3.splice(0,0,'')
dgra4.splice(0,0,'')
dgra5.splice(0,0,'')   
   	
}


if (dgrasem[0] == '5') {
dgra1.splice(0,0,'')
dgra2.splice(0,0,'')
dgra3.splice(0,0,'')
dgra4.splice(0,0,'')
dgra5.splice(0,0,'')  	

dgra1.splice(0,0,'')
dgra2.splice(0,0,'')
dgra3.splice(0,0,'')
dgra4.splice(0,0,'')
dgra5.splice(0,0,'')  

dgra1.splice(0,0,'')
dgra2.splice(0,0,'')
dgra3.splice(0,0,'')
dgra4.splice(0,0,'')
dgra5.splice(0,0,'')  

dgra1.splice(0,0,'')
dgra2.splice(0,0,'')
dgra3.splice(0,0,'')
dgra4.splice(0,0,'')
dgra5.splice(0,0,'')  
}

// if (dgrasem[1] == '2') {	
// }else {dgra1.splice(0,0,'')
// dgra2.splice(0,0,'')
// dgra3.splice(0,0,'')
// dgra4.splice(0,0,'')
// dgra5.splice(0,0,'')   
// }
// if (dgrasem[2] == '3') {	
// }else {dgra1.splice(0,0,'')
// dgra2.splice(0,0,'')
// dgra3.splice(0,0,'')
// dgra4.splice(0,0,'')
// dgra5.splice(0,0,'')   
// }

   
   
// if (dgrasem[1] == '2') {	
// }else {	
// dgra1.splice(1,1,'')
// dgra2.splice(1,1,'')
// dgra3.splice(1,1,'')
// dgra4.splice(1,1,'')
// dgra5.splice(1,1,'')   
// }

// if (dgrasem[0] == '3') {	
// }else {dgra1.splice(0,0,'')
// dgra2.splice(0,0,'')
// dgra3.splice(0,0,'')
// dgra4.splice(0,0,'')
// dgra5.splice(0,0,'')   
// }

// if (dgrasem[0] == '4') {	
// }else {dgra1.splice(0,0,'')
// dgra2.splice(0,0,'')
// dgra3.splice(0,0,'')
// dgra4.splice(0,0,'')
// dgra5.splice(0,0,'')   
// }
 
///alert(dgrasem123)

// alert(dgra1)
// alert(dgra2)
// alert(dgra3)
// alert(dgra4)
// alert(dgra5)
/////////////////////////// Variables gráfica



// ///////////////////// Variables Prueba


// var datq2m1 = <?php echo json_encode($array_dP1);?>;
// var datq2m2 = <?php echo json_encode($array_dP2);?>;
// var datq2m3 = <?php echo json_encode($array_dP3);?>;
// var datq2m4 = <?php echo json_encode($array_dP4);?>;
// var datq2m5 = <?php echo json_encode($array_dP5);?>;
// var datq2m6 = <?php echo json_encode($array_dP6);?>;
// var datq2m7 = <?php echo json_encode($array_dP7);?>;
// var datq2m8 = <?php echo json_encode($array_dP8);?>;
// var datq2m9 = <?php echo json_encode($array_dP9);?>;
// var datq2m10 = <?php echo json_encode($array_dP10);?>;
// var datq2m11 = <?php echo json_encode($array_dP11);?>;
// var datq2m12 = <?php echo json_encode($array_dP12);?>;
// var datq2m13 = <?php echo json_encode($array_dP13);?>;
// var datq2m14 = <?php echo json_encode($array_dP14);?>;
// var datq2m15 = <?php echo json_encode($array_dP15);?>;
// var datq2m16 = <?php echo json_encode($array_dP16);?>;
// var datq2m17 = <?php echo json_encode($array_dP17);?>;
// var datq2m18 = <?php echo json_encode($array_dP18);?>;
// var datq2m19 = <?php echo json_encode($array_dP19);?>;
// var datq2m20 = <?php echo json_encode($array_dP20);?>;



// //////////////////////////////Variables 1-15
// // alert(datq2m1)
// // alert(datq2m2)
// // alert(datq2m3)
// // alert(datq2m4)
// // alert(datq2m5)
// // alert(datq2m6)
// // alert(datq2m7)
// // alert(datq2m8)
// // alert(datq2m9)
// // alert(datq2m10)
// // alert(datq2m11)
// // alert(datq2m12)
// // alert(datq2m13)
// // alert(datq2m14)
// // alert(datq2m15)
// // alert(datq2m16)
// // alert(datq2m17)
// // alert(datq2m18)
// // alert(datq2m19)

// //////////////////////////////Variables 1-15





// ///////////////////////////////////////////////////////////////////// Quincena dos

// var datq3m1 = <?php echo json_encode($array_d2P1);?>;
// var datq3m2 = <?php echo json_encode($array_d2P2);?>;
// var datq3m3 = <?php echo json_encode($array_d2P3);?>;
// var datq3m4 = <?php echo json_encode($array_d2P4);?>;
// var datq3m5 = <?php echo json_encode($array_d2P5);?>;
// var datq3m6 = <?php echo json_encode($array_d2P6);?>;
// var datq3m7 = <?php echo json_encode($array_d2P7);?>;
// var datq3m8 = <?php echo json_encode($array_d2P8);?>;
// var datq3m9 = <?php echo json_encode($array_d2P9);?>;
// var datq3m10 = <?php echo json_encode($array_d2P10);?>;
// var datq3m11 = <?php echo json_encode($array_d2P11);?>;
// var datq3m12 = <?php echo json_encode($array_d2P12);?>;
// var datq3m13 = <?php echo json_encode($array_d2P13);?>;
// var datq3m14 = <?php echo json_encode($array_d2P14);?>;
// var datq3m15 = <?php echo json_encode($array_d2P15);?>;
// var datq3m16 = <?php echo json_encode($array_d2P16);?>;
// var datq3m17 = <?php echo json_encode($array_d2P17);?>;
// var datq3m18 = <?php echo json_encode($array_d2P18);?>;
// var datq3m19 = <?php echo json_encode($array_d2P19);?>;
// var datq3m20 = <?php echo json_encode($array_d2P20);?>;
// var datq3m21 = <?php echo json_encode($array_d2P21);?>;




// // alert(datq3m1)
// // alert(datq3m2)
// // alert(datq3m3)
// // alert(datq3m4)
// // alert(datq3m5)
// // alert(datq3m6)
// // alert(datq3m7)
// // alert(datq3m8)
// // alert(datq3m9)
// // alert(datq3m10)
// // alert(datq3m11)
// // alert(datq3m12)
// // alert(datq3m13)
// // alert(datq3m14)
// // alert(datq3m15)
// // alert(datq3m16)
// // alert(datq3m17)
 // // alert(datq3m18)
// // alert(datq3m19)
// // alert(datq3m20)
// /////alert(datq3m21)



// ///////////////// variables quincena dos
// ///////////////////////////////////////////////////////////////////// Quincena  dos 


// //////////////////// Variables prueba

// var dataJS1 = <?php echo json_encode($array_d1);?>;
// var dataJS2 = <?php echo json_encode($array_d2);?>;
// var dataJS3 = <?php echo json_encode($array_d3);?>;
// var dataJS4 = <?php echo json_encode($array_d4);?>;
// var dataJS5 = <?php echo json_encode($array_d5);?>;
// var dataJS6 = <?php echo json_encode($array_d6);?>;
// var dataJS7 = <?php echo json_encode($array_d7);?>;
// var dataJS8 = <?php echo json_encode($array_d8);?>;
// var dataJS9 = <?php echo json_encode($array_d9);?>;
// var dataJS10 = <?php echo json_encode($array_d10);?>;
// var dataJS11 = <?php echo json_encode($array_d11);?>;
// var dataJS12 = <?php echo json_encode($array_d12);?>;
// var dataJS13 = <?php echo json_encode($array_d13);?>;
// var dataJS14 = <?php echo json_encode($array_d14);?>;
// var dataJS15 = <?php echo json_encode($array_d15);?>;
// var dataJS16 = <?php echo json_encode($array_d16);?>;
// var dataJS17 = <?php echo json_encode($array_d17);?>;

// // alert(dataJS1)
// // alert(dataJS2)
// // alert(dataJS3)
// // alert(dataJS4)
// // alert(dataJS5)
// // alert(dataJS6)
// // alert(dataJS7)


// ///////////////////////////////////////////////////////// Segunda quincena
// var dataJS18 = <?php echo json_encode($array_d18);?>;
// var dataJS19 = <?php echo json_encode($array_d19);?>;
// var dataJS20 = <?php echo json_encode($array_d20);?>;
// var dataJS21 = <?php echo json_encode($array_d21);?>;
// var dataJS22 = <?php echo json_encode($array_d22);?>;
// var dataJS23 = <?php echo json_encode($array_d23);?>;
// var dataJS24 = <?php echo json_encode($array_d24);?>;
// var dataJS25 = <?php echo json_encode($array_d25);?>;
// var dataJS26 = <?php echo json_encode($array_d26);?>;
// var dataJS27 = <?php echo json_encode($array_d27);?>;
// var dataJS28 = <?php echo json_encode($array_d28);?>;
// var dataJS29 = <?php echo json_encode($array_d29);?>;
// var dataJS30 = <?php echo json_encode($array_d30);?>;
// var dataJS31 = <?php echo json_encode($array_d31);?>;
// var dataJS32 = <?php echo json_encode($array_d32);?>;
// var dataJS33 = <?php echo json_encode($array_d33);?>;
// var dataJS34 = <?php echo json_encode($array_d34);?>;

// //////////alert(dataJS1)

// ///////////////////////////////// Variables dia,mes y año

// var dataJmes = <?php echo json_encode($mesini);?>;
// //// Colocar mes fin

// //// Colocar mes fin
// var dataJdiaini = <?php echo json_encode($semana);?>;
// var dataJdiafin = <?php echo json_encode($semana1);?>;
// var dataJyear = <?php echo json_encode($year);?>;

// //// Agregar Final del mes

// var header1 = ["Id_Empleado","Nombre",dataJmes+' '+'1'+' '+dataJyear,dataJmes+' '+'2'+' '+dataJyear,dataJmes+' '+'3'+' '+dataJyear,dataJmes+' '+'4'+' '+dataJyear,dataJmes+' '+'5'+' '+dataJyear,dataJmes+' '+'6'+' '+dataJyear,dataJmes+' '+'7'+' '+dataJyear,dataJmes+' '+'8'+' '+dataJyear,dataJmes+' '+'9'+' '+dataJyear,dataJmes+' '+'10'+' '+dataJyear,dataJmes+' '+'11'+' '+dataJyear,dataJmes+' '+'12'+' '+dataJyear,dataJmes+' '+'13'+' '+dataJyear,dataJmes+' '+'14'+' '+dataJyear,dataJmes+' '+'15'+' '+dataJyear,'Total_Pedidos','Cantidad_a_Cobrar','Nombre_Comedor','Reg_Comedor','Total'];

// var header2 = ["Id_Empleado","Nombre",dataJmes+' '+'16'+' '+dataJyear,dataJmes+' '+'17'+' '+dataJyear,dataJmes+' '+'18'+' '+dataJyear,dataJmes+' '+'19'+' '+dataJyear,dataJmes+' '+'20'+' '+dataJyear,dataJmes+' '+'21'+' '+dataJyear,dataJmes+' '+'22'+' '+dataJyear,dataJmes+' '+'23'+' '+dataJyear,dataJmes+' '+'24'+' '+dataJyear,dataJmes+' '+'25'+' '+dataJyear,dataJmes+' '+'26'+' '+dataJyear,dataJmes+' '+'27'+' '+dataJyear,dataJmes+' '+'28'+' '+dataJyear,dataJmes+' '+'29'+' '+dataJyear,dataJmes+' '+'30'+' '+dataJyear,dataJmes+' '+'31'+' '+dataJyear,'Total_Pedidos','Cantidad_a_Cobrar','Nombre_Comedor','Reg_Comedor','Total'];

// ////// Investigar días de cortes
// ///// Header nuevos
// ////  Uno empieza en 23
// //// var header1 = ["Id_Empleado","Nombre",dataJmes+' '+'24'+' '+dataJyear,dataJmes+' '+'25'+' '+dataJyear,dataJmes+' '+'26'+' '+dataJyear,dataJmes+' '+'27'+' '+dataJyear,dataJmes+' '+'28'+' '+dataJyear,dataJmes+' '+'29'+' '+dataJyear,dataJmes+' '+'30'+' '+dataJyear,dataJmes+' '+'31'+' '+dataJyear,dataJmes+' '+'1'+' '+dataJyear,dataJmes+' '+'2'+' '+dataJyear,dataJmes+' '+'3'+' '+dataJyear,dataJmes+' '+'4'+' '+dataJyear,dataJmes+' '+'5'+' '+dataJyear,dataJmes+' '+'6'+' '+dataJyear,dataJmes+' '+'7'+' '+dataJyear,'Total_Pedidos','Cantidad_a_Cobrar','Nombre_Comedor','Reg_Comedor','Total'];
// ////  Uno empieza en 14
// //// var header2 = ["Id_Empleado","Nombre",dataJmes+' '+'8'+' '+dataJyear,dataJmes+' '+'9'+' '+dataJyear,dataJmes+' '+'10'+' '+dataJyear,dataJmes+' '+'11'+' '+dataJyear,dataJmes+' '+'12'+' '+dataJyear,dataJmes+' '+'13'+' '+dataJyear,dataJmes+' '+'14'+' '+dataJyear,dataJmes+' '+'15'+' '+dataJyear,dataJmes+' '+'16'+' '+dataJyear,dataJmes+' '+'17'+' '+dataJyear,dataJmes+' '+'18'+' '+dataJyear,dataJmes+' '+'19'+' '+dataJyear,dataJmes+' '+'20'+' '+dataJyear,dataJmes+' '+'21'+' '+dataJyear,dataJmes+' '+'22'+' '+dataJyear,dataJmes+' '+'23'+' '+dataJyear,'Total_Pedidos','Cantidad_a_Cobrar','Nombre_Comedor','Reg_Comedor','Total'];
// ///// Header nuevos
// ////// Investigar días de cortes



var headerb =[];

// ///////////////////////////////////////////////////////////////////////////////////////////// ciclo encabezados
////////////////////////// Encabezados

// ///////////////////////If encabezados			{ title: '' },

/////Hola 
////Pruebas
// var Pruebas1 = [];

// Pruebas1 = Pruebas;

var text = 'Id_Empleado,'+'Nombre,' +Pruebas+','+'Total $,'+'Id_Empleado,'+'Nombre,'+'Total,'+'Total $'



var textA = text.split(",");

// alert(textA.length)

for (var i = 0; i < textA.length; i++) {
	headerb.push({ title: textA[i].replace(/[\[\]]/g,'') })
}

 ////alert(textA.length)

var dataSet = [];

  // if (textA.length == 18) {

// for (var i = 0; i < darray1530p1.length; i++) {
// dataSet.push(['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18']);
// }

   // }

     // if (textA.length == 19) {
// for (var i = 0; i < darray1530p1.length; i++) {
// dataSet.push(['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19']);
// }
   // }
     // if (textA.length == 20) {
// for (var i = 0; i < darray1530p1.length; i++) {
// dataSet.push(['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20']);
// }
   // }
     // if (textA.length == 21) {
// for (var i = 0; i < darray1530p1.length; i++) {
// dataSet.push(['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21']);
// }
		 
   // }

// alert(textA.length)


if (textA.length == 21) {
for (var i = 0; i < darray1530p1.length; i++) {
	
dataSet.push([darray1530p1[i],darray1530p2[i],darray1530p3[i],darray1530p4[i],darray1530p5[i],darray1530p6[i],darray1530p7[i],darray1530p8[i],darray1530p9[i],darray1530p10[i],darray1530p11[i],darray1530p12[i],darray1530p13[i],darray1530p14[i],darray1530p15[i],darray1530p16[i],'$'+((darray1530p3[i]+darray1530p4[i]+darray1530p5[i]+darray1530p6[i]+darray1530p7[i]+darray1530p8[i]+darray1530p9[i]+darray1530p10[i]+darray1530p11[i]+darray1530p12[i]+darray1530p13[i]+darray1530p14[i]+darray1530p15[i]+darray1530p16[i])*30),darray1530p17[i],darray1530p18[i],darray1530p19[i], '$'+(darray1530p19[i] * 30)]);
} 	 		 
   }



if (textA.length == 22) {
for (var i = 0; i < darray1530p1.length; i++) {
	
dataSet.push([darray1530p1[i],darray1530p2[i],darray1530p3[i],darray1530p4[i],darray1530p5[i],darray1530p6[i],darray1530p7[i],darray1530p8[i],darray1530p9[i],darray1530p10[i],darray1530p11[i],darray1530p12[i],darray1530p13[i],darray1530p14[i],darray1530p15[i],darray1530p16[i],darray1530p17[i],'$'+((darray1530p3[i]+darray1530p4[i]+darray1530p5[i]+darray1530p6[i]+darray1530p7[i]+darray1530p8[i]+darray1530p9[i]+darray1530p10[i]+darray1530p11[i]+darray1530p12[i]+darray1530p13[i]+darray1530p14[i]+darray1530p15[i]+darray1530p16[i]+darray1530p17[i])*30),darray1530p18[i],darray1530p19[i],darray1530p20[i], '$'+(darray1530p20[i] * 30)]);
} 	 		 
   }

if (textA.length == 23) {
	//// alert("Entro")
	//// 18
for (var i = 0; i < darray1530p1.length; i++) {
dataSet.push([darray1530p1[i],darray1530p2[i],darray1530p3[i],darray1530p4[i],darray1530p5[i],darray1530p6[i],darray1530p7[i],darray1530p8[i],darray1530p9[i],darray1530p10[i],darray1530p11[i],darray1530p12[i],darray1530p13[i],darray1530p14[i],darray1530p15[i],darray1530p16[i],darray1530p17[i],darray1530p18[i],'$'+((darray1530p3[i]+darray1530p4[i]+darray1530p5[i]+darray1530p6[i]+darray1530p7[i]+darray1530p8[i]+darray1530p9[i]+darray1530p10[i]+darray1530p11[i]+darray1530p12[i]+darray1530p13[i]+darray1530p14[i]+darray1530p15[i]+darray1530p16[i]+darray1530p17[i]+darray1530p18[i])*30),darray1530p19[i],darray1530p20[i],darray1530p21[i],'$'+((darray1530p21[i])*30)]);
} 
			
   }
           if (textA.length == 24) {
/////17		   
for (var i = 0; i < darray1530p1.length; i++) {
dataSet.push([darray1530p1[i],darray1530p2[i],darray1530p3[i],darray1530p4[i],darray1530p5[i],darray1530p6[i],darray1530p7[i],darray1530p8[i],darray1530p9[i],darray1530p10[i],darray1530p11[i],darray1530p12[i],darray1530p13[i],darray1530p14[i],darray1530p15[i],darray1530p16[i],darray1530p17[i],darray1530p18[i],darray1530p19[i],'$'+((darray1530p3[i]+darray1530p4[i]+darray1530p5[i]+darray1530p6[i]+darray1530p7[i]+darray1530p8[i]+darray1530p9[i]+darray1530p10[i]+darray1530p11[i]+darray1530p12[i]+darray1530p13[i]+darray1530p14[i]+darray1530p15[i]+darray1530p16[i]+darray1530p17[i]+darray1530p18[i]+darray1530p19[i])*30),darray1530p20[i],darray1530p21[i],darray1530p22[i],'$'+(darray1530p22[i]*30)]);	}
	}
   
 if (textA.length == 25) {
	   
////18	   
for (var i = 0; i < darray1530p1.length; i++) {
dataSet.push([darray1530p1[i],darray1530p2[i],darray1530p3[i],darray1530p4[i],darray1530p5[i],darray1530p6[i],darray1530p7[i],darray1530p8[i],darray1530p9[i],darray1530p10[i],darray1530p11[i],darray1530p12[i],darray1530p13[i],darray1530p14[i],darray1530p15[i],darray1530p16[i],darray1530p17[i],darray1530p18[i],darray1530p19[i],darray1530p20[i],'$'+(darray1530p3[i]+darray1530p4[i]+darray1530p5[i]+darray1530p6[i]+darray1530p7[i]+darray1530p8[i]+darray1530p9[i]+darray1530p10[i]+darray1530p11[i]+darray1530p12[i]+darray1530p13[i]+darray1530p14[i]+darray1530p15[i]+darray1530p16[i]+darray1530p17[i]+darray1530p18[i]+darray1530p19[i]+darray1530p20[i])*30,darray1530p21[i],darray1530p22[i],darray1530p23[i],'$'+(darray1530p23[i]*30)]);
}  
   }
   
   
 /////26  
  if (textA.length == 26) {
	   
////18	   
for (var i = 0; i < darray1530p1.length; i++) {
dataSet.push([darray1530p1[i],darray1530p2[i],darray1530p3[i],darray1530p4[i],darray1530p5[i],darray1530p6[i],darray1530p7[i],darray1530p8[i],darray1530p9[i],darray1530p10[i],darray1530p11[i],darray1530p12[i],darray1530p13[i],darray1530p14[i],darray1530p15[i],darray1530p16[i],darray1530p17[i],darray1530p18[i],darray1530p19[i],darray1530p20[i],darray1530p21[i],'$'+(darray1530p3[i]+darray1530p4[i]+darray1530p5[i]+darray1530p6[i]+darray1530p7[i]+darray1530p8[i]+darray1530p9[i]+darray1530p10[i]+darray1530p11[i]+darray1530p12[i]+darray1530p13[i]+darray1530p14[i]+darray1530p15[i]+darray1530p16[i]+darray1530p17[i]+darray1530p18[i]+darray1530p19[i]+darray1530p20[i]+darray1530p21[i])*30,darray1530p22[i],darray1530p23[i],darray1530p24[i],'$'+(darray1530p24[i]*30)]);
}  
   }
   
   
   
      // if (textA.length == 26) {
	   
// ////18	   
// /////17		   
// for (var i = 0; i < darray1530p1.length; i++) {
// dataSet.push(['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26']);
// }  
   // }
   
   
         // if (textA.length == 27) {
	   
// ////18	   
// /////17		   
// for (var i = 0; i < darray1530p1.length; i++) {
// dataSet.push(['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27']);
// }  
   // }
   
// if (textA.length == 28) {
	   
// ////18	   
// /////17		   
// for (var i = 0; i < darray1530p1.length; i++) {
// dataSet.push(['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28']);
// }  
   // }
   
   
  // 13+5 18
  // 14+5 19
  // 15+5 20
  // 16+5 21
  // 17+5 22
  // 18+5 23
  // 19+5 24
  // 20+5 25
  
  //'Id_Empleado','Nombre','Id_Empleado','Nombre','Total'

// if (dataJdiafin>15) {
    
// for (var i = 0; i < header2.length; i++) {
	// headerb.push({ title: header2[i] })
// }
// }


// if (dataJdiafin<=15) {
    // for (var i = 0; i < header1.length; i++) {
	// headerb.push({ title: header1[i] })
// }
// }

// //////////////////////////////////////////////////////////// For insertar datos 




// for (var i = 0; i < darray1530p1.length; i++) {
// dataSet.push([darray1530p1[i],darray1530p2[i],darray1530p3[i],darray1530p4[i],darray1530p5[i],darray1530p6[i],darray1530p7[i],darray1530p8[i],darray1530p9[i],darray1530p10[i],darray1530p11[i],darray1530p12[i],darray1530p13[i],darray1530p14[i],darray1530p15[i],darray1530p16[i],darray1530p17[i],darray1530p18[i],(darray1530p3[i]+darray1530p4[i]+darray1530p5[i]+darray1530p6[i]+darray1530p7[i]+darray1530p8[i]+darray1530p9[i]+darray1530p10[i]+darray1530p11[i]+darray1530p12[i]+darray1530p13[i]+darray1530p14[i]+darray1530p15[i]+darray1530p16[i]+darray1530p17[i]+darray1530p18[i]),'$'+(darray1530p3[i]+darray1530p4[i]+darray1530p5[i]+darray1530p6[i]+darray1530p7[i]+darray1530p8[i]+darray1530p9[i]+darray1530p10[i]+darray1530p11[i]+darray1530p12[i]+darray1530p13[i]+darray1530p14[i]+darray1530p15[i]+darray1530p16[i]+darray1530p17[i]+darray1530p18[i])*30,darray1530p20[i],darray1530p21[i],'$'+darray1530p21[i]*30]);

// }



// ///////////////////['721','Luis Antonio Romero López','1','1','1','1','1','1','1','','','','','8','','','','',''],


// // alert(datq2m1)
// // alert(datq2m2)
// // alert(datq2m3)
// // alert(datq2m4)
// // alert(datq2m5)
// // alert(datq2m6)
// // alert(datq2m7)
// // alert(datq2m8)
// // alert(datq2m9)
// // alert(datq2m10)
// // alert(datq2m11)
// // alert(datq2m12)
// // alert(datq2m13)
// // alert(datq2m14)
// // alert(datq2m15)
// // alert(datq2m16)
// // alert(datq2m17)
// // alert(datq2m18)
// // alert(datq2m19)
// // alert(datq2m120)


// if (dataJdiafin>15) {
	
// // darray1530p1
// // darray1530p2
// // darray1530p3
// // darray1530p4
// // darray1530p5
// // darray1530p6
// // darray1530p7
// // darray1530p8
// // darray1530p9
// // darray1530p10
// // darray1530p11
// // darray1530p12
// // darray1530p13
// // darray1530p14
// // darray1530p15
// // darray1530p16
// // darray1530p17
// // darray1530p18
// // darray1530p19
// // darray1530p20
// // darray1530p21	


// for (var i = 0; i < darray1530p1.length; i++) {
// dataSet.push([darray1530p1[i],darray1530p2[i],darray1530p3[i],darray1530p4[i],darray1530p5[i],darray1530p6[i],darray1530p7[i],darray1530p8[i],darray1530p9[i],darray1530p10[i],darray1530p11[i],darray1530p12[i],darray1530p13[i],darray1530p14[i],darray1530p15[i],darray1530p16[i],darray1530p17[i],darray1530p18[i],(darray1530p3[i]+darray1530p4[i]+darray1530p5[i]+darray1530p6[i]+darray1530p7[i]+darray1530p8[i]+darray1530p9[i]+darray1530p10[i]+darray1530p11[i]+darray1530p12[i]+darray1530p13[i]+darray1530p14[i]+darray1530p15[i]+darray1530p16[i]+darray1530p17[i]+darray1530p18[i]),'$'+(darray1530p3[i]+darray1530p4[i]+darray1530p5[i]+darray1530p6[i]+darray1530p7[i]+darray1530p8[i]+darray1530p9[i]+darray1530p10[i]+darray1530p11[i]+darray1530p12[i]+darray1530p13[i]+darray1530p14[i]+darray1530p15[i]+darray1530p16[i]+darray1530p17[i]+darray1530p18[i])*30,darray1530p20[i],darray1530p21[i],'$'+darray1530p21[i]*30]);

// }
// }

    
// // for (var i = 0; i < datq3m1.length; i++) {
// // //////////////////// Prueba suma 
// // ///////////alert('$'+(datq3m6[i]+datq3m7[i]+datq3m8[i]+datq3m9[i]+datq3m10[i]+datq3m11[i]+datq3m12[i]+datq3m13[i]+datq3m14[i]+datq3m15[i]+datq3m16[i]+datq3m17[i]+datq3m18[i]+datq3m19[i]+datq3m20[i]+datq3m21[i])*30)		
// // //////////////////// Prueba suma 		
// // dataSet.push([datq3m1[i],datq3m2[i],datq3m6[i],datq3m7[i],datq3m8[i],datq3m9[i],datq3m10[i],datq3m11[i],datq3m12[i],datq3m13[i],datq3m14[i],datq3m15[i],datq3m16[i],datq3m17[i],datq3m18[i],datq3m19[i],datq3m20[i],datq3m21[i],(datq3m6[i]+datq3m7[i]+datq3m8[i]+datq3m9[i]+datq3m10[i]+datq3m11[i]+datq3m12[i]+datq3m13[i]+datq3m14[i]+datq3m15[i]+datq3m16[i]+datq3m17[i]+datq3m18[i]+datq3m19[i]+datq3m20[i]+datq3m21[i]),('$'+(datq3m6[i]+datq3m7[i]+datq3m8[i]+datq3m9[i]+datq3m10[i]+datq3m11[i]+datq3m12[i]+datq3m13[i]+datq3m14[i]+datq3m15[i]+datq3m16[i]+datq3m17[i]+datq3m18[i]+datq3m19[i]+datq3m20[i]+datq3m21[i])*30)]);
// // /////dataSet.push([darray1530p1[i],darray1530p2[i],darray1530p3[i],darray1530p4[i],darray1530p5[i],darray1530p6[i],darray1530p7[i],darray1530p8[i],darray1530p9[i],darray1530p10[i],darray1530p11[i],darray1530p12[i],darray1530p13[i],darray1530p14[i],darray1530p15[i],darray1530p16[i],darray1530p17[i],darray1530p18[i],(darray1530p3[i]+darray1530p4[i]+darray1530p5[i]+darray1530p6[i]+darray1530p7[i]+darray1530p8[i]+darray1530p9[i]+darray1530p10[i]+darray1530p11[i]+darray1530p12[i]+darray1530p13[i]+darray1530p14[i]+darray1530p15[i]+darray1530p16[i]+darray1530p17[i]+darray1530p18[i]),(darray1530p3[i]+darray1530p4[i]+darray1530p5[i]+darray1530p6[i]+darray1530p7[i]+darray1530p8[i]+darray1530p9[i]+darray1530p10[i]+darray1530p11[i]+darray1530p12[i]+darray1530p13[i]+darray1530p14[i]+darray1530p15[i]+darray1530p16[i]+darray1530p17[i]+darray1530p18[i])*30,darray1530p20[i],darray1530p21[i],darray1530p21[i]*30]);

// // }
// // }darray115p10

// if (dataJdiafin<=15) {

 // for (var i = 0; i < darray115p1.length; i++) {
// dataSet.push([darray115p1[i],darray115p2[i],darray115p3[i],darray115p4[i],darray115p5[i],darray115p6[i],darray115p7[i],darray115p8[i],darray115p9[i],darray115p10[i],darray115p11[i],darray115p12[i],darray115p13[i],darray115p14[i],darray115p15[i],darray115p16[i],darray115p17[i],(darray115p3[i]+darray115p4[i]+darray115p5[i]+darray115p6[i]+darray115p7[i]+darray115p8[i]+darray115p9[i]+darray115p10[i]+darray115p11[i]+darray115p12[i]+darray115p13[i]+darray115p14[i]+darray115p15[i]+darray115p16[i]+darray115p17[i]),'$'+((darray115p3[i]+darray115p4[i]+darray115p5[i]+darray115p6[i]+darray115p7[i]+darray115p8[i]+darray115p9[i]+darray115p10[i]+darray115p11[i]+darray115p12[i]+darray115p13[i]+darray115p14[i]+darray115p15[i]+darray115p16[i]+darray115p17[i])*30),darray115p19[i],darray115p20[i],'$'+(darray115p20[i]*30)]);	 
// }
// // for (var i = 0; i < datq2m1.length; i++) {
	// // ////alert(i)
	
// // ////////////// alert suma, costo y suma de valores
// // /////alert('$'+(datq2m6[i]+datq2m7[i]+datq2m8[i]+datq2m9[i]+datq2m10[i]+datq2m11[i]+datq2m12[i]+datq2m13[i]+datq2m14[i]+datq2m15[i]+datq2m16[i]+datq2m17[i]+datq2m18[i]+datq2m19[i]+datq2m19[i])*30)
// // ////////////// alert suma, costo y suma de valores

// // /////////////////alert(datq2m20[i])
// // dataSet.push([datq2m1[i],datq2m2[i],datq2m6[i],datq2m7[i],datq2m8[i],datq2m9[i],datq2m10[i],datq2m11[i],datq2m12[i],datq2m13[i],datq2m14[i],datq2m15[i],datq2m16[i],datq2m17[i],datq2m18[i],datq2m19[i],datq2m20[i],(datq2m6[i]+datq2m7[i]+datq2m8[i]+datq2m9[i]+datq2m10[i]+datq2m11[i]+datq2m12[i]+datq2m13[i]+datq2m14[i]+datq2m15[i]+datq2m16[i]+datq2m17[i]+datq2m18[i]+datq2m19[i]+datq2m20[i]),('$'+(datq2m6[i]+datq2m7[i]+datq2m8[i]+datq2m9[i]+datq2m10[i]+datq2m11[i]+datq2m12[i]+datq2m13[i]+datq2m14[i]+datq2m15[i]+datq2m16[i]+datq2m17[i]+datq2m18[i]+datq2m19[i]+datq2m20[i])*30)]);		
// // }
// ///alert(dataSet.length)
// }

// /////alert(dataSet.length)

// // alert(dataSet)

// //////////////////////////////////////////////////////////// For insertar datos 


$(document).ready(function () {
	////alert(<?php echo json_encode($array_d1);?>)
  $('#example').DataTable({
    data:dataSet,
     columns: headerb,retrieve: true,
    });
});




function ExportToExcel(type, fn, dl) {
       var elt = document.getElementById('example');
       var wb = XLSX.utils.table_to_book(elt, { sheet: "sheet1" });
       return dl ?
         XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }):
         XLSX.writeFile(wb, fn || ('MySheetName.' + (type || 'xlsx')));
    }



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
      data: ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4','Semana 5']
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
      data: dgra1
    },
    {
      name: 'Día 2',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: dgra2
    },
    {
      name: 'Día 3',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: dgra3
    },
    {
      name: 'Día 4',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: dgra4
    },
    {
      name: 'Día 5',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: dgra5
    }
  ]
};

option && myChart.setOption(option);

</script>
