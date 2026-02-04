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
$alertType = ''; // 'success' o 'error'
$formSubmitted = false;

// Datos del formulario para mostrar en caso de éxito
$datosGuardados = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $user_name;
    $departamento = $user_area;

    $jefe = test_input($_POST["inputAddress"] ?? '');
    $consumo = test_input($_POST["inputState"] ?? '');
    $fecha = test_input($_POST["inputZip"] ?? '');
    $causa = test_input($_POST["inputState1"] ?? '');
    $descripcion = test_input($_POST["descripcion"] ?? '');

    // Guardar datos para mostrar en el resumen
    $datosGuardados = [
        'jefe' => $jefe,
        'consumo' => $consumo,
        'fecha' => $fecha,
        'causa' => $causa,
        'descripcion' => $descripcion
    ];

    // Validar que la descripción no esté vacía
    if (empty($descripcion)) {
        $alertMessage = "La descripción del motivo es obligatoria.";
        $alertType = "error";
    } else {
        $fechaActual = date('Y-m-d');
        $horaActual = date('H:i:s');

        $fechaMin = '2026-01-22';
        $fechaMax = '2026-02-14';

        if ($fecha < $fechaMin || $fecha > $fechaMax) {
            $alertMessage = "La fecha seleccionada no está dentro del periodo permitido.";
            $alertType = "error";
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
                $alertType = "error";
            } else {
                // Verificar si ya existe cancelación para el mismo nombre, departamento, fecha y tipo de consumo
                $checkSql = "SELECT COUNT(*) AS count FROM Cancelaciones WHERE NOMBRE = ? AND DEPARTAMENTO = ? AND FECHA = ? AND TIPO_CONSUMO = ?";
                $checkParams = [$nombre, $departamento, $fecha, $consumo];
                $checkStmt = sqlsrv_query($conn, $checkSql, $checkParams);

                if ($checkStmt === false) {
                    $alertMessage = "Error al validar la cancelación previa.";
                    $alertType = "error";
                } else {
                    $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
                    if ($row['count'] > 0) {
                        $alertMessage = "Ya existe una cancelación registrada para esta fecha y tipo de consumo.";
                        $alertType = "error";
                    } else {
                        // Insertar en la base de datos con el campo DESCRIPCION
                        $sql = "INSERT INTO Cancelaciones 
                        ([NOMBRE],[DEPARTAMENTO],[JEFE],[TIPO_CONSUMO],[FECHA],[CAUSA],[DESCRIPCION],[ESTATUS],[FECHA_CAPTURA]) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'EN PROCESO', ?)";
                        $params = [$nombre, $departamento, $jefe, $consumo, $fecha, $causa, $descripcion, $fechaActual . ' ' . $horaActual];
                        $stmt = sqlsrv_query($conn, $sql, $params);

                        if ($stmt === false) {
                            $alertMessage = "Error al insertar los datos.";
                            $alertType = "error";
                        } else {
                            $alertMessage = "¡Cancelación guardada exitosamente!";
                            $alertType = "success";
                            $formSubmitted = true;
                        }
                    }
                }
                sqlsrv_close($conn);
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Título removido según solicitud -->
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
      transition: all 0.3s ease;
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

    .required-field::after {
      content: " *";
      color: #dc3545;
    }

    .form-control,
    .form-select,
    .form-textarea {
      background-color: rgba(255, 255, 255, 0.85);
      color: #000;
      border: none;
      font-weight: 500;
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }

    .form-control::placeholder,
    .form-textarea::placeholder {
      color: #555;
      font-style: italic;
    }

    .form-control:focus,
    .form-select:focus,
    .form-textarea:focus {
      background-color: rgba(255, 255, 255, 0.95);
      color: #000;
      outline: none;
      box-shadow: 0 0 0 3px rgba(45, 109, 166, 0.25);
    }

    .btn-primary {
      background-color: #2D6DA6;
      border: none;
      font-weight: 700;
      letter-spacing: 0.05em;
      transition: all 0.3s ease;
      padding: 12px 24px;
    }
    
    .btn-primary:hover {
      background-color: #1b4f79;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .alert-custom {
      max-width: 600px;
      margin: 20px auto 30px;
      border-radius: 12px;
      font-weight: 600;
      letter-spacing: 0.02em;
      border: none;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      padding: 20px;
      animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .alert-success-custom {
      background: rgba(40, 167, 69, 0.15);
      color: #155724;
      border-left: 5px solid #28a745;
      backdrop-filter: blur(10px);
    }

    .alert-error-custom {
      background: rgba(220, 53, 69, 0.15);
      color: #721c24;
      border-left: 5px solid #dc3545;
      backdrop-filter: blur(10px);
    }

    .alert-icon {
      font-size: 1.5rem;
      margin-right: 10px;
      vertical-align: middle;
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

    .form-textarea {
      min-height: 120px;
      resize: vertical;
    }

    .char-counter {
      font-size: 0.85rem;
      color: #666;
      text-align: right;
      margin-top: 5px;
    }

    .form-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 25px;
      padding-top: 20px;
      border-top: 1px solid rgba(0, 0, 0, 0.1);
    }

    /* Animación de éxito */
    @keyframes successPulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }

    .success-pulse {
      animation: successPulse 1s ease;
    }

    /* Card para resumen */
    .summary-card {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 20px;
      margin-top: 20px;
      border-left: 4px solid #2D6DA6;
    }

    .summary-title {
      color: #2D6DA6;
      font-weight: 600;
      margin-bottom: 15px;
      font-size: 1.1rem;
    }

    .summary-item {
      margin-bottom: 8px;
      display: flex;
    }

    .summary-label {
      font-weight: 600;
      min-width: 140px;
      color: #333;
    }

    .summary-value {
      color: #000;
      flex: 1;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .glass-container {
        margin: 30px 15px;
        padding: 25px 20px;
      }
      
      .form-footer {
        flex-direction: column;
        gap: 15px;
      }
      
      .summary-item {
        flex-direction: column;
      }
      
      .summary-label {
        min-width: auto;
        margin-bottom: 5px;
      }
    }
  </style>
</head>
<body>

  <?php if (!empty($alertMessage)): ?>
    <div class="alert-custom alert-<?php echo ($alertType == 'success') ? 'success-custom' : 'error-custom'; ?>">
      <i class="bi <?php echo ($alertType == 'success') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> alert-icon"></i>
      <?= $alertMessage ?>
    </div>
  <?php endif; ?>

  <div class="glass-container <?php echo ($formSubmitted && $alertType == 'success') ? 'success-pulse' : ''; ?>">
    <!-- Información del usuario -->
    <div class="user-info">
      <div class="user-name"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user_name); ?></div>
      <div class="user-area"><i class="bi bi-building"></i> <?php echo htmlspecialchars($user_area); ?></div>
    </div>

    <?php if (!$formSubmitted || $alertType == 'error'): ?>
     
      
      <form method="post" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="cancelacionForm">

        <div class="mb-3">
          <label for="inputEmail4" class="form-label">NOMBRE</label>
          <input type="text" class="form-control" id="inputEmail4" name="inputEmail4" value="<?= htmlspecialchars($user_name) ?>" readonly />
        </div>

        <div class="mb-3">
          <label for="inputPassword4" class="form-label">DEPARTAMENTO</label>
          <input type="text" class="form-control" id="inputPassword4" name="inputPassword4" value="<?= htmlspecialchars($user_area) ?>" readonly />
        </div>

        <div class="mb-3">
          <label for="inputAddress" class="form-label required-field">JEFE INMEDIATO QUE AUTORIZÓ</label>
          <input type="text" class="form-control" id="inputAddress" name="inputAddress" placeholder="Nombre del jefe inmediato que autoriza la cancelación" required />
        </div>

        <div class="mb-3">
          <label for="inputState" class="form-label required-field">TIPO DE CONSUMO A CANCELAR</label>
          <select id="inputState" name="inputState" class="form-select" required>
            <option value="" disabled selected>Seleccione una opción</option>
            <option value="DESAYUNO">DESAYUNO</option>
            <option value="COMIDA">COMIDA</option>
            <option value="AMBOS">AMBOS</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="inputZip" class="form-label required-field">FECHA</label>
          <input type="date" class="form-control" id="inputZip" name="inputZip" value="<?= date('Y-m-d') ?>" required />
        </div>

        <div class="mb-3">
          <label for="inputState1" class="form-label required-field">CAUSA PRINCIPAL</label>
          <select id="inputState1" name="inputState1" class="form-select" required>
            <option value="" disabled selected>Seleccione una opción</option>
            <option value="SALUD">SALUD</option>
            <option value="PERSONAL">PERSONAL (causa mayor)</option>
            <option value="VACACIONES">VACACIONES</option>
            <option value="COMISIÓN">COMISIÓN</option>
            <option value="REUNIÓN">REUNIÓN</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="descripcion" class="form-label required-field">DESCRIPCIÓN DEL MOTIVO</label>
          <textarea class="form-control form-textarea" id="descripcion" name="descripcion" placeholder="Describa con detalle el motivo de la cancelación (mínimo 20 caracteres)" required minlength="20"></textarea>
          <div class="char-counter">
            <span id="charCount">0</span> caracteres
          </div>
          <div class="form-text">Proporcione una descripción clara y completa del motivo de cancelación.</div>
        </div>

        <div class="form-footer">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-send-check"></i> ENVIAR CANCELACIÓN
          </button>
        </div>
      </form>
    <?php else: ?>
      <!-- Mensaje de éxito después del envío -->
      <div class="text-center py-4">
        <div class="mb-4">
          <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
        </div>
        <h4 class="text-success mb-3">¡Cancelación Registrada Exitosamente!</h4>
        <p class="mb-4">Tu solicitud de cancelación ha sido guardada en el sistema con el estatus <strong>"EN PROCESO"</strong>.</p>
        
        <!-- Resumen de la cancelación -->
        <div class="summary-card">
          <div class="summary-title">Resumen de la cancelación:</div>
          <div class="summary-item">
            <span class="summary-label">Nombre:</span>
            <span class="summary-value"><?php echo htmlspecialchars($user_name); ?></span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Departamento:</span>
            <span class="summary-value"><?php echo htmlspecialchars($user_area); ?></span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Jefe autorizó:</span>
            <span class="summary-value"><?php echo htmlspecialchars($datosGuardados['jefe'] ?? ''); ?></span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Tipo de consumo:</span>
            <span class="summary-value"><?php echo htmlspecialchars($datosGuardados['consumo'] ?? ''); ?></span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Fecha:</span>
            <span class="summary-value"><?php echo htmlspecialchars($datosGuardados['fecha'] ?? ''); ?></span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Causa principal:</span>
            <span class="summary-value"><?php echo htmlspecialchars($datosGuardados['causa'] ?? ''); ?></span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Descripción:</span>
            <span class="summary-value"><?php echo htmlspecialchars($datosGuardados['descripcion'] ?? ''); ?></span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Fecha registro:</span>
            <span class="summary-value"><?php echo date('d/m/Y H:i:s'); ?></span>
          </div>
        </div>
        
        <!-- Botones removidos según solicitud -->
      </div>
    <?php endif; ?>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Contador de caracteres para la descripción
    document.addEventListener('DOMContentLoaded', function() {
      const descripcionTextarea = document.getElementById('descripcion');
      const charCountElement = document.getElementById('charCount');
      
      if (descripcionTextarea && charCountElement) {
        // Actualizar contador al cargar la página
        charCountElement.textContent = descripcionTextarea.value.length;
        
        // Actualizar contador mientras se escribe
        descripcionTextarea.addEventListener('input', function() {
          charCountElement.textContent = this.value.length;
        });
        
        // Validación en tiempo real para mínimo de caracteres
        descripcionTextarea.addEventListener('blur', function() {
          if (this.value.length > 0 && this.value.length < 20) {
            this.classList.add('is-invalid');
          } else {
            this.classList.remove('is-invalid');
          }
        });
      }
      
      // Validación del formulario antes de enviar
      const form = document.getElementById('cancelacionForm');
      if (form) {
        form.addEventListener('submit', function(event) {
          const descripcion = document.getElementById('descripcion').value;
          const causa = document.getElementById('inputState1').value;
          const tipoConsumo = document.getElementById('inputState').value;
          const jefe = document.getElementById('inputAddress').value;
          
          let errors = [];
          
          if (!jefe.trim()) {
            errors.push("El nombre del jefe es obligatorio");
          }
          
          if (!tipoConsumo) {
            errors.push("Debe seleccionar el tipo de consumo a cancelar");
          }
          
          if (!causa) {
            errors.push("Debe seleccionar la causa principal");
          }
          
          if (!descripcion.trim()) {
            errors.push("La descripción del motivo es obligatoria");
          } else if (descripcion.trim().length < 20) {
            errors.push("La descripción debe tener al menos 20 caracteres");
          }
          
          if (errors.length > 0) {
            event.preventDefault();
            alert("Por favor corrija los siguientes errores:\n\n- " + errors.join("\n- "));
          }
        });
      }
      
      // Mostrar alerta de éxito si existe
      const successAlert = document.querySelector('.alert-success-custom');
      if (successAlert) {
        // Auto-ocultar después de 8 segundos (solo para éxito)
        setTimeout(() => {
          successAlert.style.opacity = '0';
          successAlert.style.transition = 'opacity 1s ease';
          setTimeout(() => {
            if (successAlert.parentNode) {
              successAlert.parentNode.removeChild(successAlert);
            }
          }, 1000);
        }, 8000);
      }
    });
  </script>
</body>
</html>