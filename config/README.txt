================================================================================
  CONFIGURACIÓN DE BASES DE DATOS - SISTEMA COMEDOR
================================================================================

DESCRIPCIÓN
-----------
Este directorio contiene la configuración centralizada para todas las conexiones
a bases de datos del sistema. Las credenciales se almacenan en el archivo .env
en la raíz del proyecto.

ARCHIVOS
--------
database.php    - Configuración centralizada y funciones de conexión
README.txt      - Este archivo (documentación)

================================================================================
  USO BÁSICO
================================================================================

1. INCLUIR LA CONFIGURACIÓN
----------------------------
Al inicio de cualquier archivo PHP que necesite acceso a base de datos:

<?php
require_once __DIR__ . '/config/database.php';

2. USAR LAS FUNCIONES DE CONEXIÓN
----------------------------------

OPCIÓN A: Obtener conexión directa
------------------------------------
$conn = getComedorConnection();

if ($conn) {
    $sql = "SELECT * FROM Conped";
    $stmt = sqlsrv_query($conn, $sql);

    // ... procesar datos ...

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

OPCIÓN B: Obtener configuración (para código legacy)
-----------------------------------------------------
$dbConfig = getComedorConfig();
$serverName = $dbConfig['serverName'];
$connectionOptions = $dbConfig['connectionOptions'];

$conn = sqlsrv_connect($serverName, $connectionOptions);

// ... usar conexión ...

sqlsrv_close($conn);

================================================================================
  BASES DE DATOS DISPONIBLES
================================================================================

1. COMEDOR (Principal)
   ---------------------
   Función: getComedorConnection()
   Config:  getComedorConfig()

   Uso:
   $conn = getComedorConnection();

2. ALQUIMISTA2024
   ---------------
   Función: getAlquimistaConnection()
   Config:  getAlquimistaConfig()

   Uso:
   $conn = getAlquimistaConnection();

3. BASENUEVA
   ----------
   Función: getBaseNuevaConnection()
   Config:  getBaseNuevaConfig()

   Uso:
   $conn = getBaseNuevaConnection();

================================================================================
  FUNCIONES HELPER
================================================================================

env($key, $default)
-------------------
Obtiene una variable de entorno del archivo .env

Ejemplo:
$dbServer = env('DB_COMEDOR_SERVER', 'default_server');

closeConnection($conn, $stmt)
------------------------------
Cierra de forma segura una conexión y statement

Ejemplo:
closeConnection($conn, $stmt);

getConnectionString($dbName)
----------------------------
Obtiene string de conexión para debugging (sin contraseña)

Ejemplo:
echo getConnectionString('comedor');
// Output: DESAROLLO-BACRO\SQLEXPRESS -> Comedor

================================================================================
  CONFIGURACIÓN DEL ARCHIVO .env
================================================================================

El archivo .env en la raíz del proyecto contiene todas las credenciales:

# Base de Datos Principal - COMEDOR
DB_COMEDOR_SERVER=DESAROLLO-BACRO\SQLEXPRESS
DB_COMEDOR_DATABASE=Comedor
DB_COMEDOR_USERNAME=Larome03
DB_COMEDOR_PASSWORD=Larome03
DB_COMEDOR_CHARSET=UTF-8

# Base de Datos - ALQUIMISTA2024
DB_ALQUIMISTA_SERVER=WIN-44O80L37Q7M\COMERCIAL
DB_ALQUIMISTA_DATABASE=ALQUIMISTA2024
DB_ALQUIMISTA_USERNAME=sa
DB_ALQUIMISTA_PASSWORD=Administrador1*
DB_ALQUIMISTA_CHARSET=UTF-8

# Base de Datos - BASENUEVA
DB_BASENUEVA_SERVER=WIN-44O80L37Q7M\COMERCIAL
DB_BASENUEVA_DATABASE=BASENUEVA
DB_BASENUEVA_USERNAME=sa
DB_BASENUEVA_PASSWORD=Administrador1*
DB_BASENUEVA_CHARSET=UTF-8

================================================================================
  SEGURIDAD
================================================================================

⚠️  IMPORTANTE:
--------------
1. NUNCA commitear el archivo .env al repositorio
   (Ya está en .gitignore)

2. NUNCA compartir las credenciales de .env por correo o chat

3. Cada entorno (desarrollo, producción) debe tener su propio .env

4. Usar .env.example como plantilla para nuevos entornos

5. Las credenciales en el código deben reemplazarse por env()

BUENOS:
✅ $password = env('DB_COMEDOR_PASSWORD');
✅ require_once 'config/database.php';

MALOS:
❌ $password = 'Larome03';
❌ Hardcodear credenciales en el código

================================================================================
  MIGRACIÓN DE CÓDIGO LEGACY
================================================================================

ANTES (código antiguo con credenciales hardcodeadas):
------------------------------------------------------
$serverName = "DESAROLLO-BACRO\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "Comedor",
    "Uid" => "Larome03",
    "PWD" => "Larome03",
    "CharacterSet" => "UTF-8"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

DESPUÉS (código migrado usando configuración):
-----------------------------------------------
require_once __DIR__ . '/config/database.php';

$dbConfig = getComedorConfig();
$serverName = $dbConfig['serverName'];
$connectionOptions = $dbConfig['connectionOptions'];
$conn = sqlsrv_connect($serverName, $connectionOptions);

O MEJOR AÚN (usando función directa):
--------------------------------------
require_once __DIR__ . '/config/database.php';
$conn = getComedorConnection();

================================================================================
  EJEMPLO COMPLETO
================================================================================

<?php
// Incluir configuración
require_once __DIR__ . '/config/database.php';

// Conectar a base de datos
$conn = getComedorConnection();

if (!$conn) {
    die("Error de conexión a la base de datos");
}

// Ejecutar consulta
$sql = "SELECT Id_Empleado, Nombre, Area FROM Conped WHERE Usuario = ?";
$params = array('usuario_ejemplo');
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "ID: " . $row['Id_Empleado'] . "\n";
        echo "Nombre: " . $row['Nombre'] . "\n";
        echo "Área: " . $row['Area'] . "\n";
    }
    sqlsrv_free_stmt($stmt);
}

// Cerrar conexión
sqlsrv_close($conn);
?>

================================================================================
  TROUBLESHOOTING
================================================================================

PROBLEMA: "Error de conexión a la base de datos"
-------------------------------------------------
SOLUCIÓN:
1. Verificar que el archivo .env existe en la raíz
2. Verificar que las credenciales en .env son correctas
3. Verificar que SQL Server está corriendo
4. Verificar que el usuario tiene permisos

PROBLEMA: "Call to undefined function env()"
---------------------------------------------
SOLUCIÓN:
Asegurarse de incluir config/database.php al inicio:
require_once __DIR__ . '/config/database.php';

PROBLEMA: "Cannot modify header information"
---------------------------------------------
SOLUCIÓN:
Incluir config/database.php ANTES de cualquier output HTML
y DESPUÉS de session_start() si se usa sesiones

PROBLEMA: Variables de entorno no se cargan
--------------------------------------------
SOLUCIÓN:
1. Verificar que .env está en la raíz del proyecto
2. Verificar que no hay espacios antes/después del =
3. Verificar que no hay líneas vacías o comentarios mal formados

================================================================================
  ARCHIVOS MIGRADOS
================================================================================

Los siguientes 37 archivos ya fueron migrados para usar config/database.php:

✅ Admiin.php
✅ AgendaPedidos.php
✅ AgendaPedidos1.php
✅ Consultadedatos.php
✅ Compras01.php
✅ Compras.php
✅ am.php
✅ am2.php
✅ CocinaTotalPedidos.php
✅ check_pending_cancelations.php
✅ dchef.php
✅ admicome4.php
✅ CHECADORF.php
✅ FormatCancel.php
✅ Formacancel123456.php
✅ EstadCancelaciones.php
✅ d1.php
✅ EstadisticasCancelaciones.php
✅ Estformcancel.php
✅ LoginFormCancel.php
✅ GenerarQRNuevoRegistro.php
✅ GenerarQR.php
✅ Desglosechecador.php
✅ Descrip_Consumo.php
✅ demolecturaQR.php
✅ gestusu.php
✅ Login2.php
✅ MenComprasCocina.php
✅ LoginValidarOrdenes.php
✅ LoingValidarOrdenes.php
✅ KPI_anacomp.php
✅ FormCanAprobUpdate.php
✅ DEMENU.php
✅ descUsuario.php
✅ dem1.php
✅ aparta_consumo_modificado.php
✅ Menpedidos.php

================================================================================
  SOPORTE
================================================================================

Para más información sobre el sistema de configuración:
- Revisar config/database.php
- Revisar .env.example
- Consultar este archivo (README.txt)

Para problemas o preguntas:
- Revisar los logs de PHP (error_log)
- Revisar logs de SQL Server
- Verificar credenciales en .env

================================================================================
