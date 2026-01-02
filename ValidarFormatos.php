<?php
// ----------------------- PHP BACKEND ---------------------------
// Asegurar que el encoding sea correcto
header('Content-Type: text/html; charset=UTF-8');

$serverName = "DESAROLLO-BACRO\\SQLEXPRESS";
$connectionInfo = array(
  "Database" => "Comedor",
  "UID" => "Larome03",
  "PWD" => "Larome03",
  "CharacterSet" => "UTF-8"
);
$conn = sqlsrv_connect($serverName, $connectionInfo);

$sql = "SELECT * FROM cancelaciones 
        WHERE convert(date,FECHA,102) > '2025-12-03' 
        ORDER BY Nombre";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) die(print_r(sqlsrv_errors(), true));

$array_tot1 = $array_tot2 = $array_tot3 = $array_tot4 = [];
$array_tot5 = $array_tot6 = $array_tot7 = $array_tot8 = $array_tot9 = $array_tot10 = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
  $array_tot1[] = $row['NOMBRE'];
  $array_tot2[] = $row['DEPARTAMENTO'];
  $array_tot3[] = $row['JEFE'];
  $array_tot4[] = $row['TIPO_CONSUMO'];
  $array_tot5[] = $row['FECHA'];
  $array_tot6[] = $row['CAUSA'];
  $array_tot7[] = $row['ESTATUS'];
  $array_tot8[] = $row['FECHA_FIN'];
  $array_tot9[] = $row['FECHA_CAPTURA'];
  $array_tot10[] = $row['ValJefDirect'];
}

$validacion_exitosa = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $var1 = test_input($_POST["name123"]);
  $var2 = test_input($_POST["name1234"]);
  $var3 = test_input($_POST["TIPOVALIDA"]);
  $var4 = test_input($_POST["DEPARTAMENTO"]);

  if (isset($_GET['newpwd']) && $_GET['newpwd'] == 'Administrador') {
    if ($var3 == 'UNICA') {
      $sql12 = "UPDATE cancelaciones SET Estatus='APROBADO' WHERE Nombre='$var1' AND Fecha='$var2'";
    } else {
      $var5 = substr($var4, 0, 4);
      $sql12 = "UPDATE cancelaciones SET Estatus='APROBADO' 
                WHERE (convert(date,FECHA,102) > '2024-10-21' 
                AND convert(date,FECHA,102) < '2024-11-06') 
                AND DEPARTAMENTO LIKE '%$var5%'";
    }
  }

  if (isset($_GET['newpwd']) && $_GET['newpwd'] == 'Coordinador') {
    if ($var3 == 'UNICA') {
      $sql12 = "UPDATE cancelaciones SET ValJefDirect='APROBADO' WHERE Nombre='$var1' AND Fecha='$var2'";
    } else {
      $var5 = substr($var4, 0, 4);
      $sql12 = "UPDATE cancelaciones SET ValJefDirect='APROBADO' 
                WHERE (convert(date,FECHA,102) > '2025-10-05' 
                AND convert(date,FECHA,102) < '2025-12-31') 
                AND DEPARTAMENTO LIKE '%$var5%'";
    }
  }

  $stmt12 = sqlsrv_query($conn, $sql12);
  $validacion_exitosa = true;
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
  return $data;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>VALIDACION DE CANCELACIONES - SISTEMA COMEDOR</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- DataTables -->
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Estilos Unix Premium Azul/Blanco -->
  <style>
    /* Variables Azul/Blanco Premium */
    :root {
      --unix-primary: #1E4E79;
      --unix-secondary: #2D6DA6;
      --unix-accent: #4a90e2;
      --unix-light: #f8fafc;
      --unix-light-gray: #e9ecef;
      --unix-gray: #6c757d;
      --unix-dark: #343a40;
      --unix-border: #dee2e6;
      --unix-success: #28a745;
      --unix-warning: #ffc107;
      --unix-danger: #dc3545;
      --unix-info: #17a2b8;
      --unix-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
      --unix-glow: 0 0 20px rgba(45, 109, 166, 0.15);
      --unix-glow-success: 0 0 20px rgba(40, 167, 69, 0.15);
    }

    /* Reset y Base */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #e3e8f0 100%);
      color: var(--unix-dark);
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      line-height: 1.6;
      min-height: 100vh;
      padding: 20px;
      position: relative;
      overflow-x: hidden;
    }

    /* Efecto de grid sutil azul */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: 
        linear-gradient(rgba(45, 109, 166, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(45, 109, 166, 0.03) 1px, transparent 1px);
      background-size: 40px 40px;
      pointer-events: none;
      z-index: -1;
      opacity: 0.3;
    }

    /* Terminal Header */
    .terminal-header {
      background: white;
      border: 1px solid var(--unix-border);
      border-radius: 12px;
      padding: 1.5rem 2rem;
      margin-bottom: 2rem;
      box-shadow: var(--unix-shadow);
      position: relative;
      overflow: hidden;
    }

    .terminal-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
      background: linear-gradient(to bottom, var(--unix-primary), var(--unix-accent));
    }

    .header-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1.5rem;
    }

    .header-title {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .terminal-icon {
      width: 50px;
      height: 50px;
      background: linear-gradient(135deg, var(--unix-primary), var(--unix-accent));
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      color: white;
      box-shadow: var(--unix-glow);
    }

    .title-text h1 {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--unix-primary);
      margin-bottom: 0.25rem;
      letter-spacing: -0.5px;
    }

    .title-text p {
      color: var(--unix-gray);
      font-size: 0.9rem;
      margin: 0;
    }

    .terminal-button {
      background: white;
      border: 2px solid var(--unix-primary);
      color: var(--unix-primary);
      padding: 0.75rem 1.5rem;
      border-radius: 8px;
      font-family: inherit;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.75rem;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }

    .terminal-button:hover {
      background: var(--unix-primary);
      border-color: var(--unix-primary);
      color: white;
      transform: translateY(-2px);
      box-shadow: var(--unix-glow);
      text-decoration: none;
    }

    .terminal-button:active {
      transform: translateY(0);
    }

    .terminal-button i {
      font-size: 1.1rem;
    }

    /* Formulario Terminal */
    .terminal-form {
      background: white;
      border: 1px solid var(--unix-border);
      border-radius: 12px;
      padding: 2.5rem;
      margin-bottom: 2.5rem;
      box-shadow: var(--unix-shadow);
      position: relative;
    }

    .form-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid var(--unix-border);
    }

    .form-header-icon {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, var(--unix-warning), var(--unix-info));
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      color: white;
    }

    .form-header-text h2 {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--unix-primary);
      margin-bottom: 0.25rem;
    }

    .form-header-text p {
      color: var(--unix-gray);
      font-size: 0.95rem;
      margin: 0;
    }

    .terminal-label {
      display: block;
      color: var(--unix-primary);
      font-weight: 600;
      margin-bottom: 0.75rem;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .terminal-label i {
      font-size: 0.9rem;
      color: var(--unix-accent);
    }

    .terminal-input,
    .terminal-select {
      background: white;
      border: 2px solid var(--unix-border);
      color: var(--unix-dark);
      border-radius: 8px;
      padding: 0.875rem 1rem;
      font-family: inherit;
      font-size: 0.95rem;
      width: 100%;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .terminal-input:focus,
    .terminal-select:focus {
      outline: none;
      border-color: var(--unix-accent);
      box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
      background: white;
    }

    .terminal-input:read-only {
      background: var(--unix-light-gray);
      cursor: not-allowed;
      opacity: 0.8;
    }

    .terminal-button-submit {
      background: linear-gradient(135deg, var(--unix-primary), var(--unix-accent));
      border: none;
      color: white;
      padding: 1.125rem 2rem;
      border-radius: 10px;
      font-family: inherit;
      font-weight: 700;
      font-size: 1.1rem;
      letter-spacing: 0.5px;
      width: 100%;
      margin-top: 1.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      cursor: pointer;
    }

    .terminal-button-submit:hover {
      transform: translateY(-3px);
      box-shadow: var(--unix-glow);
    }

    .terminal-button-submit:active {
      transform: translateY(-1px);
    }

    .terminal-button-submit::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: 0.5s;
    }

    .terminal-button-submit:hover::before {
      left: 100%;
    }

    /* Tabla Terminal */
    .terminal-table-container {
      background: white;
      border: 1px solid var(--unix-border);
      border-radius: 12px;
      padding: 2rem;
      box-shadow: var(--unix-shadow);
      overflow: hidden;
    }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid var(--unix-border);
      flex-wrap: wrap;
      gap: 1rem;
    }

    .table-title {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .table-icon {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, var(--unix-accent), var(--unix-info));
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      color: white;
    }

    .table-title-text h3 {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--unix-primary);
      margin-bottom: 0.25rem;
    }

    .table-title-text small {
      color: var(--unix-gray);
      font-size: 0.9rem;
    }

    /* DataTables Personalizado */
    .dataTables_wrapper {
      background: transparent !important;
    }

    table.dataTable {
      background: transparent !important;
      border: 1px solid var(--unix-border) !important;
      border-radius: 8px;
      overflow: hidden;
    }

    table.dataTable thead th {
      background: linear-gradient(135deg, var(--unix-primary), var(--unix-secondary)) !important;
      color: white !important;
      border-bottom: 2px solid var(--unix-secondary) !important;
      padding: 1.125rem 1rem !important;
      font-weight: 700;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      position: sticky;
      top: 0;
    }

    table.dataTable tbody td {
      background: white !important;
      color: var(--unix-dark) !important;
      border-bottom: 1px solid var(--unix-border) !important;
      padding: 1rem !important;
      font-size: 0.95rem;
      transition: all 0.2s ease;
    }

    table.dataTable tbody tr {
      background: white !important;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    table.dataTable tbody tr:hover {
      background: rgba(45, 109, 166, 0.05) !important;
      transform: translateX(4px);
    }

    table.dataTable tbody tr.selected {
      background: rgba(45, 109, 166, 0.1) !important;
      position: relative;
    }

    table.dataTable tbody tr.selected::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 3px;
      background: linear-gradient(to bottom, var(--unix-primary), var(--unix-accent));
    }

    /* Badges Terminal */
    .terminal-badge {
      display: inline-flex;
      align-items: center;
      padding: 0.375rem 0.875rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      letter-spacing: 0.3px;
      gap: 0.375rem;
      border: 1px solid;
    }

    .badge-en-proceso {
      background: rgba(255, 193, 7, 0.1);
      color: #856404;
      border-color: rgba(255, 193, 7, 0.3);
    }

    .badge-aprobado {
      background: rgba(40, 167, 69, 0.1);
      color: #155724;
      border-color: rgba(40, 167, 69, 0.3);
    }

    .badge-rechazado {
      background: rgba(220, 53, 69, 0.1);
      color: #721c24;
      border-color: rgba(220, 53, 69, 0.3);
    }

    .badge-pendiente {
      background: rgba(108, 117, 125, 0.1);
      color: var(--unix-gray);
      border-color: rgba(108, 117, 125, 0.3);
    }

    /* Scroll Personalizado */
    .dataTables_scrollBody {
      max-height: 500px !important;
      overflow-y: auto !important;
      scrollbar-width: thin;
      scrollbar-color: var(--unix-accent) var(--unix-light-gray);
    }

    .dataTables_scrollBody::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }

    .dataTables_scrollBody::-webkit-scrollbar-track {
      background: var(--unix-light-gray);
      border-radius: 4px;
    }

    .dataTables_scrollBody::-webkit-scrollbar-thumb {
      background: linear-gradient(to bottom, var(--unix-primary), var(--unix-accent));
      border-radius: 4px;
    }

    .dataTables_scrollBody::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(to bottom, var(--unix-secondary), var(--unix-accent));
    }

    /* Modal Terminal Premium */
    .terminal-modal .modal-dialog {
      max-width: 500px;
    }

    .terminal-modal .modal-content {
      background: white;
      border: 1px solid var(--unix-border);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: var(--unix-shadow);
    }

    .terminal-modal .modal-header {
      background: linear-gradient(135deg, var(--unix-primary), var(--unix-secondary));
      border-bottom: 1px solid var(--unix-secondary);
      padding: 1.5rem 2rem;
    }

    .terminal-modal .modal-title {
      color: white;
      font-weight: 700;
      font-size: 1.3rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .terminal-modal .modal-body {
      padding: 2.5rem 2rem;
      text-align: center;
    }

    .modal-success-icon {
      font-size: 4rem;
      color: var(--unix-success);
      margin-bottom: 1.5rem;
      display: inline-block;
      animation: terminalPulse 2s infinite;
    }

    @keyframes terminalPulse {
      0% {
        transform: scale(1);
        filter: drop-shadow(0 0 5px rgba(40, 167, 69, 0.3));
      }
      50% {
        transform: scale(1.05);
        filter: drop-shadow(0 0 15px rgba(40, 167, 69, 0.5));
      }
      100% {
        transform: scale(1);
        filter: drop-shadow(0 0 5px rgba(40, 167, 69, 0.3));
      }
    }

    .terminal-modal .modal-body h4 {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--unix-primary);
      margin-bottom: 0.75rem;
    }

    .terminal-modal .modal-body p {
      color: var(--unix-gray);
      font-size: 1.1rem;
      line-height: 1.6;
    }

    .terminal-modal .modal-footer {
      border-top: 1px solid var(--unix-border);
      padding: 1.5rem 2rem;
      background: var(--unix-light-gray);
    }

    .terminal-modal .btn-close {
      filter: brightness(0) invert(1);
      opacity: 0.8;
      transition: all 0.3s ease;
    }

    .terminal-modal .btn-close:hover {
      opacity: 1;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
      .terminal-header,
      .terminal-form,
      .terminal-table-container {
        padding: 1.75rem;
      }
      
      .header-content {
        flex-direction: column;
        text-align: center;
      }
      
      .header-title {
        flex-direction: column;
      }
    }

    @media (max-width: 992px) {
      body {
        padding: 15px;
      }
      
      .terminal-form {
        padding: 2rem;
      }
      
      .form-header {
        flex-direction: column;
        text-align: center;
      }
      
      .table-header {
        flex-direction: column;
        text-align: center;
      }
      
      .table-title {
        flex-direction: column;
      }
    }

    @media (max-width: 768px) {
      .terminal-header,
      .terminal-form,
      .terminal-table-container {
        padding: 1.5rem;
      }
      
      .title-text h1 {
        font-size: 1.5rem;
      }
      
      .form-header-text h2 {
        font-size: 1.3rem;
      }
      
      table.dataTable thead th,
      table.dataTable tbody td {
        padding: 0.875rem 0.75rem !important;
        font-size: 0.9rem;
      }
      
      .terminal-button-submit {
        padding: 1rem 1.5rem;
        font-size: 1rem;
      }
      
      .terminal-modal .modal-dialog {
        margin: 1rem;
      }
    }

    @media (max-width: 576px) {
      body {
        padding: 10px;
      }
      
      .terminal-header,
      .terminal-form,
      .terminal-table-container {
        padding: 1.25rem;
        border-radius: 10px;
      }
      
      .terminal-label {
        font-size: 0.85rem;
      }
      
      .terminal-input,
      .terminal-select {
        padding: 0.75rem;
        font-size: 0.9rem;
      }
      
      .terminal-button,
      .terminal-button-submit {
        width: 100%;
        justify-content: center;
      }
    }

    /* Efectos Especiales */
    .glow-effect {
      position: relative;
    }

    .glow-effect::after {
      content: '';
      position: absolute;
      top: -2px;
      left: -2px;
      right: -2px;
      bottom: -2px;
      background: linear-gradient(45deg, var(--unix-primary), var(--unix-accent), var(--unix-info), var(--unix-primary));
      border-radius: inherit;
      z-index: -1;
      filter: blur(10px);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .glow-effect:hover::after {
      opacity: 0.2;
    }

    /* Loading States */
    .terminal-loading {
      position: relative;
      pointer-events: none;
    }

    .terminal-loading::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 20px;
      height: 20px;
      margin: -10px 0 0 -10px;
      border: 2px solid var(--unix-border);
      border-top-color: var(--unix-primary);
      border-radius: 50%;
      animation: terminalSpin 0.8s linear infinite;
    }

    @keyframes terminalSpin {
      to {
        transform: rotate(360deg);
      }
    }
  </style>
</head>

<body>
  <!-- Terminal Header -->
  <div class="terminal-header">
    <div class="header-content">
      <div class="header-title">
        <div class="terminal-icon">
          <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="title-text">
          <h1>VALIDACION DE CANCELACIONES</h1>
          <p>Sistema de Gestion - Comedor Industrial</p>
        </div>
      </div>
      <a href="http://192.168.100.95/Comedor" class="terminal-button glow-effect">
        <i class="fas fa-chevron-left"></i>
        VOLVER AL MENU
      </a>
    </div>
  </div>

  <!-- Terminal Form -->
  <div class="terminal-form">
    <div class="form-header">
      <div class="form-header-icon">
        <i class="fas fa-sliders-h"></i>
      </div>
      <div class="form-header-text">
        <h2>FILTROS DE VALIDACION</h2>
        <p>Configura los parametros para procesar la validacion</p>
      </div>
    </div>
    
    <form method="post" action="" id="formValidacion">
      <div class="row g-4">
        <div class="col-md-3">
          <label for="TIPOVALIDA" class="terminal-label">
            <i class="fas fa-filter"></i> TIPO DE VALIDACION
          </label>
          <select id="TIPOVALIDA" name="TIPOVALIDA" class="terminal-select">
            <option value="MULTIPLE">VALIDACION MULTIPLE</option>
            <option value="UNICA">VALIDACION UNICA</option>
          </select>
        </div>

        <div class="col-md-3">
          <label for="DEPARTAMENTO" class="terminal-label">
            <i class="fas fa-sitemap"></i> DEPARTAMENTO
          </label>
          <select id="DEPARTAMENTO" name="DEPARTAMENTO" class="terminal-select">
            <option>Operaciones</option>
            <option>Talento humano</option>
            <option>Finanzas</option>
            <option>Administracion</option>
            <option>Auditoria</option>
          </select>
        </div>

        <div class="col-md-3">
          <label for="name123" class="terminal-label">
            <i class="fas fa-user"></i> NOMBRE
          </label>
          <input type="text" class="terminal-input" id="name123" name="name123" 
                 placeholder="Selecciona de la tabla" readonly>
        </div>

        <div class="col-md-3">
          <label for="name1234" class="terminal-label">
            <i class="fas fa-calendar"></i> FECHA
          </label>
          <input type="text" class="terminal-input" id="name1234" name="name1234" 
                 placeholder="Selecciona de la tabla" readonly>
        </div>
      </div>

      <button type="submit" class="terminal-button-submit glow-effect" id="btnValidar">
        <i class="fas fa-play-circle"></i>
        EJECUTAR VALIDACION
      </button>
    </form>
  </div>

  <!-- Terminal Table Container -->
  <div class="terminal-table-container">
    <div class="table-header">
      <div class="table-title">
        <div class="table-icon">
          <i class="fas fa-database"></i>
        </div>
        <div class="table-title-text">
          <h3>REGISTROS DE CANCELACIONES</h3>
          <small>Haz clic en una fila para seleccionarla</small>
        </div>
      </div>
      <button class="terminal-button" onclick="table.ajax.reload()">
        <i class="fas fa-redo"></i>
        ACTUALIZAR
      </button>
    </div>

    <table id="example" class="table table-hover w-100"></table>
  </div>

  <!-- Terminal Modal -->
  <div class="modal fade terminal-modal" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-check-circle"></i> VALIDACION EXITOSA
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="modal-success-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <h4>OPERACION COMPLETADA</h4>
          <p>La validacion se realizo correctamente.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="terminal-button-submit w-100" data-bs-dismiss="modal">
            <i class="fas fa-check"></i> ACEPTAR
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- JS Libraries -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>

  <script>
    // Preparar datos para la tabla
    var dataQ1 = <?php echo json_encode($array_tot1);?>;
    var dataQ2 = <?php echo json_encode($array_tot2);?>;
    var dataQ3 = <?php echo json_encode($array_tot3);?>;
    var dataQ4 = <?php echo json_encode($array_tot4);?>;
    var dataQ5 = <?php echo json_encode($array_tot5);?>;
    var dataQ6 = <?php echo json_encode($array_tot6);?>;
    var dataQ7 = <?php echo json_encode($array_tot7);?>;
    var dataQ8 = <?php echo json_encode($array_tot8);?>;
    var dataQ9 = <?php echo json_encode($array_tot9);?>;
    var dataQ10 = <?php echo json_encode($array_tot10);?>;

    // Funci車n para obtener badge de estado
    function getTerminalBadge(status) {
      status = (status || '').toUpperCase();
      switch(status) {
        case 'EN PROCESO':
          return '<span class="terminal-badge badge-en-proceso"><i class="fas fa-clock"></i> ' + status + '</span>';
        case 'APROBADO':
          return '<span class="terminal-badge badge-aprobado"><i class="fas fa-check"></i> ' + status + '</span>';
        case 'RECHAZADO':
          return '<span class="terminal-badge badge-rechazado"><i class="fas fa-times"></i> ' + status + '</span>';
        default:
          return '<span class="terminal-badge badge-pendiente"><i class="fas fa-hourglass-half"></i> ' + (status || 'PENDIENTE') + '</span>';
      }
    }

    // Preparar dataset
    var dataSet = [];
    for (let i = 0; i < dataQ1.length; i++) {
      dataSet.push([
        dataQ1[i],
        dataQ2[i],
        dataQ3[i],
        dataQ4[i],
        dataQ5[i],
        dataQ8[i] || '',
        dataQ9[i],
        dataQ6[i],
        getTerminalBadge(dataQ7[i]),
        dataQ10[i] || 'PENDIENTE'
      ]);
    }

    // Inicializar DataTable
    var table = new DataTable('#example', {
      data: dataSet,
      columns: [
        { title: 'NOMBRE', className: 'text-start' },
        { title: 'DEPARTAMENTO', className: 'text-start' },
        { title: 'JEFE INMEDIATO', className: 'text-start' },
        { title: 'TIPO CONSUMO', className: 'text-center' },
        { title: 'FECHA', className: 'text-center' },
        { title: 'FECHA FIN', className: 'text-center' },
        { title: 'FECHA CAPTURA', className: 'text-center' },
        { title: 'CAUSA', className: 'text-start' },
        { title: 'ESTATUS', className: 'text-center' },
        { title: 'VALIDACION JEFE', className: 'text-center' }
      ],
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json'
      },
      responsive: true,
      paging: false,
      scrollY: '500px',
      scrollCollapse: true,
      select: { style: 'os', blurable: true },
      order: [[4, 'desc']],
      createdRow: function(row, data, dataIndex) {
        $(row).addClass('terminal-row');
      }
    });

    // Manejar selecci車n de fila
    $('#example').on('click', 'tr', function() {
      var data = table.row(this).data();
      if (data) {
        document.getElementById("name123").value = data[0];
        document.getElementById("name1234").value = data[4];
      }
    });

    // Mostrar modal si hay validaci車n exitosa
    <?php if ($validacion_exitosa): ?>
      $(document).ready(function() {
        setTimeout(function() {
          var modal = new bootstrap.Modal(document.getElementById('successModal'));
          modal.show();
        }, 500);
      });
    <?php endif; ?>

    // Efectos de interacci車n
    $(document).ready(function() {
      // A?adir efecto hover a todos los botones terminal
      $('.terminal-button, .terminal-button-submit').hover(
        function() {
          $(this).addClass('glow-effect');
        },
        function() {
          $(this).removeClass('glow-effect');
        }
      );
    });
  </script>
</body>
</html>