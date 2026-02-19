<?php
/**
 * @file AgendaPedidos.php
 * @brief Consulta de consumos semanales del empleado con tabla y filtro por fecha y número de empleado.
 *
 * @description
 * Módulo de consulta que permite a cualquier usuario verificar los pedidos registrados
 * en la semana seleccionada. Ejecuta dos consultas SQL al inicio de la carga de página:
 *   1. Una consulta con PIVOT que agrega los totales de Desayuno/Comida por día de semana
 *      y semana (Fecha), utilizada para alimentar gráficos o estadísticas visuales.
 *   2. Una consulta de detalle que lista todos los empleados con sus elecciones por día
 *      (Lunes–Viernes), cruzando el catálogo de empleados con los pedidos registrados.
 * Los resultados se pasan al frontend (JavaScript) mediante json_encode y se filtran
 * del lado del cliente por la fecha y el número de empleado ingresados en el formulario.
 * La tabla resultante se renderiza con DataTables.
 *
 * ADVERTENCIA: Este archivo contiene credenciales de base de datos hardcodeadas.
 * Debe migrarse para usar getComedorConnection() de config/database.php.
 *
 * @module Agenda / Consulta de Pedidos
 * @access Acceso público (sin validación de sesión activa)
 *
 * @dependencies
 * - Librerías JS: jQuery 1.11.3, DataTables 1.13.4, Bootstrap 5.3.0, xlsx 0.15.1
 * - Archivos PHP: Ninguno (conexión directa hardcodeada)
 *
 * @database
 * - Tablas: PedidosComida, Catalogo_EmpArea (BD Comedor)
 * - Operaciones: SELECT (PIVOT para agregados semanales, JOIN para detalle por empleado)
 *
 * @session
 * - No utiliza variables de sesión en esta versión
 *
 * @inputs
 * - $_GET['fec'] : string - Fecha de inicio de semana seleccionada (formato YYYY-MM-DD), filtro JS
 * - $_GET['emp'] : string - Número de empleado a consultar, filtro JS
 *
 * @outputs
 * - HTML renderizado con formulario de búsqueda y tabla DataTable filtrada por cliente
 *
 * @security
 * - Credenciales hardcodeadas (pendiente de migración a variables de entorno)
 * - La función test_input() aplica trim, stripslashes y htmlspecialchars a entradas de usuario
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.0
 * @since 2024
 * @updated 2026-02-18
 */

$pedido = $name = $email = $gender = $comment = $website = "";

require_once __DIR__ . '/config/database.php';
$conn = getComedorConnection();

/* =========================================================
 * CONSULTA 1: Agregado semanal con PIVOT — totales de Desayuno/Comida por día
 * Tablas: PedidosComida
 * Retorna: Por cada Fecha de semana, la suma de Desayunos (D*) y Comidas (C*)
 *          para Lunes, Martes, Miércoles, Jueves y Viernes usando UNPIVOT + PIVOT.
 * Propósito: Alimentar arrays PHP que el frontend usa para visualización agregada.
 * =========================================================
 */
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

/* =========================================================
 * CONSULTA 2: Detalle de pedidos por empleado para la semana
 * Tablas: PedidosComida, Catalogo_EmpArea
 * Retorna: Por cada empleado registrado en el catálogo, su elección de comida
 *          (Desayuno/Comida/vacío) para cada día Lunes–Viernes en todas las fechas.
 * Operación: LEFT JOIN para incluir empleados sin pedidos (mostrando vacíos con ISNULL).
 * =========================================================
 */
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

/* Arrays para resultados de la Consulta 1 (totales PIVOT por semana) */
$array_tot1 = [];
$array_tot2 = [];
$array_tot3 = [];
$array_tot4 = [];

/* Arrays para columnas pivoteadas: Fecha de semana + C/D (Comida/Desayuno) por cada día */
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

/* Iterar resultados de Consulta 1 (PIVOT): poblar arrays por columna para uso en JS */
while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
    array_push($array_Q1,$row['Fecha']);
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

/* Arrays para resultados de Consulta 2 (detalle de pedidos por empleado para la tabla) */
$FechaT = [];
$Id_EmpleadoT = [];
$NombreT = [];
$LunesT = [];
$MartesT = [];
$MiercolesT = [];
$JuevesT = [];
$ViernesT = [];

/* Iterar resultados de Consulta 2: poblar arrays de detalle de empleados para DataTable */
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

/**
 * @brief Sanitiza una cadena de texto para su uso seguro en HTML.
 *
 * Aplica secuencialmente: eliminación de espacios extremos (trim),
 * eliminación de barras de escape (stripslashes) y conversión de
 * caracteres especiales a entidades HTML (htmlspecialchars).
 *
 * @param string $data Cadena de texto a sanitizar
 * @return string Cadena sanitizada lista para renderizar en HTML
 */
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
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Comedor Corporativo</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- DataTables -->
  <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">

  <style>
    :root {
      --primary: #1a3a6c;
      --primary-dark: #0d254a;
      --glass-bg: rgba(255, 255, 255, 0.08);
      --glass-border: rgba(255, 255, 255, 0.12);
      --glass-glow: rgba(255, 255, 255, 0.18);
      --text-light: #f0f8ff;
      --shadow: 0 12px 32px rgba(0, 10, 30, 0.4);
    }

    * {
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: linear-gradient(135deg, #0b1a3a, #1a3a6c, #0d254a);
      min-height: 100vh;
      padding: 2rem 1rem;
      color: var(--text-light);
      overflow-x: hidden;
    }

    .glass-card {
      background: var(--glass-bg);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid var(--glass-border);
      border-radius: 24px;
      box-shadow: 
        var(--shadow),
        0 0 0 1px var(--glass-glow),
        inset 0 0 0 1px rgba(255, 255, 255, 0.07);
      padding: 2.25rem;
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
    }

    .glass-card::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
      pointer-events: none;
    }

    .logo-container {
      text-align: center;
      margin-bottom: 1.8rem;
    }

    .logo-container img {
      max-width: 130px;
      height: auto;
      filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
    }

    h1 {
      font-weight: 700;
      text-align: center;
      margin-bottom: 2.2rem;
      font-size: 2.1rem;
      text-shadow: 0 2px 6px rgba(0,0,0,0.3);
      letter-spacing: -0.5px;
    }

    .form-label {
      font-weight: 600;
      color: #e0f0ff;
      margin-top: 1.1rem;
    }

    .form-control, .form-select {
      background: rgba(255, 255, 255, 0.92);
      border: none;
      border-radius: 14px;
      padding: 0.85rem 1.1rem;
      font-size: 1.02rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
      transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
      outline: none;
      box-shadow: 0 0 0 3px rgba(45, 109, 166, 0.4);
      transform: translateY(-1px);
    }

    .btn-primary {
      background: linear-gradient(135deg, #2D6DA6, #1E4E79);
      border: none;
      border-radius: 14px;
      padding: 0.85rem 1.8rem;
      font-weight: 700;
      font-size: 1.15rem;
      letter-spacing: 0.5px;
      transition: all 0.35s cubic-bezier(0.2, 0, 0.2, 1);
      box-shadow: 
        0 6px 16px rgba(0, 0, 0, 0.25),
        0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #3a7db6, #2a5e8a);
      transform: translateY(-3px);
      box-shadow: 
        0 10px 24px rgba(0, 0, 0, 0.35),
        0 6px 12px rgba(0, 0, 0, 0.25);
    }

    .btn-primary:active {
      transform: translateY(-1px);
    }

    #NC {
      font-size: 1.35rem;
      font-weight: 700;
      background: linear-gradient(135deg, #1E4E79, #2D6DA6);
      padding: 0.9rem 1.8rem;
      border-radius: 16px;
      display: inline-block;
      margin-top: 1.8rem;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
      letter-spacing: 0.3px;
    }

    .table {
      background: rgba(255, 255, 255, 0.94);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
    }

    .table th {
      background: var(--primary-dark) !important;
      color: white !important;
      font-weight: 700;
      font-size: 0.98rem;
      padding: 1rem;
    }

    .table td {
      font-weight: 600;
      color: #222 !important;
      padding: 0.9rem;
    }

    .table-striped tbody tr:nth-of-type(odd) {
      background-color: rgba(245, 249, 255, 0.65);
    }

    .nav-link {
      color: rgba(220, 240, 255, 0.95);
      text-decoration: none;
      font-weight: 700;
      padding: 0.65rem 1.4rem;
      border-radius: 14px;
      background: rgba(255, 255, 255, 0.12);
      display: inline-block;
      margin-bottom: 1.8rem;
      transition: all 0.3s ease;
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      margin-right: 1rem;
    }

    .nav-link:hover {
      background: rgba(255, 255, 255, 0.22);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .nav-buttons {
      text-align: center;
      margin-bottom: 1.8rem;
    }

    @media (max-width: 768px) {
      .glass-card {
        padding: 1.6rem;
      }
      h1 {
        font-size: 1.7rem;
      }
      .btn-primary {
        font-size: 1.05rem;
        padding: 0.8rem 1.5rem;
      }
      .nav-link {
        display: block;
        margin-right: 0;
        margin-bottom: 0.8rem;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <!-- Enlaces de navegación -->
    <div class="nav-buttons">
      <a href="." class="nav-link">← Menú principal</a>
      <a href="./LoginFormCancel.php" class="nav-link">📅 Cancelaciones</a>
    </div>

    <!-- Logo -->
    <div class="logo-container">
      <img src="Logo2.png" alt="Logo">
    </div>

    <!-- Título -->
    <h1>Consulta de Consumos Semanales</h1>

    <!-- Formulario -->
    <div class="glass-card">
      <div class="row g-3">
        <div class="col-md-6">
          <label for="fec" class="form-label">Fecha</label>
          <select name="fec" id="fec" class="form-select">
            <option value="Selecciona tu fecha a consultar">Selecciona tu fecha</option>
 <option value="2026-01-05">05/01/2026</option>
<option value="2026-01-12">12/01/2026</option>
<option value="2026-01-19">19/01/2026</option>
<option value="2026-01-26">26/01/2026</option>
          </select>
        </div>
        <div class="col-md-6">
          <label for="emp" class="form-label">No. Empleado</label>
          <input type="text" id="emp" name="emp" class="form-control" placeholder="Escribe tu No. Empleado" required>
        </div>
      </div>

      <div class="d-grid mt-4">
        <button type="button" class="btn btn-primary" onclick="Borrar(); Prueba();">Buscar</button>
      </div>

      <div id="NC" class="mt-4 text-center">No. consumos semanales:</div>
    </div>

    <!-- Tabla -->
    <div class="glass-card">
      <div class="table-responsive">
        <table id="example" class="table table-striped table-bordered w-100"></table>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
  <script src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>

  <script>
    function Borrar() {
      var table = $('#example').DataTable();
      table.clear().draw();
    }

    var dataSet = [];

    $(document).ready(function () {
      $('#example').DataTable({
        columns: [
          { title: 'Fecha' },
          { title: 'Id_Empleado' },
          { title: 'Nombre' },
          { title: 'Lunes' },
          { title: 'Martes' },
          { title: 'Miercoles' },
          { title: 'Jueves' },
          { title: 'Viernes' },
        ],
      });
    });

    function Prueba() {
      var dataQ = <?php echo json_encode($array_Q1); ?>;
      var dataQ1 = <?php echo json_encode($array_Q2); ?>;
      var dataQ2 = <?php echo json_encode($array_Q3); ?>;
      var dataQ3 = <?php echo json_encode($array_Q4); ?>;
      var dataQ4 = <?php echo json_encode($array_Q5); ?>;
      var dataQ5 = <?php echo json_encode($array_Q6); ?>;
      var dataQ6 = <?php echo json_encode($array_Q7); ?>;
      var dataQ7 = <?php echo json_encode($array_Q8); ?>;
      var dataQ8 = <?php echo json_encode($array_Q9); ?>;
      var dataQ9 = <?php echo json_encode($array_Q10); ?>;
      var dataQ10 = <?php echo json_encode($array_Q11); ?>;

      var dataT = <?php echo json_encode($FechaT); ?>;
      var dataT1 = <?php echo json_encode($Id_EmpleadoT); ?>;
      var dataT2 = <?php echo json_encode($NombreT); ?>;
      var dataT3 = <?php echo json_encode($LunesT); ?>;
      var dataT4 = <?php echo json_encode($MartesT); ?>;
      var dataT5 = <?php echo json_encode($MiercolesT); ?>;
      var dataT6 = <?php echo json_encode($JuevesT); ?>;
      var dataT7 = <?php echo json_encode($ViernesT); ?>;

      var x3 = document.getElementById("emp").value;
      var x4 = document.getElementById("fec").value;

      var desayunos = [];
      var comidas = [];

      for (var i = 0; i < dataQ.length; i++) {
        if (x4 === dataQ[i]) {
          comidas.push(dataQ1[i], dataQ3[i], dataQ5[i], dataQ7[i], dataQ9[i]);
          desayunos.push(dataQ2[i], dataQ4[i], dataQ6[i], dataQ8[i], dataQ10[i]);
        }
      }

      var t = $('#example').DataTable();
      var ent = 0;
      var suma123 = 0;

      for (var i = 0; i < dataT.length; i++) {
        if (x4 === dataT[i] && x3 === dataT1[i]) {
          ent = 1;
          if (dataT3[i] === 'Desayuno' || dataT3[i] === 'Comida') suma123++;
          if (dataT4[i] === 'Desayuno' || dataT4[i] === 'Comida') suma123++;
          if (dataT5[i] === 'Desayuno' || dataT5[i] === 'Comida') suma123++;
          if (dataT6[i] === 'Desayuno' || dataT6[i] === 'Comida') suma123++;
          if (dataT7[i] === 'Desayuno' || dataT7[i] === 'Comida') suma123++;

          t.row.add([dataT[i], dataT1[i], dataT2[i], dataT3[i], dataT4[i], dataT5[i], dataT6[i], dataT7[i]]).draw(false);
        }
      }

      document.getElementById("NC").innerHTML = "Tienes " + suma123 + " consumos para esta semana";

      if (ent == 0) {
        alert('No se encuentran los registros');
      }
    }
  </script>
</body>
</html>