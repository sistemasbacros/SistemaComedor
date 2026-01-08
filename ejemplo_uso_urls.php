<?php
/**
 * EJEMPLO DE USO: URLs Dinámicas
 * 
 * Este archivo muestra cómo usar las funciones helper para construir URLs
 * que se adaptan automáticamente al entorno (local, desarrollo, producción)
 */

require_once __DIR__ . '/config_api.php';

echo "=".str_repeat("=", 70)."=\n";
echo "   EJEMPLO DE USO: URLs DINÁMICAS\n";
echo "=".str_repeat("=", 70)."=\n\n";

// ========== INFORMACIÓN DEL ENTORNO ==========
$envInfo = getApiEnvironmentInfo();
echo "📍 ENTORNO ACTUAL: " . strtoupper($envInfo['entorno']) . "\n";
echo "🌐 API URL: " . $envInfo['api_base_url'] . "\n";
echo "🌐 APP URL: " . $envInfo['app_base_url'] . "\n\n";

echo "=".str_repeat("=", 70)."=\n";
echo "   EJEMPLOS DE USO\n";
echo "=".str_repeat("=", 70)."=\n\n";

// ========== 1. URLs DE LA API ==========
echo "1️⃣  URLs DE LA API (para cURL, APIClient, etc.)\n";
echo str_repeat("-", 72) . "\n\n";

echo "   Autenticación:\n";
echo "   - Login:          " . getApiUrl('LOGIN') . "\n";
echo "   - Logout:         " . getApiUrl('LOGOUT') . "\n";
echo "   - Validar Token:  " . getApiUrl('VALIDATE_TOKEN') . "\n\n";

echo "   Usuarios:\n";
echo "   - Listar:         " . getApiUrl('USUARIOS_LIST') . "\n";
echo "   - Crear:          " . getApiUrl('USUARIOS_CREATE') . "\n";
echo "   - Actualizar ID 5:" . getApiUrl('USUARIOS_UPDATE', ['id' => 5]) . "\n";
echo "   - Eliminar ID 10: " . getApiUrl('USUARIOS_DELETE', ['id' => 10]) . "\n\n";

echo "   Pedidos:\n";
echo "   - Listar:         " . getApiUrl('PEDIDOS_LIST') . "\n";
echo "   - Crear:          " . getApiUrl('PEDIDOS_CREATE') . "\n";
echo "   - Cancelar ID 42: " . getApiUrl('PEDIDOS_CANCELAR', ['id' => 42]) . "\n\n";

echo "   Estadísticas:\n";
echo "   - Dashboard:      " . getApiUrl('ESTADISTICAS_DASHBOARD') . "\n";
echo "   - Cancelaciones:  " . getApiUrl('ESTADISTICAS_CANCELACIONES') . "\n\n";

// ========== 2. URLs DE LA APLICACIÓN WEB ==========
echo "2️⃣  URLs DE LA APLICACIÓN WEB (para redirecciones, enlaces)\n";
echo str_repeat("-", 72) . "\n\n";

echo "   Páginas principales:\n";
echo "   - Raíz:           " . getAppUrl() . "\n";
echo "   - Login:          " . getAppUrl('Admiin.php') . "\n";
echo "   - Menú Usuario:   " . getAppUrl('MenUsuario.php') . "\n";
echo "   - Admin:          " . getAppUrl('admicome4.php') . "\n\n";

echo "   Pedidos:\n";
echo "   - Agenda:         " . getAppUrl('AgendaPedidos.php') . "\n";
echo "   - Menú Pedidos:   " . getAppUrl('Menpedidos.php') . "\n\n";

echo "   Compras:\n";
echo "   - Compras:        " . getAppUrl('Compras.php') . "\n";
echo "   - Órdenes:        " . getAppUrl('OrdenComprasCocina.html') . "\n\n";

// ========== 3. EJEMPLOS DE CÓDIGO ==========
echo "3️⃣  EJEMPLOS DE CÓDIGO\n";
echo str_repeat("-", 72) . "\n\n";

echo "   ✅ Redirección PHP:\n";
echo "   ```php\n";
echo "   header(\"Location: \" . getAppUrl('MenUsuario.php'));\n";
echo "   exit;\n";
echo "   ```\n\n";

echo "   ✅ Petición cURL a la API:\n";
echo "   ```php\n";
echo "   \$ch = curl_init(getApiUrl('LOGIN'));\n";
echo "   curl_setopt(\$ch, CURLOPT_POST, true);\n";
echo "   curl_setopt(\$ch, CURLOPT_POSTFIELDS, json_encode(\$data));\n";
echo "   // ...\n";
echo "   ```\n\n";

echo "   ✅ Usar APIClient:\n";
echo "   ```php\n";
echo "   require_once 'api_client.php';\n";
echo "   \$api = new APIClient();\n";
echo "   \$usuarios = \$api->get('/api/empleados');\n";
echo "   ```\n\n";

echo "   ✅ Enlaces en HTML:\n";
echo "   ```html\n";
echo "   <a href=\"<?php echo getAppUrl('MenUsuario.php'); ?>\">Menú Usuario</a>\n";
echo "   ```\n\n";

echo "   ✅ JavaScript (dentro de PHP):\n";
echo "   ```javascript\n";
echo "   window.location.href = '<?php echo getAppUrl(\"admicome4.php\"); ?>';\n";
echo "   ```\n\n";

// ========== 4. CAMBIAR DE ENTORNO ==========
echo "4️⃣  CÓMO CAMBIAR ENTRE ENTORNOS\n";
echo str_repeat("-", 72) . "\n\n";

echo "   El sistema detecta automáticamente el entorno según el dominio:\n\n";

echo "   Local:        localhost, 127.0.0.1, 192.168.x.x\n";
echo "   Desarrollo:   desarollo-bacros, dev, desarrollo\n";
echo "   Producción:   Cualquier otro dominio\n\n";

echo "   Para forzar un entorno manualmente, edita config_api.php:\n";
echo "   ```php\n";
echo "   // Descomentar y establecer:\n";
echo "   \$entorno = 'produccion'; // o 'local', 'desarrollo'\n";
echo "   ```\n\n";

// ========== 5. DEBUGGING ==========
echo "5️⃣  DEBUGGING Y LOGS\n";
echo str_repeat("-", 72) . "\n\n";

if (API_DEBUG) {
    echo "   ✅ DEBUG ACTIVADO - Los logs se escriben en el error_log de PHP\n\n";
    
    echo "   Ejemplo de log:\n";
    apiDebugLog('Ejemplo de mensaje de debug', ['usuario' => 'adrian.ibarra', 'accion' => 'login']);
    echo "   ✅ Log escrito en el archivo de errores de PHP\n\n";
} else {
    echo "   ❌ DEBUG DESACTIVADO - No se generan logs (modo producción)\n\n";
}

echo "   Para activar/desactivar debug, edita config_api.php:\n";
echo "   ```php\n";
echo "   'debug' => true  // o false\n";
echo "   ```\n\n";

// ========== RESUMEN ==========
echo "=".str_repeat("=", 70)."=\n";
echo "   RESUMEN\n";
echo "=".str_repeat("=", 70)."=\n\n";

echo "   ✅ getApiUrl('ENDPOINT')      - Para URLs de la API\n";
echo "   ✅ getAppUrl('archivo.php')   - Para URLs de la aplicación web\n";
echo "   ✅ getApiEnvironmentInfo()    - Para información del entorno\n";
echo "   ✅ apiDebugLog('mensaje')     - Para logs de debug\n\n";

echo "   📝 Ver documentación completa: CONFIGURACION_ENTORNOS.md\n";
echo "   📝 Ver todos los endpoints: config_api.php (línea ~60)\n\n";

echo "=".str_repeat("=", 70)."=\n\n";

?>
