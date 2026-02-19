<!--
===============================================================
DOCUMENTACIÓN: Login2.php
===============================================================
@file       Login2.php
@brief      Página de autenticación principal del Sistema Comedor

@description
  Presenta el formulario de inicio de sesión a los empleados
  del Sistema Comedor de BacroCorp. Al enviarse el formulario
  (método POST), el bloque PHP al final del archivo sanitiza
  las entradas, consulta la tabla [dbo].[ContConsultas] en SQL
  Server y, si las credenciales coinciden, redirige al módulo de
  consulta de datos. En caso contrario, muestra un mensaje de error.

  NOTA TÉCNICA: Este archivo usa credenciales y servidor de base
  de datos hardcodeados (servidor de desarrollo). Está pendiente
  de migración al esquema centralizado de config/database.php
  y al patrón de sesiones documentado en CLAUDE.md.

@module     Autenticación
@access     TODOS (página pública, no requiere sesión activa)

@dependencies
  - PHP sqlsrv extension (conexión directa a SQL Server)
  - Sin librerías JS externas (solo HTML/CSS inline)

@database
  - Servidor:       DESAROLLO-BACRO\SQLEXPRESS (hardcodeado)
  - Base de datos:  Comedor
  - Tabla(s):       [dbo].[ContConsultas]
  - Columnas:       Usuarios, Contrasena
  - Tipo de operación: SELECT (recorre todos los registros para comparar)

@session
  - No utiliza variables de sesión en esta versión

@inputs
  - $_POST['email'] : string - Nombre de usuario ingresado en el formulario
  - $_POST['psw']   : string - Contraseña ingresada en el formulario

@outputs
  - HTML renderizado al navegador (formulario de inicio de sesión)
  - Redirección HTTP a Consultadedatos.php
    cuando las credenciales son válidas
  - Echo de texto "Contraseña incorrecta" cuando la autenticación falla

@security
  - Sanitización de entradas con test_input() (trim + stripslashes + htmlspecialchars)
  - Sin protección CSRF en esta versión (pendiente implementar)
  - Sin límite de intentos de inicio de sesión (pendiente implementar)
  - Sin regeneración de ID de sesión (ver Login2.php moderno para el patrón correcto)

@author   Equipo Tecnología BacroCorp
@version  1.0
@since    2024
@updated  2026-02-18
===============================================================
-->
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
/**
 * @file Login2.php
 * @brief Bloque PHP de validación de credenciales del formulario de login
 *
 * @description
 *   Procesa el envío del formulario de autenticación (método POST).
 *   Sanitiza los campos recibidos, establece conexión directa a SQL Server,
 *   itera sobre los registros de [dbo].[ContConsultas] para verificar si
 *   el par usuario/contraseña existe, y emite una redirección HTTP en caso
 *   de éxito o un mensaje de texto en caso de fallo.
 *
 *   ADVERTENCIA: Las credenciales de base de datos están hardcodeadas.
 *   Pendiente de migración a config/database.php + getComedorConnection().
 *
 * @module     Autenticación
 * @access     TODOS
 *
 * @database
 *   - Tabla leída:    [dbo].[ContConsultas]
 *   - Columnas:       Usuarios, Contrasena
 *   - Tipo:           SELECT (full table scan para comparación)
 *
 * @inputs
 *   - $_POST['email'] : string - Nombre de usuario
 *   - $_POST['psw']   : string - Contraseña en texto plano
 *
 * @outputs
 *   - header() Redirección a Consultadedatos.php si login es exitoso
 *   - echo "Contraseña incorrecta" si la validación falla
 *
 * @security
 *   - Entradas sanitizadas con test_input() antes de comparar
 *   - La comparación usa === (tipo estricto)
 */

/* =========================================================
 * SECCIÓN: Inicialización de variables
 * =========================================================
 * Define las variables de autenticación con valor inicial nulo/undefined.
 * $valor actúa como bandera: se establece en 1 cuando las credenciales
 * coinciden con un registro de la tabla de usuarios.
 */
$usuario = $contrase = $valor;

/* =========================================================
 * SECCIÓN: Procesamiento del formulario POST
 * =========================================================
 * Verifica que la solicitud sea de tipo POST antes de procesar.
 * Sanitiza los valores recibidos mediante test_input() para
 * eliminar espacios innecesarios, barras invertidas y caracteres
 * HTML peligrosos (prevención básica de XSS).
 */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $usuario = test_input($_POST["email"]);
  $contrase = test_input($_POST["psw"]);

 
require_once __DIR__ . '/config/database.php';
$conn = getComedorConnection();

// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }

/* =========================================================
 * SECCIÓN: Consulta SQL y manejo del resultado
 * =========================================================
 * Ejecuta un SELECT completo sobre [dbo].[ContConsultas] para
 * recuperar todos los usuarios registrados. Si la consulta falla,
 * termina la ejecución mostrando los errores de sqlsrv.
 * El recorrido del resultado se realiza en el bloque while siguiente.
 */
$sql = "Select *  from [dbo].[ContConsultas]";
$stmt = sqlsrv_query( $conn, $sql );
if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}

/* =========================================================
 * SECCIÓN: Iteración de resultados y validación de credenciales
 * =========================================================
 * Recorre todos los registros de la tabla de usuarios y compara
 * de forma estricta (===) el usuario y contraseña recibidos del
 * formulario contra cada fila. Si coincide, eleva la bandera $valor a 1.
 * NOTA: La búsqueda se realiza con full table scan; no usa parámetros
 * SQL vinculados (bound parameters), por lo que depende de test_input()
 * para la sanitización de entradas.
 */
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

/* =========================================================
 * SECCIÓN: Resultado de autenticación — redirección o error
 * =========================================================
 * Evalúa la bandera $valor tras recorrer todos los registros.
 * Si $valor === 1 (credenciales válidas): redirige al módulo principal.
 * Si $valor no es 1 (credenciales no encontradas): muestra mensaje de error.
 * Libera el statement de sqlsrv antes de finalizar el bloque POST.
 * NOTA: La URL de redirección está hardcodeada a la IP del servidor.
 */
if ($valor ===1) {
  header("Location: Consultadedatos.php");
} else  {	  echo "Contraseña incorrecta"; }


sqlsrv_free_stmt( $stmt);

}

/* =========================================================
 * SECCIÓN: Funciones auxiliares
 * =========================================================
 * Funciones de utilidad usadas en el procesamiento del formulario.
 */

/**
 * @brief Sanitiza y limpia una cadena de entrada del formulario
 *
 * @description
 *   Aplica tres operaciones de limpieza en secuencia:
 *   1. trim()           — Elimina espacios y saltos de línea al inicio y fin.
 *   2. stripslashes()   — Elimina barras invertidas (escapes innecesarios).
 *   3. htmlspecialchars() — Convierte caracteres especiales HTML a entidades,
 *                           previniendo inyección de HTML/XSS básico.
 *
 * @param string $data Cadena de texto recibida del formulario (sin tratar)
 * @return string Cadena sanitizada y segura para comparación
 *
 * @example
 *   $usuario = test_input("  admin<script>  ");
 *   // Retorna: "admin&lt;script&gt;"
 *
 *   $contrase = test_input("  pass\word  ");
 *   // Retorna: "password"
 */
function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
?>