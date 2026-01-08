<?php
/**
 * EJEMPLO: Página protegida que consume la API
 * Demuestra cómo usar el token JWT en otras páginas
 */

session_start();
require_once 'api_client.php';

// 1. Verificar autenticación
$api = getAPIClient();

if (!$api->isAuthenticated()) {
    header('Location: Admiin.php?error=no_autenticado');
    exit;
}

// 2. Verificar expiración del token
if ($api->isTokenExpired()) {
    session_destroy();
    header('Location: Admiin.php?error=token_expirado');
    exit;
}

// 3. Obtener datos del usuario desde la API
$userResponse = $api->get('api/usuario');

if (!$userResponse['success']) {
    echo "Error al obtener datos: " . $userResponse['error'];
    exit;
}

$usuario = $userResponse['data'];

// 4. Ejemplo: Obtener pedidos del usuario
$pedidosResponse = $api->get('api/pedidos', ['usuario_id' => $_SESSION['user_id']]);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ejemplo API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-utensils me-2"></i>Sistema Comedor
            </a>
            <div class="ms-auto text-white">
                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                <a href="logout.php" class="btn btn-sm btn-light ms-3">
                    <i class="fas fa-sign-out-alt me-1"></i>Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <!-- Token Info -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-key me-2"></i>Estado del Token
                    </div>
                    <div class="card-body">
                        <p><strong>Usuario:</strong> <?php echo htmlspecialchars($usuario['usuario'] ?? $_SESSION['user_username']); ?></p>
                        <p><strong>Área:</strong> <?php echo htmlspecialchars($usuario['area'] ?? $_SESSION['user_area']); ?></p>
                        <p class="mb-0">
                            <strong>Token:</strong> 
                            <span class="badge bg-success">Válido</span>
                        </p>
                        <small class="text-muted d-block mt-2">
                            Expira en: <?php 
                                $tiempoRestante = $_SESSION['token_created_at'] + $_SESSION['token_expires_in'] - time();
                                echo gmdate("H:i:s", $tiempoRestante); 
                            ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Datos de la API -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-database me-2"></i>Datos desde la API
                    </div>
                    <div class="card-body">
                        <h6>Información del Usuario:</h6>
                        <pre class="bg-light p-3 rounded"><?php echo json_encode($usuario, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>

                        <?php if ($pedidosResponse['success']): ?>
                            <h6 class="mt-4">Pedidos:</h6>
                            <pre class="bg-light p-3 rounded"><?php echo json_encode($pedidosResponse['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                        <?php else: ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error al obtener pedidos: <?php echo $pedidosResponse['error']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ejemplo de formulario POST -->
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-paper-plane me-2"></i>Ejemplo: Crear Pedido
            </div>
            <div class="card-body">
                <form method="POST" action="procesar_pedido.php">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Producto</label>
                            <input type="text" name="producto" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cantidad</label>
                            <input type="number" name="cantidad" class="form-control" value="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-plus me-2"></i>Crear
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Información técnica -->
        <div class="alert alert-light border mt-4">
            <h6><i class="fas fa-info-circle me-2"></i>Información Técnica</h6>
            <ul class="mb-0">
                <li><strong>Token almacenado en:</strong> $_SESSION['jwt_token']</li>
                <li><strong>Tipo:</strong> <?php echo $_SESSION['token_type']; ?></li>
                <li><strong>Creado:</strong> <?php echo date('Y-m-d H:i:s', $_SESSION['token_created_at']); ?></li>
                <li><strong>Expira:</strong> <?php echo date('Y-m-d H:i:s', $_SESSION['token_created_at'] + $_SESSION['token_expires_in']); ?></li>
            </ul>
        </div>
    </div>

    <script>
        // Verificar expiración cada minuto
        setInterval(function() {
            const creado = <?php echo $_SESSION['token_created_at']; ?>;
            const expiraEn = <?php echo $_SESSION['token_expires_in']; ?>;
            const ahora = Math.floor(Date.now() / 1000);
            
            if (ahora >= (creado + expiraEn)) {
                alert('Tu sesión ha expirado. Serás redirigido al login.');
                window.location.href = 'Admiin.php?error=token_expirado';
            }
        }, 60000); // Cada minuto
    </script>
</body>
</html>
