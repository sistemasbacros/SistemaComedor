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


   
// $serverName = "LUISROMERO\SQLEXPRESS"; //serverName\instanceName
// $connectionInfo = array( "Database"=>"Comedor", "UID"=>"larome02", "PWD"=>"larome02");
// $conn = sqlsrv_connect( $serverName, $connectionInfo);


$serverName = "DESAROLLO-BACRO\SQLEXPRESS"; //serverName\instanceName
$connectionInfo = array( "Database"=>"Comedor", "UID"=>"Larome03", "PWD"=>"Larome03","CharacterSet" => "UTF-8");
$conn = sqlsrv_connect( $serverName, $connectionInfo);


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








function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

?>


<script type="text/javascript">



 //////////////////////////////////////////////////////////// Function javascipt agregar columnas
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



function ExportToExcel(type, fn, dl) {
       var elt = document.getElementById('example');
       var wb = XLSX.utils.table_to_book(elt, { sheet: "sheet1" });
       return dl ?
         XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }):
         XLSX.writeFile(wb, fn || ('MySheetName.' + (type || 'xlsx')));
    }


</script>