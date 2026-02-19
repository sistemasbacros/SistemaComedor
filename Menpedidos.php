<!--
 * @file Menpedidos.php
 * @brief Formulario de registro del menú semanal del empleado (Desayuno y/o Comida por día).
 *
 * @description
 * Módulo de captura de pedidos semanales. Presenta un formulario con tarjetas por día
 * (Lunes a Viernes), donde el empleado puede elegir "Desayuno", "Comida" o ninguno para
 * cada jornada. Al enviar (POST), el módulo PHP embebido valida las credenciales del
 * usuario contra la tabla ConPed, verifica que no exista ya un pedido para la semana
 * seleccionada y, si todo es correcto, inserta dos registros en PedidosComida: uno para
 * los desayunos (lunes–viernes) y otro para las comidas.
 *
 * Flujo principal:
 *   1. GET: Renderiza el formulario vacío con los días y el selector de fecha.
 *   2. POST: Sanitiza entradas -> valida credenciales -> verifica duplicado ->
 *            INSERT doble (desayunos + comidas) -> muestra modal de resultado.
 *
 * ADVERTENCIA: Este archivo contiene credenciales de base de datos hardcodeadas.
 * Debe migrarse para usar getComedorConnection() de config/database.php.
 *
 * @module Pedidos Semanales
 * @access Acceso público — autenticación interna mediante tabla ConPed
 *
 * @dependencies
 * - Librerías JS: FontAwesome 6.5.0
 * - Archivos PHP: Ninguno (conexión directa hardcodeada)
 *
 * @database
 * - Tablas: PedidosComida, ConPed (BD Comedor)
 * - Operaciones: SELECT (validación de credenciales, conteo de pedidos existentes), INSERT
 *
 * @session
 * - No utiliza sesión PHP; la autenticación se realiza mediante POST (Usuario + Contraseña)
 *
 * @inputs
 * - $_POST['gender1'...'gender10'] : string - Selección de tipo de comida por día
 *   (valores posibles: 'Desayuno', 'Comida' o vacío si no se seleccionó)
 * - $_POST['Nempleado']  : int    - Número de empleado (rango 1–1225)
 * - $_POST['Usuar']      : string - Nombre de usuario para autenticación
 * - $_POST['contrase']   : string - Contraseña del usuario
 * - $_POST['Fecha2']     : string - Fecha de inicio de semana (YYYY-MM-DD)
 *
 * @outputs
 * - HTML renderizado con formulario de selección semanal
 * - Modal JavaScript con resultado del guardado (éxito o error)
 *
 * @security
 * - test_input() aplica trim, stripslashes y htmlspecialchars con ENT_QUOTES/UTF-8
 * - Validación de credenciales contra tabla ConPed (Usuario + Contraseña en texto plano)
 * - Prevención de pedido duplicado: máximo 2 registros por semana por usuario
 * - Credenciales de BD hardcodeadas (pendiente de migración a variables de entorno)
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.0
 * @since 2024
 * @updated 2026-02-18
-->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Menú Semanal - Comedor</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --pearl: #f8f6f4;
      --navy: #0a192f;
      --navy-medium: #2a5c8a;
      --navy-dark: #1e4a70;
      --glass-bg: rgba(10, 25, 47, 0.3);
      --glass-border: rgba(255, 255, 255, 0.4);
      --shadow: 0 10px 40px rgba(10, 25, 47, 0.25);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--pearl);
      color: var(--navy);
      min-height: 100vh;
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .navbar {
      width: 100%;
      max-width: 1000px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .navbar a {
      color: var(--navy);
      text-decoration: none;
      font-size: 22px;
      font-weight: 600;
      padding: 10px 16px;
      border-radius: 12px;
      background: white;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
    }

    .navbar a:hover {
      background: var(--navy);
      color: white;
    }

    .logo {
      height: 60px;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    }

    h2 {
      text-align: center;
      margin-bottom: 30px;
      font-size: 2.2rem;
      color: var(--navy);
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
    }

    form {
      width: 100%;
      max-width: 1000px;
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 20px;
      padding: 30px;
      box-shadow: var(--shadow);
      color: #000;
      transition: transform 0.3s ease;
    }

    form:hover {
      transform: translateY(-4px);
    }

    .week-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 20px;
      margin-bottom: 28px;
    }

    .day-card {
      background: rgba(255, 255, 255, 0.25);
      border: 1px solid var(--glass-border);
      border-radius: 16px;
      padding: 16px;
      text-align: center;
      transition: all 0.3s ease;
    }

    .day-card:hover {
      background: rgba(255, 255, 255, 0.4);
      transform: scale(1.03);
    }

    .day-card h3 {
      margin-bottom: 14px;
      font-size: 1.25rem;
      font-weight: 600;
      color: #000;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      font-size: 1.05rem;
      color: #000;
    }

    input, select, button {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.95);
      color: #000;
      font-family: 'Inter', sans-serif;
      font-size: 1rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }

    input:focus, select:focus {
      outline: 2px solid var(--navy-medium);
      background: white;
    }

    button {
      background: var(--navy-medium);
      color: white;
      font-weight: 600;
      cursor: pointer;
      font-size: 1.1rem;
      letter-spacing: 0.5px;
      box-shadow: 0 4px 14px rgba(10, 25, 47, 0.35);
    }

    button:hover {
      background: var(--navy-dark);
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(10, 25, 47, 0.45);
    }

    .meal-option {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px;
      border-radius: 10px;
      margin: 6px 0;
      cursor: pointer;
      transition: background 0.2s;
      color: #000;
    }

    .meal-option:hover {
      background: rgba(255, 255, 255, 0.35);
    }

    .meal-option input {
      width: auto;
      margin: 0;
      accent-color: var(--navy-medium);
    }

    /* Modal de notificación */
    #notification-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background: white;
      padding: 24px;
      border-radius: 16px;
      max-width: 400px;
      text-align: center;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .modal-content i {
      font-size: 2.5rem;
      margin-bottom: 16px;
    }

    .modal-content.success i { color: #4CAF50; }
    .modal-content.error i { color: #f44336; }

    .modal-content h3 {
      margin-bottom: 12px;
      font-weight: 600;
      color: #1a1a1a;
    }

    .modal-content p {
      margin-bottom: 20px;
      color: #4a5568;
    }

    .modal-content button {
      background: var(--navy-medium);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
    }

    @media (max-width: 650px) {
      .navbar {
        flex-direction: column;
        gap: 15px;
      }
      h2 {
        font-size: 1.8rem;
      }
      form {
        padding: 20px;
      }
    }

    @media (max-width: 480px) {
      .week-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

<div class="navbar">
  <a href="Demo_SistemaComedor.html"><i class="fas fa-home"></i> Inicio</a>
  <img src="Logo2.png" alt="Logo" class="logo">
</div>

<h2><i class="fas fa-utensils"></i> Menú Semanal</h2>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="menuForm">

  <div class="week-grid">
    <?php
    $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
    foreach ($dias as $index => $dia) {
      $d = $index * 2 + 1;
      $c = $index * 2 + 2;
      echo "<div class='day-card'>
              <h3>$dia</h3>
              <label class='meal-option'>
                <input type='radio' name='gender$d' value='Desayuno' class='toggle-radio'>
                <span>🍳 Desayuno</span>
              </label>
              <label class='meal-option'>
                <input type='radio' name='gender$c' value='Comida' class='toggle-radio'>
                <span>🍽️ Comida</span>
              </label>
            </div>";
    }
    ?>
  </div>

  <div class="form-group">
    <label for="Nempleado"><i class="fas fa-id-badge"></i> Número de empleado</label>
    <select name="Nempleado" id="Nempleado" required>
      <?php for ($i = 1; $i <= 1225; $i++): ?>
        <option value="<?= $i ?>"><?= $i ?></option>
      <?php endfor; ?>
    </select>
  </div>

  <div class="form-group">
    <label for="Usuar"><i class="fas fa-user"></i> Usuario</label>
    <input type="text" id="Usuar" name="Usuar" placeholder="Ingresa tu usuario" required>
  </div>

  <div class="form-group">
    <label for="contrase"><i class="fas fa-lock"></i> Contraseña</label>
    <input type="password" id="contrase" name="contrase" placeholder="••••••••" required>
  </div>

  <div class="form-group">
    <label for="Fecha2"><i class="far fa-calendar-alt"></i> Fecha</label>
    <select name="Fecha2" id="Fecha2" required>
  
            <option value="2026-01-05">05/01/2026</option>
            <option value="2026-01-12">12/01/2026</option>
            <option value="2026-01-19">19/01/2026</option>
            <option value="2026-01-26">26/01/2026</option>
			 
    </select>
  </div>

  <button type="submit"><i class="fas fa-check-circle"></i> Confirmar Pedido</button>
</form>

<!-- Modal de notificación -->
<div id="notification-modal">
  <div class="modal-content">
    <i class="fas fa-check-circle"></i>
    <h3>Éxito</h3>
    <p>Tu pedido ha sido registrado correctamente.</p>
    <button onclick="closeModal()">Aceptar</button>
  </div>
</div>

<script>
  // Permitir deseleccionar radios
  document.querySelectorAll('.toggle-radio').forEach(radio => {
    radio.addEventListener('click', function() {
      if (this.dataset.wasChecked === 'true') {
        this.checked = false;
        delete this.dataset.wasChecked;
      } else {
        // Desmarcar otros del mismo grupo
        document.querySelectorAll(`input[name="${this.name}"]`).forEach(r => {
          if (r !== this) r.dataset.wasChecked = 'false';
        });
        this.dataset.wasChecked = 'true';
      }
    });
  });

  // Modal personalizado
  function showModal(type, message) {
    const modal = document.getElementById('notification-modal');
    const content = modal.querySelector('.modal-content');
    const icon = content.querySelector('i');
    const title = content.querySelector('h3');
    const text = content.querySelector('p');

    if (type === 'success') {
      icon.className = 'fas fa-check-circle';
      icon.style.color = '#4CAF50';
      title.textContent = 'Éxito';
    } else {
      icon.className = 'fas fa-exclamation-triangle';
      icon.style.color = '#f44336';
      title.textContent = 'Error';
    }
    text.textContent = message;
    modal.style.display = 'flex';
  }

  function closeModal() {
    document.getElementById('notification-modal').style.display = 'none';
  }

  // Cerrar modal al hacer clic fuera
  window.onclick = function(event) {
    const modal = document.getElementById('notification-modal');
    if (event.target === modal) closeModal();
  }
</script>

<?php
/**
 * @brief Sanitiza una cadena de texto para su uso seguro en HTML.
 *
 * Aplica secuencialmente: eliminación de espacios extremos (trim),
 * eliminación de barras de escape (stripslashes) y conversión de
 * caracteres especiales a entidades HTML con ENT_QUOTES y codificación UTF-8.
 *
 * @param string $data Cadena de texto a sanitizar
 * @return string Cadena sanitizada lista para uso seguro en HTML y consultas
 */
function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
  return $data;
}

/* =========================================================
 * PROCESAMIENTO DEL FORMULARIO POST
 * Flujo:
 *   1. Sanitiza todos los campos recibidos con test_input()
 *   2. Conecta a la BD Comedor
 *   3. Valida credenciales del usuario (ConPed)
 *   4. Verifica que no exista ya un pedido para la semana y usuario indicados
 *   5. Inserta dos registros en PedidosComida: uno para desayunos, otro para comidas
 *   6. Emite llamada JS al modal de notificación con el resultado
 * =========================================================
 */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $lunesd = test_input($_POST["gender1"] ?? '');
  $lunesc = test_input($_POST["gender2"] ?? '');
  $martesd = test_input($_POST["gender3"] ?? '');
  $martesc = test_input($_POST["gender4"] ?? '');
  $miercolesd = test_input($_POST["gender5"] ?? '');
  $miercolesc = test_input($_POST["gender6"] ?? '');
  $juevesd = test_input($_POST["gender7"] ?? '');
  $juevesc = test_input($_POST["gender8"] ?? '');
  $viernesd = test_input($_POST["gender9"] ?? '');
  $viernesc = test_input($_POST["gender10"] ?? '');
  $numemp = test_input($_POST["Nempleado"] ?? '');
  $usua = test_input($_POST["Usuar"] ?? '');
  $cont = test_input($_POST["contrase"] ?? '');
  $fecha = test_input($_POST["Fecha2"] ?? '');

  require_once __DIR__ . '/config/database.php';
  $conn = getComedorConnection();

  if (!$conn) {
    echo '<script>showModal("error", "Error al conectar con la base de datos.");</script>';
  } else {
    /* =========================================================
     * CONSULTA: Validación de credenciales de usuario
     * Tablas: ConPed
     * Retorna: Fila si Usuario y Contraseña coinciden; vacío si son incorrectos.
     * =========================================================
     */
    $sql2 = "SELECT Usuario, Contrasena FROM ConPed WHERE Usuario = ? AND Contrasena = ?";
    $stmt2 = sqlsrv_query($conn, $sql2, [$usua, $cont]);
    $credencial_valida = ($stmt2 && sqlsrv_has_rows($stmt2));

    /* =========================================================
     * CONSULTA: Verificación de pedido duplicado en la semana
     * Tablas: PedidosComida
     * Retorna: Conteo de registros existentes para el usuario y fecha indicados.
     *          Si el Total >= 2, se rechaza el nuevo pedido (ya tiene completos sus 2 registros).
     * =========================================================
     */
    $sql3 = "SELECT COUNT(*) AS Total FROM PedidosComida WHERE Fecha = ? AND Usuario = ?";
    $stmt3 = sqlsrv_query($conn, $sql3, [$fecha, $usua]);
    $row = sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC);
    $valor1 = $row['Total'] ?? 0;

    if (!$credencial_valida) {
      echo '<script>showModal("error", "Usuario o contraseña incorrectos.");</script>';
    } elseif ($valor1 >= 2) {
      echo '<script>showModal("error", "Ya tienes un pedido para esta fecha.");</script>';
    } else {
      /* =========================================================
       * INSERCIÓN: Registro del pedido semanal (dos filas por semana)
       * Tablas: PedidosComida
       * Parámetros 1 (desayunos): Id_Empleado, Usuario, Contrasena, Fecha, L-V opciones desayuno
       * Parámetros 2 (comidas):   Id_Empleado, Usuario, Contrasena, Fecha, L-V opciones comida
       * Costo fijo: 30 (unidades monetarias)
       * =========================================================
       */
      $sql = "INSERT INTO PedidosComida (Id_Empleado, Nom_Pedido, Usuario, Contrasena, Fecha, Lunes, Martes, Miercoles, Jueves, Viernes, Costo)
              VALUES (?, '', ?, ?, ?, ?, ?, ?, ?, ?, 30)";
      $params1 = [$numemp, $usua, $cont, $fecha, $lunesd, $martesd, $miercolesd, $juevesd, $viernesd];
      $params2 = [$numemp, $usua, $cont, $fecha, $lunesc, $martesc, $miercolesc, $juevesc, $viernesc];

      $stmt = sqlsrv_query($conn, $sql, $params1);
      $stmt1 = sqlsrv_query($conn, $sql, $params2);

      if ($stmt && $stmt1) {
        echo '<script>showModal("success", "¡Tu pedido se registró con éxito!");</script>';
      } else {
        echo '<script>showModal("error", "Error al registrar. Inténtalo más tarde.");</script>';
      }

      sqlsrv_free_stmt($stmt);
      sqlsrv_free_stmt($stmt1);
    }

    sqlsrv_free_stmt($stmt2);
    sqlsrv_free_stmt($stmt3);
    sqlsrv_close($conn);
  }
}
?>

</body>
</html>