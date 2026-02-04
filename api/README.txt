================================================================================
  CLIENTE API UNIFICADO - SISTEMA COMEDOR BACROCORP
================================================================================

DESCRIPCIÓN
-----------
Este directorio contiene el cliente unificado para consumir la API REST del
backend del sistema de comedor. Reemplaza y consolida todos los archivos
antiguos de configuración y clientes HTTP.

ARCHIVO PRINCIPAL
-----------------
  Api.php - Cliente unificado con todas las funcionalidades

ESTRUCTURA
----------
  /api/          - Cliente API principal (este directorio)
  /deprecated/   - Archivos obsoletos (no usar)
  /tests/        - Scripts de prueba
  /examples/     - Ejemplos de implementación

================================================================================
  USO BÁSICO
================================================================================

1. INCLUIR EL CLIENTE
---------------------
require_once __DIR__ . '/api/Api.php';

2. AUTENTICACIÓN
----------------
// Login
$result = Api::auth()->login('usuario', 'password');

// Verificar autenticación
if (!Api::isAuthenticated()) {
    header("Location: Admiin.php");
    exit;
}

// Obtener usuario actual
$user = Api::getCurrentUser();
// Retorna: ['id_empleado' => ..., 'nombre' => ..., 'area' => ..., 'usuario' => ...]

// Cerrar sesión
Api::auth()->logout();

3. PEDIDOS
----------
// Obtener perfil
$perfil = Api::pedidos()->perfil();

// Verificar si puede ordenar
$verificar = Api::pedidos()->verificar('2026-01-27');

// Crear pedido semanal
$resultado = Api::pedidos()->crear(
    '2026-01-27',  // Fecha (lunes de la semana)
    [              // Desayunos
        'lunes' => 'Desayuno',
        'martes' => 'Desayuno',
        'miercoles' => '',
        'jueves' => 'Desayuno',
        'viernes' => ''
    ],
    [              // Comidas
        'lunes' => 'Comida',
        'martes' => '',
        'miercoles' => 'Comida',
        'jueves' => 'Comida',
        'viernes' => 'Comida'
    ]
);

// Obtener mis pedidos
$pedidos = Api::pedidos()->misPedidos();

// Obtener semanas disponibles
$semanas = Api::pedidos()->semanasDisponibles();

4. CONSUMOS
-----------
// Obtener consumos de una semana
$consumos = Api::consumos()->misConsumos('2026-01-27');

// Obtener estadísticas
$estadisticas = Api::consumos()->estadisticas();

5. CANCELACIONES
----------------
// Obtener validaciones
$validaciones = Api::cancelaciones()->validaciones();

// Crear cancelación
$resultado = Api::cancelaciones()->crear([
    'jefe' => 'Nombre del jefe',
    'tipo_consumo' => 'DESAYUNO',  // DESAYUNO, COMIDA, AMBOS
    'fecha' => '2026-01-28',
    'causa' => 'SALUD'  // SALUD, PERSONAL, VACACIONES, COMISIÓN, REUNIÓN
]);

// Obtener mis cancelaciones
$cancelaciones = Api::cancelaciones()->misCancelaciones();

6. EMPLEADOS
------------
// Obtener perfil del empleado
$perfil = Api::empleados()->perfil();

// Listar empleados
$empleados = Api::empleados()->listar();

// Crear empleado
$resultado = Api::empleados()->crear($datos);

// Actualizar empleado
$resultado = Api::empleados()->actualizar($id, $datos);

// Eliminar empleado
$resultado = Api::empleados()->eliminar($id);

7. MENÚ
-------
// Listar menús
$menus = Api::menu()->listar();

// Obtener menú de una semana
$menu = Api::menu()->semana('2026-01-27');

// Crear menú
$resultado = Api::menu()->crear($datos);

// Actualizar menú
$resultado = Api::menu()->actualizar($id, $datos);

// Eliminar menú
$resultado = Api::menu()->eliminar($id);

8. REPORTES
-----------
// Obtener dashboard
$dashboard = Api::reportes()->dashboard();

// Reporte detallado
$reporte = Api::reportes()->detallado(['fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-01-31']);

// Estadísticas de cancelaciones
$stats = Api::reportes()->cancelaciones();

// Estadísticas de consumo
$stats = Api::reportes()->consumo();

================================================================================
  FORMATO DE RESPUESTA
================================================================================

Todas las llamadas retornan un array con esta estructura:

ÉXITO:
------
[
    'success' => true,
    'data' => [...],           // Datos de la API
    'http_code' => 200
]

ERROR:
------
[
    'success' => false,
    'error' => 'Mensaje de error',
    'data' => null,
    'http_code' => 400
]

MANEJO DE RESPUESTAS:
---------------------
$result = Api::pedidos()->misPedidos();

if ($result['success']) {
    $pedidos = $result['data'];
    // Procesar pedidos...
} else {
    echo "Error: " . $result['error'];
}

================================================================================
  CONFIGURACIÓN DEL ENTORNO
================================================================================

El cliente detecta automáticamente el entorno:

LOCAL:
  - Localhost sin Docker
  - URL API: http://localhost:3000/api

DESARROLLO:
  - Servidor con "desarollo" o "dev" en el hostname
  - URL API: http://desarollo-bacros:3000/api

PRODUCCIÓN:
  - Docker o servidores de producción
  - URL API: http://host.docker.internal:3000/api

FORZAR ENTORNO:
---------------
Editar api/Api.php línea ~80:
$this->entorno = 'produccion';  // local, desarrollo, produccion

================================================================================
  UTILIDADES
================================================================================

// Obtener información del sistema
$info = Api::info();
// Retorna: entorno, api_url, timeout, debug, authenticated, has_token

// Cliente HTTP directo (casos especiales)
$http = Api::http();
$result = $http->get('/custom/endpoint');

// Configuración
$config = Api::config();
$apiUrl = $config->getApiUrl();
$entorno = $config->getEntorno();

================================================================================
  FUNCIONES LEGACY (Compatibilidad)
================================================================================

Para mantener compatibilidad con código existente:

getAPIClient()              ->  Api::http()
getJwtToken()               ->  Api::getToken()
setJwtToken($token)         ->  TokenManager::save($token)
isUserAuthenticated()       ->  Api::isAuthenticated()
requireAuthentication()     ->  Api::requireAuth()
getCurrentUser()            ->  Api::getCurrentUser()

NOTA: Se recomienda migrar al nuevo sistema Api::modulo()->metodo()

================================================================================
  ENDPOINTS DISPONIBLES EN EL BACKEND
================================================================================

AUTENTICACIÓN:
  POST   /auth/login                    - Login de usuario
  GET    /auth/profile                  - Perfil del usuario
  GET    /auth/verify                   - Verificar token

PEDIDOS:
  GET    /api/pedidos/perfil            - Perfil para pedidos
  GET    /api/pedidos/verificar         - Verificar límite (?fecha=YYYY-MM-DD)
  POST   /api/pedidos                   - Crear nuevo pedido
  GET    /api/pedidos/mis-pedidos       - Obtener pedidos del usuario
  GET    /api/pedidos/semanas-disponibles - Semanas disponibles

CANCELACIONES:
  GET    /api/cancelaciones/validaciones       - Reglas de validación
  POST   /api/cancelaciones                    - Crear cancelación
  GET    /api/cancelaciones/mis-cancelaciones  - Listar cancelaciones
  GET    /api/cancelaciones/pendientes         - Cancelaciones pendientes

EMPLEADOS:
  GET    /api/empleados                 - Información del empleado
  GET    /api/empleados/perfil          - Perfil del empleado

REPORTES:
  GET    /api/reporte/reporte-detallado - Reporte detallado con PIVOT dinámico

================================================================================
  DEBUGGING
================================================================================

Para ver información de debug:

error_log("API Info: " . json_encode(Api::info()));
error_log("User: " . json_encode(Api::getCurrentUser()));
error_log("Token: " . Api::getToken());

================================================================================
  MANEJO DE ERRORES
================================================================================

El cliente maneja automáticamente:
  - Errores de conexión
  - Errores HTTP (4xx, 5xx)
  - Timeouts
  - Respuestas JSON inválidas
  - Tokens expirados

Todos los errores se retornan en el formato estándar:
[
    'success' => false,
    'error' => 'Descripción del error',
    'http_code' => código_http
]

================================================================================
  SEGURIDAD
================================================================================

- Los tokens JWT se almacenan automáticamente en $_SESSION
- Las peticiones incluyen automáticamente el header Authorization
- El cliente verifica la autenticación en cada llamada
- Los tokens expiran después de 24 horas (86400 segundos)
- El sistema regenera el session_id después del login exitoso

================================================================================
  SOPORTE
================================================================================

Para reportar problemas o sugerencias:
- Revisar los logs en error_log de PHP
- Verificar que el backend esté corriendo en http://localhost:3000
- Comprobar que el token JWT sea válido
- Revisar la configuración de entorno

Archivos relacionados:
  - api/Api.php           - Cliente unificado
  - deprecated/           - Archivos obsoletos (referencia)
  - tests/test_api.php    - Script de pruebas

================================================================================
