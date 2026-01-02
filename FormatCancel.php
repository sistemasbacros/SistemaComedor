<?php
session_start();

// Obtener información del usuario desde la sesión
$user_name = $_SESSION['user_name'] ?? '';
$user_area = $_SESSION['user_area'] ?? '';

// Si no hay sesión activa, redirigir al login
if (empty($user_name)) {
    header("Location: http://desarollo-bacros/Comedor/Admiin.php");
    exit;
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

$alertMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $user_name;
    $departamento = $user_area;

    $jefe = test_input($_POST["inputAddress"] ?? '');
    $consumo = test_input($_POST["inputState"] ?? '');
    $fecha = test_input($_POST["inputZip"] ?? '');
    $causa = test_input($_POST["inputState1"] ?? '');

    $fechaActual = date('Y-m-d');
    $horaActual = date('H:i:s');

    $fechaMin = '2025-12-21';
    $fechaMax = '2026-11-04';

    if ($fecha < $fechaMin || $fecha > $fechaMax) {
        $alertMessage = "La fecha seleccionada no está dentro del periodo permitido.";
    } else {
        $serverName = "DESAROLLO-BACRO\SQLEXPRESS";
        $connectionInfo = [
            "Database" => "Comedor",
            "UID" => "Larome03",
            "PWD" => "Larome03",
            "CharacterSet" => "UTF-8"
        ];
        $conn = sqlsrv_connect($serverName, $connectionInfo);

        if ($conn === false) {
            $alertMessage = "Error al conectar a la base de datos.";
        } else {
            // Verificar si ya existe cancelación para el mismo nombre, departamento, fecha y tipo de consumo
            $checkSql = "SELECT COUNT(*) AS count FROM Cancelaciones WHERE NOMBRE = ? AND DEPARTAMENTO = ? AND FECHA = ? AND TIPO_CONSUMO = ?";
            $checkParams = [$nombre, $departamento, $fecha, $consumo];
            $checkStmt = sqlsrv_query($conn, $checkSql, $checkParams);

            if ($checkStmt === false) {
                $alertMessage = "Error al validar la cancelación previa.";
            } else {
                $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
                if ($row['count'] > 0) {
                    $alertMessage = "Ya existe una cancelación registrada para esta fecha y tipo de consumo.";
                } else {
                    $sql = "INSERT INTO Cancelaciones 
                    ([NOMBRE],[DEPARTAMENTO],[JEFE],[TIPO_CONSUMO],[FECHA],[CAUSA],[ESTATUS],[FECHA_CAPTURA]) 
                    VALUES (?, ?, ?, ?, ?, ?, 'EN PROCESO', ?)";
                    $params = [$nombre, $departamento, $jefe, $consumo, $fecha, $causa, $fechaActual . ' ' . $horaActual];
                    $stmt = sqlsrv_query($conn, $sql, $params);

                    if ($stmt === false) {
                        $alertMessage = "Error al insertar los datos.";
                    } else {
                        $alertMessage = "Se cargó tu formato de cancelación de comidas correctamente.";
                    }
                }
            }
            sqlsrv_close($conn);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Formulario de Cancelación de Consumos</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      min-height: 100vh;
      background: url('comedor-industrial-monterrey.jpg') no-repeat center center fixed;
      background-size: cover;
      position: relative;
      overflow-x: hidden;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      animation: subtleMove 30s ease-in-out infinite alternate;
    }

    @keyframes subtleMove {
      0% { background-position: center top; }
      100% { background-position: center bottom; }
    }

    /* Difuminado sutil sobre el fondo */
    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(6px);
      z-index: -1;
    }

    .glass-container {
      background: rgba(255, 255, 255, 0.12);
      border-radius: 20px;
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.18);
      max-width: 600px;
      margin: 60px auto 40px;
      padding: 40px 30px;
      color: #000;
    }

    h3 {
      background-color: #2D6DA6;
      color: white;
      font-weight: bold;
      padding: 12px;
      border-radius: 10px;
      text-align: center;
      margin-bottom: 30px;
      user-select: none;
    }

    label {
      font-weight: 600;
      color: #000;
      user-select: none;
    }

    .form-control,
    .form-select {
      background-color: rgba(255, 255, 255, 0.85);
      color: #000;
      border: none;
      font-weight: 500;
      transition: background-color 0.3s ease;
    }

    .form-control::placeholder {
      color: #555;
    }

    .form-control:focus,
    .form-select:focus {
      background-color: rgba(255, 255, 255, 0.95);
      color: #000;
      outline: none;
      box-shadow: none;
    }

    .btn-primary {
      background-color: #2D6DA6;
      border: none;
      font-weight: 700;
      letter-spacing: 0.05em;
      transition: background-color 0.3s ease;
    }
    .btn-primary:hover {
      background-color: #1b4f79;
    }

    .btn-secondary {
      background-color: #6c757d;
      border: none;
      font-weight: 600;
      letter-spacing: 0.03em;
      transition: background-color 0.3s ease;
    }
    .btn-secondary:hover {
      background-color: #545b62;
      color: #fff;
      text-decoration: none;
    }

    .alert {
      max-width: 600px;
      margin: 0 auto 30px;
      border-radius: 10px;
      font-weight: 600;
      letter-spacing: 0.02em;
    }

    a.menu-link {
      display: block;
      text-align: center;
      margin-top: 10px;
      color: #2D6DA6;
      font-weight: 600;
      text-decoration: none;
      user-select: none;
    }
    a.menu-link:hover {
      text-decoration: underline;
    }

    .user-info {
      background: rgba(255, 255, 255, 0.15);
      border-radius: 12px;
      padding: 1rem;
      margin-bottom: 1.5rem;
      border: 1px solid rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(8px);
    }

    .user-name {
      font-weight: 700;
      font-size: 1.1rem;
      color: #000;
    }

    .user-area {
      font-weight: 500;
      font-size: 1rem;
      color: #333;
      margin-top: 0.3rem;
    }
  </style>
</head>
<body>

  <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
    <div class="alert alert-info" role="alert">
      <?= htmlspecialchars($alertMessage) ?>
    </div>
    <div class="text-center mb-4">
      <a href="http://192.168.100.95/Comedor/MenFortCanc.php" class="btn btn-secondary" aria-label="Volver al menú principal">
        ← Volver al menú principal
      </a>
    </div>
  <?php endif; ?>

  <div class="glass-container">
    <!-- Información del usuario -->
    <div class="user-info">
      <div class="user-name">👤 <?php echo htmlspecialchars($user_name); ?></div>
      <div class="user-area">🏢 <?php echo htmlspecialchars($user_area); ?></div>
    </div>

    <h3>FORMULARIO DE CANCELACIÓN DE CONSUMOS</h3>
    <form method="post" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

      <div class="mb-3">
        <label for="inputEmail4">NOMBRE</label>
        <input type="text" class="form-control" id="inputEmail4" name="inputEmail4" value="<?= htmlspecialchars($user_name) ?>" readonly />
      </div>

      <div class="mb-3">
        <label for="inputPassword4">DEPARTAMENTO</label>
        <input type="text" class="form-control" id="inputPassword4" name="inputPassword4" value="<?= htmlspecialchars($user_area) ?>" readonly />
      </div>

      <div class="mb-3">
        <label for="inputAddress">JEFE INMEDIATO QUE AUTORIZÓ</label>
        <input type="text" class="form-control" id="inputAddress" name="inputAddress" placeholder="Nombre del jefe inmediato que autoriza la cancelación" required />
      </div>

      <div class="mb-3">
        <label for="inputState">TIPO DE CONSUMO A CANCELAR</label>
        <select id="inputState" name="inputState" class="form-select" required>
          <option value="DESAYUNO">DESAYUNO</option>
          <option value="COMIDA">COMIDA</option>
          <option value="AMBOS">AMBOS</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="inputZip">FECHA</label>
        <input type="date" class="form-control" id="inputZip" name="inputZip" value="<?= date('Y-m-d') ?>" required />
      </div>

      <div class="mb-3">
        <label for="inputState1">CAUSA</label>
        <select id="inputState1" name="inputState1" class="form-select" required>
          <option value="SALUD">SALUD</option>
          <option value="PERSONAL">PERSONAL (causa mayor)</option>
          <option value="VACACIONES">VACACIONES</option>
          <option value="COMISIÓN">COMISIÓN</option>
          <option value="REUNIÓN">REUNIÓN</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary w-100">ENVIAR</button>
    </form>

    <div class="text-center mt-3">
      <a href="http://192.168.100.95/Comedor" class="menu-link">← Volver al Menú Principal</a>
    </div>
  </div>

</body>
</html>