
<?php

$serverName = "DESAROLLO-BACRO\SQLEXPRESS"; //serverName\instanceName
$connectionInfo = array("Database"=>"Comedor", "UID"=>"Larome03", "PWD"=>"Larome03","CharacterSet" => "UTF-8");
$conn = sqlsrv_connect( $serverName, $connectionInfo);


// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }

// session_start();
// Save variables into session
$_SESSION['somevalue'] = $valor;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // $name1 = test_input($_POST["yearFilter"]); /// Fecha inicio
  // $name2 = test_input($_POST["monthFilter"]);  /// Fecha fin
  
$name4= test_input($_POST["fechaInicio"]);
$name5= test_input($_POST["fechaFin"]);
	
// echo $name4;
// echo $name5;
  
  // fechaInicio
  // fechaFin
  // $name3= $name1."-".$name2;
  
     // echo $name1;
	 // echo $name2;
if (  $name2 == "TODOS") {
  $name3= $name1."-";
} else {
  $name3= $name1."-".$name2;
}



//////////////////// control PLACAS
/////Query ordenes de cancelación de alimentos.
$sqlG1 = "SELECT 
    E5.*, 
    d2.*
FROM (
    -- ENTRADAS (d2): queremos conservar todo esto, aunque no haga match
    SELECT  
        Fecha1,
        NE_EXTRAIDO1 AS No_Empleado,
        c.Nombre,
        Tipo_Comida
    FROM (
       
Select    * from (
Select a1.Id_Empleado,a1.Nombre,a1.Area,a1.Hora_Entrada,a1.Fecha,a1.NE_Extraido,a1.Tipo_Comida,a1.Fecha1,
NE_EXTRAIDO1 =case when a1.NE_EXTRAIDO1 is NULL or a1.NE_EXTRAIDO1=''   or a1.NE_EXTRAIDO1='NULL' then a2.Id_Empleado else a1.NE_EXTRAIDO1  end 
 from (


   SELECT *, 
            NE_EXTRAIDO1 = 
                CASE 
                    WHEN Nombre LIKE '%dionisio%' THEN '46'
                    WHEN Nombre LIKE '%esquivel edgar%' OR nombre LIKE '%edgar gutie%' OR nombre LIKE '%GUTIERREZ EZQUIVEL%'  THEN '18' 
                    WHEN Nombre LIKE '%Luna castro%' THEN '1' 
                    ELSE NE_Extraido 
                END
        FROM (
            SELECT *,
                LTRIM(RTRIM(
                    CASE 
                        WHEN CHARINDEX('N.E:', nombre) > 0 THEN
                            SUBSTRING(
                                nombre,
                                CHARINDEX('N.E:', nombre) + LEN('N.E:'), 
                                CASE 
                                    WHEN CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('N.E:', nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('N.E:', nombre)) - (CHARINDEX('N.E:', nombre) + LEN('N.E:'))
                                    ELSE LEN(nombre)
                                END
                            )
                        WHEN CHARINDEX('NE: ', nombre) > 0 THEN
                            SUBSTRING(
                                nombre,
                                CHARINDEX('NE: ', nombre) + LEN('NE: '), 
                                CASE 
                                    WHEN CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('NE: ', nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('NE: ', nombre)) - (CHARINDEX('NE: ', nombre) + LEN('NE: '))
                                    ELSE LEN(nombre)
                                END
                            )
                        WHEN CHARINDEX('NE:', nombre) > 0 THEN
                            SUBSTRING(
                                nombre,
                                CHARINDEX('NE:', nombre) + LEN('NE:'), 
                                CASE 
                                    WHEN CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('NE:', nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('NE:', nombre)) - (CHARINDEX('NE:', nombre) + LEN('NE:'))
                                    ELSE LEN(nombre)
                                END
                            )
                        WHEN CHARINDEX('ID:NE0', nombre) > 0 THEN
                            SUBSTRING(
                                nombre,
                                CHARINDEX('ID:NE0', nombre) + LEN('ID:NE0'), 
                                CASE 
                                    WHEN CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('ID:NE0', nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('ID:NE0', nombre)) - (CHARINDEX('ID:NE0', nombre) + LEN('ID:NE0'))
                                    ELSE LEN(nombre)
                                END
                            )
                        ELSE NULL
                    END
                )) AS NE_Extraido,
                CASE 
                    WHEN CAST(Fecha AS TIME) < '12:00:00' THEN 'Desayuno'
                    ELSE 'Comida'
                END AS Tipo_Comida,
                CONVERT(VARCHAR(10), CONVERT(DATE, Hora_Entrada, 105), 23) AS Fecha1
            FROM Entradas
            WHERE not nombre='.'  and not nombre='' and not nombre LIKE '[0-9]%'  AND (convert(date,Hora_Entrada, 103)  >= '$name4' and convert(date,Hora_Entrada, 103)  <= '$name5')  
        ) AS a ) as a1

left JOIN (

select m1.Nombre,m1.NombreV,m2.Id_Empleado,Hora_Entrada,fecha from (
Select Nombre, LEFT(Nombre, CHARINDEX(' se encuentra registrado para el', Nombre) - 1) as NombreV ,hora_entrada,fecha from entradas
where Nombre LIKE '%se encuentra registrado para el%' AND (convert(date,Hora_Entrada, 103)  >= '$name4' and convert(date,Hora_Entrada, 103)  <= '$name5') ) as  m1
left join (Select * from conped) as m2  on  m1.NombreV=m2.Nombre)  as a2
on a1.Nombre=a2.Nombre and a1.Hora_Entrada=a2.Hora_Entrada and a1.fecha=a2.fecha ) as a
    ) AS b
    LEFT JOIN (SELECT Id_Empleado, Nombre FROM ConPed) AS c ON b.NE_EXTRAIDO1 = c.Id_Empleado
) AS d2

left join (
    -- E5: PedidosComida cruzados con ConPed
    SELECT CAST(Fecha AS char) as fecha,Id_Empleado,Nombre,Tipo_Comida  FROM (
        SELECT 
            CAST(Fecha_Dia AS DATE) AS Fecha,
            Id_Empleado,
            Nombre,
            Tipo_Comida
        FROM (
            SELECT * FROM (
                SELECT 
                    Id_Empleado,
                    Usuario,
                    Fecha AS Fecha_Base,
                    Dia,
                    -- Calcular la fecha del día correspondiente en la semana
                    DATEADD(DAY,
                        CASE Dia
                            WHEN 'Lunes' THEN 0
                            WHEN 'Martes' THEN 1
                            WHEN 'Miercoles' THEN 2
                            WHEN 'Jueves' THEN 3
                            WHEN 'Viernes' THEN 4
                        END
                        - (DATEPART(WEEKDAY, Fecha) + @@DATEFIRST - 2) % 7
                    , Fecha) AS Fecha_Dia,
                    Tipo_Comida,
                    Costo
                FROM PedidosComida 
                CROSS APPLY (
                    VALUES 
                        ('Lunes', Lunes),
                        ('Martes', Martes),
                        ('Miercoles', Miercoles),
                        ('Jueves', Jueves),
                        ('Viernes', Viernes)
                ) AS unpivoted(Dia, Tipo_Comida)
            ) AS e1
            RIGHT JOIN 
                (SELECT Id_Empleado AS Id_Empleado2, Nombre FROM ConPed) AS e2
                ON e1.Id_Empleado = e2.Id_Empleado2
        ) AS E4
    ) AS E5
	where (ltrim(rtrim(Fecha))>= '$name4' and  ltrim(rtrim(Fecha)) <= '$name5') and not Tipo_Comida='' 
) AS E5
    ON CAST(E5.Fecha AS CHAR) + cast(E5.Id_Empleado as char) + E5.Tipo_Comida =
       CAST(d2.Fecha1 AS CHAR) + cast(d2.No_Empleado as char) + d2.Tipo_Comida

---- Filtramos por la fecha de d2, NO por E5
--where (convert(date,ltrim(rtrim(d2.Fecha1)), 103)  >= '2025-06-03' and convert(date,ltrim(rtrim(d2.Fecha1)), 103)  <= '2025-06-04')
ORDER BY d2.Fecha1, CAST(d2.No_Empleado AS FLOAT);";
/////Query ordenes de cancelación de alimentos.

/// Ejecutar Query
$stmtG1  = sqlsrv_query( $conn,$sqlG1);



$array_G1 = [];
$array_G2 = [];
$array_G3 = [];
$array_G4 = [];
$array_G5 = [];
$array_G6 = [];
$array_G7 = [];
$array_G8 = [];



while( $row = sqlsrv_fetch_array($stmtG1,SQLSRV_FETCH_NUMERIC) ) {



array_push($array_G1,$row[0]);
array_push($array_G2,$row[1]);
array_push($array_G3,$row[2]);
array_push($array_G4,$row[3]);
array_push($array_G5,$row[4]);
array_push($array_G6,$row[5]);
array_push($array_G7,$row[6]);
array_push($array_G8,$row[7]);



}



 
////////sumatoria- total  checador
$sqlTCH = "SELECT 
    FORMAT(CONVERT(DATE, Fecha), 'yyyy-MM') AS Mes,
    Tipo_Comida,
    COUNT(*) AS Cantidad,
    SUM(COUNT(*)) OVER (PARTITION BY FORMAT(CONVERT(DATE, Fecha), 'yyyy-MM')) AS Total_Mensual
FROM (
    SELECT 
        CONVERT(VARCHAR(10), CONVERT(DATE, Hora_Entrada, 105), 23) AS Fecha,
        CASE 
            WHEN Nombre LIKE '%dionisio%' THEN '46'
            WHEN Nombre LIKE '%esquivel edgar%' OR nombre LIKE '%edgar gutie%' THEN '18' 
            WHEN Nombre LIKE '%Luna castro%' THEN '1' 
            ELSE
                LTRIM(RTRIM(
                    CASE 
                        WHEN CHARINDEX('N.E:', nombre) > 0 THEN
                            SUBSTRING(
                                nombre,
                                CHARINDEX('N.E:', nombre) + LEN('N.E:'), 
                                CASE 
                                    WHEN CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('N.E:', nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('N.E:', nombre)) - (CHARINDEX('N.E:', nombre) + LEN('N.E:'))
                                    ELSE LEN(nombre)
                                END
                            )
                        WHEN CHARINDEX('NE: ', nombre) > 0 THEN
                            SUBSTRING(
                                nombre,
                                CHARINDEX('NE: ', nombre) + LEN('NE: '), 
                                CASE 
                                    WHEN CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('NE: ', nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('NE: ', nombre)) - (CHARINDEX('NE: ', nombre) + LEN('NE: '))
                                    ELSE LEN(nombre)
                                END
                            )
                        WHEN CHARINDEX('NE:', nombre) > 0 THEN
                            SUBSTRING(
                                nombre,
                                CHARINDEX('NE:', nombre) + LEN('NE:'), 
                                CASE 
                                    WHEN CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('NE:', nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('NE:', nombre)) - (CHARINDEX('NE:', nombre) + LEN('NE:'))
                                    ELSE LEN(nombre)
                                END
                            )
                        WHEN CHARINDEX('ID:NE0', nombre) > 0 THEN
                            SUBSTRING(
                                nombre,
                                CHARINDEX('ID:NE0', nombre) + LEN('ID:NE0'), 
                                CASE 
                                    WHEN CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('ID:NE0', nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('ID:NE0', nombre)) - (CHARINDEX('ID:NE0', nombre) + LEN('ID:NE0'))
                                    ELSE LEN(nombre)
                                END
                            )
                        ELSE NULL
                    END
                ))
        END AS No_Empleado,
        nombre,
        CASE 
            WHEN CAST(Fecha AS TIME) < '12:00:00' THEN 'Desayuno'
            ELSE 'Comida'
        END AS Tipo_Comida
    FROM Entradas
      WHERE not nombre='.'  and not nombre='' and not nombre LIKE '[0-9]%'
) AS Base
where Fecha >= '$name4' and    Fecha<= '$name5' 
GROUP BY FORMAT(CONVERT(DATE, Fecha), 'yyyy-MM'), Tipo_Comida
ORDER BY Mes, Tipo_Comida;";


/// Ejecutar Query
$stmtTCH  = sqlsrv_query( $conn,$sqlTCH);



$array_TCH1 = [];
$array_TCH2 = [];
$array_TCH3 = [];
$array_TCH4 = [];




while( $row = sqlsrv_fetch_array($stmtTCH,SQLSRV_FETCH_NUMERIC) ) {



array_push($array_TCH1,$row[0]);
array_push($array_TCH2,$row[1]);
array_push($array_TCH3,$row[2]);
array_push($array_TCH4,$row[3]);



}





//////////////////




//////// sumatoria- total  reservaciones
 $sqlTR = "SELECT 
    FORMAT(Fecha, 'yyyy-MM') AS Mes,
    Tipo_Comida,
    COUNT(*) AS Cantidad,
    SUM(COUNT(*)) OVER (PARTITION BY FORMAT(Fecha, 'yyyy-MM')) AS Total_Mensual
FROM (
    SELECT 
        CAST(
            DATEADD(DAY,
                CASE Dia
                    WHEN 'Lunes' THEN 0
                    WHEN 'Martes' THEN 1
                    WHEN 'Miercoles' THEN 2
                    WHEN 'Jueves' THEN 3
                    WHEN 'Viernes' THEN 4
                END
                - (DATEPART(WEEKDAY, Fecha) + @@DATEFIRST - 2) % 7,
                Fecha
            ) AS DATE
        ) AS Fecha,
        Tipo_Comida
    FROM PedidosComida 
    CROSS APPLY (
        VALUES 
            ('Lunes', Lunes),
            ('Martes', Martes),
            ('Miercoles', Miercoles),
            ('Jueves', Jueves),
            ('Viernes', Viernes)
    ) AS unpivoted(Dia, Tipo_Comida)
    WHERE Tipo_Comida IS NOT NULL AND LTRIM(RTRIM(Tipo_Comida)) <> ''
) AS Datos
where Fecha >= '$name4' and    Fecha<= ' $name5' 
GROUP BY 
    FORMAT(Fecha, 'yyyy-MM'),
    Tipo_Comida
ORDER BY 
    Mes, Tipo_Comida;";

///////////////////////////////////////
/// Ejecutar Query
$stmtTR = sqlsrv_query( $conn,$sqlTR);



$stmtTR1 = [];
$stmtTR2 = [];
$stmtTR3 = [];
$stmtTR4 = [];




while( $row = sqlsrv_fetch_array($stmtTR,SQLSRV_FETCH_NUMERIC) ) {



array_push($stmtTR1,$row[0]);
array_push($stmtTR2,$row[1]);
array_push($stmtTR3,$row[2]);
array_push($stmtTR4,$row[3]);



}



 }
 

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
 }



?>

<!DOCTYPE html>
<html lang="es">
<head>

  <meta charset="UTF-8" />

  <title>Reporte Comedor</title>

  <!-- Fuente Poppins para header -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap" rel="stylesheet" />

  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

  <!-- ECharts -->
  <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>

  <!-- Export libraries -->
  <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  
  
  
  <!-- CSS de Select -->
<link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css">

<!-- JS de Select -->
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Flatpickr CSS -->
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      background: #f5f7fa;
    }

    header {
      background:#265d88;
      color: white;
      padding: 20px;
      text-align: center;
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      font-size: 2.4rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      user-select: none;
      box-shadow: 0 3px 10px rgba(0,0,0,0.3);
    }

    footer {
      background:#265d88;
      color: white;
      text-align: center;
      padding: 10px;
      margin-top: 50px;
      font-size: 0.9rem;
    }

    .container {
      max-width: 1200px;
      margin: 30px auto;
      padding: 0 20px;
    }

    h2 {
      margin-top: 0;
    }

    h3 {
      margin-top: 40px;
      color: #004085;
      border-bottom: 3px solid #007bff;
      padding-bottom: 5px;
    }

    .filter-bar {
      margin: 20px 0;
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
      align-items: center;
    }

    .filter-bar select {
      padding: 6px 12px;
      border-radius: 5px;
      border: 1px solid #ccc;
      font-size: 1rem;
    }

    .filter-bar button {
      padding: 6px 16px;
      border-radius: 4px;
      cursor: pointer;
      border: none;
      font-weight: 600;
      font-size: 1rem;
      transition: background-color 0.3s ease;
    }

    #buscarBtn {
      background: #007bff; color: white;
    }

    #buscarBtn:hover {
      background: #0056b3;
    }

    #limpiarBtn {
      background: #6c757d; color: white;
    }

    #limpiarBtn:hover {
      background: #565e64;
    }

    #exportExcelBtn {
      background: #28a745; color: white;
    }

    #exportExcelBtn:hover {
      background: #1e7e34;
    }

    #exportPdfBtn {
      background: #dc3545; color: white;
    }

    #exportPdfBtn:hover {
      background: #a71d2a;
    }

    .charts-row {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
      margin-top: 20px;
    }

    .chart-container {
      flex: 1 1 40%;
      min-width: 300px;
      height: 400px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      padding: 20px;
    }

    table.dataTable {
      border-radius: 8px;
      background: white;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    /* Encabezados tablas: azul marino con texto blanco */
    table.dataTable thead th {
      background-color: #265d88 !important;
      color: white !important;
    }

    .reservaciones {
      background-color: #cce5ff !important;
      color: #004085 !important;
    }

    .checador {
      background-color: #d4edda !important;
      color: #155724 !important;
    }

    @media (max-width: 768px) {
      .charts-row {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>

  <header>
    REPORTE COMEDOR
  </header>

  <div class="container">
     <a href="Demo_SistemaComedor.html"><i class="fas fa-arrow-left"></i> Menú principal</a>
<form method="post" action=""> 
    <!-- FILTROS -->
    <div class="filter-bar">
 
	  <div class="row mb-3">
      <div class="col-md-6">
        <label for="fechaInicio" class="form-label">Fecha Inicio</label>
        <input type="date" class="form-control" id="fechaInicio" name="fechaInicio">
      </div>
      <div class="col-md-6">
        <label for="fechaFin" class="form-label">Fecha Fin</label>
        <input type="date" class="form-control" id="fechaFin" name="fechaFin">
      </div>
    </div>
	
      <button type="submit"  id="buscarBtn">Buscar</button> </form>

      <button id="exportExcelBtn">Exportar Excel</button>
    </div>

    <!-- TABLA PRINCIPAL -->
    <h3>Reservaciones vs Checador</h3>
<div style="overflow-x: auto;">
  <table id="comidasTable" class="display nowrap" style="width:100%"></table>
</div>

    <!-- TABLA + GRÁFICO RESERVACIONES -->
     <h3>Totales Checador por Tipo y Mes</h3>
    <div class="charts-row">
      <div style="flex: 1 1 55%;">
        <table id="totalesReservacionesTable" class="display nowrap" style="width:100%">
	

        </table>
      </div>
	  
	 
    </div>


	 <div id="graficaComparativa" style="width: 100%; height: 500px; margin-bottom: 30px;"></div>
    </div>

  </div>

  <footer>
    Dashboard generado automáticamente • © 2025
  </footer>

  <script>
  
   var dataG1 = <?php echo json_encode($array_G1);?>;
   var dataG2 = <?php echo json_encode($array_G2);?>;
   var dataG3 = <?php echo json_encode($array_G3);?>;
   var dataG4 = <?php echo json_encode($array_G4);?>;
   var dataG5 = <?php echo json_encode($array_G5);?>;
   var dataG6 = <?php echo json_encode($array_G6);?>;
   var dataG7 = <?php echo json_encode($array_G7);?>;
   var dataG8 = <?php echo json_encode($array_G8);?>;
   
   // alert(dataG1)
      // alert(dataG2)
	     // alert(dataG3)
  
  
// $array_TCH1 = [];
// $array_TCH2 = [];
// $array_TCH3 = [];
// $array_TCH4 = [];

  
     var dataTCH1 = <?php echo json_encode($array_TCH1);?>;
	 var dataTCH2 = <?php echo json_encode($array_TCH2);?>;
	 var dataTCH3 = <?php echo json_encode($array_TCH3);?>;
	 var dataTCH4 = <?php echo json_encode($array_TCH4);?>;
	 
	 // alert(dataTCH1)
	 	 // alert(dataTCH2)
		 
		 
		 
		 /////  reservaciones 


     var dataTR1= <?php echo json_encode($stmtTR1);?>;
	 var dataTR2 = <?php echo json_encode($stmtTR2);?>;
	 var dataTR3= <?php echo json_encode($stmtTR3);?>;
	 var dataTR4= <?php echo json_encode($stmtTR4);?>;
	 
	 // alert(dataTR1)
	 
	 

	 ///// llena tabla 1
	 	 var dataSet = [ ];
		
		 
	 
	 for (var i = 0; i < dataG1.length; i++) {
	dataSet.push([dataG1[i],dataG2[i],dataG3[i],dataG4[i],dataG5[i],dataG6[i],dataG7[i],dataG8[i]]);
	}	
	 ///// llena tabla 1


	 /////  reservaciones 
  
  
    $(document).ready(function () {
      // $('#comidasTable').DataTable({ scrollX: true });
	  
	  
new DataTable('#comidasTable', {
  data: dataSet,
  columns: [
    { title: 'Fecha (Reservación)' },
    { title: 'ID Empleado' },
    { title: 'Nombre Reservación' },
    { title: 'Tipo de Comida (Reservación)' },
    { title: 'Fecha (Checador)' },
    { title: 'No. Empleado' },
    { title: 'Nombre Checador' },
    { title: 'Tipo de Comida (Checador)' }
  ],
  paging: true,
  pageLength: 5000, // 📌 Aumenta el número de filas por página (puedes ajustarlo a 50 o más si deseas)
  lengthMenu: [5000,10000,15000,20000], // 📌 Opciones de cantidad de filas por página
  ordering: true,
  responsive: true,
  ordering: true,
  order: [[4, 'asc'], [0, 'asc']], // 🟢 Ordenar por Fecha (Checador) y luego Fecha (Reservación)
  scrollX: true,
  scrollY: '400px', // 📌 Scroll vertical habilitado con altura fija
  language: {
    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
  },
  select: true,
  dom: 'frtip'
});



///// llena tabla 2
 	 var dataSet2 = [];
	 
    for (var i = 0; i < dataTCH1.length; i++) {
	dataSet2.push([dataTR1[i],dataTR2[i],dataTR3[i],dataTR4[i],dataTCH1[i],dataTCH2[i],dataTCH3[i],dataTCH4[i]]);
	}	
	
new DataTable('#totalesReservacionesTable', {
  data: dataSet2,
  columns: [
    { title: 'Mes_Reservación' },
    { title: 'Tipo' },
    { title: 'Cantidad' },
    { title: 'Total' },    { title: 'Mes_Checador' },
    { title: 'Tipo' },
    { title: 'Cantidad' },
    { title: 'Total' }
  ],
  paging: true,
  pageLength:2, // 📌 Aumenta el número de filas por página (puedes ajustarlo a 50 o más si deseas)
  // lengthMenu: [5, 10, 25, 50, 100], // 📌 Opciones de cantidad de filas por página
  ordering: true,
  responsive: true,
  scrollX: true,
  scrollY: '400px', // 📌 Scroll vertical habilitado con altura fija
  language: {
    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
  },
  select: true,
  dom: 'frtip'
});	
	
	
	
	
	
// ///// llena tabla 3
 	 // var dataSet3 = []; 
    // for (var i = 0; i < dataTR1.length; i++) {
	// dataSet3.push([dataTR1[i],dataTR2[i],dataTR3[i],dataTR4[i]]);
	// }	
	  
// new DataTable('#totalesChecadorTable', {
  // data: dataSet3,
  // columns: [
    // { title: 'Mes' },
    // { title: 'Tipo_Comida' },
    // { title: 'Cantidad' },
    // { title: 'Total_Mensual' }
  // ],
  // paging: true,
  // pageLength:2, // 📌 Aumenta el número de filas por página (puedes ajustarlo a 50 o más si deseas)
  // // lengthMenu: [5, 10, 25, 50, 100], // 📌 Opciones de cantidad de filas por página
  // ordering: true,
  // responsive: true,
  // scrollX: true,
  // scrollY: '400px', // 📌 Scroll vertical habilitado con altura fija
  // language: {
    // url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
  // },
  // select: true,
  // dom: 'frtip'
// });	
		  
	  
	      // var reservacionesData = []; 
	  
///////////////////llenar reservaciones  gráfica 

// Generador de datos JSON para reservaciones
// const tipos = ['Comida', 'Desayuno'];
// const meses = ['2025-06', '2025-07'];
// const reservacionesData = [];

// // for (let i = 0; i < dataTR1.length; i++) {
  // for (let j = 0; j < dataTR1.length; j++) {
    // // const cantidad = Math.floor(Math.random() * (1000 - 500 + 1)) + 500; // Número aleatorio entre 500 y 1000
    // reservacionesData.push({
      // mes: dataTR1[i],
      // tipo: dataTR2[i],
      // cantidad: dataTR3[i]
    // });
  // }
  
  
   var reservacionesData = [];  
  for (var i = 0; i < dataTR1.length; i++) {
	
 reservacionesData.push({ mes:dataTR1[i], tipo:dataTR2[i], cantidad:dataTR3[i] });
 
	}	
// }
// checadorData  dataTCH1


   var checadorData = [];  
  for (var i = 0; i < dataTCH1.length; i++) {
	
 checadorData.push({ mes:dataTCH1[i], tipo:dataTCH2[i], cantidad:dataTCH3[i] });
 
	}	
	
	///////////////////
	// Obtener meses y tipos únicos
  const mesesSet = new Set();
  const tiposSet = new Set();

  reservacionesData.forEach(d => {
    mesesSet.add(d.mes);
    tiposSet.add(d.tipo);
  });

  checadorData.forEach(d => {
    mesesSet.add(d.mes);
    tiposSet.add(d.tipo);
  });

  const meses = Array.from(mesesSet).sort();
  const tipos = Array.from(tiposSet);

  // Agrupador por tipo y mes
  function agruparPorTipoYMes(data, tipoFiltro, meses) {
    return meses.map(mes => {
      const total = data
        .filter(d => d.mes === mes && d.tipo === tipoFiltro)
        .reduce((sum, d) => sum + parseInt(d.cantidad), 0);
      return total;
    });
  }

  // Crear series
  const series = [];
  tipos.forEach(tipo => {
    series.push({
      name: `Reservaciones - ${tipo}`,
      type: 'bar',
      data: agruparPorTipoYMes(reservacionesData, tipo, meses)
    });

    series.push({
      name: `Checador - ${tipo}`,
      type: 'bar',
      data: agruparPorTipoYMes(checadorData, tipo, meses)
    });
  });

  // Inicializar gráfica ECharts
  var chart = echarts.init(document.getElementById('graficaComparativa'));

  chart.setOption({
    title: {
      text: 'Comparativa: Reservaciones vs Checador por Tipo y Mes',
      left: 'center'
    },
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' }
    },
    legend: { top: 40 },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: { type: 'category', data: meses },
    yAxis: { type: 'value', name: 'Cantidad' },
    series: series,
    color: ['#007bff', '#28a745', '#ffc107', '#17a2b8', '#6f42c1', '#e83e8c']
  });
	
	/////////////////////
	
	

////////////////////// llenar reservaciones gráfica	  
	  
	  
	  
      // $('#totalesReservacionesTable').DataTable();
      // $('#totalesChecadorTable').DataTable();

      // Datos de gráficos simulados:
      // const reservacionesData = [
        // { mes: '2025-06', tipo: 'Comida', cantidad: 855 },
        // { mes: '2025-06', tipo: 'Desayuno', cantidad: 742 },
        // { mes: '2025-07', tipo: 'Comida', cantidad: 561 },
        // { mes: '2025-07', tipo: 'Desayuno', cantidad: 520 }
      // ];
      // const checadorData = [
        // { mes: '2023-01', tipo: 'Comida', cantidad: 84 },
        // { mes: '2023-01', tipo: 'Desayuno', cantidad: 86 }
      // ];

      // function transformData(data) {
        // const mesesSet = new Set(data.map(d => d.mes));
        // const meses = Array.from(mesesSet).sort();

        // const tipos = [...new Set(data.map(d => d.tipo))];
        // const series = tipos.map(tipo => {
          // return {
            // name: tipo,
            // type: 'bar',
            // data: meses.map(mes => {
              // const found = data.find(d => d.mes === mes && d.tipo === tipo);
              // return found ? found.cantidad : 0;
            // })
          // };
        // });

        // return { meses, series };
      // }

      // const chartReservaciones = echarts.init(document.getElementById('chartReservaciones'));
      // const chartChecador = echarts.init(document.getElementById('chartChecador'));

      // function renderCharts() {
        // const { meses: mesesR, series: seriesR } = transformData(reservacionesData);
        // const { meses: mesesC, series: seriesC } = transformData(checadorData);

        // chartReservaciones.setOption({
          // title: { text: 'Reservaciones por Tipo y Mes', left: 'center' },
          // tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
          // legend: { top: 30 },
          // xAxis: { type: 'category', data: mesesR },
          // yAxis: { type: 'value', name: 'Cantidad' },
          // series: seriesR,
          // color: ['#007bff', '#28a745']
        // });

        // chartChecador.setOption({
          // title: { text: 'Checador por Tipo y Mes', left: 'center' },
          // tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
          // legend: { top: 30 },
          // xAxis: { type: 'category', data: mesesC },
          // yAxis: { type: 'value', name: 'Cantidad' },
          // series: seriesC,
          // color: ['#ffc107', '#17a2b8']
        // });
      // }

      // renderCharts();

      // $('#buscarBtn').click(function () {
        // const year = $('#yearFilter').val();
        // const month = $('#monthFilter').val();
        // const selectedMes = `${year}-${month}`;

        // $('#comidasTable').DataTable().search(selectedMes).draw();
        // $('#totalesReservacionesTable').DataTable().columns(0).search(selectedMes).draw();
        // $('#totalesChecadorTable').DataTable().columns(0).search(selectedMes).draw();

        // const filteredResData = reservacionesData.filter(d => d.mes === selectedMes);
        // const filteredCheData = checadorData.filter(d => d.mes === selectedMes);

        // const { meses: mesesR, series: seriesR } = transformData(filteredResData);
        // chartReservaciones.setOption({ xAxis: { data: mesesR }, series: seriesR });

        // const { meses: mesesC, series: seriesC } = transformData(filteredCheData);
        // chartChecador.setOption({ xAxis: { data: mesesC }, series: seriesC });
      // });

      // $('#limpiarBtn').click(function () {
        // $('#yearFilter').val('2025');
        // $('#monthFilter').val('07');

        // $('#comidasTable').DataTable().search('').draw();
        // $('#totalesReservacionesTable').DataTable().columns(0).search('').draw();
        // $('#totalesChecadorTable').DataTable().columns(0).search('').draw();

        // renderCharts();
      // });

      // window.addEventListener('resize', () => {
        // chartReservaciones.resize();
        // chartChecador.resize();
      // });


      // ----- EXPORTACIÓN EXCEL -----
      function exportTablesToExcel() {
        const wb = XLSX.utils.book_new();

        // Exportar tabla principal comidasTable
        const comidasTable = document.getElementById('comidasTable');
        const ws1 = XLSX.utils.table_to_sheet(comidasTable);
        XLSX.utils.book_append_sheet(wb, ws1, "Comidas");

        // Exportar tabla totalesReservacionesTable
        const reservacionesTable = document.getElementById('totalesReservacionesTable');
        const ws2 = XLSX.utils.table_to_sheet(reservacionesTable);
        XLSX.utils.book_append_sheet(wb, ws2, "Reservaciones");

        // Exportar tabla totalesChecadorTable
        // const checadorTable = document.getElementById('totalesChecadorTable');
        // const ws3 = XLSX.utils.table_to_sheet(checadorTable);
        // XLSX.utils.book_append_sheet(wb, ws3, "Checador");

        XLSX.writeFile(wb, "Dashboard_Comidas.xlsx");
      }

      $('#exportExcelBtn').click(exportTablesToExcel);


      // ----- EXPORTACIÓN PDF -----
      async function exportDashboardToPdf() {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'pt', 'a4');
        const margin = 40;
        let yPos = margin;

        // Exportar tablas (captura con html2canvas)
        const tables = [
          { id: 'comidasTable', title: 'Tabla Principal - Comidas' },
          { id: 'totalesReservacionesTable', title: 'Totales Reservaciones' }
		  // ,
          // { id: 'totalesChecadorTable', title: 'Totales Checador' }
        ];

        for (const tbl of tables) {
          pdf.setFontSize(14);
          pdf.text(tbl.title, margin, yPos);
          yPos += 20;

          const tableElem = document.getElementById(tbl.id);
          const canvas = await html2canvas(tableElem, { scale: 2 });
          const imgData = canvas.toDataURL('image/png');
          const imgProps = pdf.getImageProperties(imgData);
          const pdfWidth = pdf.internal.pageSize.getWidth() - margin * 2;
          const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

          if (yPos + pdfHeight > pdf.internal.pageSize.getHeight() - margin) {
            pdf.addPage();
            yPos = margin;
          }

          pdf.addImage(imgData, 'PNG', margin, yPos, pdfWidth, pdfHeight);
          yPos += pdfHeight + 20;
        }

        // Exportar gráficos
        const charts = [
          { id: 'chartReservaciones', title: 'Gráfico de Reservaciones' },
          { id: 'chartChecador', title: 'Gráfico de Checador' }
        ];

        for (const chart of charts) {
          pdf.setFontSize(14);
          pdf.text(chart.title, margin, yPos);
          yPos += 20;

          const chartElem = document.getElementById(chart.id);
          const canvas = await html2canvas(chartElem, { scale: 2 });
          const imgData = canvas.toDataURL('image/png');
          const imgProps = pdf.getImageProperties(imgData);
          const pdfWidth = pdf.internal.pageSize.getWidth() - margin * 2;
          const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

          if (yPos + pdfHeight > pdf.internal.pageSize.getHeight() - margin) {
            pdf.addPage();
            yPos = margin;
          }

          pdf.addImage(imgData, 'PNG', margin, yPos, pdfWidth, pdfHeight);
          yPos += pdfHeight + 20;
        }

        pdf.save('Dashboard_Comidas.pdf');
      }

      // $('#exportPdfBtn').click(exportDashboardToPdf);

    });
  </script>

</body>
</html>
