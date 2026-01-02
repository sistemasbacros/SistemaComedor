<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  

<html>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {font-family: Arial Narrow;
 background-color: rgba(241, 238, 237);
   font-size: 24px;

}
* {box-sizing: border-box;}

/* Full-width input fields */
input[type=text], input[type=password] {
  width: 100%;
  padding: 15px;
  margin: 5px 0 22px 0;
  display: inline-block;
  border: none;
  background: #f1f1f1;
}

/* Add a background color when the inputs get focus */
input[type=text]:focus, input[type=password]:focus {
  background-color: #ddd;
  outline: none;
}

/* Set a style for all buttons */
button {
  background-color: #808283;
  color: white;
  padding: 14px 20px;
  margin: 8px 0;
  border: none;
  cursor: pointer;
  width: 100%;
  opacity: 0.9;
}

button:hover {
  opacity:1;
}

/* Extra styles for the cancel button */
.cancelbtn {
  padding: 14px 20px;
  background-color: black;
}

/* Float cancel and signup buttons and add an equal width */
.cancelbtn, .signupbtn {
  float: left;
  width: 50%;
}

/* Add padding to container elements */
.container {
  padding: 16px;
}

/* The Modal (background) */
.modal {
  display: none; /* Hidden by default */
  position: fixed; /* Stay in place */
  z-index: 1; /* Sit on top */
  left: 0;
  top: 0;
  width: 100%; /* Full width */
  height: 100%; /* Full height */
  overflow: auto; /* Enable scroll if needed */
  background-color: #474e5d;
  padding-top: 50px;
}

/* Modal Content/Box */
.modal-content {
  background-color: #fefefe;
  margin: 5% auto 15% auto; /* 5% from the top, 15% from the bottom and centered */
  border: 1px solid #888;
  width: 80%; /* Could be more or less, depending on screen size */
}

/* Style the horizontal ruler */
hr {
  border: 1px solid #f1f1f1;
  margin-bottom: 25px;
}
 
/* The Close Button (x) */
.close {
  position: absolute;
  right: 35px;
  top: 15px;
  font-size: 40px;
  font-weight: bold;
  color: #f1f1f1;
}

.close:hover,
.close:focus {
  color: #f44336;
  cursor: pointer;
}

/* Clear floats */
.clearfix::after {
  content: "";
  clear: both;
  display: table;
}

/* Change styles for cancel button and signup button on extra small screens */
@media screen and (max-width: 300px) {
  .cancelbtn, .signupbtn {
     width: 100%;
  }
}

.img-container {
        text-align: left;
      }
</style>
<body>
<div class="container">
<p><a href="Demo_SistemaComedor.html">Menu principal</a></p>
      <h1>Ingresa ahora</h1>
      <p>Por favor, ingresa los datos de la cuenta</p>
      <hr>
      <label for="email"><b>Usuario</b></label>
      <input type="text" placeholder="Ingresa tu correo electrónico" id="correo" name="email" required>

      <label for="psw"><b>Contraseña</b></label>
      <input type="password" placeholder="Ingresa tu contraseña"  id="contrase" name="psw" required>
      
      <!-- <label> -->
        <!-- <input type="checkbox" checked="checked" name="remember" style="margin-bottom:15px"> Remember me -->
      <!-- </label> -->

      <!-- <p>By creating an account you agree to our <a href="#" style="color:dodgerblue">Terms & Privacy</a>.</p> -->

      <div class="clearfix">
        <button type="button" onclick="document.getElementById('id01').style.display='none'" class="cancelbtn">Cancelar</button>
        <button type="submit" name="submit" class="signupbtn">Aceptar</button>
		

      </div>
    </div>
	    <div class="img-container"> <!-- Block parent element -->
    <img src="Logo2.png" width="300" height="200"> </div>
</body>
</html>

</form>




<?php

$usuario = $contrase = $valor;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $usuario = test_input($_POST["email"]);
  $contrase = test_input($_POST["psw"]);

 
// ////////////////// Select
// $serverName = "LUISROMERO\SQLEXPRESS"; //serverName\instanceName
// $connectionInfo = array( "Database"=>"Comedor", "UID"=>"larome02", "PWD"=>"larome02");
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

$sql = "Select *  from [dbo].[ContConsultas]";
$stmt = sqlsrv_query( $conn, $sql );
if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}

while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
      // echo $row['Usuarios'].", ".$row['Contrasena']."<br />";
	  
//////////////////////// IF validación  contraseña

if ($row['Usuarios'] === $usuario and $row['Contrasena'] === $contrase) {
  $valor = 1;
  
}	  else  { 
///echo "La contraseña no se encuentra en la tabla"; 
}

//////////////////////// If validación contraseña	  
	  }

if ($valor ===1) {
  header("Location: http://192.168.100.95/Comedor/Consultadedatos.php");
} else  {	  echo "Contraseña incorrecta"; }


sqlsrv_free_stmt( $stmt);

}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
?>