<!--
 @file KPI_anacomp.php
 @brief Matriz de indicadores de evaluación para Analistas de Compras de BacroCorp.

 @description
 Este módulo implementa una herramienta interactiva de evaluación de desempeño para el
 equipo de Analistas de Compras. Permite registrar calificaciones en seis criterios clave
 (Asistencia, Requisición, Cuadro comparativo de proveedores, Órdenes de compra,
 Evaluación de proveedores y Reportes de compras semanales), calcular porcentajes de
 cumplimiento respecto a metas predefinidas y visualizar los resultados comparativos
 en un gráfico de barras ECharts. Los datos se pueden exportar a Excel.

 Flujo principal:
   1. El usuario ingresa el nombre e ID del empleado a evaluar y una fecha.
   2. Pulsa "Agregar Columnas" para insertar dinámicamente columnas de evaluación en la tabla.
   3. Completa los campos de número de formatos logrados por criterio.
   4. Las funciones input1–input6 / nueva1–nueva6 calculan el porcentaje en tiempo real.
   5. input7 / nueva6 calculan el promedio total y actualizan el gráfico ECharts.
   6. El usuario puede exportar la tabla final usando "Exporta tu tabla a excel".
   7. Al pulsar "Guardar evaluaciones" se envía el formulario POST al mismo archivo PHP,
      que conecta a la base de datos KPI para persistencia (actualmente comentado).

 @module Módulo de KPIs y Evaluación de Desempeño
 @access DIRECCIÓN | RECURSOS HUMANOS | ADMINISTRADOR

 @dependencies
 - JS CDN: jQuery 3.5.1, DataTables 1.13.4, DataTables Bootstrap, ECharts 5.4.2, XLSX.js 0.15.1
 - PHP: sqlsrv (extensión Microsoft SQL Server)

 @database
 - Base de datos: KPI (DESAROLLO-BACRO\SQLEXPRESS)
 - Tablas: (conexión establecida; queries de inserción/selección comentados)
 - Patrones SQL: SELECT plano; lógica de negocio en JavaScript del lado cliente

 @analytics
 - Tipo de visualización: Gráfico de barras ECharts (calificaciones por analista)
 - Métricas calculadas:
     * Porcentaje de cumplimiento por criterio (logrado / meta * 100)
     * Promedio total de los 6 criterios por analista
 - Período de análisis: Definido por el campo "Birthday" (fecha de evaluación)

 @inputs
 - Campos HTML: fname (nombre del evaluado), fname1 (ID empleado), fecha (fecha evaluación)
 - Botones: "Agregar Columnas" (CreateTable), "Guardar evaluaciones" (POST), "Exporta a Excel"
 - $_POST campos: fname, fname1, fecha (procesados en bloque PHP al final)

 @outputs
 - Tabla DataTables interactiva con criterios y calificaciones por analista
 - Párrafos dinámicos con calificación final por persona
 - Gráfico de barras ECharts comparativo de calificaciones
 - Exportación a Excel via XLSX.js o método tableToExcel

 @security
 - Formulario POST con action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>"
 - Función test_input() para sanitización de entradas: trim, stripslashes, htmlspecialchars
 - NOTA: Credenciales de base de datos hardcodeadas (pendiente migrar a .env)

 @author Equipo Tecnología BacroCorp
 @version 1.0
 @since 2024
 @updated 2026-02-18
-->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">

<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap.min.css" rel="stylesheet">

  <script type="text/javascript" src="https://fastly.jsdelivr.net/npm/echarts@5.4.2/dist/echarts.min.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap.min.js"></script>
<script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>


<style>
table, th, td {
  border: 4px solid gray;
}
table.center {
  margin-left: auto; 
  margin-right: auto;
}

p {
  color: navy;
  text-indent: 8px;
  text-transform: uppercase;
   font-size: 14px;
}
</style>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
  
  <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  
</head>
<body>

<!-- <table id="example1" class="center"> -->
  <!-- <tr> -->
    <!-- <th>Calificación final Linet</th> -->
    <!-- <th>Calificación final Violeta</th> -->
  <!-- </tr> -->
  <!-- <tr> -->
    <!-- <td id="por1" name= "por1"></td> -->
    <!-- <td id="por2" name= "por2"></td> -->
  <!-- </tr> -->
<!-- </table> -->

</body>

<h1 for="Name">MATRIZ DE INDICADORES ANALISTAS DE COMPRAS</h1>   <label for="fname">Persona a evaluar:</label>
  <input type="text" id="fname" name="fname"><br><br>   <label for="fname">Id Empleado:</label>
  <input type="text" id="fname1" name="fname1"><br><br>  <label for="birthday">Birthday:</label>
  <input type="date" id="fecha" name="fecha"><br> <button type="button" onclick="CreateTable()">Agregar Columnas!</button> <button type="submit">Guardar evaluaciones!</button>
  <button onclick="ExportToExcel('xlsx');tableToExcel('example', 'W3C Example Table')">Exporta tu tabla a excel</button>

<br>
<br>
<br>


 
 <table id="example" class="table table-striped table-bordered" style="width:80%">
     <thead>
            <tr>
			    <th>PUNTOS A EVALUAR</th>
                <th>No. De Pruebas</th>

            </tr>
        </thead>
        <tbody>
            <tr>
			<td>Asistencia</td>
                <td id="mytd">30</td>

            </tr>
			<tr>
			<td>Requesición</td>
                <td id="mytd1">41</td>

            </tr>
		<tr>
			<td>Cuadro comparativo de provedores</td>
                <td id="mytd2">41</td>
				</tr>

			<tr>
			<td>Ordenes de compra</td>
                <td id="mytd3">41</td> 
            
</tr>
			<tr>
			<td>Evaluación de provedores</td>
                <td id="mytd4">3</td>
            </tr>
		
	<tr>
			<td>Reportes de compras semanal</td>
                <td id="mytd5">4</td>
            </tr>
			
			<tr>
			<td id="mytd6">Total</td>
                <td></td>
            </tr>
			
				<tr>
			<td id="mytd7">Total meta</td>
                <td>100%</td>
                          </tr>
	
	
			
            <!-- <tr> -->
                <!-- <td>Garrett Winters</td> -->
                <!-- <td><input type="text" id="row-2-age" name="row-2-age" value="63"></td> -->
                <!-- <td><input type="text" id="row-2-position" name="row-2-position" value="Accountant"></td> -->
                <!-- <td><select size="1" id="row-2-office" name="row-2-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo" selected="selected"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Ashton Cox</td> -->
                <!-- <td><input type="text" id="row-3-age" name="row-3-age" value="66"></td> -->
                <!-- <td><input type="text" id="row-3-position" name="row-3-position" value="Junior Technical Author"></td> -->
                <!-- <td><select size="1" id="row-3-office" name="row-3-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco" selected="selected"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Cedric Kelly</td> -->
                <!-- <td><input type="text" id="row-4-age" name="row-4-age" value="22"></td> -->
                <!-- <td><input type="text" id="row-4-position" name="row-4-position" value="Senior Javascript Developer"></td> -->
                <!-- <td><select size="1" id="row-4-office" name="row-4-office"> -->
                    <!-- <option value="Edinburgh" selected="selected"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Airi Satou</td> -->
                <!-- <td><input type="text" id="row-5-age" name="row-5-age" value="33"></td> -->
                <!-- <td><input type="text" id="row-5-position" name="row-5-position" value="Accountant"></td> -->
                <!-- <td><select size="1" id="row-5-office" name="row-5-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo" selected="selected"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Brielle Williamson</td> -->
                <!-- <td><input type="text" id="row-6-age" name="row-6-age" value="61"></td> -->
                <!-- <td><input type="text" id="row-6-position" name="row-6-position" value="Integration Specialist"></td> -->
                <!-- <td><select size="1" id="row-6-office" name="row-6-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York" selected="selected"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Herrod Chandler</td> -->
                <!-- <td><input type="text" id="row-7-age" name="row-7-age" value="59"></td> -->
                <!-- <td><input type="text" id="row-7-position" name="row-7-position" value="Sales Assistant"></td> -->
                <!-- <td><select size="1" id="row-7-office" name="row-7-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco" selected="selected"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Rhona Davidson</td> -->
                <!-- <td><input type="text" id="row-8-age" name="row-8-age" value="55"></td> -->
                <!-- <td><input type="text" id="row-8-position" name="row-8-position" value="Integration Specialist"></td> -->
                <!-- <td><select size="1" id="row-8-office" name="row-8-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo" selected="selected"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Colleen Hurst</td> -->
                <!-- <td><input type="text" id="row-9-age" name="row-9-age" value="39"></td> -->
                <!-- <td><input type="text" id="row-9-position" name="row-9-position" value="Javascript Developer"></td> -->
                <!-- <td><select size="1" id="row-9-office" name="row-9-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco" selected="selected"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Sonya Frost</td> -->
                <!-- <td><input type="text" id="row-10-age" name="row-10-age" value="23"></td> -->
                <!-- <td><input type="text" id="row-10-position" name="row-10-position" value="Software Engineer"></td> -->
                <!-- <td><select size="1" id="row-10-office" name="row-10-office"> -->
                    <!-- <option value="Edinburgh" selected="selected"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Jena Gaines</td> -->
                <!-- <td><input type="text" id="row-11-age" name="row-11-age" value="30"></td> -->
                <!-- <td><input type="text" id="row-11-position" name="row-11-position" value="Office Manager"></td> -->
                <!-- <td><select size="1" id="row-11-office" name="row-11-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London" selected="selected"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Quinn Flynn</td> -->
                <!-- <td><input type="text" id="row-12-age" name="row-12-age" value="22"></td> -->
                <!-- <td><input type="text" id="row-12-position" name="row-12-position" value="Support Lead"></td> -->
                <!-- <td><select size="1" id="row-12-office" name="row-12-office"> -->
                    <!-- <option value="Edinburgh" selected="selected"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Charde Marshall</td> -->
                <!-- <td><input type="text" id="row-13-age" name="row-13-age" value="36"></td> -->
                <!-- <td><input type="text" id="row-13-position" name="row-13-position" value="Regional Director"></td> -->
                <!-- <td><select size="1" id="row-13-office" name="row-13-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco" selected="selected"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Haley Kennedy</td> -->
                <!-- <td><input type="text" id="row-14-age" name="row-14-age" value="43"></td> -->
                <!-- <td><input type="text" id="row-14-position" name="row-14-position" value="Senior Marketing Designer"></td> -->
                <!-- <td><select size="1" id="row-14-office" name="row-14-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London" selected="selected"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Tatyana Fitzpatrick</td> -->
                <!-- <td><input type="text" id="row-15-age" name="row-15-age" value="19"></td> -->
                <!-- <td><input type="text" id="row-15-position" name="row-15-position" value="Regional Director"></td> -->
                <!-- <td><select size="1" id="row-15-office" name="row-15-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London" selected="selected"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Michael Silva</td> -->
                <!-- <td><input type="text" id="row-16-age" name="row-16-age" value="66"></td> -->
                <!-- <td><input type="text" id="row-16-position" name="row-16-position" value="Marketing Designer"></td> -->
                <!-- <td><select size="1" id="row-16-office" name="row-16-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London" selected="selected"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Paul Byrd</td> -->
                <!-- <td><input type="text" id="row-17-age" name="row-17-age" value="64"></td> -->
                <!-- <td><input type="text" id="row-17-position" name="row-17-position" value="Chief Financial Officer (CFO)"></td> -->
                <!-- <td><select size="1" id="row-17-office" name="row-17-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York" selected="selected"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Gloria Little</td> -->
                <!-- <td><input type="text" id="row-18-age" name="row-18-age" value="59"></td> -->
                <!-- <td><input type="text" id="row-18-position" name="row-18-position" value="Systems Administrator"></td> -->
                <!-- <td><select size="1" id="row-18-office" name="row-18-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York" selected="selected"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Bradley Greer</td> -->
                <!-- <td><input type="text" id="row-19-age" name="row-19-age" value="41"></td> -->
                <!-- <td><input type="text" id="row-19-position" name="row-19-position" value="Software Engineer"></td> -->
                <!-- <td><select size="1" id="row-19-office" name="row-19-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London" selected="selected"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Dai Rios</td> -->
                <!-- <td><input type="text" id="row-20-age" name="row-20-age" value="35"></td> -->
                <!-- <td><input type="text" id="row-20-position" name="row-20-position" value="Personnel Lead"></td> -->
                <!-- <td><select size="1" id="row-20-office" name="row-20-office"> -->
                    <!-- <option value="Edinburgh" selected="selected"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Jenette Caldwell</td> -->
                <!-- <td><input type="text" id="row-21-age" name="row-21-age" value="30"></td> -->
                <!-- <td><input type="text" id="row-21-position" name="row-21-position" value="Development Lead"></td> -->
                <!-- <td><select size="1" id="row-21-office" name="row-21-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York" selected="selected"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Yuri Berry</td> -->
                <!-- <td><input type="text" id="row-22-age" name="row-22-age" value="40"></td> -->
                <!-- <td><input type="text" id="row-22-position" name="row-22-position" value="Chief Marketing Officer (CMO)"></td> -->
                <!-- <td><select size="1" id="row-22-office" name="row-22-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York" selected="selected"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Caesar Vance</td> -->
                <!-- <td><input type="text" id="row-23-age" name="row-23-age" value="21"></td> -->
                <!-- <td><input type="text" id="row-23-position" name="row-23-position" value="Pre-Sales Support"></td> -->
                <!-- <td><select size="1" id="row-23-office" name="row-23-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York" selected="selected"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Doris Wilder</td> -->
                <!-- <td><input type="text" id="row-24-age" name="row-24-age" value="23"></td> -->
                <!-- <td><input type="text" id="row-24-position" name="row-24-position" value="Sales Assistant"></td> -->
                <!-- <td><select size="1" id="row-24-office" name="row-24-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Angelica Ramos</td> -->
                <!-- <td><input type="text" id="row-25-age" name="row-25-age" value="47"></td> -->
                <!-- <td><input type="text" id="row-25-position" name="row-25-position" value="Chief Executive Officer (CEO)"></td> -->
                <!-- <td><select size="1" id="row-25-office" name="row-25-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London" selected="selected"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Gavin Joyce</td> -->
                <!-- <td><input type="text" id="row-26-age" name="row-26-age" value="42"></td> -->
                <!-- <td><input type="text" id="row-26-position" name="row-26-position" value="Developer"></td> -->
                <!-- <td><select size="1" id="row-26-office" name="row-26-office"> -->
                    <!-- <option value="Edinburgh" selected="selected"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Jennifer Chang</td> -->
                <!-- <td><input type="text" id="row-27-age" name="row-27-age" value="28"></td> -->
                <!-- <td><input type="text" id="row-27-position" name="row-27-position" value="Regional Director"></td> -->
                <!-- <td><select size="1" id="row-27-office" name="row-27-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Brenden Wagner</td> -->
                <!-- <td><input type="text" id="row-28-age" name="row-28-age" value="28"></td> -->
                <!-- <td><input type="text" id="row-28-position" name="row-28-position" value="Software Engineer"></td> -->
                <!-- <td><select size="1" id="row-28-office" name="row-28-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco" selected="selected"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Fiona Green</td> -->
                <!-- <td><input type="text" id="row-29-age" name="row-29-age" value="48"></td> -->
                <!-- <td><input type="text" id="row-29-position" name="row-29-position" value="Chief Operating Officer (COO)"></td> -->
                <!-- <td><select size="1" id="row-29-office" name="row-29-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco" selected="selected"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Shou Itou</td> -->
                <!-- <td><input type="text" id="row-30-age" name="row-30-age" value="20"></td> -->
                <!-- <td><input type="text" id="row-30-position" name="row-30-position" value="Regional Marketing"></td> -->
                <!-- <td><select size="1" id="row-30-office" name="row-30-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo" selected="selected"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Michelle House</td> -->
                <!-- <td><input type="text" id="row-31-age" name="row-31-age" value="37"></td> -->
                <!-- <td><input type="text" id="row-31-position" name="row-31-position" value="Integration Specialist"></td> -->
                <!-- <td><select size="1" id="row-31-office" name="row-31-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Suki Burks</td> -->
                <!-- <td><input type="text" id="row-32-age" name="row-32-age" value="53"></td> -->
                <!-- <td><input type="text" id="row-32-position" name="row-32-position" value="Developer"></td> -->
                <!-- <td><select size="1" id="row-32-office" name="row-32-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London" selected="selected"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Prescott Bartlett</td> -->
                <!-- <td><input type="text" id="row-33-age" name="row-33-age" value="27"></td> -->
                <!-- <td><input type="text" id="row-33-position" name="row-33-position" value="Technical Author"></td> -->
                <!-- <td><select size="1" id="row-33-office" name="row-33-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London" selected="selected"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Gavin Cortez</td> -->
                <!-- <td><input type="text" id="row-34-age" name="row-34-age" value="22"></td> -->
                <!-- <td><input type="text" id="row-34-position" name="row-34-position" value="Team Leader"></td> -->
                <!-- <td><select size="1" id="row-34-office" name="row-34-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco" selected="selected"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Martena Mccray</td> -->
                <!-- <td><input type="text" id="row-35-age" name="row-35-age" value="46"></td> -->
                <!-- <td><input type="text" id="row-35-position" name="row-35-position" value="Post-Sales support"></td> -->
                <!-- <td><select size="1" id="row-35-office" name="row-35-office"> -->
                    <!-- <option value="Edinburgh" selected="selected"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Unity Butler</td> -->
                <!-- <td><input type="text" id="row-36-age" name="row-36-age" value="47"></td> -->
                <!-- <td><input type="text" id="row-36-position" name="row-36-position" value="Marketing Designer"></td> -->
                <!-- <td><select size="1" id="row-36-office" name="row-36-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco" selected="selected"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Howard Hatfield</td> -->
                <!-- <td><input type="text" id="row-37-age" name="row-37-age" value="51"></td> -->
                <!-- <td><input type="text" id="row-37-position" name="row-37-position" value="Office Manager"></td> -->
                <!-- <td><select size="1" id="row-37-office" name="row-37-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco" selected="selected"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Hope Fuentes</td> -->
                <!-- <td><input type="text" id="row-38-age" name="row-38-age" value="41"></td> -->
                <!-- <td><input type="text" id="row-38-position" name="row-38-position" value="Secretary"></td> -->
                <!-- <td><select size="1" id="row-38-office" name="row-38-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco" selected="selected"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Vivian Harrell</td> -->
                <!-- <td><input type="text" id="row-39-age" name="row-39-age" value="62"></td> -->
                <!-- <td><input type="text" id="row-39-position" name="row-39-position" value="Financial Controller"></td> -->
                <!-- <td><select size="1" id="row-39-office" name="row-39-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco" selected="selected"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Timothy Mooney</td> -->
                <!-- <td><input type="text" id="row-40-age" name="row-40-age" value="37"></td> -->
                <!-- <td><input type="text" id="row-40-position" name="row-40-position" value="Office Manager"></td> -->
                <!-- <td><select size="1" id="row-40-office" name="row-40-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London" selected="selected"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Jackson Bradshaw</td> -->
                <!-- <td><input type="text" id="row-41-age" name="row-41-age" value="65"></td> -->
                <!-- <td><input type="text" id="row-41-position" name="row-41-position" value="Director"></td> -->
                <!-- <td><select size="1" id="row-41-office" name="row-41-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York" selected="selected"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Olivia Liang</td> -->
                <!-- <td><input type="text" id="row-42-age" name="row-42-age" value="64"></td> -->
                <!-- <td><input type="text" id="row-42-position" name="row-42-position" value="Support Engineer"></td> -->
                <!-- <td><select size="1" id="row-42-office" name="row-42-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Bruno Nash</td> -->
                <!-- <td><input type="text" id="row-43-age" name="row-43-age" value="38"></td> -->
                <!-- <td><input type="text" id="row-43-position" name="row-43-position" value="Software Engineer"></td> -->
                <!-- <td><select size="1" id="row-43-office" name="row-43-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London" selected="selected"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Sakura Yamamoto</td> -->
                <!-- <td><input type="text" id="row-44-age" name="row-44-age" value="37"></td> -->
                <!-- <td><input type="text" id="row-44-position" name="row-44-position" value="Support Engineer"></td> -->
                <!-- <td><select size="1" id="row-44-office" name="row-44-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo" selected="selected"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Thor Walton</td> -->
                <!-- <td><input type="text" id="row-45-age" name="row-45-age" value="61"></td> -->
                <!-- <td><input type="text" id="row-45-position" name="row-45-position" value="Developer"></td> -->
                <!-- <td><select size="1" id="row-45-office" name="row-45-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York" selected="selected"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Finn Camacho</td> -->
                <!-- <td><input type="text" id="row-46-age" name="row-46-age" value="47"></td> -->
                <!-- <td><input type="text" id="row-46-position" name="row-46-position" value="Support Engineer"></td> -->
                <!-- <td><select size="1" id="row-46-office" name="row-46-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco" selected="selected"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Serge Baldwin</td> -->
                <!-- <td><input type="text" id="row-47-age" name="row-47-age" value="64"></td> -->
                <!-- <td><input type="text" id="row-47-position" name="row-47-position" value="Data Coordinator"></td> -->
                <!-- <td><select size="1" id="row-47-office" name="row-47-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Zenaida Frank</td> -->
                <!-- <td><input type="text" id="row-48-age" name="row-48-age" value="63"></td> -->
                <!-- <td><input type="text" id="row-48-position" name="row-48-position" value="Software Engineer"></td> -->
                <!-- <td><select size="1" id="row-48-office" name="row-48-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York" selected="selected"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Zorita Serrano</td> -->
                <!-- <td><input type="text" id="row-49-age" name="row-49-age" value="56"></td> -->
                <!-- <td><input type="text" id="row-49-position" name="row-49-position" value="Software Engineer"></td> -->
                <!-- <td><select size="1" id="row-49-office" name="row-49-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco" selected="selected"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Jennifer Acosta</td> -->
                <!-- <td><input type="text" id="row-50-age" name="row-50-age" value="43"></td> -->
                <!-- <td><input type="text" id="row-50-position" name="row-50-position" value="Junior Javascript Developer"></td> -->
                <!-- <td><select size="1" id="row-50-office" name="row-50-office"> -->
                    <!-- <option value="Edinburgh" selected="selected"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Cara Stevens</td> -->
                <!-- <td><input type="text" id="row-51-age" name="row-51-age" value="46"></td> -->
                <!-- <td><input type="text" id="row-51-position" name="row-51-position" value="Sales Assistant"></td> -->
                <!-- <td><select size="1" id="row-51-office" name="row-51-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York" selected="selected"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Hermione Butler</td> -->
                <!-- <td><input type="text" id="row-52-age" name="row-52-age" value="47"></td> -->
                <!-- <td><input type="text" id="row-52-position" name="row-52-position" value="Regional Director"></td> -->
                <!-- <td><select size="1" id="row-52-office" name="row-52-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London" selected="selected"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Lael Greer</td> -->
                <!-- <td><input type="text" id="row-53-age" name="row-53-age" value="21"></td> -->
                <!-- <td><input type="text" id="row-53-position" name="row-53-position" value="Systems Administrator"></td> -->
                <!-- <td><select size="1" id="row-53-office" name="row-53-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London" selected="selected"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Jonas Alexander</td> -->
                <!-- <td><input type="text" id="row-54-age" name="row-54-age" value="30"></td> -->
                <!-- <td><input type="text" id="row-54-position" name="row-54-position" value="Developer"></td> -->
                <!-- <td><select size="1" id="row-54-office" name="row-54-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco" selected="selected"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Shad Decker</td> -->
                <!-- <td><input type="text" id="row-55-age" name="row-55-age" value="51"></td> -->
                <!-- <td><input type="text" id="row-55-position" name="row-55-position" value="Regional Director"></td> -->
                <!-- <td><select size="1" id="row-55-office" name="row-55-office"> -->
                    <!-- <option value="Edinburgh" selected="selected"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Michael Bruce</td> -->
                <!-- <td><input type="text" id="row-56-age" name="row-56-age" value="29"></td> -->
                <!-- <td><input type="text" id="row-56-position" name="row-56-position" value="Javascript Developer"></td> -->
                <!-- <td><select size="1" id="row-56-office" name="row-56-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
            <!-- <tr> -->
                <!-- <td>Donna Snider</td> -->
                <!-- <td><input type="text" id="row-57-age" name="row-57-age" value="27"></td> -->
                <!-- <td><input type="text" id="row-57-position" name="row-57-position" value="Customer Support"></td> -->
                <!-- <td><select size="1" id="row-57-office" name="row-57-office"> -->
                    <!-- <option value="Edinburgh"> -->
                        <!-- Edinburgh -->
                    <!-- </option> -->
                    <!-- <option value="London"> -->
                        <!-- London -->
                    <!-- </option> -->
                    <!-- <option value="New York" selected="selected"> -->
                        <!-- New York -->
                    <!-- </option> -->
                    <!-- <option value="San Francisco"> -->
                        <!-- San Francisco -->
                    <!-- </option> -->
                    <!-- <option value="Tokyo"> -->
                        <!-- Tokyo -->
                    <!-- </option> -->
                <!-- </select></td> -->
            <!-- </tr> -->
        </tbody>
        <!-- <tfoot> -->
            <!-- <tr> -->
                <!-- <th>Name</th> -->
                <!-- <th>Age</th> -->
                <!-- <th>Position</th> -->
                <!-- <th>Office</th> -->
            <!-- </tr> -->
        <!-- </tfoot> -->
    </table>
	
	<br>
	
	<div  id="myDIV" class="myDIV">

</div>
	
	  <div id="container" style="height:60%;width: 60%;position: absolute;" ></div>
		<iframe id="txtArea1" style="display:none"></iframe>
	
	</form>
	
	<script>
	$('#example').DataTable();
	
	</script>


<script>

 var data123=[];
 var nombre=[];
 
 ///////////////Array 1
 
 <!-- Asistencia -->
 <!-- Cuadro comparativo de provedores -->
 <!-- Evaluación de provedores -->
 <!-- Ordenes de compra -->
 <!-- Reportes de compras semanal -->
 <!-- Requesición -->
 
 var C=[];
 var C1=[];  <!-- Asistencia -->
 var C2=[];  <!-- Cuadro comparativo de provedores -->
 var C3=[];  <!-- Evaluación de provedores -->
 var C4=[];  <!-- Ordenes de compra -->
 var C5=[];  <!-- Reportes de compras semanal -->
 var C6=[];   <!-- Requesición -->
 
  var C7=[];   <!-- Total -->
  var C8=[];   <!-- Total meta -->
 
C.push({ title:'PUNTOS A EVALUAR'},{ title:'No. De Pruebas'})
C1.push('Asistencia','30')
C2.push('Cuadro comparativo de provedores','41')
C3.push('Evaluación de provedores','3')
C4.push('Ordenes de compra','41')
C5.push('Reportes de compras semanal','4')
C6.push('Requesición','41')
C7.push('Total','')
C8.push('Total meta','100%')
 
 
 
 
/**
 * @function upperCase
 * @description Calcula el porcentaje de cumplimiento del criterio "Asistencia" para la
 *   primera columna de evaluación (Evaluado 1). Divide el valor ingresado entre la meta
 *   definida en la celda mytd y muestra el resultado en porcentaje.
 * @returns {void}
 */
function upperCase() {
  <!-- const x = document.getElementById("row-1-age"); -->
  <!-- x.value = x.value.toUpperCase(); -->
  document.getElementById("row-1-position").value = Math.round((parseFloat(document.getElementById("row-1-age").value)*100)/ parseFloat(document.getElementById("mytd").innerText)) +'%'
}

/**
 * @function upperCase2
 * @description Calcula el porcentaje de cumplimiento del criterio "Cuadro comparativo
 *   de proveedores" para la primera columna de evaluación. Meta referenciada en mytd2.
 * @returns {void}
 */
function upperCase2() {
document.getElementById("row-1-position2").value = Math.round((parseFloat(document.getElementById("row-1-age2").value)*100)/ parseFloat(document.getElementById("mytd2").innerText)) +'%'
}

/**
 * @function upperCase3
 * @description Calcula el porcentaje de cumplimiento del criterio "Evaluación de
 *   proveedores" para la primera columna de evaluación. Meta referenciada en mytd4.
 * @returns {void}
 */
function upperCase3() {
document.getElementById("row-1-position4").value = Math.round((parseFloat(document.getElementById("row-1-age4").value)*100)/ parseFloat(document.getElementById("mytd4").innerText)) +'%'
}

/**
 * @function upperCase4
 * @description Calcula el porcentaje de cumplimiento del criterio "Órdenes de compra"
 *   para la primera columna de evaluación. Meta referenciada en mytd3.
 * @returns {void}
 */
function upperCase4() {
document.getElementById("row-1-position3").value = Math.round((parseFloat(document.getElementById("row-1-age3").value)*100)/ parseFloat(document.getElementById("mytd3").innerText)) +'%'
}

/**
 * @function upperCase5
 * @description Calcula el porcentaje de cumplimiento del criterio "Reportes de compras
 *   semanal" para la primera columna de evaluación. Meta referenciada en mytd5.
 * @returns {void}
 */
function upperCase5() {
document.getElementById("row-1-position5").value = Math.round((parseFloat(document.getElementById("row-1-age5").value)*100)/ parseFloat(document.getElementById("mytd5").innerText)) +'%'
}

/**
 * @function upperCase6
 * @description Calcula el porcentaje de cumplimiento del criterio "Requisición" para
 *   la primera columna de evaluación. Meta referenciada en mytd1.
 * @returns {void}
 */
function upperCase6() {
document.getElementById("row-1-position1").value = Math.round((parseFloat(document.getElementById("row-1-age1").value)*100)/ parseFloat(document.getElementById("mytd1").innerText)) +'%'

}

/**
 * @function upperCase7
 * @description Calcula el promedio total de los 6 criterios de evaluación (calificación
 *   global) para la primera persona evaluada. Suma los 6 porcentajes, divide entre 6,
 *   redondea al entero más cercano y actualiza tanto el campo row-1-positionT como el
 *   elemento por1 (visible en pantalla como indicador de calificación final).
 * @returns {void}
 */
function upperCase7() {
<!-- document.getElementById("row-1-position1").value = Math.round((parseFloat(document.getElementById("row-1-age1").value)*100)/ parseFloat(document.getElementById("mytd1").innerText)) +'%' -->

/////alert((Math.round((parseFloat(document.getElementById("row-1-position").value.replace("%", "")) +parseFloat(document.getElementById("row-1-position2").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position4").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position3").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position5").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position1").value.replace("%", "")))/6))+'%')

document.getElementById("row-1-positionT").value = (Math.round((parseFloat(document.getElementById("row-1-position").value.replace("%", "")) +parseFloat(document.getElementById("row-1-position2").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position4").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position3").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position5").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position1").value.replace("%", "")))/6))+'%' 

document.getElementById("por1").innerHTML = (Math.round((parseFloat(document.getElementById("row-1-position").value.replace("%", "")) +parseFloat(document.getElementById("row-1-position2").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position4").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position3").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position5").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position1").value.replace("%", "")))/6))+'%' 

}


////// funciones input 1
/**
 * @function input1
 * @description Calcula el porcentaje de cumplimiento de "Asistencia" para la segunda
 *   columna de evaluación (Evaluado 2). Lee el campo "segunda" y la meta de mytd.
 * @returns {void}
 */
function input1() {
document.getElementById("tercera").value = Math.round((parseFloat(document.getElementById("segunda").value)*100)/ parseFloat(document.getElementById("mytd").innerText)) +'%'
}
////// funciones input 1

///// funciones input 2
/**
 * @function input2
 * @description Calcula el porcentaje de cumplimiento de "Cuadro comparativo de
 *   proveedores" para la segunda columna de evaluación. Lee "segunda2" y la meta de mytd2.
 * @returns {void}
 */
function input2() {
document.getElementById("tercer2").value = Math.round((parseFloat(document.getElementById("segunda2").value)*100)/ parseFloat(document.getElementById("mytd2").innerText)) +'%'
}
///// funciones input 2

///// funciones input 3
/**
 * @function input3
 * @description Calcula el porcentaje de cumplimiento de "Evaluación de proveedores"
 *   para la segunda columna de evaluación. Lee "segunda4" y la meta de mytd4.
 * @returns {void}
 */
function input3() {
///alert('Hola')
//document.getElementById("tercer4").value =  "Hola"
document.getElementById("tercer4").value = Math.round((parseFloat(document.getElementById("segunda4").value)*100)/ parseFloat(document.getElementById("mytd4").innerText)) +'%'
}
///// funciones input 3


///// funciones input 4
/**
 * @function input4
 * @description Calcula el porcentaje de cumplimiento de "Órdenes de compra" para la
 *   segunda columna de evaluación. Lee "segunda3" y la meta de mytd3.
 * @returns {void}
 */
function input4() {
///alert('Hola')
//document.getElementById("tercer4").value =  "Hola"
document.getElementById("tercer3").value = Math.round((parseFloat(document.getElementById("segunda3").value)*100)/ parseFloat(document.getElementById("mytd3").innerText)) +'%'
}
///// funciones input 4


///// funciones input 5
/**
 * @function input5
 * @description Calcula el porcentaje de cumplimiento de "Reportes de compras semanal"
 *   para la segunda columna de evaluación. Lee "segund5" y la meta de mytd5.
 * @returns {void}
 */
function input5() {
///alert('Hola')
//document.getElementById("tercer4").value =  "Hola"
document.getElementById("tercer5").value = Math.round((parseFloat(document.getElementById("segund5").value)*100)/ parseFloat(document.getElementById("mytd5").innerText)) +'%'
}
///// funciones input 5

///// funciones input 6
/**
 * @function input6
 * @description Calcula el porcentaje de cumplimiento de "Requisición" para la segunda
 *   columna de evaluación. Lee "segunda1" y la meta de mytd1.
 * @returns {void}
 */
function input6() {
///alert('Hola')
//document.getElementById("tercer4").value =  "Hola"
document.getElementById("tercer1").value = Math.round((parseFloat(document.getElementById("segunda1").value)*100)/ parseFloat(document.getElementById("mytd1").innerText)) +'%'
}
///// funciones input 7

/**
 * @function input7
 * @description Calcula el promedio total de los 6 criterios para la segunda persona
 *   evaluada (Evaluado 2). Suma los 6 porcentajes tercer1–tercer5 y tercera, divide
 *   entre 6 y actualiza el campo row-1-positionT1234 y el elemento por2.
 * @returns {void}
 */
function input7() {
document.getElementById("row-1-positionT1234").value = Math.round((parseFloat(document.getElementById("tercer1").value.replace("%", ""))+parseFloat(document.getElementById("tercer5").value.replace("%", ""))+parseFloat(document.getElementById("tercer3").value.replace("%", ""))+parseFloat(document.getElementById("tercer4").value.replace("%", ""))+parseFloat(document.getElementById("tercer2").value.replace("%", ""))+parseFloat(document.getElementById("tercera").value.replace("%", "")))/6)+'%'

document.getElementById("por2").innerHTML = Math.round((parseFloat(document.getElementById("tercer1").value.replace("%", ""))+parseFloat(document.getElementById("tercer5").value.replace("%", ""))+parseFloat(document.getElementById("tercer3").value.replace("%", ""))+parseFloat(document.getElementById("tercer4").value.replace("%", ""))+parseFloat(document.getElementById("tercer2").value.replace("%", ""))+parseFloat(document.getElementById("tercera").value.replace("%", "")))/6)+'%'

}
///// funciones input 7
////(Math.round((parseFloat(document.getElementById("row-1-position").value.replace("%", "")) +parseFloat(document.getElementById("row-1-position2").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position4").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position3").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position5").value.replace("%", ""))+parseFloat(document.getElementById("row-1-position1").value.replace("%", "")))/6))+'%' 


////////////////////////////Funciones nuevos campos
/**
 * @function nueva1
 * @description Calcula el porcentaje de cumplimiento de "Asistencia" para una columna
 *   de evaluación agregada dinámicamente. Usa el nombre del evaluado (fname) como sufijo
 *   para identificar los elementos de input (p1{nombre}) y resultado (pT1{nombre}).
 * @returns {void}
 */
function nueva1() {
var x6 = document.getElementById("fname").value;
///alert(x6)
document.getElementById("pT1"+x6).value = Math.round((parseFloat(document.getElementById("p1"+x6).value)*100)/ parseFloat(document.getElementById("mytd").innerText)) +'%'
}

////// Función 2
/**
 * @function nueva2
 * @description Calcula el porcentaje de cumplimiento de "Cuadro comparativo de proveedores"
 *   para una columna dinámica. Identifica los campos mediante el nombre del evaluado (fname).
 * @returns {void}
 */
function nueva2() {
var x6 = document.getElementById("fname").value;
document.getElementById("pT2"+x6).value = Math.round((parseFloat(document.getElementById("p2"+x6).value)*100)/ parseFloat(document.getElementById("mytd2").innerText)) +'%'
}

/**
 * @function nueva3
 * @description Calcula el porcentaje de cumplimiento de "Evaluación de proveedores"
 *   para una columna dinámica. Meta referenciada en mytd4.
 * @returns {void}
 */
function nueva3() {
var x6 = document.getElementById("fname").value;
document.getElementById("pT3"+x6).value = Math.round((parseFloat(document.getElementById("p3"+x6).value)*100)/ parseFloat(document.getElementById("mytd4").innerText)) +'%'
}

/**
 * @function nueva4
 * @description Calcula el porcentaje de cumplimiento de "Órdenes de compra" para
 *   una columna dinámica. Meta referenciada en mytd3.
 * @returns {void}
 */
function nueva4() {
var x6 = document.getElementById("fname").value;
document.getElementById("pT4"+x6).value = Math.round((parseFloat(document.getElementById("p4"+x6).value)*100)/ parseFloat(document.getElementById("mytd3").innerText)) +'%'
}

/**
 * @function nueva5
 * @description Calcula el porcentaje de cumplimiento de "Reportes de compras semanal"
 *   para una columna dinámica. Meta referenciada en mytd5.
 * @returns {void}
 */
function nueva5() {
var x6 = document.getElementById("fname").value;
document.getElementById("pT5"+x6).value = Math.round((parseFloat(document.getElementById("p5"+x6).value)*100)/ parseFloat(document.getElementById("mytd5").innerText)) +'%'
}

/**
 * @function nueva6
 * @description Calcula el porcentaje de cumplimiento de "Requisición" para una columna
 *   dinámica, luego calcula el promedio total de los 6 criterios, actualiza los campos
 *   pT7 (promedio), pT8 / p8 (meta 100%), registra la calificación en el array de datos
 *   global, agrega un párrafo visible con el resultado y llama a myFunctionG() para
 *   redibujar el gráfico ECharts con la nueva persona incluida.
 * @returns {void}
 */
function nueva6() {
var x6 = document.getElementById("fname").value;
document.getElementById("pT6"+x6).value = Math.round((parseFloat(document.getElementById("p6"+x6).value)*100)/ parseFloat(document.getElementById("mytd1").innerText)) +'%'

document.getElementById("pT7"+x6).value = Math.round((parseFloat(document.getElementById("pT1"+x6).value.replace("%", ""))+parseFloat(document.getElementById("pT2"+x6).value.replace("%", ""))+parseFloat(document.getElementById("pT3"+x6).value.replace("%", ""))+parseFloat(document.getElementById("pT4"+x6).value.replace("%", ""))+parseFloat(document.getElementById("pT5"+x6).value.replace("%", ""))+parseFloat(document.getElementById("pT6"+x6).value.replace("%", "")))/6)+'%'

document.getElementById("pT8"+x6).value = "100%"

document.getElementById("p8"+x6).value = "100%"

C.push({ title:'No. De Pruebas'},{ title:x6})
C1.push(document.getElementById("p1"+x6).value,document.getElementById("pT1"+x6).value)
C2.push(document.getElementById("p2"+x6).value,document.getElementById("pT2"+x6).value)
C3.push(document.getElementById("p3"+x6).value,document.getElementById("pT3"+x6).value)
C4.push(document.getElementById("p4"+x6).value,document.getElementById("pT4"+x6).value)
C5.push(document.getElementById("p5"+x6).value,document.getElementById("pT5"+x6).value)
C6.push(document.getElementById("p6"+x6).value,document.getElementById("pT6"+x6).value)
C7.push('',document.getElementById("pT7"+x6).value)
C8.push('100%','100%')




 <!-- var C1=[];  Asistencia -->
 <!-- var C2=[];  <!-- Cuadro comparativo de provedores --> -->
 <!-- var C3=[];  <!-- Evaluación de provedores --> -->
 <!-- var C4=[];  <!-- Ordenes de compra --> -->
 <!-- var C5=[];  <!-- Reportes de compras semanal --> -->
 <!-- var C6=[];   <!-- Requesición --> -->

//////////////////////////////
<!-- var para = document.createElement("p"); -->
<!-- para.innerText = "Calificación de "+x6+ ':'+document.getElementById("pT7"+x6).value; -->
<!-- document.getElementById("myDIV").appendChild(para); -->
<!-- document.body.appendChild(para); -->


var para = document.createElement("p");
var node = document.createTextNode("Calificación de "+x6+ ':'+document.getElementById("pT7"+x6).value);

para.appendChild(node);
document.getElementById("myDIV").appendChild(para);



nombre.push(x6)
data123.push(document.getElementById("pT7"+x6).value.replace("%", ""))

myFunctionG();

}

///// Función 2

/////////////////////////77  Continuar  el proximo lunes 10 de julio/2023
<!-- function ncampos1() { -->
<!-- document.getElementById("pT1").value = Math.round((parseFloat(document.getElementById("p1").value)*100)/ parseFloat(document.getElementById("mytd").innerText)) +'%' -->
<!-- } -->
<!-- function ncampos2() { -->
<!-- document.getElementById("pT1").value = Math.round((parseFloat(document.getElementById("p1").value)*100)/ parseFloat(document.getElementById("mytd").innerText)) +'%' -->
<!-- } -->
<!-- function ncampos3() { -->
<!-- document.getElementById("pT1").value = Math.round((parseFloat(document.getElementById("p1").value)*100)/ parseFloat(document.getElementById("mytd").innerText)) +'%' -->
<!-- } -->
<!-- function ncampos4() { -->
<!-- document.getElementById("pT1").value = Math.round((parseFloat(document.getElementById("p1").value)*100)/ parseFloat(document.getElementById("mytd").innerText)) +'%' -->
<!-- } -->
<!-- function ncampos5() { -->
<!-- document.getElementById("pT1").value = Math.round((parseFloat(document.getElementById("p1").value)*100)/ parseFloat(document.getElementById("mytd").innerText)) +'%' -->
<!-- } -->

////////////////////////////Funciones nuevos campos 

</script>



  <script type="text/javascript">  
  

  
  /**
   * @function myFunctionG
   * @description Renderiza o actualiza el gráfico de barras ECharts en el contenedor
   *   #container con las calificaciones comparativas de todos los analistas evaluados
   *   hasta el momento. Usa los arrays globales `nombre` (eje X, nombres de evaluados)
   *   y `data123` (eje Y, porcentaje promedio de cada analista). Formatea las etiquetas
   *   de valor con signo de porcentaje y el tooltip muestra el formato "{b}:{c}%".
   *   Se registra un listener de resize para ajuste responsive.
   * @param {void}
   * @returns {void}
   */
  function myFunctionG() {
  //////////////variables  gráfica
    <!-- alert(document.getElementById("row-1-positionT1234").value) -->
    <!-- alert(document.getElementById("row-1-positionT").value) -->
  //////////////variables  gráfica
  
  ///////////////// var data java
  <!-- var dato1= document.getElementById("row-1-positionT").value.replace("%", ""); -->
  <!-- var dato2= document.getElementById("row-1-positionT1234").value.replace("%", ""); -->
  
  ///////////////// var data java
  
      var dom = document.getElementById('container');
    var myChart = echarts.init(dom, null, {
      renderer: 'canvas',
      useDirtyRect: false
    });
    var app = {};
    
    var option;

    option = {  tooltip: {
    trigger: 'axis',
    axisPointer: {
      // Use axis to trigger tooltip
      type: 'shadow' // 'shadow' as default; can also be 'line' or 'shadow'
    },    formatter: '{b}:{c}%'
  },
  legend: {},
  grid: {
    left: '3%',
    right: '4%',
    bottom: '3%',
    containLabel: true
  },
  xAxis: {
    type: 'category',
    data: nombre
  },
  yAxis: {
    type: 'value',  axisLabel: {
    formatter: "{value} %"
  }
  },
  series: [
    {
      data: data123,
      type: 'bar',
	  label: {
        show: true,
		formatter: "{c}%"
      }
    }
  ]
};

    if (option && typeof option === 'object') {
      myChart.setOption(option);
    }

    window.addEventListener('resize', myChart.resize);

}


<!-- function CrearTabla() { -->
<!-- new DataTable('#example', { -->
    <!-- columns: [ -->
        <!-- { title: 'Name' }, -->
        <!-- { title: 'Position' }, -->
        <!-- { title: 'Office' }, -->
        <!-- { title: 'Extn.' }, -->
        <!-- { title: 'Start date' }, -->
        <!-- { title: 'Salary' } -->
    <!-- ], -->
    <!-- data: dataSet -->
<!-- }); -->

<!-- } -->

/**
 * @function CreateTable
 * @description Agrega dinámicamente dos pares de columnas a la tabla DataTables #example
 *   para el evaluado cuyo nombre se encuentra en el campo fname. La primera columna
 *   recibe el encabezado "No de Formatos que se tienen" con inputs de captura (p1..p8 + nombre).
 *   La segunda columna recibe el nombre del evaluado como encabezado y contiene inputs
 *   de porcentaje calculado (pT1..pT8 + nombre) de solo lectura. Itera sobre todas las
 *   filas de la tabla (rows 1..N) para insertar los <td> con los campos correspondientes.
 *   Los inputs de captura invocan las funciones nueva1()–nueva6() mediante onchange.
 * @returns {void}
 */
function CreateTable() {


<!-- new DataTable('#example', { -->
    <!-- columns: [ -->
        <!-- { title: 'Name' }, -->
        <!-- { title: 'Position' }, -->
        <!-- { title: 'Office' }, -->
        <!-- { title: 'Extn.' }, -->
        <!-- { title: 'Start date' }, -->
        <!-- { title: 'Salary' } -->
    <!-- ], -->
    <!-- data: dataSet -->
<!-- }); -->

<!-- var orderArrayHeader = ["S.No", "Date", "Product Name", "Client Name"]; -->
<!-- var thead = document.createElement('thead'); -->

<!-- var table = document.getElementById("example"); -->

<!-- table.appendChild(thead); -->

<!-- for (var i=3; i<orderArrayHeader.length; i++) { -->
    <!-- thead.appendChild(document.createElement("th")). -->
          <!-- appendChild(document.createTextNode(orderArrayHeader[i])); -->
<!-- } -->
 
 <!-- var cars = ["BMW", "Volvo", "Saab", "Ford"]; -->

 <!-- var table = document.getElementById("example"); -->
  <!-- var header = table.createTHead(); -->

<!-- for (var i = 0; i < cars.length; i++) { -->
   <!-- /////alert(cars[i]); -->
     <!-- var row = header.insertRow(i); -->
  <!-- var cell = row.insertCell(0); -->
  <!-- cell.innerHTML = "<thead><tr><th>"+cars[i]+"</th></tr> </thead>"; -->
<!-- } -->

	<!-- var table = document.getElementById("example"); -->
	<!-- var rows = table.rows; -->
	<!-- ///console.log("rows", rows); -->
	
	<!-- for (var i = 0; i < rows.length; ++i) {                 -->
        <!-- // td = rows[i].cells; -->
		<!-- var td = document.createElement("td"); -->
		<!-- td.innerText = i; -->
		<!-- rows[i].appendChild(td);	 -->
	<!-- } -->
	
  ////////////////////////////////////////////////////  agregar Encabezados	
   var x6 = document.getElementById("fname").value;

/////////////////////////////////// Prueba demo segunda función  
/////////////////////////////////// Prueba demo segunda función 


  
  <!-- alert(x6) -->
  
  var table = document.getElementById("example");
  var th = document.createElement("th");
  th.innerText = "No de Formatos que se tienen";
  var row1 = table.rows[0];
  row1.appendChild(th);
  
  
  
    var table = document.getElementById("example");
	var rows = table.rows;
	/////alert(rows.length)
	console.log("rows", rows);
	
	for (var i = 1; i < rows.length; ++i) {                
        // td = rows[i].cells;
		var td = document.createElement("td");
		////td.innerText = i;
		td.innerHTML = '<input type="text" id="p'+i+x6+'"name="p'+i+x6+'" onchange="nueva'+i+'();" >';
		rows[i].appendChild(td);	
	}


  
  var table = document.getElementById("example");
  var th = document.createElement("th");
  th.innerText = x6;
  var row1 = table.rows[0];
  row1.appendChild(th);
  ////////////////////////////////////////////////////  agregar Encabezados
  
  
     var table = document.getElementById("example");
	var rows = table.rows;
	/////alert(rows.length)
	console.log("rows", rows);
	
	for (var i = 1; i < rows.length; ++i) {                
        // td = rows[i].
		var td = document.createElement("td");
		////td.innerText = i;
		td.innerHTML = '<input type="text" id="pT'+i+x6+'" name="pT'+i+x6+'"onchange="">';
		rows[i].appendChild(td);	
	}
  


 
  ////////////////////////////////////////////////////////añadir columnas
  <!-- var table = document.getElementById("example"); -->
	<!-- var rows = table.rows; -->
	<!-- /////alert(rows.length) -->
	<!-- console.log("rows", rows); -->
	
	<!-- for (var i = 0; i < rows.length; ++i) {                 -->
        <!-- // td = rows[i].cells; -->
		<!-- var td = document.createElement("td"); -->
		<!-- td.innerText = i; -->
		<!-- rows[i].appendChild(td);	 -->
	<!-- }   	 -->
 
<!-- } -->
  <!-- ////////////////////////////////////////////////////////añadir columnas -->
  
  <!-- ///////////////// Añadir rows -->
	<!-- var table = document.getElementById("example"); -->
	<!-- var tr = document.createElement("tr"); -->
    <!-- var th = document.createElement("th"); -->
    <!-- var td = document.createElement("td"); -->
    <!-- td.innerText = "im a td"; -->
    <!-- th.innerText = "im a th"; -->
    <!-- tr.appendChild(th); -->
    <!-- tr.appendChild(td); -->
    <!-- table.appendChild(tr); -->
}

/**
 * @function ExportToExcel
 * @description Reconstruye la DataTable #example con los datos completos almacenados en
 *   los arrays C, C1–C8 (encabezados y filas de todos los evaluados) antes de ejecutar
 *   la exportación. Primero destruye y vacía la tabla existente, la recrea con DataTables
 *   usando las columnas dinámicas en C y los datos en C1–C8, luego delega la descarga al
 *   método tableToExcel externo. El parámetro 'type' es 'xlsx' por convención.
 * @param {string} type - Tipo de archivo de salida (ej. 'xlsx')
 * @param {string} [fn] - Nombre de archivo de salida (opcional)
 * @param {boolean} [dl] - Si true retorna base64 en lugar de descargar archivo
 * @returns {void}
 */
function ExportToExcel(type, fn, dl) {
$('#example').DataTable().destroy();
$('#example').empty();



var dataSet = [
  C1,C2,C3,C4,C5,C6,C7,C8
];
 
new DataTable('#example', {
    columns: C,
    data: dataSet
});


       <!-- var elt = document.getElementById('example'); -->
       <!-- var wb = XLSX.utils.table_to_book(elt, { sheet: "sheet1" }); -->
       <!-- return dl ? -->
        <!-- XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }): -->
         <!-- XLSX.writeFile(wb, fn || ('MySheetName.' + (type || 'xlsx'))); -->
    }
	
	
/**
 * @function tableToExcel
 * @description IIFE (Immediately Invoked Function Expression) que retorna una función de
 *   exportación a Excel usando el protocolo data URI con MIME type vnd.ms-excel.
 *   Serializa el HTML de la tabla indicada dentro de una plantilla de libro Excel XML,
 *   codifica en base64 y asigna a window.location.href para disparar la descarga.
 *   Nota: Funciona en navegadores que soporten data URIs con charset UTF-8.
 * @param {string|HTMLElement} table - ID de la tabla HTML o referencia al elemento DOM
 * @param {string} [name] - Nombre de la hoja de trabajo dentro del libro Excel
 * @returns {void}
 */
	var tableToExcel = (function() {
  var uri = 'data:application/vnd.ms-excel;base64,'
    , template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--><meta http-equiv="content-type" content="text/plain; charset=UTF-8"/></head><body><table>{table}</table></body></html>'
    , base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) }
    , format = function(s, c) { return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) }
  return function(table, name) {
    if (!table.nodeType) table = document.getElementById(table)
    var ctx = {worksheet: name || 'Worksheet', table: table.innerHTML}
    window.location.href = uri + base64(format(template, ctx))
  }
})()
	
	
	
/**
 * @function exportTableToExcel
 * @description Exporta una tabla HTML a un archivo .xls creando un enlace de descarga
 *   dinámico con data URI de tipo application/vnd.ms-excel. Compatible con Internet
 *   Explorer (usando msSaveOrOpenBlob) y navegadores modernos. El HTML de la tabla se
 *   codifica en la URL reemplazando espacios por %20. El archivo se nombra con la
 *   extensión .xls o "excel_data.xls" por defecto.
 * @param {string} tableID - ID del elemento de tabla HTML a exportar
 * @param {string} [filename=''] - Nombre del archivo de salida sin extensión
 * @returns {void}
 */
	function exportTableToExcel(tableID, filename = ''){
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    
    // Specify file name
    filename = filename?filename+'.xls':'excel_data.xls';
    
    // Create download link element
    downloadLink = document.createElement("a");
    
    document.body.appendChild(downloadLink);
    
    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['\ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob( blob, filename);
    }else{
        // Create a link to the file
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
    
        // Setting the file name
        downloadLink.download = filename;
        
        //triggering the function
        downloadLink.click();
    }
}





  </script>

<?php
/**
 * Bloque PHP de procesamiento del formulario de evaluación.
 *
 * Recibe el envío POST del formulario (acción sobre sí mismo). Actualmente los campos
 * $_POST["name"], $_POST["email"] etc. están comentados. Establece conexión a la base
 * de datos KPI en el servidor DESAROLLO-BACRO\SQLEXPRESS.
 * Las sentencias de INSERT e SELECT están comentadas; solo verifica la conexión.
 *
 * NOTA DE MIGRACIÓN PENDIENTE: Las credenciales de conexión están hardcodeadas.
 * Deben migrarse a config/database.php usando las variables de entorno del archivo .env.
 */

$pedido = $name = $email = $gender = $comment = $website = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // $name = test_input($_POST["name"]);
  // $email = test_input($_POST["email"]);
  // $website = test_input($_POST["website"]);
  // $comment = test_input($_POST["comment"]);
  // $gender = test_input($_POST["gender"]);
  // $pedido = test_input($_POST["Npedido"]);
  
  
  
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
$conn = getKpiConnection();
if (!$conn) {
    die('Error de conexión a la base de datos KPI.');
}

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

/**
 * Sanitiza y limpia una entrada de formulario.
 *
 * Aplica tres transformaciones secuenciales:
 *   1. trim()             — Elimina espacios al inicio y al final.
 *   2. stripslashes()     — Elimina barras invertidas (protección contra magic quotes).
 *   3. htmlspecialchars() — Convierte caracteres especiales HTML a entidades para
 *                           prevenir ataques XSS.
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
// https://www.dynamsoft.com/codepool/mobile-qr-code-scanner-in-html5.html
?>