<!--
 @file Desglosechecador.php
 @brief Desglose de registros de asistencia del checador (lector QR) con filtros por rango de fecha.

 @description
 Módulo de consulta de registros de entrada del Sistema Checador integrado al comedor.
 Extrae y muestra los registros de acceso capturados por el lector QR, permitiendo filtrar
 por rango de fechas (día-mes-año inicial a día-mes-año final). El flujo es:

   1. El sistema ejecuta en PHP la consulta contra la tabla Entradas (registros QR brutos).
      La consulta analítica parsea el campo Nombre (que contiene datos crudos del lector QR
      en dos formatos posibles) para extraer el nombre legible del empleado.
   2. Los datos procesados se serializan a JSON y se pasan como variables JavaScript.
   3. Al hacer clic en "Consultar", la función AgregaCampos() filtra los registros por
      el rango de fechas seleccionado en la UI y construye el dataset de la DataTable.
   4. La DataTable muestra los registros filtrados con columnas: Nombre, Fecha, Entrada.
   5. El usuario puede exportar la tabla filtrada a Excel usando el botón XLSX.js.

 Formatos de datos QR parseados por la consulta SQL:
   - Formato 1 (Nombre empieza con 'i'): Contiene patrón "RE:{nombre_area} A:{datos}"
     → Extrae el nombre de área limpio eliminando prefijo 'RE:' y sufijo ' AREA'.
   - Formato 2 (Nombre empieza con 'N'): Contiene patrón "N.E:{nombre} NSS:{numero}"
     → Extrae el nombre del empleado eliminando 'N.E:' y ' NSS:' con delimitadores ':'.

 @module Módulo de Checador y Control de Asistencias
 @access DIRECCIÓN | RECURSOS HUMANOS | COCINA | ADMINISTRADOR

 @dependencies
 - JS CDN: jQuery 3.5.1, DataTables 1.13.4, ECharts 5.4.2, XLSX.js 0.15.1
 - PHP: sqlsrv (extensión Microsoft SQL Server)

 @database
 - Base de datos: Comedor (DESAROLLO-BACRO\SQLEXPRESS)
 - Tablas:
     * [dbo].[Entradas] — Registros de acceso QR del checador de empleados.
       Columnas relevantes: Nombre (datos crudos QR), Hora_Entrada (hora de entrada), Fecha (fecha del registro)
 - Patrones SQL: CASE WHEN LIKE, CHARINDEX, SUBSTRING, LEFT, LEN, LTRIM, RTRIM, REPLACE

 @analytics
 - Tipo de visualización: Tabla DataTables interactiva con filtrado de fechas cliente-side
 - Métricas calculadas:
     * Registros de entrada por empleado en el rango de fechas seleccionado
     * Filtrado por rango: día/mes/año inicio → día/mes/año fin
 - Período de análisis: Rango libre definido por el usuario (Mes inicio, Día inicio,
   Mes final, Día final, Año)

 @inputs
 - Filtros UI: Mes inicio (01–12), Día inicio (01–31), Mes final (01–12),
   Día final (01–31), Año (2023–2030)
 - Botón: "Consultar" → dispara AgregaCampos()
 - Botón: "Exporta tu tabla a excel" → llama ExportToExcel('xlsx')
 - $_POST campos: Mes, Semana (día inicio), Mesfinal, Semana1 (día fin), Anio

 @outputs
 - Tabla DataTables con columnas: Nombre (empleado), Fecha (hora de entrada), Entrada (fecha)
 - Exportación a Excel (.xlsx) via biblioteca XLSX.js usando XLSX.utils.table_to_book

 @security
 - Función test_input() para sanitización: trim, stripslashes, htmlspecialchars
 - NOTA: Credenciales de base de datos hardcodeadas (pendiente migrar a .env)
 - NOTA: No hay verificación de sesión activa en este módulo

 @author Equipo Tecnología BacroCorp
 @version 1.0
 @since 2024
 @updated 2026-02-18
-->
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
	    <div class="img-container"> <!-- Block parent element -->
    <img src="Logo2.png" width="75" height="50"> </div>
	<table id="example" class="display" width="50%"></table>
   <label for="html">Mes inicio</label>
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
   <label for="html">Día inicio</label>
	<select name="Semana" id="Semana">
	<option value="01">1</option>
	<option value="02">2</option>
	<option value="03">3</option>
	<option value="04">4</option>
	<option value="05">5</option>
	<option value="06">6</option>
	<option value="07">7</option>
	<option value="08">8</option
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
	<select name="Mesfinal" id="Mesfinal">
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
<input type="submit" name="submit" value="Consultar" onclick="AgregaCampos();">		
	
		
			
<button onclick="ExportToExcel('xlsx')">Exporta tu tabla a excel</button>



</body>
</html>


<?php
/**
 * Bloque PHP de carga de datos del checador.
 *
 * Ejecutado en cada carga de página (incluso sin POST). Lee los parámetros del formulario
 * mediante test_input() para prevenir inyecciones, establece conexión a la base de datos
 * Comedor, ejecuta la consulta de parseo de registros QR y población los arrays PHP
 * que luego se serializan a JSON para consumo JavaScript.
 *
 * Variables de entrada ($_POST):
 *   - Mes       → $mesini  : Mes de inicio del filtro
 *   - Mesfinal  → $mesfin  : Mes de fin del filtro
 *   - Semana    → $semana  : Día de inicio del filtro
 *   - Semana1   → $semana1 : Día de fin del filtro
 *   - Anio      → $year    : Año del filtro
 *
 * Arrays poblados para exportación a JS:
 *   - $array_tot1 : Nombres de empleados parseados del QR
 *   - $array_tot2 : Fechas/horas de entrada (Hora_Entrada)
 *   - $array_tot3 : Fechas de los registros (Fecha original)
 *
 * NOTA DE MIGRACIÓN PENDIENTE: Las credenciales de conexión están hardcodeadas.
 * Deben migrarse a config/database.php usando las variables de entorno del archivo .env.
 */
//////onclick="AgregaCampos()"
$mes = $semana = $anio;

 /////echo $_POST["Mes"];

// if ( true === ( isset( $mesini ) ? $mesini : null ) ) {
    // echo "no tiene valor"
// }




///////////////////////////////////////////////////// Variables html
$mesini = test_input($_POST["Mes"]);
$mesfin = test_input($_POST["Mesfinal"]);
$semana = test_input($_POST["Semana"]);	
$semana1 = test_input($_POST["Semana1"]);
$year = test_input($_POST["Anio"]);
///////////////////////////////////////////////////// Variables html

// //////////////////77echo $mesini;
// echo "<br>";
// echo $semana;
// echo "<br>";
// echo $semana1;
// echo "<br>";
// echo $mesini;
// echo "<br>";
// echo $mesfin;
// echo "<br>";
// echo $year;
// echo "<br>";


   
require_once __DIR__ . '/config/database.php';
$conn = getComedorConnection();


/* =========================================================
 * CONSULTA ANALÍTICA: Parseo de Registros QR del Checador para Extracción de Nombres
 * =========================================================
 * Patrón SQL: CASE WHEN LIKE + CHARINDEX + SUBSTRING + LEFT + LEN + LTRIM + RTRIM + REPLACE
 * Bases de datos: Comedor
 * Tablas: [dbo].[Entradas]
 * Descripción:
 *   La tabla Entradas almacena datos crudos del lector QR en el campo Nombre. El contenido
 *   puede venir en dos formatos según el tipo de credencial QR escaneada:
 *
 *   FORMATO 1 — Credencial de área (Nombre inicia con 'i'):
 *     El campo contiene algo como: "iRE:NOMBRE AREA A:datos_adicionales"
 *     Proceso de extracción:
 *       1. SUBSTRING desde la posición de 'RE:' hasta el final del string.
 *       2. LEFT hasta la posición de 'A:' en ese substring.
 *       3. Eliminación de ' AREA' y 'RE:' con REPLACE.
 *       4. LTRIM/RTRIM para eliminar espacios residuales.
 *     Resultado: Nombre del área limpio.
 *
 *   FORMATO 2 — Credencial de empleado (Nombre inicia con 'N'):
 *     El campo contiene algo como: "N:NOMBRE NSS:12345"
 *     Proceso de extracción:
 *       1. SUBSTRING desde posición después del primer ':' hasta el final.
 *       2. LEFT hasta la posición del siguiente ':' en ese substring.
 *       3. Eliminación de 'N.E:' y ' NSS:' con REPLACE.
 *       4. LTRIM/RTRIM para eliminar espacios residuales.
 *     Resultado: Nombre del empleado limpio.
 *
 *   Filtra registros donde Nombre resultante sea NULL (registros no reconocibles).
 *   Renombra: Hora_Entrada → Fecha (hora de entrada), Fecha → Hora (fecha del registro).
 *
 * Columnas retornadas:
 *   - Nombre : Nombre del empleado o área extraído del string QR
 *   - Fecha  : Hora real de entrada (campo Hora_Entrada original)
 *   - Hora   : Fecha del registro de asistencia (campo Fecha original)
 * =========================================================
 */
$sql = "Select * from (
Select Nombre= case when Nombre like 'i%' then ltrim(rtrim(replace(Replace(left(Substring(Nombre,Charindex('RE:',Nombre,1),len(Nombre)-Charindex('RE:',Nombre,1)),
Charindex('A:',Substring(Nombre,Charindex('RE:',Nombre,1),len(Nombre)-Charindex('RE:',Nombre,1)),1)),' AREA',''),'RE:','')))
when Nombre like 'N%' then ltrim(rtrim(replace(replace(left(substring(Nombre,charindex(':',Nombre,1)+1,len(Nombre)-(charindex(':',Nombre,1)+1)),charindex(':',substring(Nombre,charindex(':',Nombre,1)+1,len(Nombre)-(charindex(':',Nombre,1)+1)),1)),'N.E:',''),' NSS:','')))
end   ,
Hora_Entrada as Fecha , Fecha as Hora from Entradas) as A where Nombre is not null";


$stmt = sqlsrv_query( $conn, $sql );
// if( $stmt === false) {
    // die( print_r( sqlsrv_errors(), true) );
// }


$array_tot1 = [];
$array_tot2 = [];
$array_tot3 = [];


while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
array_push($array_tot1 ,$row['Nombre']);
array_push($array_tot2,$row['Fecha']);
array_push($array_tot3 ,$row['Hora']);

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



 //////////////////////////////////////////////////////////// Function javascipt agregar columnas
/**
 * @function AgregaCampos
 * @description Función principal de filtrado y visualización del checador. Ejecutada al
 *   presionar "Consultar", realiza las siguientes operaciones:
 *   1. Lee los selectores de fecha: Mes inicio (e), Mes final (e1), Día inicio (e2),
 *      Día final (e3) y Año (e4) del formulario HTML.
 *   2. Construye las fechas de inicio y fin en formato DD-MM-YYYY.
 *   3. Itera sobre el array de registros QR (dataqu21, dataqu22, dataqu23) comparando
 *      las fechas en formato string (comparación por posición de chars).
 *   4. Maneja dos casos de rango:
 *      - Cuando día inicio > día final (rango cruza fin/inicio de mes): usa OR lógico.
 *      - Cuando día inicio <= día final (rango normal): usa AND lógico.
 *   5. Construye el array dataSet con los registros filtrados [Nombre, Fecha, Hora].
 *   6. Inicializa la DataTable #example con los datos filtrados, configurando la
 *      paginación con máximos de 200 registros por página.
 * @returns {void}
 */
 function  AgregaCampos() {
	 
var dataqu21 = <?php echo json_encode($array_tot1);?>;
var dataqu22 = <?php echo json_encode($array_tot2);?>;
var dataqu23 = <?php echo json_encode($array_tot3);?>;



var e = document.getElementById("Mes").value;
var e1 = document.getElementById("Mesfinal").value;
var e2 = document.getElementById("Semana").value;
var e3 = document.getElementById("Semana1").value;
var e4 = document.getElementById("Anio").value;


fechaini= e2+"-"+e+"-"+e4;

fechafin=e3+"-"+e1+"-"+e4;


// alert(fechaini.substring(0, 2));
// alert(fechaini.substring(3,5));
// alert(fechaini.substring(6,10));


var dataSet = [];

for (var i = 0; i < dataqu22.length; i++) {
	
	
	
// if ((dataqu22[i].substring(0, 2) >= fechaini.substring(0, 2) && dataqu22[i].substring(3,5) >= fechaini.substring(3,5) && dataqu22[i].substring(6,10) >= fechaini.substring(6,10)) &&  (dataqu22[i].substring(0, 2) <= fechafin.substring(0, 2) && dataqu22[i].substring(3,5) <=  fechafin.substring(3,5)&& dataqu22[i].substring(6,10) <=  fechafin.substring(6,10)) ) {
// dataSet.push( [dataqu21[i],dataqu22[i],dataqu23[i]]);
// }


if (e2> e3) {
if ((dataqu22[i].substring(0, 2) >= fechaini.substring(0, 2) && dataqu22[i].substring(3,5) >= fechaini.substring(3,5) && dataqu22[i].substring(6,10) >= fechaini.substring(6,10)) ||  (dataqu22[i].substring(0, 2) <= fechafin.substring(0, 2) && dataqu22[i].substring(3,5) ==  fechafin.substring(3,5)&& dataqu22[i].substring(6,10) ==  fechafin.substring(6,10)) ) {
dataSet.push( [dataqu21[i],dataqu22[i],dataqu23[i]]);
}
} else {if ((dataqu22[i].substring(0, 2) >= fechaini.substring(0, 2) && dataqu22[i].substring(3,5) >= fechaini.substring(3,5) && dataqu22[i].substring(6,10) >= fechaini.substring(6,10)) &&  (dataqu22[i].substring(0, 2) <= fechafin.substring(0, 2) && dataqu22[i].substring(3,5) <=  fechafin.substring(3,5)&& dataqu22[i].substring(6,10) <=  fechafin.substring(6,10)) ) {
dataSet.push( [dataqu21[i],dataqu22[i],dataqu23[i]]);
} } 



}



// alert(dataqu21)	
// alert(dataqu22)
// alert(dataqu23)



new DataTable('#example', {
    columns: [
        { title: 'Nombre ' },
		{ title: 'Fecha' },
		{ title: 'Entrada' },
    ],
    data: dataSet , lengthMenu: [
        [200, -1],
        [200,'All'],
    ]
});
	
	}




// $(document).ready(function () {
	// ////alert(<?php echo json_encode($array_d1);?>)
  // $('#example').DataTable({
        // data: '',
        // columns: [
            // { title: 'Prueba' },
            // { title: 'Prueba1' },
            // { title: '' },
            // { title: '' },
            // { title: '' },
            // { title: '' },
		    // { title: '' },
			// { title: '' },
			// { title: '' },
			// { title: '' },
			// { title: '' },
			// { title: '' },
			// { title: '' },
		    // { title: '' },
		    // { title: '' },
			// { title: '' },
			// { title: '' },
			// { title: '' },
			// { title: '' },
			// { title: '' },			
			// ////{ title: 'COMPLEMENTOS' },
        // ],retrieve: true,
    // });
// });


// function Borrar() {
// var table = $('#example').DataTable();
// table.destroy();
// }
// table.clear()



/**
 * @function ExportToExcel
 * @description Exporta la DataTable #example a un archivo Excel (.xlsx o .xls) usando
 *   la biblioteca XLSX.js. Convierte el elemento de tabla HTML completo en un libro de
 *   trabajo Excel con una sola hoja llamada "sheet1". Si el parámetro dl es true,
 *   retorna el contenido en base64 en lugar de disparar la descarga directa.
 * @param {string} type - Tipo de archivo de salida. Ej: 'xlsx', 'xls', 'csv'
 * @param {string} [fn] - Nombre del archivo de salida (por defecto 'MySheetName.xlsx')
 * @param {boolean} [dl=false] - Si true retorna base64; si false descarga el archivo
 * @returns {string|void} Base64 del archivo si dl=true, void si dl=false
 */
function ExportToExcel(type, fn, dl) {
       var elt = document.getElementById('example');
       var wb = XLSX.utils.table_to_book(elt, { sheet: "sheet1" });
       return dl ?
         XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }):
         XLSX.writeFile(wb, fn || ('MySheetName.' + (type || 'xlsx')));
    }


</script>