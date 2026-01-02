

<p ><a href="http://192.168.100.95/Comedor">Menu principal  </a> </p>


  <form method="post" action="">  
  

  <div class="col-md-2">
      <label for="inputZip" class="form-label" style="text-align:center;color:black;font-weight: bold; border: 1px  solid white">FECHA INICIO</label>
    <input type="date" class="form-control" id="inputZip" name="inputZip" value="<?php echo date('Y-m-d');?>">
  </div>
  
    <div class="col-md-2">
    <label for="inputZip" class="form-label" style="text-align:center;color:black;font-weight: bold; border: 1px  solid white">FECHA FIN</label>
    <input type="date" class="form-control" id="inputZip1" name="inputZip1" value="<?php echo date('Y-m-d');?>">

 </div>
 
   <div class="col-md-3">
  <button  type="submit" style="font-size:100%;background-color:#2D6DA6;color:white;font-weight: bold;">BUSCAR</button>
 </div>

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
  font-size|: 12px;
	    font-weight: bold;	
    }
	
	    .table th{
  font-size: 15px;
		  color: white;
  background: #1E4E79;
    font-weight: bold;
    }
	
</style>

  
      <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
	<script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
	

<div class="inner1"> <table id="example" class="table table-striped table-bordered" width="100%"  style="font-size:100%;background-color:#2D6DA6;color:white;font-weight: bold;"></table> </div>
          </form>



<?php


$pedido = $name = $email = $gender = $comment = $website = "";



$serverName = "DESAROLLO-BACRO\SQLEXPRESS"; //serverName\instanceName
$connectionInfo = array( "Database"=>"Comedor", "UID"=>"Larome03", "PWD"=>"Larome03","CharacterSet" => "UTF-8");
$conn = sqlsrv_connect( $serverName, $connectionInfo);

// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }



/////Query ordenes de cancelación de alimentos.
$sql = "Select *  from  cancelaciones order by Nombre";
/////Query ordenes de cancelación de alimentos.

/// Ejecutar Query
$stmt = sqlsrv_query( $conn, $sql );



if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}

/////////////////// Variables formatos cancelación 
$array_tot1 = [];
$array_tot2 = [];
$array_tot3 = [];
$array_tot4 = [];
$array_tot5 = [];
$array_tot6 = [];
$array_tot7 = [];
$array_tot8 = [];
$array_tot9 = [];




while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {


/////////////////////////////////////////////////// Array nuevas variables

////echo $row['CLunes'];
array_push($array_tot1,$row['NOMBRE']);
array_push($array_tot2,$row['DEPARTAMENTO']);
array_push($array_tot3,$row['JEFE']);
array_push($array_tot4,$row['TIPO_CONSUMO']);
array_push($array_tot5,$row['FECHA']);
array_push($array_tot6,$row['CAUSA']);
array_push($array_tot7,$row['ESTATUS']);
array_push($array_tot8,$row['FECHA_FIN']);
array_push($array_tot9,$row['FECHA_CAPTURA']);

}

////////////////////////////////////// while query tabla

if ($_SERVER["REQUEST_METHOD"] == "POST") {

$var1 = test_input($_POST["name123"]);  /// Usuario
$var2 = test_input($_POST["name1234"]); /// Contraseña


$sql12 = "Update cancelaciones  set Estatus='APROBADO'  where Nombre='$var1' and Fecha='$var2'";
/////Query ordenes de cancelación de alimentos.

/// Ejecutar Query
$stmt12 = sqlsrv_query( $conn, $sql12 );
header("Refresh:0");
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
 }

?>

<script>



	var dataQ1 = <?php echo json_encode($array_tot1);?>;
    var dataQ2 = <?php echo json_encode($array_tot2);?>;
    var dataQ3 = <?php echo json_encode($array_tot3);?>;
	var dataQ4 = <?php echo json_encode($array_tot4);?>;
    var dataQ5 = <?php echo json_encode($array_tot5);?>;
    var dataQ6 = <?php echo json_encode($array_tot6);?>;
    var dataQ7 = <?php echo json_encode($array_tot7);?>;
	var dataQ8 = <?php echo json_encode($array_tot8);?>;
    var dataQ9 = <?php echo json_encode($array_tot9);?>;
	
	

function Borrar() {
var table = $('#example').DataTable();
 
table.clear()
}

var dataSet = [
]





for (let i = 0; i < dataQ1.length; i++) {
  dataSet.push([dataQ1[i],dataQ2[i],dataQ3[i],dataQ4[i],dataQ5[i], dataQ8[i], dataQ9[i],dataQ6[i],dataQ7[i]]);
};

// {"CC" : darray1[i],"No" : darray2[i],"Unidad" : darray3[i],"Equipo" : darray4[i]}





 




new DataTable('#example', {
	        columns: [
            { title: 'Nombre' },
            { title: 'Departamento' },
            { title: 'Jefe inmediato' },
            { title: 'Tipo de consumo a cancelar'},
			{ title: 'Fecha inicio'},
			{ title: 'Fecha fin'},
			{ title: 'Fecha captura formato'},
			{ title: 'Causa'},
		    { title: 'Estatus'},

        ],responsive: true,  data:dataSet,paging:   false
 
})



</script>


<script type="text/javascript">

var table = $('#example').DataTable();

var prueba=[];
  
$('#example').on( 'click', 'tr', function () {

prueba= table.row( this ).data()
 ////alert("Seleccionaste los siguentes datos" +" "+ prueba)
 
 ////alert(prueba)
 
  document.getElementById("name123").value = prueba[0]; 
  
    document.getElementById("name1234").value = prueba[4]; 
 
 
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



 function theFunction () {

////////////////// product.replace("?", "") 

// prueba.push( product.replace("?", "") );
// alert(prueba)
	
var arrayJson=JSON.stringify(prueba);
 
 
 

 			// alert("Se elimino tu contrato");
 
		// mediante ajax, enviamos por POST el json en la variable: arrayDeValores
		$.post("am.php",{arrayDeValores:arrayJson},function(data) {
// alert(data)
			// Mostramos el texto devuelto por el archivo php
							window.location.href = 'http://192.168.100.95/Comedor/am.php'; //Will take you to Google
		});
		

    }


 
</script>

