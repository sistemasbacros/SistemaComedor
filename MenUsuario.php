<?php
// ==================================================
// PROTECCIÓN DE SEGURIDAD MEJORADA - NO ELIMINAR
// ==================================================

// Configuración de sesión
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',  // Cambiado a raíz para compatibilidad con Docker
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'  // Cambiado de Strict a Lax para mejor compatibilidad
]);

// Configuración de seguridad
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();

// DEBUG: Ver estado de sesión
error_log("=== DEBUG MenUsuario.php ===");
error_log("Session ID actual: " . session_id());
error_log("Session ID guardado: " . ($_SESSION['session_id'] ?? 'NO EXISTE'));
error_log("authenticated_from_login: " . (isset($_SESSION['authenticated_from_login']) ? ($_SESSION['authenticated_from_login'] ? 'true' : 'false') : 'NO EXISTE'));
error_log("browser_fingerprint guardado: " . ($_SESSION['browser_fingerprint'] ?? 'NO EXISTE'));
error_log("browser_fingerprint actual: " . md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']));
error_log("user_name: " . ($_SESSION['user_name'] ?? 'NO EXISTE'));

// Verificación estricta de autenticación - CORREGIDO
$isAuthenticated = (
    isset($_SESSION['authenticated_from_login']) && 
    $_SESSION['authenticated_from_login'] === true &&
    isset($_SESSION['session_id']) && 
    $_SESSION['session_id'] === session_id() &&
    isset($_SESSION['browser_fingerprint']) && 
    $_SESSION['browser_fingerprint'] === md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'])
);

error_log("isAuthenticated: " . ($isAuthenticated ? 'SI' : 'NO'));

// Permitir acceso durante el mismo request después de procesar POST
if (!$isAuthenticated) {
    error_log("FALLÓ AUTENTICACIÓN - Redirigiendo a Admiin.php");
    // Destruir completamente la sesión
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time()-3600, '/');
    
    // Redirigir al login
    header("Location: Admiin.php");
    exit;
}

// Verificar expiración de sesión (30 minutos)
$sessionTimeout = 30 * 60; // 30 minutos
if (isset($_SESSION['LOGIN_TIME']) && (time() - $_SESSION['LOGIN_TIME'] > $sessionTimeout)) {
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time()-3600, '/');
    header("Location: Admiin.php");
    exit;
}

// SOLO INVALIDAR EL ACCESO SI NO ES UNA PETICIÓN POST
// Esto permite que los formularios funcionen correctamente
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['one_time_access']);
}

// Actualizar tiempo de actividad
$_SESSION['LAST_ACTIVITY'] = time();

// Obtener información del usuario desde la sesión
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_area = $_SESSION['user_area'] ?? 'Sistema de Comedor';

// ==================================================
// CONFIGURACIÓN AUTOMÁTICA DE BLOQUEO 
// JUEVES 13:00 hasta DOMINGO 23:59
// ==================================================

// Obtener día de la semana (1 = lunes, 7 = domingo)
$dia_semana = date('N');
$hora_actual = date('H:i');

// Bloquear desde jueves 13:00 hasta domingo 23:59
if (($dia_semana == 3 && $hora_actual >= '16:30') || // Jueves desde 13:00
    $dia_semana == 4 || // Viernes completo
    $dia_semana == 5 || // Viernes completo
    $dia_semana == 6 || // Sábado completo
    ($dia_semana == 7 && $hora_actual <= '23:59')) { // Domingo hasta 23:59
    // Periodo bloqueado
    $PEDIDOS_BLOQUEADOS = true;
    $mensaje_bloqueo = "El registro de pedidos para esta semana ha finalizado. Vuelve el próximo lunes.";
} else {
    // Periodo disponible (lunes a jueves antes de 13:00)
    $PEDIDOS_BLOQUEADOS = false;
    $mensaje_bloqueo = "";
}

// ==================================================
// FIN DE PROTECCIÓN - TU CÓDIGO ORIGINAL COMIENZA AQUÍ
// ==================================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Comedor - Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #1a365d;
            --primary-blue: #2d5f9d;
            --accent-blue: #3b82f6;
            --secondary-blue: #60a5fa;
            --light-blue: #dbeafe;
            --white-pearl: #f8fafc;
            --light-gray: #e2e8f0;
            --medium-gray: #94a3b8;
            --dark-gray: #475569;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --purple-color: #8b5cf6;
            --teal-color: #14b8a6;
            --pink-color: #ec4899;
            --indigo-color: #6366f1;
            --card-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            --glass-bg: rgba(255, 255, 255, 0.8);
            --glass-border: rgba(255, 255, 255, 0.5);
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-dark) 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            color: var(--dark-gray);
        }
        
        .glass-effect {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--card-shadow);
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue));
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            min-height: 100vh;
            color: white;
            position: fixed;
            width: 280px;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.15);
            left: 0;
            top: 0;
        }
        
        .sidebar.hidden {
            transform: translateX(-100%);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 14px 20px;
            margin: 6px 0;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(8px);
        }
        
        .sidebar .nav-link.disabled {
            color: rgba(255, 255, 255, 0.5);
            cursor: not-allowed;
        }
        
        .sidebar .nav-link.disabled:hover {
            background: rgba(255, 0, 0, 0.1);
            transform: none;
        }
        
        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: linear-gradient(135deg, var(--accent-blue), var(--secondary-blue));
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .sidebar .nav-link.active::before {
            transform: scaleY(1);
        }
        
        .sidebar .nav-link.disabled::before {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
        }
        
        .sidebar .nav-link i {
            margin-right: 14px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        .lock-badge {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--warning-color);
            font-size: 14px;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 25px;
            transition: all 0.3s ease;
            background-color: var(--light-blue);
            min-height: 100vh;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            background: var(--white-pearl);
        }
        
        .card:hover {
            transform: translateY(-8px);
            box-shadow: var(--hover-shadow);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: white;
            border-radius: 16px 16px 0 0 !important;
            font-weight: 600;
            padding: 18px 25px;
            position: relative;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .card-header.blocked {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
        }
        
        .card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, rgba(255,255,255,0.5), transparent);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-blue), var(--primary-blue));
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 15px;
        }
        
        .user-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-blue), var(--secondary-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 18px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .user-info h5 {
            margin: 0;
            font-size: 1.1rem;
            color: white;
        }
        
        .user-info p {
            margin: 0;
            font-size: 0.85rem;
            opacity: 0.8;
            color: white;
        }
        
        .main-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
            color: white;
            padding: 20px 25px;
            margin: -25px -25px 25px -25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 100;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .user-display {
            display: flex;
            align-items: center;
            gap: 20px;
            background: rgba(255, 255, 255, 0.15);
            padding: 12px 20px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        
        .user-info-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-name-display {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .user-area-display {
            font-size: 0.95rem;
            opacity: 0.9;
        }
        
        .toggle-sidebar-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 10px;
            width: 45px;
            height: 45px;
            font-size: 1.3rem;
            color: white;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .toggle-sidebar-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.6rem;
            color: var(--primary-dark);
            position: fixed;
            top: 25px;
            left: 25px;
            z-index: 1001;
            background: var(--white-pearl);
            border-radius: 10px;
            width: 50px;
            height: 50px;
            box-shadow: var(--card-shadow);
        }
        
        .report-iframe-container {
            position: relative;
            width: 100%;
            height: 800px;
            border: none;
            overflow: hidden;
        }
        
        .report-iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 0 0 16px 16px;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 100;
            border-radius: 0 0 16px 16px;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--light-blue);
            border-top: 5px solid var(--accent-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            color: var(--primary-dark);
            font-weight: 500;
            font-size: 1.1rem;
        }
        
        .section-title {
            color: var(--primary-dark);
            margin-bottom: 30px;
            padding-bottom: 18px;
            border-bottom: 3px solid var(--accent-blue);
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 120px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-blue), transparent);
        }
        
        .blocked-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 100;
            border-radius: 0 0 16px 16px;
            text-align: center;
            padding: 40px;
        }
        
        .blocked-icon {
            font-size: 4rem;
            color: var(--danger-color);
            margin-bottom: 20px;
        }
        
        .blocked-message {
            font-size: 1.2rem;
            color: var(--dark-gray);
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .blocked-details {
            color: var(--medium-gray);
            font-size: 1rem;
        }
        
        .section {
            display: none;
        }
        
        .section.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .error-message {
            background: var(--white-pearl);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            box-shadow: var(--card-shadow);
            border-left: 6px solid var(--danger-color);
        }
        
        .error-icon {
            font-size: 4rem;
            color: var(--danger-color);
            margin-bottom: 20px;
        }
        
        .error-title {
            color: var(--danger-color);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .error-description {
            color: var(--dark-gray);
            margin-bottom: 25px;
            font-size: 1.1rem;
        }
        
        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .iframe-status {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            z-index: 10;
        }
        
        .qr-fallback {
            padding: 40px;
            text-align: center;
        }
        
        .qr-fallback-icon {
            font-size: 4rem;
            color: var(--info-color);
            margin-bottom: 20px;
        }
        
        .qr-fallback-title {
            color: var(--primary-dark);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .qr-fallback-description {
            color: var(--dark-gray);
            margin-bottom: 25px;
            font-size: 1.1rem;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 250px;
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding-top: 80px;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .toggle-sidebar-btn {
                display: none;
            }
            
            .report-iframe-container {
                height: 600px;
            }
            
            .main-header {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .header-left {
                width: 100%;
                justify-content: space-between;
            }
            
            .header-title {
                font-size: 1.5rem;
            }
            
            .user-display {
                flex-direction: column;
                gap: 5px;
                text-align: center;
                width: 100%;
                margin-top: 10px;
            }
        }
        
        @media (max-width: 768px) {
            .report-iframe-container {
                height: 500px;
            }
        }
        
        @media (max-width: 576px) {
            .report-iframe-container {
                height: 400px;
            }
            
            .header-left {
                flex-direction: column;
                gap: 10px;
            }
            
            .header-title {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .error-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="user-profile">
            <div class="user-avatar">
                <span>US</span>
            </div>
            <div class="user-info">
                <h5><?php echo htmlspecialchars($user_name); ?></h5>
                <p><?php echo htmlspecialchars($user_area); ?></p>
            </div>
        </div>
        
        <ul class="nav flex-column px-3">
            <!-- MÓDULO DE MENÚ ELIMINADO -->
            <li class="nav-item">
                <a class="nav-link <?php echo $PEDIDOS_BLOQUEADOS ? 'disabled' : ''; ?>" href="#" data-section="pedidos" id="pedidos-link">
                    <i class="fas fa-clipboard-list"></i> Pedidos
                    <?php if ($PEDIDOS_BLOQUEADOS): ?>
                        <span class="lock-badge">
                            <i class="fas fa-lock"></i>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-section="consulta">
                    <i class="fas fa-search"></i> Consulta tus registros por semana
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-section="reporte">
                    <i class="fas fa-chart-bar"></i> Mi Reporte Personal de Consumos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-section="qr">
                    <i class="fas fa-qrcode"></i> Generar QR
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link text-danger" href="admicome4.php?logout=true" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </div>

    <!-- Botón de menú móvil -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header Mejorado con Botón Integrado -->
        <div class="main-header">
            <div class="header-left">
                <button class="toggle-sidebar-btn" id="toggleSidebar" title="Mostrar/Ocultar Menú">
                    <i class="fas fa-bars"></i>
                </button>
                
                <h1 class="header-title">Portal de Comedor - Usuario</h1>
            </div>
            
            <div class="user-display">
                <div class="user-info-header">
                    <div class="user-name-display"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-area-display"><?php echo htmlspecialchars($user_area); ?></div>
                </div>
            </div>
        </div>

        <!-- Sección de Pedidos (ahora será la primera que se muestre) -->
        <div id="pedidos" class="section active">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center <?php echo $PEDIDOS_BLOQUEADOS ? 'blocked' : ''; ?>">
                    <div>
                        <i class="fas fa-clipboard-list me-2"></i>Sistema de Pedidos
                        <?php if ($PEDIDOS_BLOQUEADOS): ?>
                            <small class="ms-2"><i class="fas fa-lock"></i> BLOQUEADO</small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-primary" id="refresh-pedidos-btn" <?php echo $PEDIDOS_BLOQUEADOS ? 'disabled' : ''; ?>>
                            <i class="fas fa-sync-alt me-1"></i>Actualizar
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 position-relative">
                    <!-- Indicador de carga para Pedidos -->
                    <div id="pedidos-loading" class="loading-overlay">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">Cargando sistema de pedidos...</div>
                    </div>
                    
                    <?php if ($PEDIDOS_BLOQUEADOS): ?>
                        <!-- Overlay de bloqueo -->
                        <div class="blocked-overlay">
                            <div class="blocked-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div class="blocked-message">
                                El registro de pedidos no está disponible
                            </div>
                            <div class="blocked-details">
                                <?php echo $mensaje_bloqueo; ?>
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Los pedidos estarán disponibles nuevamente el próximo lunes.
                                </small>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Contenedor del iframe (solo si no está bloqueado) -->
                        <div class="report-iframe-container">
                            <iframe src="Menpedidos1.php" 
                                    class="report-iframe" 
                                    id="pedidos-iframe"
                                    onload="hideLoading('pedidos-loading')">
                            </iframe>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Consulta de Registros Section -->
        <div id="consulta" class="section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-search me-2"></i>Sistema de Consulta de Registros
                    </div>
                    <div>
                        <button class="btn btn-sm btn-primary" id="refresh-consulta-btn">
                            <i class="fas fa-sync-alt me-1"></i>Actualizar
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 position-relative">
                    <!-- Indicador de carga para Consulta -->
                    <div id="consulta-loading" class="loading-overlay">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">Cargando sistema de consulta...</div>
                    </div>
                    
                    <!-- Contenedor del iframe -->
                    <div class="report-iframe-container">
                        <iframe src="AgendaPedidos1.php" 
                                class="report-iframe" 
                                id="consulta-iframe"
                                onload="hideLoading('consulta-loading')">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reporte Personal de Consumos Section -->
        <div id="reporte" class="section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-chart-bar me-2"></i>Sistema de Reportes de Consumo
                    </div>
                    <div>
                        <button class="btn btn-sm btn-primary" id="refresh-reporte-btn">
                            <i class="fas fa-sync-alt me-1"></i>Actualizar
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 position-relative">
                    <!-- Indicador de carga para Reporte -->
                    <div id="reporte-loading" class="loading-overlay">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">Cargando sistema de reportes...</div>
                    </div>
                    
                    <!-- Contenedor del iframe -->
                    <div class="report-iframe-container">
                        <iframe src="descUsuario.php" 
                                class="report-iframe" 
                                id="reporte-iframe"
                                onload="hideLoading('reporte-loading')">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generar QR Section -->
        <div id="qr" class="section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-qrcode me-2"></i>Generador de Códigos QR
                    </div>
                    <div>
                        <button class="btn btn-sm btn-primary" id="refresh-qr-btn">
                            <i class="fas fa-sync-alt me-1"></i>Actualizar
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 position-relative">
                    <!-- Indicador de carga para QR -->
                    <div id="qr-loading" class="loading-overlay">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">Cargando generador de QR...</div>
                    </div>
                    
                    <!-- Contenedor del iframe con fallback mejorado -->
                    <div class="report-iframe-container">
                        <iframe src="GenerarQR1.php" 
                                class="report-iframe" 
                                id="qr-iframe"
                                onload="hideLoading('qr-loading')">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Estado del sidebar
        let sidebarVisible = true;
        
        // Funciones para manejo de carga
        function hideLoading(loadingId) {
            const loadingElement = document.getElementById(loadingId);
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
        }
        
        function showError(loadingId, message) {
            const loadingElement = document.getElementById(loadingId);
            if (loadingElement) {
                loadingElement.innerHTML = `
                    <div class="error-message">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 class="error-title">Error de Carga</h3>
                        <p class="error-description">${message}</p>
                        <div class="error-actions">
                            <button class="btn btn-primary" onclick="retryLoad('${loadingId}')">
                                <i class="fas fa-redo me-2"></i>Reintentar
                            </button>
                            <button class="btn btn-outline-secondary" onclick="goToPedidos()">
                                <i class="fas fa-clipboard-list me-2"></i>Volver a Pedidos
                            </button>
                        </div>
                    </div>
                `;
            }
        }
        
        function retryLoad(loadingId) {
            const sectionId = loadingId.replace('-loading', '');
            const iframe = document.getElementById(sectionId + '-iframe');
            const loading = document.getElementById(loadingId);
            
            if (iframe && loading) {
                // Restaurar loading spinner
                loading.innerHTML = `
                    <div class="loading-spinner"></div>
                    <div class="loading-text">Cargando...</div>
                `;
                loading.style.display = 'flex';
                
                // Recargar iframe
                iframe.src = iframe.src;
            }
        }
        
        function goToPedidos() {
            // Navegar a la sección de pedidos
            document.querySelectorAll('.nav-link').forEach(function(l) {
                l.classList.remove('active');
            });
            document.querySelectorAll('.section').forEach(function(s) {
                s.classList.remove('active');
            });
            
            document.querySelector('[data-section="pedidos"]').classList.add('active');
            document.getElementById('pedidos').classList.add('active');
        }
        
        // Navigation functionality
        function initializeNavigation() {
            document.querySelectorAll('.nav-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Si el enlace está deshabilitado, mostrar alerta y no hacer nada
                    if (this.classList.contains('disabled')) {
                        alert('⚠️ <?php echo $mensaje_bloqueo; ?>');
                        return;
                    }
                    
                    // Remove active class from all links
                    document.querySelectorAll('.nav-link').forEach(function(l) {
                        l.classList.remove('active');
                    });
                    
                    // Remove active class from all sections
                    document.querySelectorAll('.section').forEach(function(s) {
                        s.classList.remove('active');
                    });
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Show corresponding section
                    const sectionId = this.getAttribute('data-section');
                    const section = document.getElementById(sectionId);
                    
                    if (section) {
                        section.classList.add('active');
                    }
                    
                    // Mostrar indicador de carga para cada sección
                    const loadingElement = document.getElementById(sectionId + '-loading');
                    if (loadingElement) {
                        loadingElement.style.display = 'flex';
                        
                        // Ocultar loading después de 3 segundos máximo (más rápido)
                        setTimeout(function() {
                            if (loadingElement.style.display === 'flex') {
                                hideLoading(sectionId + '-loading');
                            }
                        }, 3000);
                    }
                });
            });
        }

        // Toggle sidebar functionality
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebarVisible = !sidebarVisible;
            
            if (sidebarVisible) {
                sidebar.classList.remove('hidden');
                mainContent.classList.remove('expanded');
                this.innerHTML = '<i class="fas fa-bars"></i>';
            } else {
                sidebar.classList.add('hidden');
                mainContent.classList.add('expanded');
                this.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });

        // Mobile menu functionality
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        });

        // Botones de actualización
        document.getElementById('refresh-pedidos-btn').addEventListener('click', function() {
            <?php if (!$PEDIDOS_BLOQUEADOS): ?>
                const iframe = document.getElementById('pedidos-iframe');
                const loading = document.getElementById('pedidos-loading');
                
                if (loading && iframe) {
                    loading.style.display = 'flex';
                    iframe.src = iframe.src;
                }
            <?php endif; ?>
        });

        document.getElementById('refresh-consulta-btn').addEventListener('click', function() {
            const iframe = document.getElementById('consulta-iframe');
            const loading = document.getElementById('consulta-loading');
            
            if (loading && iframe) {
                loading.style.display = 'flex';
                iframe.src = iframe.src;
            }
        });

        document.getElementById('refresh-reporte-btn').addEventListener('click', function() {
            const iframe = document.getElementById('reporte-iframe');
            const loading = document.getElementById('reporte-loading');
            
            if (loading && iframe) {
                loading.style.display = 'flex';
                iframe.src = iframe.src;
            }
        });

        document.getElementById('refresh-qr-btn').addEventListener('click', function() {
            const iframe = document.getElementById('qr-iframe');
            const loading = document.getElementById('qr-loading');
            
            if (loading && iframe) {
                loading.style.display = 'flex';
                iframe.src = iframe.src.split('?')[0] + '?t=' + new Date().getTime();
            }
        });
        
        // Logout confirmation
        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('¿Está seguro que desea cerrar sesión?')) {
                window.location.href = this.href;
            }
        });

        // Simular carga inicial (más rápido)
        window.addEventListener('load', function() {
            setTimeout(function() {
                const pedidosLoading = document.getElementById('pedidos-loading');
                if (pedidosLoading && pedidosLoading.style.display === 'flex') {
                    hideLoading('pedidos-loading');
                }
            }, 500);
        });

        // Prevenir acceso desde cache
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        // Inicialización completa
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar navegación
            initializeNavigation();
            
            // Asegurarse de que Pedidos esté activo al cargar
            const pedidosSection = document.getElementById('pedidos');
            const pedidosLink = document.querySelector('[data-section="pedidos"]');
            
            if (pedidosSection && pedidosLink) {
                pedidosSection.classList.add('active');
                pedidosLink.classList.add('active');
            }
        });
    </script>
</body>
</html>