<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pruebas de Endpoints API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .test-container { max-width: 1200px; margin: 2rem auto; }
        .endpoint-card { background: white; border-radius: 10px; padding: 1.5rem; margin-bottom: 1rem; }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .response-box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 1rem; max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container test-container">
        <h1 class="text-center text-white mb-4">🔧 Pruebas de Endpoints API</h1>
        
        <?php
        session_start();
        require_once __DIR__ . '/token_manager.php';
        require_once __DIR__ . '/endpoint_helpers.php';
        
        // Verificar autenticación
        if (!isUserAuthenticated()) {
            echo '<div class="alert alert-warning text-center">';
            echo '<h4>⚠️ No estás autenticado</h4>';
            echo '<p>Necesitas hacer login primero.</p>';
            echo '<a href="Admiin.php" class="btn btn-primary">Ir al Login</a>';
            echo '</div>';
            exit;
        }
        
        $current_user = getCurrentUser();
        
        // Ejecutar pruebas si se solicita
        $run_tests = isset($_GET['run']) && $_GET['run'] === 'true';
        $test_results = [];
        
        if ($run_tests) {
            $test_results = verificarEndpoints();
        }
        ?>
        
        <!-- Información del usuario -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-user"></i> Usuario Autenticado</h5>
            </div>
            <div class="card-body">
                <p><strong>Nombre:</strong> <?= htmlspecialchars($current_user['nombre'] ?? 'N/A') ?></p>
                <p><strong>ID:</strong> <?= htmlspecialchars($current_user['id_empleado'] ?? 'N/A') ?></p>
                <p><strong>Área:</strong> <?= htmlspecialchars($current_user['area'] ?? 'N/A') ?></p>
                <p><strong>Token válido:</strong> <?= getValidToken() ? '✅ Sí' : '❌ No' ?></p>
            </div>
        </div>
        
        <!-- Controles -->
        <div class="text-center mb-4">
            <?php if (!$run_tests): ?>
                <a href="?run=true" class="btn btn-success btn-lg">
                    <i class="fas fa-play"></i> Ejecutar Pruebas
                </a>
            <?php else: ?>
                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">
                    <i class="fas fa-refresh"></i> Limpiar Resultados
                </a>
            <?php endif; ?>
            <a href="Demo_SistemaComedor.html" class="btn btn-primary">
                <i class="fas fa-home"></i> Menú Principal
            </a>
        </div>
        
        <?php if ($run_tests): ?>
        <!-- Resultados de las pruebas -->
        <div class="row">
            <?php foreach ($test_results as $nombre => $resultado): ?>
            <div class="col-md-6 mb-3">
                <div class="endpoint-card">
                    <h6 class="<?= $resultado['status'] === 'OK' ? 'status-ok' : 'status-error' ?>">
                        <?= $resultado['status'] === 'OK' ? '✅' : '❌' ?> <?= htmlspecialchars($nombre) ?>
                    </h6>
                    <p class="mb-1">
                        <strong>Endpoint:</strong> <?= htmlspecialchars($resultado['endpoint']) ?>
                    </p>
                    <p class="mb-1">
                        <strong>Método:</strong> <?= htmlspecialchars($resultado['method']) ?>
                    </p>
                    <p class="mb-1">
                        <strong>Estado:</strong> 
                        <span class="<?= $resultado['status'] === 'OK' ? 'status-ok' : 'status-error' ?>">
                            <?= htmlspecialchars($resultado['status']) ?>
                        </span>
                    </p>
                    <?php if (isset($resultado['error']) && $resultado['error']): ?>
                    <div class="mt-2">
                        <strong>Error:</strong>
                        <div class="response-box">
                            <code><?= htmlspecialchars($resultado['error']) ?></code>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Resumen -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar"></i> Resumen de Pruebas</h5>
            </div>
            <div class="card-body">
                <?php
                $total = count($test_results);
                $exitosos = array_count_values(array_column($test_results, 'status'))['OK'] ?? 0;
                $fallidos = $total - $exitosos;
                ?>
                <p><strong>Total de endpoints probados:</strong> <?= $total ?></p>
                <p class="status-ok"><strong>Exitosos:</strong> <?= $exitosos ?></p>
                <p class="status-error"><strong>Fallidos:</strong> <?= $fallidos ?></p>
                
                <?php if ($fallidos === 0): ?>
                <div class="alert alert-success">
                    🎉 ¡Todos los endpoints funcionan correctamente!
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    ⚠️ Algunos endpoints necesitan atención.
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- Información sobre los endpoints -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Endpoints Disponibles</h5>
            </div>
            <div class="card-body">
                <p>Esta herramienta probará los siguientes endpoints:</p>
                <ul>
                    <li><code>GET /api/empleados/perfil</code> - Perfil del empleado</li>
                    <li><code>GET /api/cancelaciones/validaciones</code> - Validaciones de cancelaciones</li>
                    <li><code>GET /api/pedidos/mis-pedidos</code> - Mis pedidos</li>
                    <li><code>GET /api/pedidos/semanas-disponibles</code> - Semanas disponibles</li>
                    <li><code>GET /api/pedidos/perfil</code> - Perfil para pedidos</li>
                    <li><code>GET /api/cancelaciones/mis-cancelaciones</code> - Mis cancelaciones</li>
                </ul>
                <p class="text-muted">Haz clic en "Ejecutar Pruebas" para probar todos los endpoints.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>