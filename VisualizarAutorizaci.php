    <div class="img-container"> <!-- Block parent element -->
    <img src="Logo2.png" width="7%" height="10%"> </div>

<p><a href="http://192.168.100.95/Comedor">Menu principal  </a>   
</p>


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
	
</div>
<div class="inner1"> <table id="example" class="table table-striped table-bordered" width="100%"  style="font-size:100%;background-color:#2D6DA6;color:white;font-weight: bold;"></table> </div>



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

}

////////////////////////////////////// while query tabla

// if ($_SERVER["REQUEST_METHOD"] == "POST") {

// }

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





function Borrar() {
var table = $('#example').DataTable();
 
table.clear()
}

var dataSet = [
]





for (let i = 0; i < dataQ1.length; i++) {
  dataSet.push([dataQ1[i],dataQ2[i],dataQ3[i],dataQ4[i],dataQ5[i],dataQ8[i],dataQ6[i],dataQ7[i]]);
}

// {"CC" : darray1[i],"No" : darray2[i],"Unidad" : darray3[i],"Equipo" : darray4[i]}




;
 
$(document).ready(function () {
    $('#example').DataTable({
        columns: [
            { title: 'Nombre' },
            { title: 'Departamento' },
            { title: 'Jefe inmediato' },
            { title: 'Tipo de consumo a cancelar'},
			{ title: 'Fecha inicio'},
		    { title: 'Fecha fin'},
			{ title: 'Causa'},
		    { title: 'Estatus'},

        ],responsive: true,  data:dataSet,paging:   false
    });
	
	
});


</script>


<script type="text/javascript">


// function Prueba() {
	


	// var dataQ = <?php echo json_encode($array_Q1);?>;
	// var dataQ1 = <?php echo json_encode($array_Q2);?>;
	// var dataQ2 = <?php echo json_encode($array_Q3);?>;
	// var dataQ3 = <?php echo json_encode($array_Q4);?>;
	// var dataQ4 = <?php echo json_encode($array_Q5);?>;
	// var dataQ5 = <?php echo json_encode($array_Q6);?>;
	// var dataQ6 = <?php echo json_encode($array_Q7);?>;
	// var dataQ7 = <?php echo json_encode($array_Q8);?>;
	// var dataQ8 = <?php echo json_encode($array_Q9);?>;
	// var dataQ9 = <?php echo json_encode($array_Q10);?>;
	// var dataQ10 = <?php echo json_encode($array_Q11);?>;	
	
	// ///alert(dataQ)
	
	
	

// var dataT =  <?php echo json_encode($FechaT);?>;
// var dataT1 = <?php echo json_encode($Id_EmpleadoT);?>;
// var dataT2 = <?php echo json_encode($NombreT);?>;
// var dataT3 = <?php echo json_encode($LunesT);?>;
// var dataT4 = <?php echo json_encode($MartesT);?>;
// var dataT5 = <?php echo json_encode($MiercolesT);?>;
// var dataT6 = <?php echo json_encode($JuevesT);?>;
// var dataT7 = <?php echo json_encode($ViernesT);?>;

	
	
// //////Valores controles html
  // // var x = document.getElementById("Semana").value;	
  // // var x1 = document.getElementById("Mes").value;
  // // var x2 = document.getElementById("Anio").value;
  
   // var x3 = document.getElementById("emp").value;
   
   
      // var x4 = document.getElementById("fec").value;
	  
	  // // alert(x4)

// //////Valores controles html
// var desayunos = [];
// var comidas = [];



// ///////////////////////////////////////////////////////////////////////////// Nuevas variables Desayuno
// var desayunos1 = [];
// var desayunos2 = [];
// var desayunos3 = [];
// var desayunos4 = [];
// var desayunos5 = [];

// ///////////////////////////////////////////////////////////////////////////// Nuevas variables Cómida
// var comidas1 = [];
// var comidas2 = [];
// var comidas3 = [];
// var comidas4 = [];
// var comidas5 = [];



// ////var desemanas = ['Lunes','Martes','Miercoles','Jueves','Viernes'];

// ///// Ejemplo

// for (var i = 0; i < dataQ.length; i++) {

	// var dataQ1 //// Cómida 1
	// var dataQ2 
	// var dataQ3  //// Cómida 2 
	// var dataQ4  
	// var dataQ5  //// Cómida 2 
	// var dataQ6  
	// var dataQ7     //// Cómida 4
	// var dataQ8   
	// var dataQ9     //// Cómida 5
	// var dataQ10  


   // if (x4 ===  dataQ[i]) {	
 // //alert(dataQ10[i])
// comidas.push(dataQ1[i],dataQ3[i],dataQ5[i],dataQ7[i],dataQ9[i])
// desayunos.push(dataQ2[i],dataQ4[i],dataQ6[i],dataQ8[i],dataQ10[i])     
   // } 

// }


// ////////////////// código fuente tabla


   // var t = $('#example').DataTable();
   



// // $(document).ready(function(){
    // // $("#example").children("td").first().css({"background-color":"#C94BCB"}); // Choose your color!
// // });
  
    // var counter = 0;
	
	
		
	// ///alert((x2+'-'+x1+'-'+x))
 // var ent= 0;
 // var suma123;

// suma123=0; 


// // if (dataT3[i]=== 'Desayuno' ||  dataT3[i]=== 'Comida') {
  // // /suma123=  suma123+1
// // }
	
// for (var i = 0; i < dataT.length; i++) {
	
// // alert((x2+'-'+x1+'-'+x))
// // /////alert(dataT[i])

  // if (x4 === dataT[i]&& x3 ===dataT1[i]) {	
  // ent=1;
// ////alert('Entro')

// if (dataT3[i]=== 'Desayuno' ||  dataT3[i]=== 'Comida') {
// suma123=  suma123+1
// }
// if (dataT4[i]=== 'Desayuno' ||  dataT4[i]=== 'Comida') {
// suma123=  suma123+1
// }
// if (dataT5[i]=== 'Desayuno' ||  dataT5[i]=== 'Comida') {
// suma123=  suma123+1
// }
// if (dataT6[i]=== 'Desayuno' ||  dataT6[i]=== 'Comida') {
// suma123=  suma123+1
// }
// if (dataT7[i]=== 'Desayuno' ||  dataT7[i]=== 'Comida') {
// suma123=  suma123+1
// }


// t.row.add([dataT[i],dataT1[i],dataT2[i],dataT3[i],dataT4[i],dataT5[i],dataT6[i],dataT7[i]]).draw(false); 

   // }
// }	

// // alert("Tienes "+suma123+" consumos para esta semana")

// document.getElementById("NC").innerHTML = "Tienes "+suma123+" consumos para esta semana";

// if (ent == 0) {
// alert('No se encuentran los resgistros')
// }

// // /////Cambio de color
// // var row = t
    // // .row('1') //This assumes that the official DT row index is named "8", if instead you
    // // .node();  //want the actual 8th row, just remove the ' marks and use row(8)

// // $(row).css("background-color","red");
       // // counter++;
// /////Cambio de color

 // // $('td:eq(4)', row).css('background-color', 'Yellow');  //Original Date

// var app = {};

// var chartDom = document.getElementById('main');
// var myChart = echarts.init(chartDom);
// var option;

// const posList = [
  // 'left',
  // 'right',
  // 'top',
  // 'bottom',
  // 'inside',
  // 'insideTop',
  // 'insideLeft',
  // 'insideRight',
  // 'insideBottom',
  // 'insideTopLeft',
  // 'insideTopRight',
  // 'insideBottomLeft',
  // 'insideBottomRight'
// ];
// app.configParameters = {
  // rotate: {
    // min: -90,
    // max: 90
  // },
  // align: {
    // options: {
      // left: 'left',
      // center: 'center',
      // right: 'right'
    // }
  // },
  // verticalAlign: {
    // options: {
      // top: 'top',
      // middle: 'middle',
      // bottom: 'bottom'
    // }
  // },
  // position: {
    // options: posList.reduce(function (map, pos) {
      // map[pos] = pos;
      // return map;
    // }, {})
  // },
  // distance: {
    // min: 0,
    // max: 100
  // }
// };
// app.config = {
  // rotate: 90,
  // align: 'left',
  // verticalAlign: 'middle',
  // position: 'insideBottom',
  // distance: 15,
  // onChange: function () {
    // const labelOption = {
      // rotate: app.config.rotate,
      // align: app.config.align,
      // verticalAlign: app.config.verticalAlign,
      // position: app.config.position,
      // distance: app.config.distance
    // };
    // myChart.setOption({
      // series: [
        // {
          // label: labelOption
        // },
        // {
          // label: labelOption
        // },
        // {
          // label: labelOption
        // },
        // {
          // label: labelOption
        // }
      // ]
    // });
  // }
// };
// const labelOption = {
  // show: true,
  // position: app.config.position,
  // distance: app.config.distance,
  // align: app.config.align,
  // verticalAlign: app.config.verticalAlign,
  // rotate: app.config.rotate,
  // formatter: '{c}  {name|{a}}',
  // fontSize: 16,
  // rich: {
    // name: {}
  // }
// };
// option = {
  // tooltip: {
    // trigger: 'axis',
    // axisPointer: {
      // type: 'shadow'
    // }
  // },
  // legend: {
    // data: ['Desayunos','Comidas']
  // },
  // toolbox: {
    // show: true,
    // orient: 'vertical',
    // left: 'right',
    // top: 'center',
    // feature: {
      // mark: { show: true },
      // <!-- dataView: { show: true, readOnly: false }, -->
      // <!-- magicType: { show: true, type: ['line', 'bar', 'stack'] }, -->
      // restore: { show: true },
      // saveAsImage: { show: true }
    // }
  // },
  // xAxis: [
    // {
      // type: 'category',
      // axisTick: { show: false },
      // data: ['Lunes', 'Martes', 'Miercoles', 'Jueves','Viernes']
    // }
  // ],
  // yAxis: [
    // {
      // type: 'value'
    // }
  // ],
  // series: [
    // {
      // name: 'Desayunos',
      // type: 'bar',
      // barGap: 0,
      // label: labelOption,
      // emphasis: {
        // focus: 'series'
      // },
      // data: desayunos
    // },
    // {
      // name: 'Comidas',
      // type: 'bar',
      // label: labelOption,
      // emphasis: {
        // focus: 'series'
      // },
      // data: comidas
    // }
  // ]
// };

// option && myChart.setOption(option);



// }


 
// var app = {};

// var chartDom = document.getElementById('main');
// var myChart = echarts.init(chartDom);
// var option;

// const posList = [
  // 'left',
  // 'right',
  // 'top',
  // 'bottom',
  // 'inside',
  // 'insideTop',
  // 'insideLeft',
  // 'insideRight',
  // 'insideBottom',
  // 'insideTopLeft',
  // 'insideTopRight',
  // 'insideBottomLeft',
  // 'insideBottomRight'
// ];
// app.configParameters = {
  // rotate: {
    // min: -90,
    // max: 90
  // },
  // align: {
    // options: {
      // left: 'left',
      // center: 'center',
      // right: 'right'
    // }
  // },
  // verticalAlign: {
    // options: {
      // top: 'top',
      // middle: 'middle',
      // bottom: 'bottom'
    // }
  // },
  // position: {
    // options: posList.reduce(function (map, pos) {
      // map[pos] = pos;
      // return map;
    // }, {})
  // },
  // distance: {
    // min: 0,
    // max: 100
  // }
// };
// app.config = {
  // rotate: 90,
  // align: 'left',
  // verticalAlign: 'middle',
  // position: 'insideBottom',
  // distance: 15,
  // onChange: function () {
    // const labelOption = {
      // rotate: app.config.rotate,
      // align: app.config.align,
      // verticalAlign: app.config.verticalAlign,
      // position: app.config.position,
      // distance: app.config.distance
    // };
    // myChart.setOption({
      // series: [
        // {
          // label: labelOption
        // },
        // {
          // label: labelOption
        // },
        // {
          // label: labelOption
        // },
        // {
          // label: labelOption
        // }
      // ]
    // });
  // }
// };
// const labelOption = {
  // show: true,
  // position: app.config.position,
  // distance: app.config.distance,
  // align: app.config.align,
  // verticalAlign: app.config.verticalAlign,
  // rotate: app.config.rotate,
  // formatter: '{c}  {name|{a}}',
  // fontSize: 16,
  // rich: {
    // name: {}
  // }
// };
// option = {
  // tooltip: {
    // trigger: 'axis',
    // axisPointer: {
      // type: 'shadow'
    // }
  // },
  // legend: {
    // data: ['Desayunos','Comidas']
  // },
  // toolbox: {
    // show: true,
    // orient: 'vertical',
    // left: 'right',
    // top: 'center',
    // feature: {
      // mark: { show: true },
      // <!-- dataView: { show: true, readOnly: false }, -->
      // <!-- magicType: { show: true, type: ['line', 'bar', 'stack'] }, -->
      // restore: { show: true },
      // saveAsImage: { show: true }
    // }
  // },
  // xAxis: [
    // {
      // type: 'category',
      // axisTick: { show: false },
      // data: ['Lunes', 'Martes', 'Miercoles', 'Jueves','Viernes']
    // }
  // ],
  // yAxis: [
    // {
      // type: 'value'
    // }
  // ],
  // series: [
    // {
      // name: 'Desayunos',
      // type: 'bar',
      // barGap: 0,
      // label: labelOption,
      // emphasis: {
        // focus: 'series'
      // },
      // data: []
    // },
    // {
      // name: 'Comidas',
      // type: 'bar',
      // label: labelOption,
      // emphasis: {
        // focus: 'series'
      // },
      // data: []
    // }
  // ]
// };

// option && myChart.setOption(option);


 function theFunction () {
// alert(prueba)
////////////////// product.replace("?", "") 

// prueba.push( product.replace("?", "") );
// alert(prueba)
	
var arrayJson=JSON.stringify(prueba);
 
 			// alert("Se elimino tu contrato");
 
		// mediante ajax, enviamos por POST el json en la variable: arrayDeValores
		$.post("am1.php",{arrayDeValores:arrayJson},function(data) {
				
 alert(data)
 
 window.location.href = 'http://192.168.100.95/Comedor/ValidarFormatos.php/'; //Will take you to Google.
	
			// Mostramos el texto devuelto por el archivo php
		
		});
		

    }
 
</script>



 



