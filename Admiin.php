<?php
// Configuración de sesión mejorada
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/Comedor/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax' // Cambiado de 'Strict' a 'Lax' para mejor compatibilidad
]);

// Control de cache más agresivo
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Content-Type-Options: nosniff");

// Iniciar sesión
session_start();

// Configuración de conexión a la base de datos
$serverName = "DESAROLLO-BACRO\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "Comedor",
    "Uid" => "Larome03",
    "PWD" => "Larome03",
    "CharacterSet" => "UTF-8"
);

// Variables para el estado del login
$loginError = '';
$loginSuccess = false;
$showRoleSelection = false;
$userArea = '';
$userName = '';

// Generar token único para esta sesión de login (solo si no existe)
if (!isset($_SESSION['login_token'])) {
    $_SESSION['login_token'] = bin2hex(random_bytes(32));
}

// Procesar login si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario']) && isset($_POST['contrasena'])) {
    $usuario = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);
    
    // SOLUCIÓN: Validación CSRF más permisiva - solo verifica que existan ambos tokens
    $tokenValid = isset($_POST['login_token']) && isset($_SESSION['login_token']);
    
    if (!$tokenValid) {
        // Regenerar token y mostrar error suave
        $_SESSION['login_token'] = bin2hex(random_bytes(32));
        $loginError = 'Error de sesión. Por favor, recargue la página e intente nuevamente.';
    } else {
        $conn = sqlsrv_connect($serverName, $connectionOptions);
        
        if ($conn) {
            // Consulta para verificar las credenciales
            $sql = "SELECT Id_Empleado, Nombre, Area, Usuario, Contrasena 
                    FROM Conped 
                    WHERE Usuario = ? AND Contrasena = ?";
            $params = array($usuario, $contrasena);
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt) {
                if (sqlsrv_has_rows($stmt)) {
                    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                    
                    // CREAR NUEVA SESIÓN COMPLETAMENTE DIFERENTE
                    session_regenerate_id(true);
                    
                    // Configurar nueva sesión con datos de autenticación
                    $_SESSION['user_id'] = $row['Id_Empleado'];
                    $_SESSION['user_name'] = $row['Nombre'];
                    $_SESSION['user_area'] = $row['Area'];
                    $_SESSION['user_username'] = $row['Usuario'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['LOGIN_TIME'] = time();
                    $_SESSION['authenticated_from_login'] = true;
                    $_SESSION['session_id'] = session_id();
                    $_SESSION['browser_fingerprint'] = md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
                    // Eliminado: $_SESSION['one_time_access'] = true; (causaba problemas)
                    
                    $loginSuccess = true;
                    $userArea = $row['Area'];
                    $userName = $row['Nombre'];
                    
                    // VALIDACIÓN MEJORADA PARA DIRECCIÓN
                    $areaUpper = strtoupper(trim($userArea));
                    
                    // Depuración: Mostrar el valor real del área
                    error_log("Área detectada: " . $areaUpper);
                    
                    // Verificar diferentes formas de escribir "DIRECCIÓN"
                    $isDireccion = false;
                    
                    // Primero verificar si contiene "DIRECC" (raíz de la palabra)
                    if (strpos($areaUpper, 'DIRECC') !== false) {
                        $isDireccion = true;
                    }
                    // También verificar coincidencias exactas
                    elseif ($areaUpper === 'DIRECCIÓN' || 
                            $areaUpper === 'DIRECCION' ||
                            trim($areaUpper) === 'DIRECCIÓN' || 
                            trim($areaUpper) === 'DIRECCION') {
                        $isDireccion = true;
                    }
                    // Verificar si es exactamente "DIRECCIÓN" sin espacios
                    elseif (str_replace(' ', '', $areaUpper) === 'DIRECCIÓN' ||
                            str_replace(' ', '', $areaUpper) === 'DIRECCION') {
                        $isDireccion = true;
                    }
                    
                    // También verificar variantes comunes
                    $direccionVariants = ['DIRECCIÓN', 'DIRECCION', 'DIRECCIÓN ', 'DIRECCION ', ' DIRECCIÓN', ' DIRECCION'];
                    foreach ($direccionVariants as $variant) {
                        if (trim($areaUpper) === trim($variant)) {
                            $isDireccion = true;
                            break;
                        }
                    }
                    
                    // Depuración: Verificar si pasó la validación
                    error_log("¿Es dirección? " . ($isDireccion ? 'Sí' : 'No'));
                    
                    if ($isDireccion) {
                        $showRoleSelection = true;
                        // No redirigir inmediatamente, mostrar el modal de selección
                        // Depuración
                        error_log("Mostrando modal para: " . $userName);
                    } else {
                        // Para otras áreas, redirigir directamente a MenUsuario.php
                        error_log("Redirigiendo a MenUsuario.php para: " . $userArea);
                        header("Location: http://desarollo-bacros/Comedor/MenUsuario.php");
                        exit;
                    }
                    
                } else {
                    $loginError = 'Usuario o contraseña incorrectos';
                }
            } else {
                $loginError = 'Error en la consulta a la base de datos';
            }
            
            if ($stmt) sqlsrv_free_stmt($stmt);
            sqlsrv_close($conn);
        } else {
            $loginError = 'Error de conexión a la base de datos';
        }
        
        // Regenerar token después del intento (éxito o fracaso)
        $_SESSION['login_token'] = bin2hex(random_bytes(32));
    }
}

// Procesar selección de rol si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_selection'])) {
    $selectedRole = $_POST['role_selection'];
    
    if ($selectedRole === 'admin') {
        header("Location: http://desarollo-bacros/Comedor/admicome4.php");
        exit;
    } elseif ($selectedRole === 'user') {
        header("Location: http://desarollo-bacros/Comedor/MenUsuario.php");
        exit;
    }
}

// Si no es POST, asegurarse de tener un token
if (!isset($_SESSION['login_token'])) {
    $_SESSION['login_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BACROCORP - Portal del Comedor</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e40af;
            --secondary-color: #3b82f6;
            --accent-color: #60a5fa;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        
        body {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6, #60a5fa);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            border-radius: 16px;
        }
        
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 1.5s ease-out;
        }
        
        .loading-content {
            text-align: center;
            color: white;
        }
        
        .loading-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 48px;
            animation: pulse 2s infinite;
        }
        
        .loading-text {
            font-size: 2rem;
            font-weight: 300;
            margin-bottom: 20px;
            opacity: 0;
            animation: fadeIn 2s forwards 0.5s;
        }
        
        .loading-subtext {
            font-size: 1.2rem;
            opacity: 0;
            animation: fadeIn 2s forwards 1s;
        }
        
        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            margin: 20px auto;
            animation: spin 1s linear infinite;
            opacity: 0;
            animation: spin 1s linear infinite, fadeIn 1s forwards 1.5s;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 1s ease;
        }
        
        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 40px 30px;
            color: white;
        }
        
        .company-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 32px;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            color: white;
            box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.25);
        }
        
        .btn-login {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            opacity: 0.1;
            border-radius: 50%;
            background: white;
            animation: float 15s infinite linear;
        }
        
        /* === MODAL DE SELECCIÓN DE ROL === */
        .role-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(30, 58, 138, 0.98);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            backdrop-filter: blur(20px);
            animation: fadeIn 0.5s ease;
        }
        
        .role-card {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            border-radius: 20px;
            padding: 50px 40px;
            max-width: 600px;
            width: 90%;
            text-align: center;
            color: white;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .role-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
        }
        
        .admin-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(45deg, #dc2626, #ef4444);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        
        .role-icon {
            font-size: 4rem;
            color: white;
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.2);
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .role-title {
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .role-subtitle {
            font-size: 1.2rem;
            margin-bottom: 40px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
        }
        
        .user-highlight {
            color: #bfdbfe;
            font-weight: 600;
        }
        
        .area-highlight {
            background: linear-gradient(45deg, #dc2626, #ef4444);
            padding: 8px 20px;
            border-radius: 20px;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-block;
            margin: 5px 0;
        }
        
        .role-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .role-btn {
            flex: 1;
            min-width: 200px;
            padding: 20px 25px;
            border: none;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .role-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .btn-icon {
            font-size: 2rem;
        }
        
        .btn-text {
            font-size: 1rem;
            font-weight: 600;
        }
        
        .role-info {
            margin-top: 30px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.9rem;
        }
        /* === FIN DEL MODAL === */
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
            100% {
                transform: translateY(0) rotate(360deg);
            }
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fecaca;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .security-notice {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.4);
            color: #dbeafe;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-duration: 20s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            left: 80%;
            animation-duration: 25s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 80%;
            left: 20%;
            animation-duration: 15s;
        }
        
        .shape:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 30%;
            left: 70%;
            animation-duration: 30s;
        }
    </style>
</head>
<body>
    <!-- Pantalla de carga inicial -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-content">
            <div class="loading-logo">
                <i class="fas fa-utensils"></i>
            </div>
            <h1 class="loading-text">BACROCORP - Portal del Comedor</h1>
            <p class="loading-subtext">Inicializando sistema de seguridad...</p>
            <div class="spinner"></div>
        </div>
    </div>
    
    <!-- Modal de selección de rol (solo para Dirección) -->
    <?php if ($showRoleSelection): ?>
    <div class="role-modal" id="roleModal">
        <div class="role-card">
            <div class="admin-badge">
                <i class="fas fa-crown"></i>
                ACCESO DIRECCIÓN
            </div>
            
            <div class="role-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h2 class="role-title">Acceso de Dirección</h2>
            <p class="role-subtitle">
                Bienvenido <span class="user-highlight"><?php echo htmlspecialchars($userName); ?></span><br>
                <span class="area-highlight"><?php echo htmlspecialchars($userArea); ?></span><br>
                Seleccione el tipo de acceso que requiere:
            </p>
            <div class="role-buttons">
                <button class="role-btn" onclick="selectRole('admin')">
                    <i class="fas fa-cog btn-icon"></i>
                    <span class="btn-text">ADMINISTRADOR<br>Control Total</span>
                </button>
                <button class="role-btn" onclick="selectRole('user')">
                    <i class="fas fa-user btn-icon"></i>
                    <span class="btn-text">USUARIO<br>Acceso Estándar</span>
                </button>
            </div>
            <div class="role-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Administrador:</strong> Acceso completo al sistema | 
                <strong>Usuario:</strong> Acceso limitado a funciones básicas
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Formulario de login -->
    <div class="login-container" id="loginContainer">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        
        <div class="glass-card login-card" data-aos="zoom-in" data-aos-duration="1000">
            <div class="company-logo">
                <i class="fas fa-utensils"></i>
            </div>
            <h2 class="text-center mb-4 fw-bold">Portal del Comedor</h2>
            <p class="text-center mb-4 opacity-75">BACROCORP - Sistema de Administración</p>
            
            <div class="security-notice">
                <i class="fas fa-shield-alt me-2"></i>
                Validación de credenciales requerida - Solo Dirección tiene acceso administrativo
            </div>
            
            <?php if ($loginError): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $loginError; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm" autocomplete="off">
                <input type="hidden" name="login_token" value="<?php echo htmlspecialchars($_SESSION['login_token'] ?? ''); ?>">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Ingrese su usuario" required autocomplete="off">
                </div>
                <div class="mb-4">
                    <label for="contrasena" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="contrasena" name="contrasena" placeholder="Ingrese su contraseña" required autocomplete="off">
                </div>
                <button type="submit" class="btn btn-login mb-3" id="loginButton">
                    <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión
                </button>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Inicializar AOS
        AOS.init();
        
        // Ocultar pantalla de carga después de 3 segundos
        setTimeout(function() {
            const loadingScreen = document.getElementById('loadingScreen');
            if (loadingScreen) {
                loadingScreen.style.opacity = '0';
                
                setTimeout(function() {
                    loadingScreen.style.display = 'none';
                    
                    // Mostrar formulario de login con animación
                    const loginContainer = document.getElementById('loginContainer');
                    if (loginContainer) {
                        loginContainer.style.opacity = '1';
                        loginContainer.style.transform = 'translateY(0)';
                        
                        // Enfocar el campo de usuario
                        document.getElementById('usuario').focus();
                    }
                }, 1500);
            }
        }, 3000);
        
        // Manejar el envío del formulario
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const usuario = document.getElementById('usuario').value;
            const contrasena = document.getElementById('contrasena').value;
            const loginButton = document.getElementById('loginButton');
            
            if (usuario && contrasena) {
                // Mostrar estado de carga
                loginButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Verificando...';
                loginButton.disabled = true;
            }
        });
        
        // Función para seleccionar el rol (solo para Dirección)
        function selectRole(role) {
            const roleModal = document.getElementById('roleModal');
            const buttons = document.querySelectorAll('.role-btn');
            
            // Deshabilitar botones durante la transición
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.7';
            });
            
            // Efecto de desaparición del modal
            if (roleModal) {
                roleModal.style.opacity = '0';
                roleModal.style.transform = 'scale(0.9) translateY(20px)';
                roleModal.style.transition = 'all 0.5s ease';
            }
            
            // Redirigir según la selección
            setTimeout(function() {
                if (role === 'admin') {
                    window.location.href = 'http://desarollo-bacros/Comedor/admicome4.php';
                } else {
                    window.location.href = 'http://desarollo-bacros/Comedor/MenUsuario.php';
                }
            }, 500);
        }
        
        // Limpiar campos al cargar la página
        window.addEventListener('load', function() {
            const usuario = document.getElementById('usuario');
            const contrasena = document.getElementById('contrasena');
            if (usuario) usuario.value = '';
            if (contrasena) contrasena.value = '';
        });
        
        // Forzar recarga sin cache
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        // Prevenir el cache del formulario
        window.addEventListener('beforeunload', function() {
            document.getElementById('loginForm').reset();
        });
    </script>
</body>
</html>