<!--
 @file Consultadedatos.php
 @brief Dashboard analítico de pedidos del comedor con visualizaciones múltiples y tabla exportable.

 @description
 Módulo de consulta y análisis de datos del Sistema Comedor. Permite al usuario seleccionar
 un día, mes y año para obtener métricas y gráficos interactivos sobre los pedidos realizados
 en esa semana. El flujo es:
   1. El usuario selecciona Día, Mes y Año mediante selectores HTML.
   2. Al presionar "Consultar" se ejecuta ConsultGraf(), que filtra los arrays PHP (pasados
      como JSON al JS) y construye las series de datos para cuatro gráficos ECharts.
   3. Simultáneamente, Borrar() limpia la DataTable antes de agregar nuevas filas.
   4. La DataTable #example muestra Área, Fecha, Total de Platillos servidos y costo
      estimado (platillos × $30 MXN).
   5. Los gráficos generados son:
      a) Gráfico de barras agrupadas por área y día (#container) — pedidos por área/día de la semana.
      b) Gráfico de pastel por área (#main) — participación proporcional de cada área.
      c) Gráfico de barras horizontales apiladas por categoría de gasto semanal (#main1).
      d) Gráfico de pastel "donut" mensual (#main2) — total de platillos servidos por mes del año.

 Fuentes de datos PHP → SQL Server:
   - $sql  : Consulta PIVOT que transpone pedidos diarios por área (PedidosComida + Catalogo_EmpArea).
   - $sql1 : Totales mensuales de pedidos (PedidosComida agrupada por año-mes).
   - $sql4 : Costos de compras por semana del mes (Compras_Costos con cálculo de número de semana).

 @module Módulo de Reportes y KPIs
 @access DIRECCIÓN | RECURSOS HUMANOS | ADMINISTRADOR

 @dependencies
 - JS CDN: jQuery 3.5.1, DataTables 1.13.4, ECharts 5.4.2
 - PHP: sqlsrv (extensión Microsoft SQL Server)

 @database
 - Base de datos: Comedor (DESAROLLO-BACRO\SQLEXPRESS)
 - Tablas:
     * [dbo].[PedidosComida]       — Pedidos de comida por empleado y semana (columnas: Lunes–Viernes)
     * [dbo].[Catalogo_EmpArea]    — Catálogo de empleados con área asignada
     * [dbo].[Compras_Costos]      — Registro de costos de compras por categoría y fecha
 - Patrones SQL: PIVOT, UNION ALL, GROUP BY, DATEADD, ISNULL, datepart(week,...), substring

 @analytics
 - Tipo de visualización:
     * Gráfico de barras agrupadas (pedidos por área y día)
     * Gráfico de pastel (distribución por área en la semana seleccionada)
     * Gráfico de barras horizontales apiladas (costos de compras por semana y categoría)
     * Gráfico de pastel donut (total de platillos servidos por mes en el año)
 - Métricas calculadas:
     * Pedidos por área por día de la semana (Lunes–Viernes)
     * Total de platillos servidos por área en la semana
     * Costo estimado por área (platillos × $30 MXN)
     * Totales mensuales de pedidos acumulados por año
     * Costos de compras (Carnicería, Frutas, Verduras, Lácteos, Accesorios) por semana
 - Período de análisis: Semana específica según selección Día-Mes-Año; totales mensuales del año

 @inputs
 - Filtros UI: Selector Mes (01–12), Selector Día (01–31), Selector Año (2023–2030)
 - Botón: "Consultar" → dispara Borrar() + ConsultGraf()
 - $_POST campos: Mes, Semana (día), Anio

 @outputs
 - DataTable con columnas: Área, Fecha, Total_Platillos, Total (costo estimado MXN)
 - Cuatro gráficos ECharts interactivos con tooltips y leyendas
 - Herramienta de guardado como imagen en gráfico de barras (ECharts toolbox)

 @security
 - Formulario POST con action sobre sí mismo
 - Función test_input() para sanitización: trim, stripslashes, htmlspecialchars
 - NOTA: Credenciales de base de datos hardcodeadas (pendiente migrar a .env)

 @author Equipo Tecnología BacroCorp
 @version 1.0
 @since 2024
 @updated 2026-02-18
-->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css"></script>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
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
  
  <style>
body {
 font-family: Arial Narrow;
 background-color: rgba(241, 238, 237);
   font-size: 18px;
}


.img-container {
        text-align: right;
      }

</style>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body >
<p><a href="Demo_SistemaComedor.html">Menu principal</a></p>      <div class="img-container"> <!-- Block parent element -->
    <img src="Logo2.png" width="150" height="100"> </div>
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
		<br>		
		
				<br>

	  <input type="Button" name="submit" value="Consultar" onclick="Borrar();ConsultGraf()">  
<div>
<div id="container" style="height:50%;width:50%;display:block;float: left;"></div>
<div id="main"      style="height:50%;width:50%;display:block;float: right;"></div>
</div>
<div style="height:50%;width:50%;float: left;">
<table id="example" class="display" width="50%" ></table>
</div>

<div>
<div id="main1" style="height:50%;width:50%;display:block;float: right;"></div>
</div>


<div>
<div id="main2" style="height:50%;width:50%;display:block;float: right;"></div>
</div>


</body>
</html>
</form>

<?php


$mes = $semana = $anio;

$mes = test_input($_POST["Mes"]);
  $semana = test_input($_POST["Semana"]);
  ///$anio = test_input($_POST["Anio"]);



////////////////// Select

require_once __DIR__ . '/config/database.php';
$conn = getComedorConnection();


/* =========================================================
 * CONSULTA ANALÍTICA: Pedidos por Área y Día de la Semana mediante PIVOT
 * =========================================================
 * Patrón SQL: PIVOT + UNION ALL + INNER JOIN + GROUP BY + DATEADD + ISNULL
 * Bases de datos: Comedor
 * Tablas: [dbo].[PedidosComida], [dbo].[Catalogo_EmpArea]
 * Descripción:
 *   Genera una tabla desnormalizada con el total de pedidos por área y día de la semana.
 *   El flujo interno de la consulta es:
 *     1. UNION ALL (capa interna): Descompone las columnas Lunes–Viernes de PedidosComida
 *        en filas individuales, calculando para cada día la fecha real via DATEADD (+0 a +4 días
 *        desde la fecha de inicio de semana). Genera una clave compuesta Clave_Uni = inicial
 *        del tipo de platillo + nombre del día (ej. 'CLunes', 'DViernes').
 *     2. PIVOT: Transpone las filas de días en columnas binarias/conteo usando la clave Clave_Uni.
 *        Columnas resultantes del PIVOT: CLunes, DLunes, CMartes, DMartes, CMiercoles, DMiercoles,
 *        CJueves, DJueves, CViernes, DViernes.
 *     3. INNER JOIN con Catalogo_EmpArea: Agrega el campo Area al empleado.
 *     4. GROUP BY Area, Fecha: Agrega los totales por área y semana (fecha de inicio de semana).
 * Columnas retornadas:
 *   - Area       : Nombre del área del empleado
 *   - Fecha      : Fecha de inicio de la semana (formato del campo en PedidosComida)
 *   - CLunes     : Total de platillos tipo C pedidos el lunes
 *   - DLunes     : Total de platillos tipo D pedidos el lunes
 *   - CMartes    : Total de platillos tipo C pedidos el martes
 *   - DMartes    : Total de platillos tipo D pedidos el martes
 *   - CMiercoles : Total de platillos tipo C pedidos el miércoles
 *   - DMiercoles : Total de platillos tipo D pedidos el miércoles
 *   - CJueves    : Total de platillos tipo C pedidos el jueves
 *   - DJueves    : Total de platillos tipo D pedidos el jueves
 *   - CViernes   : Total de platillos tipo C pedidos el viernes
 *   - DViernes   : Total de platillos tipo D pedidos el viernes
 * =========================================================
 */
$sql = "Select * from (
Select Area,Fecha,sum(CLunes) as CLunes,sum(DLunes) AS DLunes,sum(CMartes) as CMartes,sum(DMartes) as DMartes,sum(CMiercoles) as CMiercoles,
sum(DMiercoles) as DMiercoles,sum(CJueves) as CJueves,sum(DJueves) as DJueves,sum(CViernes) as CViernes,sum(DViernes) as DViernes from (
Select * from (select Id_Empleado as Empleado,Nombre,Area from [dbo].[Catalogo_EmpArea])  as w
inner join (
Select * from (
Select Id_Empleado,Fecha,
Sum(CLunes) as CLunes,Sum(DLunes) as DLunes,
Sum(CMartes) as CMartes,Sum(DMartes) as DMartes,
Sum(CMiercoles) as CMiercoles,Sum(DMiercoles) as DMiercoles,
Sum(CJueves) as CJueves,Sum(DJueves) as DJueves,
Sum(CViernes) as CViernes,Sum(DViernes) as DViernes
from (
SELECT Fecha,fecha_dia,Id_Empleado,ISNULL(CLunes, 0) as CLunes,ISNULL(DLunes, 0) as DLunes ,ISNULL(CMartes, 0) as CMartes ,
ISNULL(DMartes, 0) as DMartes,ISNULL(CMiercoles, 0)  as CMiercoles,ISNULL(DMiercoles, 0)  as DMiercoles,ISNULL(CJueves, 0) as CJueves,ISNULL(DJueves, 0)  as DJueves
,ISNULL(CViernes, 0) as CViernes,ISNULL(DViernes, 0) as DViernes
FROM  
(
Select * from (
Select * , left(descripcion,1)+D as Clave_Uni from  (

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
Group  by Id_Empleado,Fecha,Viernes
 ) as n
) as f
) AS TableToPivot 
PIVOT  
(  
  SUM(Total)  
  FOR Clave_Uni  IN (CLunes,DLunes,CMartes,DMartes,CMiercoles,DMiercoles,CJueves,DJueves,CViernes,DViernes)  
) AS PivotTable ) as M
Group by Id_Empleado,Fecha) as m ) as Q
on w.Empleado = Q.Id_Empleado ) as r
Group by Area,Fecha) as v";

//////////////////////////////////////////////////////// Declarar Querys

/////////////////////////////////////////////////////////////////////////////Query gráfica mensual
/* =========================================================
 * CONSULTA ANALÍTICA: Totales Mensuales de Pedidos del Año
 * =========================================================
 * Patrón SQL: GROUP BY + CASE WHEN + SUM + left(Fecha,7)
 * Bases de datos: Comedor
 * Tablas: [dbo].[PedidosComida]
 * Descripción:
 *   Calcula el total de platillos pedidos por mes en el año filtrado. Para cada fila
 *   de PedidosComida, convierte cada columna de día (Lunes–Viernes) en un valor binario
 *   (1 si hay pedido, 0 si está vacío) usando CASE WHEN. Luego suma esos valores binarios
 *   por mes agrupando por los primeros 7 caracteres de la fecha (formato YYYY-MM).
 *   Esto permite mostrar la distribución anual de pedidos en el gráfico de pastel donut.
 * Columnas retornadas:
 *   - Fecha : Año-Mes en formato YYYY-MM (primeros 7 caracteres del campo Fecha)
 *   - Total : Suma total de platillos pedidos en ese mes (conteo de días con pedido)
 * =========================================================
 */
$sql1 = "Select * from (
Select left(Fecha,7) as Fecha, Sum(LunesB) + Sum(MartesB) +
 Sum(MiercolesB) + Sum(JuevesB) + Sum(ViernesB)  as Total
from  (
Select Id_empleado,Fecha, LunesB=  case  when Lunes = '' then 0 else 1 end
, MartesB=  case  when Martes = '' then 0 else 1 end
, MiercolesB=  case  when Miercoles = '' then 0 else 1 end
, JuevesB=  case  when Jueves = '' then 0 else 1 end
, ViernesB=  case  when Viernes = '' then 0 else 1 end
from [dbo].[PedidosComida]  ) as a
Group by left(Fecha,7)) AS A";

/////////////////////////////////////////////////////////////////////////////Query gráfica mensual

/* =========================================================
 * CONSULTA ANALÍTICA: Costos de Compras por Semana del Mes
 * =========================================================
 * Patrón SQL: datepart(week,...), CONVERT(date,fecha,103), DATEADD, substring, right, left
 * Bases de datos: Comedor
 * Tablas: [dbo].[Compras_Costos]
 * Descripción:
 *   Enriquece los registros de la tabla Compras_Costos con el número de semana dentro
 *   del mes (Id_Semana = 0..4). El cálculo usa la diferencia entre el número de semana
 *   del año del día actual y el número de semana del primer día del mes.
 *   También extrae componentes de fecha: día (primeros 2 chars), mes (chars 4-5) y año
 *   (últimos 4 chars) asumiendo el formato DD-MM-YYYY. Esta consulta alimenta el gráfico
 *   de barras horizontales apiladas de costos por categoría y semana.
 * Columnas retornadas:
 *   - (todas las columnas originales de Compras_Costos, más:)
 *   - Id_Semana : Número de semana dentro del mes (0-based, ej. 0=primera semana)
 *   - dia       : Día del mes extraído (2 caracteres de la izquierda)
 *   - mes       : Mes extraído (posición 4-5 de la cadena)
 *   - anio      : Año extraído (últimos 4 caracteres)
 *   - (columnas de categorías asumidas): Carnes, Frutas, Verduras, Lacteos, Accesorios
 * =========================================================
 */
$sql4 = "Select * from (Select *,
Id_Semana=datepart(week,CONVERT(date,fecha,103))
- datepart(week, dateadd(dd,-day(CONVERT(date,fecha,103))+1,CONVERT(date,fecha,103))), left(fecha,2) as dia,
substring(fecha,4,2) as mes, right(fecha,4) as anio
from [dbo].[Compras_Costos]) as a";



$stmt4 = sqlsrv_query( $conn,$sql4);


///////////////////////////////////////////////////////////////////////////


$stmt = sqlsrv_query( $conn, $sql );


$stmt1 = sqlsrv_query( $conn, $sql1 );

////////////////////////////////////////////Ejecutar Querys

if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}


///// Array gráfica mensual
$array_grafM1 = [];
$array_grafM2 = [];
///// Array gráfica mensual


// $array_int = [];

// //////////////////////////////Declarar variables
$array_int1 = [];
$array_int2 = [];
$array_int3 = [];
$array_int4 = [];
$array_int5 = [];
$array_int6 = [];
$array_int7 = [];
$array_int8 = [];
$array_int9 = [];
$array_int10 = [];
$array_int11 = [];
$array_int12 = [];

//////////////////////////// While Gráfica 


$array_grafM1 = [];
$array_grafM2 = [];

$array_gastD1 = [];
$array_gastD2 = [];
$array_gastD3 = [];
$array_gastD4 = [];
$array_gastD5 = [];
$array_gastD6 = [];
$array_gastD7 = [];
$array_gastD8 = [];
$array_gastD9 = [];
$array_gastD10 = [];

while( $row = sqlsrv_fetch_array($stmt4, SQLSRV_FETCH_ASSOC) ) {
array_push($array_gastD1,$row['Fecha']);
array_push($array_gastD2,$row['Carnes']);
array_push($array_gastD3,$row['Frutas']);
array_push($array_gastD4,$row['Verduras']);
array_push($array_gastD5,$row['Lacteos']);
array_push($array_gastD6,$row['Accesorios']);
array_push($array_gastD7,$row['Id_Semana']);
array_push($array_gastD8,$row['dia']);
array_push($array_gastD9,$row['mes']);
array_push($array_gastD10,$row['anio']);

}



while( $row = sqlsrv_fetch_array( $stmt1, SQLSRV_FETCH_ASSOC) ) {
array_push($array_grafM1,$row['Fecha']);
array_push($array_grafM2,$row['Total']);
}

while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
	
array_push($array_int1,$row['Area']);
array_push($array_int2,$row['Fecha']);
array_push($array_int3,$row['CLunes']);
array_push($array_int4,$row['DLunes']);
array_push($array_int5,$row['CMartes']);
array_push($array_int6,$row['DMartes']);
array_push($array_int7,$row['CMiercoles']);
array_push($array_int8,$row['DMiercoles']);
array_push($array_int9,$row['CJueves']);
array_push($array_int10,$row['DJueves']);
array_push($array_int11,$row['CViernes']);
array_push($array_int12,$row['DViernes']);
	 
}


sqlsrv_free_stmt( $stmt);


/**
 * Sanitiza y limpia una entrada de formulario.
 *
 * Aplica tres transformaciones secuenciales:
 *   1. trim()             — Elimina espacios al inicio y al final.
 *   2. stripslashes()     — Elimina barras invertidas (protección contra magic quotes).
 *   3. htmlspecialchars() — Convierte caracteres especiales HTML a entidades (prevención XSS).
 *
 * @param string $data Cadena de texto proveniente de un campo de formulario.
 * @return string Cadena sanitizada lista para uso seguro.
 */
function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;

}

?>
  <script type="text/javascript">
  

 var datagastd1 = <?php echo json_encode($array_gastD1);?>;
 var datagastd2 = <?php echo json_encode($array_gastD2);?>;
 var datagastd3 = <?php echo json_encode($array_gastD3);?>;
 var datagastd4 = <?php echo json_encode($array_gastD4);?>;
 var datagastd5 = <?php echo json_encode($array_gastD5);?>;
 var datagastd6 = <?php echo json_encode($array_gastD6);?>;
 var datagastd7 = <?php echo json_encode($array_gastD7);?>;
 var datagastd8 = <?php echo json_encode($array_gastD8);?>;
 var datagastd9 = <?php echo json_encode($array_gastD9);?>;
 var datagastd10 = <?php echo json_encode($array_gastD10);?>;
  

  ////////////////////////// Variables PHP to JS
    // var data = <?php echo json_encode($array_int);?>;
	
 var dataG1 = <?php echo json_encode($array_int1);?>;	
 var dataG2 = <?php echo json_encode($array_int2);?>;
 var dataG3 = <?php echo json_encode($array_int3);?>;
 var dataG4 = <?php echo json_encode($array_int4);?>;
 var dataG5 = <?php echo json_encode($array_int5);?>;
 var dataG6 = <?php echo json_encode($array_int6);?>;
 var dataG7 = <?php echo json_encode($array_int7);?>;
 var dataG8 = <?php echo json_encode($array_int8);?>;
 var dataG9 = <?php echo json_encode($array_int9);?>;
 var dataG10 = <?php echo json_encode($array_int10);?>;
 var dataG11 = <?php echo json_encode($array_int11);?>;
var dataG12 = <?php echo json_encode($array_int12);?>;	


// $array_grafM1 = [];
// $array_grafM2 = [];




 var dGM1  =  <?php echo json_encode($array_grafM1);?>;
 var  dGM2 = <?php echo json_encode($array_grafM2);?>;

//////////////////////////////////////////////////// Function consultas gráficas
/**
 * @function ConsultGraf
 * @description Función principal de visualización. Ejecutada al presionar "Consultar",
 *   lee los selectores Día (x), Mes (x1) y Año (x2) del formulario HTML y filtra los
 *   arrays de datos PHP (pasados como JSON) para el período seleccionado. Luego:
 *   1. Filtra datagastd (costos de compras) por mes, año y número de semana (1–4),
 *      construyendo arrays de costos por categoría (Carnicería, Frutas, Verduras, Lácteos,
 *      Accesorios) para las 4 semanas del mes y un array de totales semanales.
 *   2. Filtra dGM (totales mensuales) por año y distribuye en 12 variables GM1–GM12
 *      (una por mes) para el gráfico de pastel donut mensual.
 *   3. Filtra dataG (pedidos por área y día) comparando la fecha construida (YYYY-MM-DD)
 *      e itera sobre áreas predefinidas (Administración, Licitaciones, Talento humano,
 *      Gestión de proyectos, Finanzas y Contabilidad, C.A. de Bacrocorp, Operaciones,
 *      Asistente de dirección) para construir los arrays por área con totales Lunes–Viernes.
 *   4. Agrega filas a la DataTable #example con: Área, Fecha, Total platillos, costo ($30/platillo).
 *   5. Renderiza cuatro gráficos ECharts:
 *      - #main1   : Barras horizontales apiladas (costos de compras por semana y categoría)
 *      - #main2   : Pastel donut (platillos servidos por mes en el año seleccionado)
 *      - #main    : Pastel (participación de cada área en la semana seleccionada)
 *      - #container: Barras agrupadas por área y día (Lunes–Viernes)
 * @returns {void}
 */
function ConsultGraf() {
	
	////////////////////////////////// Variables html
  var x = document.getElementById("Semana").value;	
  var x1 = document.getElementById("Mes").value;
  var x2 = document.getElementById("Anio").value;
  ////////////////////////////////// Variables html
		
////////////////////////////////////////////////////Array javascript áreas
var area = [];
var admi = [];
var operaciones = [];
var licitaciones = [];
var TI = [];
var Compras = [];
var RH = [];

////////////////////////////////////////////////////Array javascript áreas


///////////////////////////////////////////////////////// Variables Gráfica mensual
var GM1;
var GM2;
var GM3;
var GM4;
var GM5;
var GM6;
var GM7;
var GM8;
var GM9;
var GM10;
var GM11;
var GM12;


var gastd1 = [];
var gastd2 = [];
var gastd3 = [];
var gastd4 = [];




///////////////////////////////////// SEMANA1
var cargastd4 = [];
var frugastd4 = [];
var vergastd4 = [];
var lacgastd4 = [];
var accegastd4 = [];
//////////////////////////////////// SEMANA1


///////////////////////////////////// SEMANA2
var cargastd3 = [];
var frugastd3 = [];
var vergastd3 = [];
var lacgastd3 = [];
var accegastd3 = [];
//////////////////////////////////// SEMANA2

///////////////////////////////////// SEMANA3
var cargastd2 = [];
var frugastd2 = [];
var vergastd2 = [];
var lacgastd2 = [];
var accegastd2 = [];
//////////////////////////////////// SEMANA3


///////////////////////////////////// SEMANA4
var cargastd1 = [];
var frugastd1 = [];
var vergastd1 = [];
var lacgastd1 = [];
var accegastd1 = [];
//////////////////////////////////// SEMANA4


var total = [];

var vat=0;

for (var i = 0; i < datagastd1.length; i++) {

 
if ( x1 === datagastd9[i] && x2 === datagastd10[i] && datagastd7[i] === 1 ) {
	
total.push(parseFloat(datagastd2[i])+parseFloat(datagastd3[i])+parseFloat(datagastd4[i])+parseFloat(datagastd5[i])+parseFloat(datagastd6[i]));
cargastd1.push(datagastd2[i]);
frugastd1.push(datagastd3[i]);
vergastd1.push(datagastd4[i]);
lacgastd1.push(datagastd5[i]);
accegastd1.push(datagastd6[i]);
vat = 1

}


if (x1 === datagastd9[i] && x2 === datagastd10[i] && datagastd7[i] === 2 ) {
	total.push(parseFloat(datagastd2[i])+parseFloat(datagastd3[i])+parseFloat(datagastd4[i])+parseFloat(datagastd5[i])+parseFloat(datagastd6[i]));
cargastd1.push(datagastd2[i]);
frugastd1.push(datagastd3[i]);
vergastd1.push(datagastd4[i]);
lacgastd1.push(datagastd5[i]);
accegastd1.push(datagastd6[i]);
vat = 2
}


if (x1 === datagastd9[i] && x2 === datagastd10[i] && datagastd7[i] === 3 ) {
	total.push(parseFloat(datagastd2[i])+parseFloat(datagastd3[i])+parseFloat(datagastd4[i])+parseFloat(datagastd5[i])+parseFloat(datagastd6[i]));
cargastd1.push(datagastd2[i]);
frugastd1.push(datagastd3[i]);
vergastd1.push(datagastd4[i]);
lacgastd1.push(datagastd5[i]);
accegastd1.push(datagastd6[i]);
vat = 3
}
 

// alert(vat)


if ( x1 === datagastd9[i] && x2 === datagastd10[i] && datagastd7[i] === 4 && vat=== 0) {	
////////////////////////////////alert(datagastd7[i])
total.push('','','',parseFloat(datagastd2[i])+parseFloat(datagastd3[i])+parseFloat(datagastd4[i])+parseFloat(datagastd5[i])+parseFloat(datagastd6[i]));
cargastd1.push('','','',datagastd2[i]);
frugastd1.push('','','',datagastd3[i]);
vergastd1.push('','','',datagastd4[i]);
lacgastd1.push('','','',datagastd5[i]);
accegastd1.push('','','',datagastd6[i]);
} else if ( x1 === datagastd9[i] && x2 === datagastd10[i] && datagastd7[i] === 4 ) {	
////////////////////////////////alert(datagastd7[i])
total.push(parseFloat(datagastd2[i])+parseFloat(datagastd3[i])+parseFloat(datagastd4[i])+parseFloat(datagastd5[i])+parseFloat(datagastd6[i]));
cargastd1.push(datagastd2[i]);
frugastd1.push(datagastd3[i]);
vergastd1.push(datagastd4[i]);
lacgastd1.push(datagastd5[i]);
accegastd1.push(datagastd6[i]);
}
	
	}

 
 for (var i = 0; i < dGM1.length; i++) {

if ( x2 === dGM1[i].substring(0, 4)) {	


if ( dGM1[i].substring(5,7) === '01') {	
GM1= dGM2[i]
}
if ( dGM1[i].substring(5,7) === '02') {	
GM2=dGM2[i]
}
if ( dGM1[i].substring(5,7) === '03') {	
GM3=dGM2[i]
}
if ( dGM1[i].substring(5,7) === '04') {	
GM4=dGM2[i]
}
if ( dGM1[i].substring(5,7) === '05') {	
GM5=dGM2[i]
}
if ( dGM1[i].substring(5,7) === '06') {	
GM6=dGM2[i]
}
if ( dGM1[i].substring(5,7) === '07') {	
GM7=dGM2[i]
}

if ( dGM1[i].substring(5,7) === '08') {	
GM8=dGM2[i]
}

if ( dGM1[i].substring(5,7) === '09') {	
GM9=dGM2[i]
}

if ( dGM1[i].substring(5,7) === '10') {	
GM10=dGM2[i]
}

if ( dGM1[i].substring(5,7) === '11') {	
GM11=dGM2[i]
}

if ( dGM1[i].substring(5,7) === '12') {	
GM12=dGM2[i]
}

}
}

var Admi = [];
var Lici = [];
var TH = [];
var GP = [];
var FC = [];
var CB = [];
var Operar = [];
var AD = [];
	
   var t = $('#example').DataTable();
  
    var counter = 0;
	
for (var i = 0; i < dataG1.length; i++) {

if ( dataG1[i] === 'Administración' && (x2+'-'+x1+'-'+x) ===  dataG2[i]) {	

Admi.push(dataG3[i]+dataG4[i],dataG5[i]+dataG6[i],dataG7[i]+dataG8[i],dataG9[i]+dataG10[i],dataG11[i]+dataG12[i]);
}
if ( dataG1[i] === 'Licitaciones' && (x2+'-'+x1+'-'+x) ===  dataG2[i]) {	
Lici.push(dataG3[i]+dataG4[i],dataG5[i]+dataG6[i],dataG7[i]+dataG8[i],dataG9[i]+dataG10[i],dataG11[i]+dataG12[i]);
}
if ( dataG1[i] === 'Talento humano' && (x2+'-'+x1+'-'+x) ===  dataG2[i]) {	
////alert('Entro')
TH.push(dataG3[i]+dataG4[i],dataG5[i]+dataG6[i],dataG7[i]+dataG8[i],dataG9[i]+dataG10[i],dataG11[i]+dataG12[i]);
}
if ( dataG1[i] === 'Gestión de proyectos' && (x2+'-'+x1+'-'+x) ===  dataG2[i]) {	
GP.push(dataG3[i]+dataG4[i],dataG5[i]+dataG6[i],dataG7[i]+dataG8[i],dataG9[i]+dataG10[i],dataG11[i]+dataG12[i]);
}
if ( dataG1[i] === 'Finanzas y Contabilidad' && (x2+'-'+x1+'-'+x) ===  dataG2[i]) {	
FC.push(dataG3[i]+dataG4[i],dataG5[i]+dataG6[i],dataG7[i]+dataG8[i],dataG9[i]+dataG10[i],dataG11[i]+dataG12[i]);
}
if ( dataG1[i] === 'C. A. de Bacrocorp' && (x2+'-'+x1+'-'+x) ===  dataG2[i]) {	
CB.push(dataG3[i]+dataG4[i],dataG5[i]+dataG6[i],dataG7[i]+dataG8[i],dataG9[i]+dataG10[i],dataG11[i]+dataG12[i]);
}
if ( dataG1[i] === 'Operaciones' && (x2+'-'+x1+'-'+x) ===  dataG2[i]) {	
Operar.push(dataG3[i]+dataG4[i],dataG5[i]+dataG6[i],dataG7[i]+dataG8[i],dataG9[i]+dataG10[i],dataG11[i]+dataG12[i]);
}
if ( dataG1[i] === 'Asistente de dirección' && (x2+'-'+x1+'-'+x) ===  dataG2[i]) {	
AD.push(dataG3[i]+dataG4[i],dataG5[i]+dataG6[i],dataG7[i]+dataG8[i],dataG9[i]+dataG10[i],dataG11[i]+dataG12[i]);
}


if ( (x2+'-'+x1+'-'+x) ===  dataG2[i]) {	
t.row.add([dataG1[i],dataG2[i],dataG3[i]+dataG4[i]+dataG5[i]+dataG6[i]+dataG7[i]+dataG8[i]+dataG9[i]+dataG10[i]+dataG11[i]+dataG12[i],'$'+((dataG3[i]+dataG4[i]+dataG5[i]+dataG6[i]+dataG7[i]+dataG8[i]+dataG9[i]+dataG10[i]+dataG11[i]+dataG12[i])* 30)]).draw(false); 
} 	


}


// alert(TH)



const sumAdmi = Admi.reduce((partialSum, a) => partialSum + a, 0);
const sumlici = Lici.reduce((partialSum, a) => partialSum + a, 0);
const sumTH = TH.reduce((partialSum, a) => partialSum + a, 0);
const sumGP = GP.reduce((partialSum, a) => partialSum + a, 0);
const sumFC = FC.reduce((partialSum, a) => partialSum + a, 0);
const sumCB = CB.reduce((partialSum, a) => partialSum + a, 0);
const sumOperar = Operar.reduce((partialSum, a) => partialSum + a, 0);
const sumAD = AD.reduce((partialSum, a) => partialSum + a, 0);


 var demototal;

demototal = sumAdmi+sumlici+sumTH+sumGP+sumFC+sumCB+sumOperar+sumAD;


// alert(demototal)


var chartDom = document.getElementById('main1');
var myChart = echarts.init(chartDom);
var option;

option = {
  tooltip: {
    trigger: 'axis',
    axisPointer: {
      // Use axis to trigger tooltip
      type: 'shadow' // 'shadow' as default; can also be 'line' or 'shadow'
    }
  },
  legend: {},
  grid: {
    left: '3%',
    right: '4%',
    bottom: '3%',
    containLabel: true
  },
  xAxis: {
    type: 'value',
	 axisLabel: {
        formatter: '${value}'
      }
  },
  yAxis: {
    type: 'category',
    data: ['Semana 1','Semana 2','Semana 3', 'Semana4']
  },
  series: [
    {
      name: 'Carnicería',
      type: 'bar',
      stack: 'total',
      label: {
        show: true,
		formatter: function(d) {
		// Create our number formatter.
const formatter = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',

});
        return  formatter.format(d.data);
      }
      },
      emphasis: {
        focus: 'series'
      },
      data: cargastd1
    },
    {
      name: 'Frutas',
      type: 'bar',
      stack: 'total',
      label: {
        show: true	,formatter: function(d) {
		// Create our number formatter.
const formatter = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',

});
        return  formatter.format(d.data);
      }
      },
      emphasis: {
        focus: 'series'
      },
      data:  frugastd1
    },
    {
      name: 'Verduras',
      type: 'bar',
      stack: 'total',
      label: {
        show: true,formatter: function(d) {
		// Create our number formatter.
const formatter = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',
});
        return  formatter.format(d.data);
      }
      },
      emphasis: {
        focus: 'series'
      },
      data: vergastd1
    },
    {
      name: 'Lácteos',
      type: 'bar',
      stack: 'total',
      label: {
        show: true,formatter: function(d) {
		// Create our number formatter.
const formatter = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',

});
        return  formatter.format(d.data);
      }
      },
      emphasis: {
        focus: 'series'
      },
      data: lacgastd1
    },
    {
      name: 'Accesorios',
      type: 'bar',
      stack: 'total',
      label: {
        show: true,formatter: function(d) {
		// Create our number formatter.
const formatter = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',
});
        return  formatter.format(d.data);
      }
      },
      emphasis: {
        focus: 'series'
      },
      data: accegastd1
    },
	{
      name: 'Total',
      type: 'line',
      stack: 'total',
      label: {
        show: true,formatter: function(d) {
		// Create our number formatter.
const formatter = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',

  // These options are needed to round to whole numbers if that's what you want.
  //minimumFractionDigits: 0, // (this suffices for whole numbers, but will print 2500.10 as $2,500.1)
  //maximumFractionDigits: 0, // (causes 2500.99 to be printed as $2,501)
});
        return  formatter.format(d.data);
      }
      },
      emphasis: {
        focus: 'series'
      },
      data:total
    }
  ]
};
option && myChart.setOption(option);

var chartDom = document.getElementById('main2');
var myChart = echarts.init(chartDom);
var option;

option = {
  tooltip: {
    trigger: 'item'
  },
  legend: {
    top: '5%',
    left: 'center'
  },
  series: [
    {
      name: 'Total de platillos servidos',
      type: 'pie',
      radius: ['40%', '70%'],
      avoidLabelOverlap: false,
      itemStyle: {
        borderRadius: 10,
        borderColor: '#fff',
        borderWidth: 2
      },
      label: {
        show: false,
        position: 'center'
      },
      emphasis: {
        label: {
          show: true,
          fontSize: 40,
          fontWeight: 'bold'
        }
      },
      labelLine: {
        show: false
      },
      data: [
        { value: GM1, name: 'Enero'},
        { value: GM2, name: 'Febrero'},
        { value: GM3, name: 'Marzo'},
        { value: GM4, name: 'Abril'},
        { value: GM5, name: 'Mayo'},
		{ value: GM6, name: 'Junio'},
		{ value: GM7, name: 'Julio'},
		{ value: GM8, name: 'Agosto'},
		{ value: GM9, name: 'Septiembre'},
		{ value: GM10, name: 'Octubre'},
		{ value: GM11, name: 'Noviembre'},
		{ value: GM12, name: 'Diciembre'},
      ]
    }
  ]
};

option && myChart.setOption(option);


var chartDom = document.getElementById('main');
var myChart = echarts.init(chartDom);
var option;

option = {
  title: {
    text: 'Personal',
    subtext: 'Datos semanales',
    left: 'center'
  },
  tooltip: {
    trigger: 'item'
  },
  legend: {
    orient: 'vertical',
    left: 'left'
  },
  series: [
    {
      name: 'Datos comedor',
      type: 'pie',
      radius: '50%',
      data: [
  
        { value: sumAdmi, name: 'Administración' },
        { value: sumlici, name: 'Licitaciones' },
        { value: sumTH, name: 'Talento humano' },
        { value: sumGP, name: 'Gestión de proyectos' },
        { value: sumFC, name: 'Finanzas y Contabilidad' },
		{ value: sumCB, name: 'C. A. de Bacrocorp'},
		{ value: sumOperar, name: 'Operaciones'},
		{ value: sumAD, name: 'Asistente de dirección'},
      ],
      emphasis: {
        itemStyle: {
          shadowBlur: 10,
          shadowOffsetX: 0,
          shadowColor: 'rgba(0, 0, 0, 0.5)'
        }
      }
    }
  ]
};

option && myChart.setOption(option);
  

    var dom = document.getElementById('container');
    var myChart = echarts.init(dom, null, {
      renderer: 'canvas',
      useDirtyRect: false
    });
    var app = {};
    
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
    data: ['Administración', 'Licitaciones', 'Talento humano', 'Gestión de proyectos','Finanzas y Contabilidad','C. A. de Bacrocorp','Operaciones','Asistente de dirección','Total']
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
      data: ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes']
    }
  ],
  yAxis: [
    {
      type: 'value'
    }
  ],
  series: [    {
      name: 'Administración',
      type: 'bar',
      barGap: 0,
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: Admi
    },
    {
      name: 'Licitaciones',
      type: 'bar',
      barGap: 0,
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: Lici
    },
    {
      name: 'Talento humano',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: TH
    },
    {
      name: 'Gestión de proyectos',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: GP
    },
    {
      name: 'Finanzas y Contabilidad',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: FC
    },
    {
      name: 'C. A. de Bacrocorp',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: CB
    },{
      name: 'Operaciones',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: Operar
    },{
      name: 'Asistente de dirección',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: AD
    },{
      name: (demototal*30)+' MXN',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data:  ['', '', '', '',demototal] 
    }
  ]
};

    if (option && typeof option === 'object') {
      myChart.setOption(option);
    }

    window.addEventListener('resize', myChart.resize);
	
	/////

}

var chartDom = document.getElementById('main');
var myChart = echarts.init(chartDom);
var option;

option = {
  title: {
    text: 'Personal',
    subtext: 'Datos semanales',
    left: 'center'
  },
  tooltip: {
    trigger: 'item'
  },
  legend: {
    orient: 'vertical',
    left: 'left'
  },
  series: [
    {
      name: 'Datos comedor',
      type: 'pie',
      radius: '50%',
      data: [
        { value: '', name: 'Administración' },
        { value: '', name: 'Licitaciones' },
        { value: '', name: 'Talento humano' },
        { value: '', name: 'Gestión de proyectos' },
        { value: '', name: 'Finanzas y Contabilidad' },
		{ value: '', name: 'C. A. de Bacrocorp' },
		{ value: '', name: 'Operaciones'},
	    { value: '', name: 'Asistente de dirección'},
      ],
      emphasis: {
        itemStyle: {
          shadowBlur: 10,
          shadowOffsetX: 0,
          shadowColor: 'rgba(0, 0, 0, 0.5)'
        }
      }
    }
  ]
};

option && myChart.setOption(option);
  
  ////////////////////////////////////////////////////////////// gráfica 2
    var dom = document.getElementById('container');
    var myChart = echarts.init(dom, null, {
      renderer: 'canvas',
      useDirtyRect: false
    });
    var app = {};
    
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
		,
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
    data: ['Administración', 'Licitaciones', 'Talento humano', 'Gestión de proyectos','Finanzas y Contabilidad','C. A. de Bacrocorp','Operaciones','Asistente de dirección']
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
      data: ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes']
    }
  ],
  yAxis: [
    {
      type: 'value'
    }
  ],
  series: [
  {
      name: 'Administración',
      type: 'bar',
      barGap: 0,
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: []
    },
    {
      name: 'Licitaciones',
      type: 'bar',
      barGap: 0,
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: []
    },
    {
      name: 'Talento humano',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: []
    },
    {
      name: 'Gestión de proyectos',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: []
    },
    {
      name: 'Finanzas y Contabilidad',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: []
    } ,
    {
      name: 'C. A. de Bacrocorp',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: []
    } ,
    {
      name: 'Operaciones',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: []
    },
    {
      name: '',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: []
    }
  ]
};

    if (option && typeof option === 'object') {
      myChart.setOption(option);
    }

    window.addEventListener('resize', myChart.resize);
	
	/////
	
	//////////////////////////////////////////////////////////////////////////////////////////////// gráfica 3
var chartDom = document.getElementById('main1');
var myChart = echarts.init(chartDom);
var option;

option = {
  tooltip: {
    trigger: 'axis',
    axisPointer: {
      // Use axis to trigger tooltip
      type: 'shadow' // 'shadow' as default; can also be 'line' or 'shadow'
    }
  },
  legend: {},
  grid: {
    left: '3%',
    right: '4%',
    bottom: '3%',
    containLabel: true
  },
  xAxis: {
    type: 'value',
	 axisLabel: {
        formatter: '${value}'
      }
  },
  yAxis: {
    type: 'category',
    data: ['Semana 1','Semana 2','Semana 3','Semana 4']
  },
  series: [
    {
      name: 'Carnicería',
      type: 'bar',
      stack: 'total',
      label: {
        show: true,
		formatter: function(d) {
		// Create our number formatter.
const formatter = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',

});
        return  formatter.format(d.data);
      }
      },
      emphasis: {
        focus: 'series'
      },
      data: [320, 302, 301, 334, 390, 330, 320]
    },
    {
      name: 'Frutas',
      type: 'bar',
      stack: 'total',
      label: {
        show: true	,formatter: function(d) {
		// Create our number formatter.
const formatter = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',

});
        return  formatter.format(d.data);
      }
      },
      emphasis: {
        focus: 'series'
      },
      data: [120, 132, 101, 134, 90, 230, 210]
    },
    {
      name: 'Verduras',
      type: 'bar',
      stack: 'total',
      label: {
        show: true,formatter: function(d) {

const formatter = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',

});
        return  formatter.format(d.data);
      }
      },
      emphasis: {
        focus: 'series'
      },
      data: [220, 182, 191, 234, 290, 330, 310]
    },
    {
      name: 'Lácteos',
      type: 'bar',
      stack: 'total',
      label: {
        show: true,formatter: function(d) {
const formatter = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',
});
        return  formatter.format(d.data);
      }
      },
      emphasis: {
        focus: 'series'
      },
      data: [150, 212, 201, 154, 190, 330, 410]
    },
    {
      name: 'Accesorios',
      type: 'bar',
      stack: 'total',
      label: {
        show: true,formatter: function(d) {
		// Create our number formatter.
const formatter = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',

});
        return  formatter.format(d.data);
      }
      },
      emphasis: {
        focus: 'series'
      },
      data: [820, 832, 901, 934, 1290, 1330, 1320]
    }
  ]
};
option && myChart.setOption(option);



var chartDom = document.getElementById('main2');
var myChart = echarts.init(chartDom);
var option;

option = {
  tooltip: {
    trigger: 'item'
  },
  legend: {
    top: '5%',
    left: 'center'
  },
  series: [
    {
      name: 'Total de platillos servidos',
      type: 'pie',
      radius: ['40%', '70%'],
      avoidLabelOverlap: false,
      itemStyle: {
        borderRadius: 10,
        borderColor: '#fff',
        borderWidth: 2
      },
      label: {
        show: false,
        position: 'center'
      },
      emphasis: {
        label: {
          show: true,
          fontSize: 40,
          fontWeight: 'bold'
        }
      },
      labelLine: {
        show: false
      },
      data: [
        { value: '', name: 'Enero'},
        { value: '', name: 'Febrero'},
        { value: '', name: 'Marzo'},
        { value: '', name: 'Abril'},
        { value: '', name: 'Mayo'},
		{ value: '', name: 'Junio'},
		{ value: '', name: 'Julio'},
		{ value: '', name: 'Agosto'},
		{ value: '', name: 'Septiembre'},
		{ value: '', name: 'Octubre'},
		{ value: '', name: 'Noviembre'},
		{ value: '', name: 'Diciembre'},
      ]
    }
  ]
};

option && myChart.setOption(option);

	
  </script>





<script>



/**
 * @function Borrar
 * @description Limpia todas las filas de la DataTable #example antes de cargar nuevos
 *   resultados de consulta. Invocada junto con ConsultGraf() al presionar "Consultar",
 *   evita la acumulación de datos de consultas anteriores en la tabla.
 * @returns {void}
 */
function Borrar() {
var table = $('#example').DataTable();

table.clear()
}
var dataSet = [];
 
$(document).ready(function () {
    $('#example').DataTable({
        data: dataSet,
        columns: [
            { title: 'Área' },
            { title: 'Fecha' },
            { title: 'Total_Platillos' },
            { title: 'Total' }
        ],
    });
});

</script>