<?php
require_once __DIR__ . '/config/database.php';
////////////////// Insert
$conn = getTicketConnection();

// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }

$sql = "Select * from [dbo].[T3] where replace(substring(Fecha, Charindex('/', fecha,0),2),'/','')='8'";
$stmt = sqlsrv_query( $conn, $sql );
// if( $stmt === false) {
    // die( print_r( sqlsrv_errors(), true) );
// }


/////////////////// Variables 
$array1 = [];
$array2 = [];
$array3 = [];
$array4 = [];
$array5 = [];
$array6 = [];
$array7 = [];
$array8 = [];
$array9 = [];
$array10 = [];
$array11 = [];
$array12 = [];
$array13 = [];
$array14 = [];

$array15 = [];
$array16 = [];
$array17 = [];





while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
	
array_push($array1,$row['Nombre ']);	
array_push($array2,$row['Correo']);	
array_push($array3,$row['Prioridad']);	
array_push($array4,$row['Empresa']);	
array_push($array5,$row['Asunto']);	
array_push($array6,$row['Mensaje']);	
array_push($array7,$row['Adjuntos']);	
array_push($array8,$row['Fecha']);	
array_push($array9,$row['Hora']);	
array_push($array10,$row['Id_Ticket']);	
array_push($array11,$row['Estatus']);
array_push($array12,$row['PA']);
array_push($array13,$row['Tipo']);
array_push($array14,$row['Tiempo_Ejec']);


array_push($array15,$row['HoraT']);
array_push($array16,$row['FinT']);
array_push($array17,$row['FeT']);

}

sqlsrv_free_stmt( $stmt);



function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
 }

?>




<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">

<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap.min.css" rel="stylesheet">


  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  
      <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

	  
  	<script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
	
	<style>
    .table td{
  font-size: 12px;
  font-weight: bold;
		
    }
	
	    .table th{
  font-size: 15px;
		  color: white;
  background: #1E4E79;
  font-weight: bold;
    }
</style>

<!-- <link href="https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator.min.css" rel="stylesheet"> -->
<!-- <script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js"></script> -->
   <a href="http://192.168.100.95/TicketBacros/M/website-menu-05/index.html" style="color:black;font-size:25px;font-weight: bold;">INICIO</a>
  <button class="btn btn-default115" id="Car" name='Carg' onclick="theFunction1()" style="color:black;font-size:25px;font-weight: bold;"> Asignar </button>
  <button class="btn btn-default1" id="EX" name='EX' onclick="ExportToExcel('xlsx')"> <img src="EXCEL.PNG" width="40" /> </button> 	 
	 
<div>


 <div class="inner1"> <table id="example" class="table table-striped table-bordered" width="100%">
    

<!-- Ordenes de Servicio					 -->

<script>


var darray1 = <?php echo json_encode($array1);?>;
var darray2 = <?php echo json_encode($array2);?>;
var darray3 = <?php echo json_encode($array3);?>;
var darray4 = <?php echo json_encode($array4);?>;
var darray5 = <?php echo json_encode($array5);?>;
var darray6 = <?php echo json_encode($array6);?>;
var darray7 = <?php echo json_encode($array7);?>;
var darray8 = <?php echo json_encode($array8);?>;
var darray9 = <?php echo json_encode($array9);?>;
var darray10 = <?php echo json_encode($array10);?>;
var darray11 = <?php echo json_encode($array11);?>;
var darray12 = <?php echo json_encode($array12);?>;
var darray13 = <?php echo json_encode($array13);?>;
var darray14 = <?php echo json_encode($array14);?>;


var darray15 = <?php echo json_encode($array15);?>;
var darray16 = <?php echo json_encode($array16);?>;
var darray17 = <?php echo json_encode($array17);?>;





var dataSet = [];

for (var i = 0; i < darray1.length; i++) {
	dataSet.push([darray1[i],darray2[i],darray3[i],darray4[i],darray5[i],darray6[i],darray7[i],darray8[i],darray9[i],darray10[i],darray11[i],darray12[i],darray13[i],darray15[i],darray16[i],darray17[i],darray14[i]])
}

 
 
 
 document.querySelectorAll('button[data-bs-toggle="tab"]').forEach((el) => {
    el.addEventListener('shown.bs.tab', () => {
        DataTable.tables({ visible: true, api: true }).columnsv.adjust();
    });
});
 
new DataTable('#example', {    columns: [
        { title: 'Nombre', "searchable": false },
		{ title: 'Correo', "searchable": false },
		{ title: 'Prioridad', "searchable": false },
        { title: 'Departamento', "searchable": false },
        { title: 'Asunto', "searchable": false},
        { title: 'Mensaje', "searchable": false },
        { title: 'Adjunto', "searchable": false },
        { title: 'Fecha', "searchable": true},
        { title: 'Hora_Inicio', "searchable": false },
        { title: 'No Ticket', "searchable": false },
		{ title: 'Estatus', "searchable": false },
	    { title: 'Responsable', "searchable": false },
		{ title: 'Fecha_Asignación', "searchable": false},
		{ title: 'Hora_Asignación', "searchable": false },
		{ title: 'Fecha_Cierre' , "searchable": false},
		{ title: 'Hora_Cierre', "searchable": false },
		{ title: 'Tiempo_Procesar', "searchable": false },

    ],
    data: dataSet,scrollX:644,scrollY: 700,
  scrollCollapse: true,   lengthMenu: [
        [200, -1],
        [200,'All'],
    ]
});


		function ExportToExcel(type, fn, dl) {
		
			
       var elt = document.getElementById('example');
       var wb = XLSX.utils.table_to_book(elt, { sheet: "CentrodeCostos" });
       return dl ?
         XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }):
         XLSX.writeFile(wb, fn || ('CentroCostos.' + (type || 'xlsx')));
    }




var table = $('#example').DataTable();

var prueba=[];
  
$('#example').on( 'click', 'tr', function () {

prueba= table.row( this ).data()
// alert("Seleccionaste los siguentes datos" +" "+ prueba[9]+ prueba[5]+ prueba[6] )
} );



table.on('click', 'tbody tr', (e) => {
    let classList = e.currentTarget.classList;
 
    if (classList.contains('selected')) {
        classList.remove('selected');
	}
    else {
        table.rows('.selected').nodes().each((row) => row.classList.remove('selected'));
        classList.add('selected');
		
		 // alert('Hola')
    }
});

// //// Function Borrar
// function theFunction () {
    // // alert(prueba)
	
// var arrayJson=JSON.stringify(prueba);
 			// alert("Se elimino el centro de costos");
 
		// // mediante ajax, enviamos por POST el json en la variable: arrayDeValores
		// $.post("BorrarCentrodeCostos.php",{arrayDeValores:arrayJson},function(data) {
 // // alert(data)
			// // Mostramos el texto devuelto por el archivo php
			// location.reload();
		// });
		

    // }

// //// Function Borrar


	//////////////////////////////  Function enviar datos  formulario editar texto
	 function theFunction1 () {
		 
		////alert(prueba)
		 
		// convertimos el array en un json para enviarlo al PHP
		var arrayJson=JSON.stringify(prueba);
 
		// mediante ajax, enviamos por POST el json en la variable: arrayDeValores
		$.post("t1.php",{arrayDeValores:arrayJson},function(data) {
 
			// Mostramos el texto devuelto por el archivo php
			alert(data);
						window.location.href = 'http://192.168.100.95/TicketBacros/AsigBien.php'; //Will take you to Google.
	
			
		});
		
		 }

	</script>