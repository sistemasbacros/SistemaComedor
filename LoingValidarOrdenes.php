<link rel="stylesheet" href="https://unpkg.com/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://unpkg.com/bs-brain@2.0.4/components/logins/login-4/assets/css/login-4.css">
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
    background: rgba(0, 0, 0, 0.55); /* Semi-transparent background */
    backdrop-filter: blur(5px); /* Frosted glass effect */
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
    background: rgba(255, 255, 255, 0.2); /* Add transparency to allow background effect */
    border-radius: 16px;
    padding: 40px;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
    backdrop-filter: blur(15px); /* Frosted glass blur */
    -webkit-backdrop-filter: blur(15px); /* Safari support */
    border: 1px solid rgba(255, 255, 255, 0.18);
    z-index: 1;
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
    background-color: rgba(255, 255, 255, 0.3); /* Lighter background */
    border: none;
    color: #fff;
  }

  .form-control::placeholder {
    color: rgba(255, 255, 255, 0.8);
  }

  .form-control:focus {
    background-color: rgba(255, 255, 255, 0.4); /* Slightly brighter focus */
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

<p><a href="Demo_SistemaComedor.html">Menu principal</a></p>    
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
</section>

<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

$name = test_input($_POST["email"]); /// Usuario
$name1 = test_input($_POST["password"]); /// Contraseña

///Conectar base de datos
$conn = getComedorConnection();

///Conectar base de datos

////// Consulta a la base de datos.

$sql = "Select *  from ConPedContra where usuario='$name' and Contrasena='$name1'";
$stmt = sqlsrv_query( $conn, $sql );
if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}

$query = sqlsrv_query($conn,$sql, array(), array( "Scrollable" => 'static' ));

$row_count = sqlsrv_num_rows($query);

$array_tot9 = [];

$stmt = sqlsrv_query( $conn, $sql );

while( $row = sqlsrv_fetch_array( $stmt,SQLSRV_FETCH_NUMERIC) ) {
	
echo $row[0];	

$dat= [];

if ($row_count== 0) {
echo'<script type="text/javascript">
    alert("USUARIO O CONTRASEÑA INCORRECTA");
    </script>';	
	
} else {
header("Location: http://192.168.100.95/Comedor/ValidarFormatos.php?newpwd=$row[5]");
}
}

}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
 }

?>
