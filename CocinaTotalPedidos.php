<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css"></script>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>"> 

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
  
  <style>
body {
  font-family: Arial Narrow;
  background-color: rgba(241, 238, 237);
   font-size: 20px;
 
}


.img-container {
        text-align:right;
      }
	  
</style>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
     <div class="img-container"> <!-- Block parent element -->
    <img src="Logo2.png" width="75" height="75"> </div>
<body 
<p><a href="Demo_SistemaComedor.html">Menu principal  </a>   
</p>
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
							
	<button  type="button" name="buscar" onclick="Borrar();Prueba();">Buscar</button>			

<div id="main" style="height:45%"></div>

<table id="example" class="display" width="50%"></table>
</body>
</html>

</form>



<?php


$pedido = $name = $email = $gender = $comment = $website = "";



// $serverName = "LUISROMERO\SQLEXPRESS"; //serverName\instanceName
// $connectionInfo = array( "Database"=>"Comedor", "UID"=>"larome02", "PWD"=>"larome02","CharacterSet" => "UTF-8");
// $conn = sqlsrv_connect( $serverName, $connectionInfo);


$serverName = "DESAROLLO-BACRO\SQLEXPRESS"; //serverName\instanceName
$connectionInfo = array( "Database"=>"Comedor", "UID"=>"Larome03", "PWD"=>"Larome03","CharacterSet" => "UTF-8");
$conn = sqlsrv_connect( $serverName, $connectionInfo);


// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }


//////////////////////////////////////////////////Prueba nuevo query
$sql = "Select Fecha,
Sum(CLunes) as CLunes,Sum(DLunes) as DLunes,
Sum(CMartes) as CMartes,Sum(DMartes) as DMartes,
Sum(CMiercoles) as CMiercoles,Sum(DMiercoles) as DMiercoles,
Sum(CJueves) as CJueves,Sum(DJueves) as DJueves,
Sum(CViernes) as CViernes,Sum(DViernes) as DViernes
from (
SELECT Fecha,fecha_dia,ISNULL(CLunes, 0) as CLunes,ISNULL(DLunes, 0) as DLunes ,ISNULL(CMartes, 0) as CMartes ,
ISNULL(DMartes, 0) as DMartes,ISNULL(CMiercoles, 0)  as CMiercoles,ISNULL(DMiercoles, 0)  as DMiercoles,ISNULL(CJueves, 0) as CJueves,ISNULL(DJueves, 0)  as DJueves
,ISNULL(CViernes, 0) as CViernes,ISNULL(DViernes, 0) as DViernes
FROM  
(
Select * from (
Select * , left(descripcion,1)+D as Clave_Uni from  (
Select Fecha,Lunes as descripcion ,left(DATEADD(day, 0, Fecha),12)  as fecha_dia,Count(Lunes) as Total,D='Lunes' from [dbo].[PedidosComida] 
Where  not Lunes = ''
Group  by Fecha,Lunes
union all
Select Fecha,Martes as descripcion,left(DATEADD(day, 1, Fecha),12) as fecha_dia ,Count(*) as Total,D='Martes' from [dbo].[PedidosComida] 
Where  not Martes = ''
Group  by Fecha,Martes
union all
Select Fecha,Miercoles as descripcion,left(DATEADD(day, 2, Fecha),12) as fecha_dia ,Count(*) as Total,D='Miercoles' from [dbo].[PedidosComida] 
Where  not Miercoles = ''
Group  by Fecha,Miercoles
union all
Select Fecha,Jueves as descripcion,left(DATEADD(day, 3, Fecha),12) as fecha_dia ,Count(*) as Total ,D='Jueves' from [dbo].[PedidosComida] 
Where  not Jueves = ''
Group  by Fecha,Jueves
union all
Select Fecha,Viernes as descripcion,left(DATEADD(day, 4, Fecha),12) as fecha_dia ,Count(*) as Total, D='Viernes' from [dbo].[PedidosComida] 
Where  not Viernes = ''
Group  by Fecha,Viernes ) as n
) as f
) AS TableToPivot 
PIVOT  
(  
  SUM(Total)  
  FOR Clave_Uni  IN (CLunes,DLunes,CMartes,DMartes,CMiercoles,DMiercoles,CJueves,DJueves,CViernes,DViernes)  
) AS PivotTable ) as M
Group by Fecha";


$sql1 = "Select Fecha,c.Id_Empleado, Nombre, ISNULL(Lunes, '') as Lunes, ISNULL(Martes, '') as Martes, ISNULL(Miercoles, '') as Miercoles
,ISNULL(Jueves, '') as Jueves,ISNULL(Viernes, '')  as Viernes
from (Select Id_Empleado,Nombre,Area from [dbo].[Catalogo_EmpArea]) as a
left join
(Select * from (Select *  from [dbo].[PedidosComida] ) as b) as c
on a.Id_Empleado = c.Id_Empleado";



$stmt = sqlsrv_query( $conn, $sql );


$stmt1 = sqlsrv_query( $conn, $sql1);


if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}

/////////////////// Variables dias 


$array_tot1 = [];
$array_tot2 = [];
$array_tot3 = [];
$array_tot4 = [];


/////////////////////////// Variables arreglos querys nuevos.
$array_Q1 = [];
$array_Q2 = [];
$array_Q3 = [];
$array_Q4 = [];
$array_Q5 = [];
$array_Q6 = [];
$array_Q7 = [];
$array_Q8 = [];
$array_Q9 = [];
$array_Q10 = [];
$array_Q11 = [];


while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {


/////////////////////////////////////////////////// Array nuevas variables
array_push($array_Q1,$row['Fecha']);

////echo $row['CLunes'];
array_push($array_Q2,$row['CLunes']);
array_push($array_Q3,$row['DLunes']);
array_push($array_Q4,$row['CMartes']);
array_push($array_Q5,$row['DMartes']);
array_push($array_Q6,$row['CMiercoles']);
array_push($array_Q7,$row['DMiercoles']);
array_push($array_Q8,$row['CJueves']);
array_push($array_Q9,$row['DJueves']);
array_push($array_Q10,$row['CViernes']);
array_push($array_Q11,$row['DViernes']);

}

////////////////////////////////////// while query tabla

$FechaT = [];
$Id_EmpleadoT = [];
$NombreT = [];
$LunesT = [];
$MartesT = [];
$MiercolesT = [];
$JuevesT = [];
$ViernesT = [];


while( $row = sqlsrv_fetch_array( $stmt1, SQLSRV_FETCH_ASSOC) ) {
	array_push($FechaT,$row['Fecha']);
	array_push($Id_EmpleadoT,$row['Id_Empleado']);
	array_push($NombreT,$row['Nombre']);
	array_push($LunesT,$row['Lunes']);
	array_push($MartesT,$row['Martes']);
	array_push($MiercolesT,$row['Miercoles']);
	array_push($JuevesT,$row['Jueves']);
	array_push($ViernesT,$row['Viernes']);
	

}



sqlsrv_free_stmt( $stmt);
sqlsrv_free_stmt( $stmt1);



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

function Borrar() {
var table = $('#example').DataTable();
 
table.clear()
}


var dataSet = [
];
 
$(document).ready(function () {
    $('#example').DataTable({
        columns: [
            { title: 'Fecha' },
            { title: 'Id_Empleado' },
            { title: 'Nombre' },
            { title: 'Lunes'},
			{ title: 'Martes'},
		    { title: 'Miercoles'},
			{ title: 'Jueves'},
			{ title: 'Viernes'},
        ],
    });
});


</script>



<script type="text/javascript">


function Prueba() {
	


	var dataQ = <?php echo json_encode($array_Q1);?>;
	var dataQ1 = <?php echo json_encode($array_Q2);?>;
	var dataQ2 = <?php echo json_encode($array_Q3);?>;
	var dataQ3 = <?php echo json_encode($array_Q4);?>;
	var dataQ4 = <?php echo json_encode($array_Q5);?>;
	var dataQ5 = <?php echo json_encode($array_Q6);?>;
	var dataQ6 = <?php echo json_encode($array_Q7);?>;
	var dataQ7 = <?php echo json_encode($array_Q8);?>;
	var dataQ8 = <?php echo json_encode($array_Q9);?>;
	var dataQ9 = <?php echo json_encode($array_Q10);?>;
	var dataQ10 = <?php echo json_encode($array_Q11);?>;	
	
	///alert(dataQ)
	
	
	

var dataT =  <?php echo json_encode($FechaT);?>;
var dataT1 = <?php echo json_encode($Id_EmpleadoT);?>;
var dataT2 = <?php echo json_encode($NombreT);?>;
var dataT3 = <?php echo json_encode($LunesT);?>;
var dataT4 = <?php echo json_encode($MartesT);?>;
var dataT5 = <?php echo json_encode($MiercolesT);?>;
var dataT6 = <?php echo json_encode($JuevesT);?>;
var dataT7 = <?php echo json_encode($ViernesT);?>;

	
	
//////Valores controles html
  var x = document.getElementById("Semana").value;	
  var x1 = document.getElementById("Mes").value;
  var x2 = document.getElementById("Anio").value;
//////Valores controles html
var desayunos = [];
var comidas = [];



///////////////////////////////////////////////////////////////////////////// Nuevas variables Desayuno
var desayunos1 = [];
var desayunos2 = [];
var desayunos3 = [];
var desayunos4 = [];
var desayunos5 = [];

///////////////////////////////////////////////////////////////////////////// Nuevas variables Cómida
var comidas1 = [];
var comidas2 = [];
var comidas3 = [];
var comidas4 = [];
var comidas5 = [];



////var desemanas = ['Lunes','Martes','Miercoles','Jueves','Viernes'];

///// Ejemplo

for (var i = 0; i < dataQ.length; i++) {

	var dataQ1 //// Cómida 1
	var dataQ2 
	var dataQ3  //// Cómida 2 
	var dataQ4  
	var dataQ5  //// Cómida 2 
	var dataQ6  
	var dataQ7     //// Cómida 4
	var dataQ8   
	var dataQ9     //// Cómida 5
	var dataQ10  


   if ((x2+'-'+x1+'-'+x) ===  dataQ[i]) {	
 //alert(dataQ10[i])
comidas.push(dataQ1[i],dataQ3[i],dataQ5[i],dataQ7[i],dataQ9[i])
desayunos.push(dataQ2[i],dataQ4[i],dataQ6[i],dataQ8[i],dataQ10[i])     
   } 

}


////////////////// código fuente tabla


   var t = $('#example').DataTable();
  
    var counter = 0;
	
	
		
	///alert((x2+'-'+x1+'-'+x))

	
for (var i = 0; i < dataT.length; i++) {
	
// alert((x2+'-'+x1+'-'+x))
// /////alert(dataT[i])

  if ((x2+'-'+x1+'-'+x) === dataT[i]) {	
////alert('Entro')
t.row.add([dataT[i],dataT1[i],dataT2[i],dataT3[i],dataT4[i],dataT5[i],dataT6[i],dataT7[i]]).draw(false); 

   } 	
}	
       // counter++;

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
    data: ['Desayunos','Comidas']
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
      data: ['Lunes', 'Martes', 'Miercoles', 'Jueves','Viernes']
    }
  ],
  yAxis: [
    {
      type: 'value'
    }
  ],
  series: [
    {
      name: 'Desayunos',
      type: 'bar',
      barGap: 0,
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: desayunos
    },
    {
      name: 'Comidas',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: comidas
    }
  ]
};

option && myChart.setOption(option);



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
    data: ['Desayunos','Comidas']
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
      data: ['Lunes', 'Martes', 'Miercoles', 'Jueves','Viernes']
    }
  ],
  yAxis: [
    {
      type: 'value'
    }
  ],
  series: [
    {
      name: 'Desayunos',
      type: 'bar',
      barGap: 0,
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: []
    },
    {
      name: 'Comidas',
      type: 'bar',
      label: labelOption,
      emphasis: {
        focus: 'series'
      },
      data: []
    }
  ]
};

option && myChart.setOption(option);
 
</script>



