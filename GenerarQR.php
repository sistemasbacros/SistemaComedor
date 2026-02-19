<!--
 @file GenerarQR.php
 @brief Módulo de generación de códigos QR personales para check-in en comedor.

 @description
 Permite a los empleados de BacroCorp generar su código QR personal para
 registrar su asistencia al servicio de comedor. El usuario selecciona los
 parámetros de su consumo (fecha, tipo de comida, nombre y credenciales) y
 el sistema valida contra los datos precargados desde la base de datos para
 generar el QR únicamente si existe un pedido registrado para esa combinación.
 La generación del código QR es completamente del lado del cliente mediante
 la librería QRious.js; no se realiza ninguna petición al servidor en el momento
 de la generación. El QR codifica: nombre del empleado, tipo de comida y fecha.
 El código generado puede descargarse como imagen PNG.

 @module Módulo de Códigos QR
 @access Empleados con credenciales válidas registradas en PedidosComida

 @dependencies
   PHP:
   - sqlsrv (extensión MSSQL para PHP)
   - config/database.php (NO utilizado — conexión hardcodeada, pendiente de migrar)
   JavaScript CDN:
   - jQuery 3.5.1 (https://code.jquery.com/jquery-3.5.1.js)
   - DataTables 1.13.4 (https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js)
   - ECharts 5.4.2 (https://fastly.jsdelivr.net/npm/echarts@5.4.2/dist/echarts.min.js)
   - QRious 4.0.2 (https://unpkg.com/qrious@4.0.2/dist/qrious.js)
   - XLSX 0.15.1 (https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js)
   - Font Awesome 4.7.0 (https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css)

 @database
   Servidor: DESAROLLO-BACRO\SQLEXPRESS (hardcodeado — pendiente migrar a .env)
   Base de datos: Comedor
   Tablas:
   - [dbo].[PedidosComida]   — Pedidos semanales por empleado (Lunes a Viernes)
   - [dbo].[Catalogo_EmpArea] — Catálogo de empleados y áreas
   Operaciones: SELECT (consulta de pedidos usando UNION ALL por día de semana)

 @session
   Variables: No utiliza variables de sesión PHP (autenticación local en JS)

 @workflow
   1. PHP ejecuta al cargar la página: consulta todos los pedidos activos
      de la semana con credenciales y los serializa como arrays PHP.
   2. Los arrays PHP se pasan a JavaScript mediante json_encode().
   3. El usuario selecciona Mes, Día, Año, Tipo de comida e ingresa
      su Nombre completo, Usuario y Contraseña en el formulario HTML.
   4. Al presionar "Aceptar", la función generar() busca en los arrays
      JS una coincidencia exacta de fecha, nombre, tipo de comida y credenciales.
   5. Si hay coincidencia, QRious genera el código QR en el elemento <img>.
   6. Si no hay coincidencia, muestra una alerta de error al usuario.
   7. El botón "Descargar" permite guardar el QR como archivo Demo.png.

 @inputs
   HTML Form (client-side):
   - #Mes      (select) — Mes del consumo (01–12)
   - #Semana   (select) — Día del mes (01–31)
   - #Anio     (select) — Año del consumo (2023–2030)
   - #Comida   (select) — Tipo de consumo: Desayuno | Comida
   - #fname    (text)   — Nombre completo del empleado
   - #Usuar    (text)   — Usuario del empleado
   - #contrase (password) — Contraseña del empleado

 @outputs
   Imagen QR generada en canvas mediante QRious.js (formato PNG descargable).
   Formato del texto codificado en el QR:
   "[Nombre] se encuentra registrado para el [TipoComida] con fecha de [YYYY-MM-DD]"

 @security
   - La validación de credenciales se realiza del lado del cliente comparando
     contra arrays JS inyectados desde PHP (esquema de seguridad débil —
     las credenciales quedan expuestas en el HTML renderizado).
   - La función test_input() aplica trim, stripslashes y htmlspecialchars.
   - ADVERTENCIA: La conexión a BD usa credenciales hardcodeadas. Pendiente
     migrar a variables de entorno usando config/database.php.
   - No hay verificación de sesión activa en este módulo.

 @author Equipo Tecnología BacroCorp
 @version 1.0
 @since 2024
 @updated 2026-02-18
-->
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
	
  <button onclick="generar();">Aceptar</button>
  <br>	
   <a href="GenerarQRNuevoRegistro.php">Registrarse</a>.
  <br>
<div style="height:70%;width:70%;float: left;">
	<img alt="Código QR" id="codigo">
	<button id="btnDescargar">Descargar</button>
</div>
<div class="img-container"> <!-- Block parent element -->
    <img src="Logo2.png" width="200" height="200"> </div>
</body>
</html>
<?php
/**
 * @section Procesamiento del servidor — Precarga de pedidos para validación cliente
 * @brief Consulta todos los pedidos registrados en PedidosComida y los exporta
 *        como variables JavaScript para que la función generar() pueda validar
 *        credenciales y existencia de pedido completamente del lado del cliente.
 *
 * @details
 * La consulta principal aplica UNION ALL sobre las columnas de días de la semana
 * (Lunes, Martes, Miércoles, Jueves, Viernes) para transponer la estructura semanal
 * de PedidosComida a filas individuales por día. Se hace LEFT JOIN con Catalogo_EmpArea
 * para obtener datos adicionales del empleado.
 *
 * Los resultados se almacenan en 8 arrays paralelos indexados:
 * - $array_Camp1 → Id_Empleado
 * - $array_Camp2 → Nombre
 * - $array_Camp3 → Fecha (semana del pedido)
 * - $array_Camp4 → fecha_dia (fecha exacta del consumo día a día)
 * - $array_Camp5 → descripcion (tipo/nombre de platillo)
 * - $array_Camp6 → Total (conteo de registros)
 * - $array_Camp7 → Usuario (credencial de acceso)
 * - $array_Camp8 → Contrasena (contraseña del empleado)
 *
 * @uses getComedorConnection() Conexión centralizada desde config/database.php.
 * @warning Los datos de Usuario y Contrasena se envían al cliente en texto plano
 *          dentro de variables JavaScript, lo que representa un riesgo de seguridad.
 *
 * @uses sqlsrv_connect()     Establece conexión con SQL Server
 * @uses sqlsrv_query()       Ejecuta la consulta SQL
 * @uses sqlsrv_fetch_array() Itera sobre el resultado fila por fila
 * @uses sqlsrv_free_stmt()   Libera el statement de memoria
 * @uses json_encode()        Serializa arrays PHP para inyección en JavaScript
 */

require_once __DIR__ . '/config/database.php';
$conn = getComedorConnection();


$sql = "Select t.Id_Empleado as Id,Nombre,Fecha,ltrim(rtrim(cast(fecha_dia as char) )) as fecha_dia,descripcion,Total,Usuario,Contrasena from (
Select Id_Empleado,Fecha,convert(date,fecha_dia, 101) as fecha_dia,descripcion,Total,Usuario,Contrasena  from (
Select Id_Empleado,Fecha,Lunes as descripcion ,left(DATEADD(day, 0, Fecha),12)  as fecha_dia,Count(Lunes) as Total,D=Month(Fecha),D1='1',Usuario,Contrasena from [dbo].[PedidosComida] 
Where  not Lunes = ''
Group  by Id_Empleado,Fecha,Lunes,Usuario,Contrasena
union all
Select Id_Empleado,Fecha,Martes as descripcion,left(DATEADD(day, 1, Fecha),12) as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1='2',Usuario,Contrasena from [dbo].[PedidosComida] 
Where  not Martes  = ''
Group  by Id_Empleado,Fecha,Martes,Usuario,Contrasena
union all
Select Id_Empleado,Fecha,Miercoles as descripcion,left(DATEADD(day, 2, Fecha),12) as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1='3',Usuario,Contrasena from [dbo].[PedidosComida] 
Where  not Miercoles = ''
Group  by Id_Empleado,Fecha,Miercoles,Usuario,Contrasena
union all
Select Id_Empleado,Fecha,Jueves as descripcion,left(DATEADD(day, 3, Fecha),12) as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1='4',Usuario,Contrasena from [dbo].[PedidosComida] 
Where  not Jueves = ''
Group  by Id_Empleado,Fecha,Jueves,Usuario,Contrasena
union all
Select Id_Empleado,Fecha,Viernes as descripcion,left(DATEADD(day, 4, Fecha),12) as fecha_dia ,Count(*) as Total,D=Month(Fecha),D1='5',Usuario,Contrasena from [dbo].[PedidosComida] 
Where  not Viernes = ''
Group  by Id_Empleado,Fecha,Viernes,Usuario,Contrasena) as a) as t
left join 
(Select * from [dbo].[Catalogo_EmpArea]) as t1
on t.Id_Empleado = t1.Id_Empleado";


$stmt = sqlsrv_query( $conn, $sql );


if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}


///////////////////////////////// var array
$array_Camp1 = [];
$array_Camp2 = [];
$array_Camp3 = [];
$array_Camp4 = [];
$array_Camp5 = [];
$array_Camp6 = [];
$array_Camp7 = [];
$array_Camp8 = [];
///////////////////////////////// var array

while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {

// echo $row['Id'];
// echo $row['Nombre'];
// echo $row['Fecha'];
// echo $row['fecha_dia'];
// echo $row['descripcion'];
// echo $row['Total'];

array_push($array_Camp1,$row['Id']);
array_push($array_Camp2,$row['Nombre']);
array_push($array_Camp3,$row['Fecha']);
array_push($array_Camp4,$row['fecha_dia']);
array_push($array_Camp5,$row['descripcion']);
array_push($array_Camp6,$row['Total']);
array_push($array_Camp7,$row['Usuario']);
array_push($array_Camp8,$row['Contrasena']);

}



sqlsrv_free_stmt( $stmt);


/**
 * Sanitiza datos de entrada para prevenir inyecciones y caracteres maliciosos.
 *
 * @param  string $data Cadena de texto a sanitizar.
 * @return string Cadena saneada sin espacios extremos, sin barras invertidas
 *                y con caracteres especiales HTML escapados.
 */
function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

?>

<script type="text/javascript">
/**
 * @section Variables JavaScript — Datos de pedidos inyectados desde PHP
 * @description Arrays paralelos que contienen todos los pedidos de la semana
 *              cargados desde la base de datos al momento de renderizar la página.
 *              Cada índice `i` corresponde a un registro único de consumo por día.
 *
 * @var {Array<string>} datCamp1 - Id_Empleado de cada registro
 * @var {Array<string>} datCamp2 - Nombre completo del empleado
 * @var {Array<string>} datCamp3 - Fecha de la semana del pedido (fecha de registro)
 * @var {Array<string>} datCamp4 - Fecha exacta del día de consumo (YYYY-MM-DD)
 * @var {Array<string>} datCamp5 - Descripción del platillo (tipo de comida)
 * @var {Array<number>} datCamp6 - Conteo total de registros agrupados
 * @var {Array<string>} datCamp7 - Usuario de acceso del empleado
 * @var {Array<string>} datCamp8 - Contraseña del empleado
 */
var datCamp1 = <?php echo json_encode($array_Camp1);?>;
var datCamp2 = <?php echo json_encode($array_Camp2);?>;
var datCamp3 = <?php echo json_encode($array_Camp3);?>;
var datCamp4 = <?php echo json_encode($array_Camp4);?>;
var datCamp5 = <?php echo json_encode($array_Camp5);?>;
var datCamp6 = <?php echo json_encode($array_Camp6);?>;
var datCamp7 = <?php echo json_encode($array_Camp7);?>;
var datCamp8 = <?php echo json_encode($array_Camp8);?>;

	
	


/**
 * @function generar
 * @description Valida los datos del formulario contra los pedidos cargados desde
 *              la base de datos y genera el código QR del empleado si existe
 *              una coincidencia exacta de fecha, nombre, tipo de comida y credenciales.
 *
 * @workflow
 *   1. Lee los valores seleccionados: día (#Semana), mes (#Mes), año (#Anio),
 *      tipo de comida (#Comida), nombre completo (#fname), usuario (#Usuar)
 *      y contraseña (#contrase).
 *   2. Construye la fecha en formato YYYY-MM-DD para comparación.
 *   3. Itera sobre todos los registros en datCamp1..datCamp8.
 *   4. Verifica que la fecha construida coincida con datCamp4[i], el nombre con
 *      datCamp2[i] (quitando el sufijo " (Nombre del platillo)"), el tipo de
 *      comida con datCamp5[i], el usuario con datCamp7[i] y la contraseña con
 *      datCamp8[i].
 *   5. Si hay coincidencia, instancia QRious en el elemento <img id="codigo">
 *      con tamaño 350px, fondo transparente y color verde (#8bc34a).
 *   6. Si ninguna iteración encontró coincidencia (textcod === undefined),
 *      muestra un alert de error al usuario.
 *   7. Configura el evento onclick del botón #btnDescargar para crear un
 *      enlace dinámico que descarga el QR como Demo.png.
 *
 * @returns {void} No retorna valor. Modifica el DOM directamente:
 *                 genera el QR en #codigo y configura el botón #btnDescargar.
 *
 * @sideEffects
 *   - Modifica el atributo src del elemento <img id="codigo"> con el QR generado.
 *   - Asocia un manejador onclick al botón <button id="btnDescargar">.
 *   - Muestra alert() si no se encuentra el registro.
 *
 * @example
 *   // Llamado desde el botón "Aceptar":
 *   <button onclick="generar();">Aceptar</button>
 */
function generar() {
 
  var x = document.getElementById("Semana").value;	
  var x1 = document.getElementById("Mes").value;
  var x2 = document.getElementById("Anio").value;
  var x3 = document.getElementById("fname").value;
  var x4 = document.getElementById("Comida").value;
  var x5 = document.getElementById("Usuar").value;
  var x6 = document.getElementById("contrase").value;
  
  
  // Usuar
  // contrase
  
  
  // alert("Día:"+x)
  // alert("Mes:"+x1)
  // alert("Año:"+x2)
  // alert("Nombre:"+x3)  
// alert(x2+"-"+x1+"-"+x) /////Fecha  
// alert(x3) /// Nombre
// alert(x4)//// Tipo de comida
/////alert(("Comida (Nombre del platillo)").replace(" (Nombre del platillo)", ""))

var textcod;

	const $imagen = document.querySelector("#codigo"),
			$boton = document.querySelector("#btnDescargar");
  
for (var i = 0; i < datCamp1.length; i++) {
	/////alert(datCamp4[i])
	
if ( (x2+"-"+x1+"-"+x) === datCamp4[i]  &&  x3 === datCamp2[i] && x4 === (datCamp5[i].replace(" (Nombre del platillo)", "")) && datCamp7[i]== x5   && datCamp8[i]== x6) {
	////////&& datCamp7[i]== x5   && datCamp8[i]== x6
	textcod= datCamp2[i] + " se encuentra registrado para el " +	datCamp5[i].replace(" (Nombre del platillo)", "") + " con fecha de " + datCamp4[i] 

		new QRious({
			element: $imagen,
			value: textcod, // La URL o el texto
			size: 350,
			backgroundAlpha: 0, // 0 para fondo transparente
			foreground: "#8bc34a", // Color del QR
			level: "H", // Puede ser L,M,Q y H (L es el de menor nivel, H el mayor)
		});
} 
}

// alert(textcod)

 if (textcod === undefined) {
     alert('No se encuentra el registro,para la comida seleccionada!');
  }

$boton.onclick = () => {
			const enlace = document.createElement("a");
			enlace.href = $imagen.src;
			enlace.download = "Demo.png";
			enlace.click();
		}



}
</script>