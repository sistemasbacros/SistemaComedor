<?php
require_once __DIR__ . '/config/database.php';
$dat = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = test_input($_POST["email"]);
    $contrasena = test_input($_POST["password"]);

    $conn = getComedorConnection();

    if (!$conn) {
        die("Error de conexión: " . print_r(sqlsrv_errors(), true));
    }

    $sql = "SELECT * FROM ConPed WHERE usuario = ? AND Contrasena = ?";
    $params = array($usuario, $contrasena);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)) {
        array_push($dat, $row[0], $row[1], $row[2], $row[3], $row[4]);
    }

    sqlsrv_close($conn);
}

function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login - Sistema Comedor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap + Fuente -->
  <link rel="stylesheet" href="https://unpkg.com/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.5.1.js"></script>

  <!-- Estilos personalizados -->
  <style>
    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      background: url('comedor-industrial-monterrey.jpg') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      overflow: hidden;
      position: relative;
      animation: floatBackground 20s ease-in-out infinite alternate;
    }

    body::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.55);
      backdrop-filter: blur(5px);
      z-index: 0;
    }

    @keyframes floatBackground {
      0% { background-position: center top; }
      100% { background-position: center bottom; }
    }

    .login-container {
      position: relative;
      z-index: 1;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      animation: floatCard 6s ease-in-out infinite alternate;
    }

    @keyframes floatCard {
      0% { transform: translateY(0px); }
      100% { transform: translateY(-10px); }
    }

    .login-box {
      background: rgba(255, 255, 255, 0.12);
      border-radius: 16px;
      padding: 40px;
      max-width: 400px;
      width: 100%;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
      backdrop-filter: blur(15px);
      -webkit-backdrop-filter: blur(15px);
      border: 1px solid rgba(255, 255, 255, 0.18);
    }

    .login-box h2 {
      font-weight: 700;
      color: #fff;
      margin-bottom: 30px;
      text-align: center;
    }

    .form-label {
      font-weight: 600;
      color: #fff;
    }

    .form-control {
      background-color: rgba(255, 255, 255, 0.2);
      border: none;
      color: #fff;
    }

    .form-control::placeholder {
      color: rgba(255, 255, 255, 0.8);
    }

    .form-control:focus {
      background-color: rgba(255, 255, 255, 0.3);
      color: #fff;
    }

    .btn-primary {
      background-color: #2D6DA6;
      border: none;
      font-weight: bold;
    }

    .btn-primary:hover {
      background-color: #1f4c74;
    }

    .toggle-password {
      cursor: pointer;
      position: absolute;
      right: 15px;
      top: 38px;
      color: #ccc;
    }

    .back-link {
      text-align: center;
      margin-top: 20px;
    }

    .back-link a {
      color: #fff;
      text-decoration: none;
      font-size: 0.9rem;
    }

    .back-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="login-container">
  <div class="login-box">
    <h2>Sistema de Comedor</h2>
    <form method="post" action="">
      <div class="mb-3">
        <label for="email" class="form-label">Usuario</label>
        <input type="text" class="form-control" id="email" name="email" placeholder="Ingrese su usuario" required>
      </div>

      <div class="mb-3 position-relative">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Ingrese su contraseña" required>
        <span class="toggle-password" onclick="togglePassword()">👁️</span>
      </div>

      <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary">Ingresar</button>
      </div>
    </form>

    <div class="back-link">
      <a href="Demo_SistemaComedor.html">← Volver al menú principal</a>
    </div>
  </div>
</div>

<script>
function togglePassword() {
  const input = document.getElementById("password");
  const type = input.getAttribute("type") === "password" ? "text" : "password";
  input.setAttribute("type", type);
}

const datos = <?php echo json_encode($dat); ?>;

if (datos.length > 0) {
  $.post("Ldata.php", { arrayDeValores: JSON.stringify(datos) }, function(response) {
    window.location.href = "http://192.168.100.95/Comedor/FormatCancel.php";
  });
} else if (<?php echo json_encode($_SERVER["REQUEST_METHOD"] === "POST"); ?>) {
  alert("Usuario o contraseña incorrecta.");
}
</script>

</body>
</html>
