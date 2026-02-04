<?php
/**
 * ============================================================================
 * TEST DE API UNIFICADA - Sistema Comedor
 * ============================================================================
 * Script para probar todas las funcionalidades del cliente API unificado
 * Ejecutar: php tests/test_api_unificada.php
 */

// Cargar cliente API
require_once __DIR__ . '/../api/Api.php';

// Configuración de prueba
$TEST_USUARIO = 'adrian.ibarra';
$TEST_PASSWORD = 'Adriiba1029';

echo "================================================================================\n";
echo "  TEST DE API UNIFICADA - Sistema Comedor\n";
echo "================================================================================\n\n";

// ============================================================================
// 1. INFORMACIÓN DEL SISTEMA
// ============================================================================
echo "[1] INFORMACIÓN DEL SISTEMA\n";
echo str_repeat("-", 80) . "\n";
$info = Api::info();
foreach ($info as $key => $value) {
    echo sprintf("  %-20s: %s\n", ucfirst($key), is_bool($value) ? ($value ? 'SÍ' : 'NO') : $value);
}
echo "\n";

// ============================================================================
// 2. TEST DE AUTENTICACIÓN
// ============================================================================
echo "[2] TEST DE LOGIN\n";
echo str_repeat("-", 80) . "\n";
echo "  Usuario: $TEST_USUARIO\n";
echo "  Intentando login...\n";

$loginResult = Api::auth()->login($TEST_USUARIO, $TEST_PASSWORD);

if ($loginResult['success']) {
    echo "  ✅ LOGIN EXITOSO\n";
    $userData = $loginResult['data'];
    echo "  Token: " . substr($userData['token'] ?? 'N/A', 0, 50) . "...\n";
    echo "  Tipo: " . ($userData['token_type'] ?? 'N/A') . "\n";

    if (isset($userData['user_info'])) {
        echo "  Usuario Info:\n";
        echo "    - ID Empleado: " . ($userData['user_info']['id_empleado'] ?? 'N/A') . "\n";
        echo "    - Nombre: " . ($userData['user_info']['nombre'] ?? 'N/A') . "\n";
        echo "    - Área: " . ($userData['user_info']['area'] ?? 'N/A') . "\n";
        echo "    - Usuario: " . ($userData['user_info']['usuario'] ?? 'N/A') . "\n";
    }
    echo "\n";

    // Verificar autenticación
    echo "[3] VERIFICAR AUTENTICACIÓN\n";
    echo str_repeat("-", 80) . "\n";
    $isAuth = Api::isAuthenticated();
    echo "  Autenticado: " . ($isAuth ? "✅ SÍ" : "❌ NO") . "\n";

    $currentUser = Api::getCurrentUser();
    if ($currentUser) {
        echo "  Usuario actual:\n";
        echo "    - ID: " . $currentUser['id_empleado'] . "\n";
        echo "    - Nombre: " . $currentUser['nombre'] . "\n";
        echo "    - Área: " . $currentUser['area'] . "\n";
    }
    echo "\n";

    // ============================================================================
    // 4. TEST DE PEDIDOS
    // ============================================================================
    echo "[4] TEST DE PEDIDOS\n";
    echo str_repeat("-", 80) . "\n";

    // Perfil
    echo "  [4.1] Obtener perfil de pedidos...\n";
    $perfil = Api::pedidos()->perfil();
    if ($perfil['success']) {
        echo "    ✅ Perfil obtenido\n";
        echo "    Datos: " . json_encode($perfil['data'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "    ❌ Error: " . $perfil['error'] . "\n";
    }
    echo "\n";

    // Verificar pedidos
    echo "  [4.2] Verificar si puede ordenar (fecha: " . date('Y-m-d') . ")...\n";
    $verificar = Api::pedidos()->verificar(date('Y-m-d'));
    if ($verificar['success']) {
        echo "    ✅ Verificación exitosa\n";
        $data = $verificar['data'];
        echo "    Puede ordenar: " . (($data['puede_ordenar'] ?? false) ? 'SÍ' : 'NO') . "\n";
        echo "    Total pedidos: " . ($data['total'] ?? 0) . "\n";
        echo "    Mensaje: " . ($data['mensaje'] ?? 'N/A') . "\n";
    } else {
        echo "    ❌ Error: " . $verificar['error'] . "\n";
    }
    echo "\n";

    // Mis pedidos
    echo "  [4.3] Obtener mis pedidos...\n";
    $misPedidos = Api::pedidos()->misPedidos();
    if ($misPedidos['success']) {
        echo "    ✅ Pedidos obtenidos\n";
        $pedidos = $misPedidos['data'];
        echo "    Total: " . count($pedidos) . " pedidos\n";
        if (!empty($pedidos)) {
            echo "    Últimos 3 pedidos:\n";
            foreach (array_slice($pedidos, 0, 3) as $pedido) {
                echo "      - " . ($pedido['fecha'] ?? 'N/A') . " | " . ($pedido['tipo'] ?? 'N/A') . "\n";
            }
        }
    } else {
        echo "    ❌ Error: " . $misPedidos['error'] . "\n";
    }
    echo "\n";

    // Semanas disponibles
    echo "  [4.4] Obtener semanas disponibles...\n";
    $semanas = Api::pedidos()->semanasDisponibles();
    if ($semanas['success']) {
        echo "    ✅ Semanas obtenidas\n";
        $data = $semanas['data'];
        echo "    Total: " . count($data) . " semanas\n";
        if (!empty($data)) {
            echo "    Primeras 3 semanas:\n";
            foreach (array_slice($data, 0, 3) as $semana) {
                if (is_array($semana)) {
                    echo "      - " . ($semana['fecha_inicio'] ?? 'N/A') . " a " . ($semana['fecha_fin'] ?? 'N/A') . "\n";
                } else {
                    echo "      - $semana\n";
                }
            }
        }
    } else {
        echo "    ❌ Error: " . $semanas['error'] . "\n";
    }
    echo "\n";

    // ============================================================================
    // 5. TEST DE CONSUMOS
    // ============================================================================
    echo "[5] TEST DE CONSUMOS\n";
    echo str_repeat("-", 80) . "\n";

    echo "  [5.1] Obtener mis consumos (semana actual)...\n";
    $consumos = Api::consumos()->misConsumos(date('Y-m-d'));
    if ($consumos['success']) {
        echo "    ✅ Consumos obtenidos\n";
        $data = $consumos['data'];
        if (isset($data['totales'])) {
            echo "    Totales:\n";
            echo "      - Desayunos: " . ($data['totales']['desayunos'] ?? 0) . "\n";
            echo "      - Comidas: " . ($data['totales']['comidas'] ?? 0) . "\n";
            echo "      - Total: " . ($data['totales']['total'] ?? 0) . "\n";
        }
    } else {
        echo "    ⚠️ Error (puede que no exista el endpoint aún): " . $consumos['error'] . "\n";
    }
    echo "\n";

    // ============================================================================
    // 6. TEST DE CANCELACIONES
    // ============================================================================
    echo "[6] TEST DE CANCELACIONES\n";
    echo str_repeat("-", 80) . "\n";

    echo "  [6.1] Obtener validaciones de cancelaciones...\n";
    $validaciones = Api::cancelaciones()->validaciones();
    if ($validaciones['success']) {
        echo "    ✅ Validaciones obtenidas\n";
        $data = $validaciones['data'];
        echo "    Fecha mínima: " . ($data['fecha_minima'] ?? 'N/A') . "\n";
        echo "    Fecha máxima: " . ($data['fecha_maxima'] ?? 'N/A') . "\n";
        if (isset($data['tipos_consumo_permitidos'])) {
            echo "    Tipos permitidos: " . implode(', ', $data['tipos_consumo_permitidos']) . "\n";
        }
        if (isset($data['causas_permitidas'])) {
            echo "    Causas permitidas: " . implode(', ', $data['causas_permitidas']) . "\n";
        }
    } else {
        echo "    ❌ Error: " . $validaciones['error'] . "\n";
    }
    echo "\n";

    echo "  [6.2] Obtener mis cancelaciones...\n";
    $misCancelaciones = Api::cancelaciones()->misCancelaciones();
    if ($misCancelaciones['success']) {
        echo "    ✅ Cancelaciones obtenidas\n";
        $data = $misCancelaciones['data'];
        echo "    Total: " . count($data) . " cancelaciones\n";
    } else {
        echo "    ❌ Error: " . $misCancelaciones['error'] . "\n";
    }
    echo "\n";

    // ============================================================================
    // 7. TEST DE EMPLEADOS
    // ============================================================================
    echo "[7] TEST DE EMPLEADOS\n";
    echo str_repeat("-", 80) . "\n";

    echo "  [7.1] Obtener perfil de empleado...\n";
    $perfilEmpleado = Api::empleados()->perfil();
    if ($perfilEmpleado['success']) {
        echo "    ✅ Perfil obtenido\n";
        $data = $perfilEmpleado['data'];
        echo "    ID: " . ($data['id_empleado'] ?? 'N/A') . "\n";
        echo "    Nombre: " . ($data['nombre'] ?? 'N/A') . "\n";
        echo "    Área: " . ($data['area'] ?? 'N/A') . "\n";
    } else {
        echo "    ❌ Error: " . $perfilEmpleado['error'] . "\n";
    }
    echo "\n";

    // ============================================================================
    // 8. TEST DE VALIDACIÓN DE TOKEN
    // ============================================================================
    echo "[8] VALIDAR TOKEN\n";
    echo str_repeat("-", 80) . "\n";

    echo "  Validando token actual...\n";
    $tokenValidation = Api::auth()->validarToken();
    if ($tokenValidation['success']) {
        echo "    ✅ Token válido\n";
    } else {
        echo "    ❌ Token inválido o expirado\n";
    }
    echo "\n";

    // ============================================================================
    // 9. LOGOUT
    // ============================================================================
    echo "[9] LOGOUT\n";
    echo str_repeat("-", 80) . "\n";
    echo "  Cerrando sesión...\n";
    Api::auth()->logout();
    echo "  ✅ Sesión cerrada\n";
    echo "  Autenticado: " . (Api::isAuthenticated() ? "SÍ" : "NO") . "\n";
    echo "\n";

} else {
    echo "  ❌ LOGIN FALLIDO\n";
    echo "  Error: " . $loginResult['error'] . "\n";
    echo "  HTTP Code: " . ($loginResult['http_code'] ?? 'N/A') . "\n";
    echo "\n";
    echo "  DIAGNÓSTICO:\n";
    echo "  - Verifica que el backend esté corriendo en " . Api::config()->getApiUrl() . "\n";
    echo "  - Verifica las credenciales de prueba\n";
    echo "  - Revisa los logs del backend\n";
}

echo "================================================================================\n";
echo "  TEST COMPLETADO\n";
echo "================================================================================\n";
?>
