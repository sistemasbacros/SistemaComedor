<form name="formulario1" method="POST" action="">
  <script type="text/javascript" src="https://unpkg.com/@zxing/library@latest"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>  
    <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <style type="text/css">
    input[type=submit] {
      padding: 35px 45px;
      background: #ccc;
      border: 0 none;
      cursor: pointer;
      -webkit-border-radius: 5px;
      border-radius: 5px;
    }
	
 /* Remove the navbar's default margin-bottom and rounded borders */ 
    .navbar {
      margin-bottom: 0;
      border-radius: 0;
    }
    
    /* Add a gray background color and some padding to the footer */
    footer {
      background-color: #f2f2f2;
      padding: 25px;
    }
    
  .carousel-inner img {
      width: 18%; /* Set width to 100% */
      margin: auto;
      min-height:25px;
  }

  /* Hide the carousel text when the screen is less than 600 pixels wide */
  @media (max-width: 600px) {
    .carousel-caption {
      display: none; 
    }
  }
		
  </style>



<div id="myCarousel" class="carousel slide" data-ride="carousel">
    <!-- Indicators -->
    <ol class="carousel-indicators">
      <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
      <li data-target="#myCarousel" data-slide-to="1"></li>
    </ol>

    <!-- Wrapper for slides -->
    <div class="carousel-inner" role="listbox">
      <div class="item active">
       <img src="Logo2.png">
        <div class="carousel-caption">

        </div>      
      </div>

      <div class="item">
       <img src="logo3.png">
        <div class="carousel-caption">

        </div>      
      </div>
    </div>

    <!-- Left and right controls -->
    <a class="left carousel-control" href="#myCarousel" role="button" data-slide="prev">
      <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
      <span class="sr-only">Previous</span>
    </a>
    <a class="right carousel-control" href="#myCarousel" role="button" data-slide="next">
      <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
      <span class="sr-only">Next</span>
    </a>
</div>

<div class="container text-center">    
  <p id="result" name="result"></p>
   <h7 >Usuario:</h7> <input type="text" name="name" id='name'>   <h7 >Complementos:</h7> <input type="text" name="cot" id="cot">
  <input type="submit" id="submit" name="submit" value="Registrarme"> <h7 id="result1" name="result1" style="font-size:150%;background-color:#2D6DA6;color:white;font-weight: bold;">Total:</h7>
  </div> <br>
  <div class="row">
    <div class="col-sm-4">
  <video id="webcam-preview"  width="500" height="200"> </video>
    </div>
	
    <div class="col-sm-4"> 
       <div >
      <img src="tecafe.jpg"  width="250" height="100" onclick="cafete()"><h3>CAFÉ O TÉ</h3>
      </div>
	  
	  	<div >
      <img src="deschable2.jpg"  width="250" height="100" onclick="desechable()"><h3>DESECHABLE</h3>
      </div>
	  
	  	  	<div >
      <img src="istockphoto-1137764161-612x612.jpg"  width="250" height="100" onclick="comidallevar()"><h3>COMIDA PARA LLEVAR</h3>
      </div>
	  
	  
    </div>
    <div class="col-sm-4">
      <div >
      <img src="Tortillas.jpeg"  width="250" height="100" onclick="tortillas()"><h3>TORTILLAS</h3>
      </div>
	  <br>
      <div >
      <img src="AGUASFRECAS.jpg"  width="250" height="100" onclick="agua()"><h3>AGUA</h3>
      </div>
	  <br>

	  
 <div >   
 
	  
    </div>
  </div>
</div>



</form>

<script>
  function ponleFocus() {
    document.getElementById("name").focus();
  }

  ponleFocus();

  const codeReader = new ZXing.BrowserQRCodeReader();

  codeReader.decodeFromVideoDevice(null, 'webcam-preview', (result, err) => {
    if (result) {
      // properly decoded qr code
      console.log('Found QR code!', result)
      document.getElementById('result').textContent = result.text

      document.getElementById('name').value = result.text


      var element = document.getElementById("demo");
      element.remove();

      ///window.location.reload()

      // // window.location = window.location
      // var input = document.getElementById("name");
      // input.addEventListener("keypress", function(event) {
      // if (event.key === "Enter") {
      // event.preventDefault();
      // document.getElementById("submit").click();
      // }
      // });



      // const input = document.getElementById("name");

      // // This handler will be executed only once when the cursor
      // // moves over the unordered list
      // input.addEventListener(
      // "mouseenter",
      // (event) => {
      // event.preventDefault();
      // document.getElementById("submit").click();
      // },
      // false
      // );

      // var element = document.getElementById('name');
      // element.addEventListener('name', function() {
      // event.preventDefault();
      // document.getElementById("submit").click();
      // });

    }

    if (err) {
      // As long as this error belongs into one of the following categories
      // the code reader is going to continue as excepted. Any other error
      // will stop the decoding loop.
      //
      // Excepted Exceptions:
      //
      //  - NotFoundException
      //  - ChecksumException
      //  - FormatException

      if (err instanceof ZXing.NotFoundException) {
        console.log('No QR code found.')
      }

      if (err instanceof ZXing.ChecksumException) {
        console.log('A code was found, but it\'s read value was not valid.')
      }

      if (err instanceof ZXing.FormatException) {
        console.log('A code was found, but it was in a invalid format.')
      }
    }
  })
</script>




<?php

// if (isset($var)) {
/////echo "Esta variable está definida, así que se imprimirá";
// }

$name = test_input($_POST["name"]);

$cot = test_input($_POST["cot"]);


$name2;
$fechaActual;
$fechaActual1;


$fechaActual123 = date('d-m-Y');
  
  // echo  $fechaActual123;
  
  $fechaTotales = date('Y-m-d');
  
// echo $fechaTotales;
  
  
  // and year respectively 
$firstday = date('Y-m-d', strtotime("this week"));
  
 // echo $firstday ;  
  
// echo "First day of this week: ", $firstday;
  
  $serverName = "DESAROLLO-BACRO\SQLEXPRESS"; //serverName\instanceName
  $connectionInfo = array("Database" => "Comedor", "UID" => "Larome03", "PWD" => "Larome03", "CharacterSet" => "UTF-8");
  $conn = sqlsrv_connect($serverName, $connectionInfo);
  
  
 $sql250 = "Select Hora_Entrada,Tipo_Comida,Count(*) as Total from (
Select  *, Tipo_Comida=  case when Fecha > '12:40:00' then 'Comida' else 'Desayuno' end from [dbo].[Entradas]
where Hora_Entrada like '%$fechaActual123%' and  not Nombre='' and  not Nombre='.') as a
Group by Hora_Entrada,Tipo_Comida
ORDER BY Hora_Entrada"; 

$stmt150 = sqlsrv_query($conn,$sql250);


$arrayT1 = [];

$fechaActual1 = date('H:i:s', time()+3600);


  
while( $row = sqlsrv_fetch_array($stmt150,SQLSRV_FETCH_NUMERIC) ) {

// echo $row[1];


if (strtotime($fechaActual1) < strtotime('12:40:00') and $row[1]== 'Desayuno') {

    // echo "Entro a desayuno";
	array_push($arrayT1,$row[2]);

} 
	

   if (strtotime($fechaActual1)  >  strtotime('12:40:00') and $row[1]== 'Comida') {
   
    // echo "Entro a comida";
	array_push($arrayT1,$row[2]);
} 


} 
  
  
$sql127 = "Select * from (
Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,0,cast(Fecha as date)),Tipo='Desayuno' from PedidosComida
where Fecha like '%$firstday%' and Lunes like '%Desayuno%'
Group by Fecha
union all
Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,0,cast(Fecha as date)) ,Tipo='Comida'  from PedidosComida
where Fecha like '%$firstday%' and Lunes like '%Comida%'
Group by Fecha
Union all
Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,1,cast(Fecha as date)) ,Tipo='Desayuno' from PedidosComida
where Fecha like '%$firstday%' and Martes like '%Desayuno%'
Group by Fecha
union all
Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,1,cast(Fecha as date)),Tipo='Comida'   from PedidosComida
where Fecha like '%$firstday%' and Martes like '%Comida%'
Group by Fecha
Union all
Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,2,cast(Fecha as date)) ,Tipo='Desayuno' from PedidosComida
where Fecha like '%$firstday%' and Miercoles like '%Desayuno%'
Group by Fecha
union all
Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,2,cast(Fecha as date)),Tipo='Comida'   from PedidosComida
where Fecha like '%$firstday%' and Miercoles like '%Comida%'
Group by Fecha
Union all
Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,3,cast(Fecha as date)) ,Tipo='Desayuno' from PedidosComida
where Fecha like '%$firstday%' and Jueves like '%Desayuno%'
Group by Fecha
union all
Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,3,cast(Fecha as date)),Tipo='Comida'   from PedidosComida
where Fecha like '%$firstday%' and Jueves like '%Comida%'
Group by Fecha
Union all
Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,4,cast(Fecha as date)) ,Tipo='Desayuno' from PedidosComida
where Fecha like '%$firstday%' and Viernes like '%Desayuno%'
Group by Fecha
union all
Select Fecha ,Count(*) as Total, FechaDia=DATEADD(day,4,cast(Fecha as date)),Tipo='Comida'   from PedidosComida
where Fecha like '%$firstday%' and Viernes like '%Comida%'
Group by Fecha) as a where FechaDia='$fechaTotales'";

$stmt127 = sqlsrv_query($conn,$sql127);


$arrayT = [];


  $fechaActual1 = date('H:i:s', time()+3600);

 // echo   $fechaActual1;

// echo date('H:i',$fechaActual1+strtotime('1:00:00'));


while( $row = sqlsrv_fetch_array($stmt127,SQLSRV_FETCH_NUMERIC) ) {
	  
   if (strtotime($fechaActual1) < strtotime('12:40:00') and $row[3]== 'Desayuno') {

	array_push($arrayT,$row[1]);

} 
	

   if (strtotime($fechaActual1)  >  strtotime('12:40:00') and $row[3]== 'Comida') {
	array_push($arrayT,$row[1]);
} 	
	  
	  
	 	    // if ( $row[2]== $fechaTotales) {
			// echo "entro a uno";
   // array_push($arrayT,$row[1]);
  // }  
	  
	  
	  	  // echo $row[3];
	  
	    // if (strtotime($fechaActual1) > strtotime('12:40:00') and  $row[3]=='Comida' and $row[2]== $fechaTotales) {
			// echo "entro a uno";
   // array_push($arrayT,$row[1]);
  // } 

// echo $row[2];

	    // if (strtotime($fechaActual1) < strtotime('12:40:00') and $row[2]== $fechaTotales) {
			// echo $row[1];
   // array_push($arrayT,$row[1]);
  // }   

}



if ($_SERVER["REQUEST_METHOD"] == "POST") {
	
	
	/////echo $cot;
	
    // $var_PHP = "<script> document.writeln(complemento); </script>"; // igualar el valor de la variable JavaScript a PHP 

    // echo $var_PHP;   	

  // echo $_POST['name'];
  $name = test_input($_POST["name"]);



  ///echo $name;

  $name = str_replace('Ñ', ':', str_replace('Ã“', 'Ó', $name));

  // $hoy = getdate();
  // print_r($hoy);


  $fechaActual = date('d-m-Y');
  // echo $fechaActual;

  date_default_timezone_set('America/Mexico_City');
  $fechaActual1 = date('H:i:s');
  /////echo $fechaActual1;

  ///$hoy='Comida'

  if (strtotime($fechaActual1) > strtotime('13:00:00')) {
    $hoy = 'Comida';
  } else {
    $hoy = 'Desayuno';
  }



  // $serverName = "LUISROMERO\SQLEXPRESS"; //serverName\instanceName
  // $connectionInfo = array( "Database"=>"Comedor", "UID"=>"larome02", "PWD"=>"larome02","CharacterSet" => "UTF-8");
  // $conn = sqlsrv_connect( $serverName, $connectionInfo);


  $serverName = "DESAROLLO-BACRO\SQLEXPRESS"; //serverName\instanceName
  $connectionInfo = array("Database" => "Comedor", "UID" => "Larome03", "PWD" => "Larome03", "CharacterSet" => "UTF-8");
  $conn = sqlsrv_connect($serverName, $connectionInfo);


  // if( $conn ) {
  // echo "Conexión establecida.<br />";
  // }else{
  // echo "Conexión no se pudo establecer.<br />";
  // die( print_r( sqlsrv_errors(), true));
  // }


  ////////////////////////// Validación Totales de cómidas
  $sql1 = "Select Hora_Entrada,Tipo_Comida,count(*) as Total  from (Select ltrim(rtrim(Replace(left(substring(Nombre,CHARINDEX(':',Nombre)+1,LEN(Nombre)-CHARINDEX(':',Nombre)+1),charindex(':',substring(Nombre,CHARINDEX(':',Nombre)+1,LEN(Nombre)-CHARINDEX(':',Nombre)+1))),'.E:','')))
as Nombre,Hora_Entrada,Fecha, Tipo_Comida=  case when Fecha > '12:40:00' then 'Comida' else 'Desayuno' end,
Id_Semana=datepart(week,CONVERT(date,Hora_Entrada,103))
- datepart(week, dateadd(dd,-day(CONVERT(date,Hora_Entrada,103))+1,CONVERT(date,Hora_Entrada,103)))
+1 
from  [dbo].[Entradas]
where Not Nombre = '') as a
where Tipo_Comida = '$hoy' and Hora_Entrada = '$fechaActual'
Group by Hora_Entrada,Tipo_Comida";

  $stmt1 = sqlsrv_query($conn, $sql1);

  $TotalPal = "";


  if (sqlsrv_has_rows($stmt1)) {
    while ($row = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_ASSOC)) {
      //// echo $row['Total'];
      if ($row['Total'] < 200) {
        $TotalPal = 0;
      } else {
        $TotalPal = 1;
      }
    }
  } else {
    $TotalPal = 0;
  }


  /////echo $stmt1;

  ////////////////////////// Validación Totales de cómidas

  if ($TotalPal == 0) {


  if ($cot == 'CAFÉ O TÉ' or $cot == 'TORTILLAS' or $cot == 'AGUA' or $cot == 'DESECHABLE' or $cot == 'COMIDA PARA LLEVAR' ) {
	 $sql = "insert into complementos (Id_Empleado,Nombre,Complemento,Fecha,Hora) Values('','$name','$cot','$fechaActual','$fechaActual1')";  
  }	else  {     $sql = "insert into [dbo].[Entradas] (Id_Empleado,Nombre,Area,Hora_Entrada,Fecha) Values('','$name','','$fechaActual','$fechaActual1')"; }	

    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
      die(print_r(sqlsrv_errors(), true));
    }

    // // while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
    // // echo $row['Usuario'].", ".$row['Contrasena']."<br />";

    // // }

    sqlsrv_free_stmt($stmt);
    // echo $name;
    // echo "Te encuentras registrado";
    // echo $name.", "."Te encuentras registrado"."<br />";

    // echo '<script language="javascript">';
    // echo 'alert("message successfully sent")';
    // echo '</script>';

    /////echo strpos($name,"N.E:")-8;

    /////echo substr($name,8,strpos($name,"N.E:")-8);

    ////// 
    // Cambiar Nombre
    $name1 = explode(":", $name);

    $Com;

    if ($name1[0]  == 'NOMBRE') {
      $name2 = str_replace(" NSS", "", str_replace(" TEL DE EMERGENCIA", "", str_replace("N.E", "", $name1[1])));
    } elseif ($name1[0]  == 'ID') {

      $name2 = str_replace("AREA", "", $name1[2]);
    } else {
      $name2 = str_replace("NSS", "", $name1[0]);
    }

    // echo $name2;
	
	  if ($cot == 'CAFÉ O TÉ' or $cot == 'TORTILLAS' or $cot == 'AGUA' or $cot == 'DESECHABLE' or $cot == 'COMIDA PARA LLEVAR' ) {
		$Com=$cot;
	  } else {
		  
    if ($fechaActual1 > '12:25:00') {
      $Com = 'Comida';
    } else {
      $Com = 'Desayuno';
    }
		  
		  }

    // Cambiar Nombre
    echo '<div  id="demo" style=" font-size: 30px;;;color:red">' . $name2 . ' ' . $fechaActual . ' ' . $fechaActual1 . ' ' . $Com . ' </div>';
  } else {
    echo "No se encuentran lugares disponibles";
  }
}


function test_input($data)
{
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
?>
<script language="javascript">


var dT = <?php echo json_encode($arrayT);?>;

var dT1 = <?php echo json_encode($arrayT1);?>;




document.getElementById("result1").innerHTML = 'Total de platillos disponibles:' +(dT-dT1);

document.getElementById('cot').setAttribute('value','' );	   

  function cafete() {
     alert('Seleccionaste la opción de té o café');
	 
	  // document.getElementById("cot").innerHTML = "CAFÉ O TÉ";
	   
// document.getElementById("cot").text = "My value";

 document.getElementById('cot').setAttribute('value','CAFÉ O TÉ' );	


    document.getElementById("name").focus();

 }
 
   function tortillas() {
     alert('Seleccionaste la opción de tortillas');
 document.getElementById('cot').setAttribute('value','TORTILLAS' );	
   document.getElementById("name").focus(); 

 }
 
    function agua() {
     alert('Seleccionaste la opción de agua');
	 document.getElementById('cot').setAttribute('value','AGUA' );	
   document.getElementById("name").focus();	 
 
 }

    function desechable() {
     alert('Seleccionaste la opción de desechable');
	 document.getElementById('cot').setAttribute('value','DESECHABLE' );	
  document.getElementById("name").focus();	 	 
 } 
 
 
 
     function comidallevar() {
     alert('Seleccionaste la opción de comida para llevar');
	 document.getElementById('cot').setAttribute('value','COMIDA PARA LLEVAR' );	
  document.getElementById("name").focus();	 	 
 } 




  n = <?php echo json_encode($name2); ?>;
  n1 = <?php echo json_encode($fechaActual); ?>;
  n2 = <?php echo json_encode($fechaActual1); ?>;
  n3 = <?php echo json_encode($Com); ?>;


  if (n == null) {} else { //myHTML = "Ticket:"+"<br><br><br>"+ n+ "<br><br><br>" + n1+"<br><br><br>"+n2+"<br><br><br>"+n3+"<br>Gracias por tu consumo<br><br><br><br><br><br><br><br><br><br><br>";
    myHTML = '<table border="0" width="278px"> 	<tr><td> <p align="center"><b><font face="Segoe UI" style="font-size: 16pt">&nbsp;</font></b><img src="/Comedor/Logo2.png" width="116" height="90"></td> 	</tr>	<tr> <td> <p align="center"><b>	<font face="Segoe UI" style="font-size: 16pt">&nbsp;Ticket de Consumo</font></b></td> </tr> <tr> <td height="28">		<b><font face="Segoe UI" style="font-size: 16pt">&nbsp;Fecha:' + n1 + '</font></b></td></tr><tr> <td height="28">		<b><font face="Segoe UI" style="font-size: 16pt">&nbsp;Hora:' + n2 + '</font></b></td></tr> 	<tr>		<td>		<p align="center">		<b><font face="Segoe UI" style="font-size: 16pt">&nbsp;Usuario:</font></b></td>	</tr>	<tr><td>		<p align="center">		<b><font face="Segoe UI" style="font-size: 16pt">&nbsp;' + n + '</font></b></td></tr><tr> 		<td style="vertical-align: top;-webkit-print-color-adjust: exact;print-color-adjust: exact; border-color: #808080; padding: 0.02in" height="90" >			<b><font face="Segoe UI" style="font-size: 16pt">Consumo:' + n3 + '</font></b></td></tr> 	<tr> <td style="vertical-align: top; border-color: #050505; padding: 0.02in">		&nbsp;</td></tr></table>';
  myWin = window.open("about:blank", "_blank");
    myWin.document.write(myHTML);
    myWin.print();
    myWin.close();
  }

  // alert(n)
</script>