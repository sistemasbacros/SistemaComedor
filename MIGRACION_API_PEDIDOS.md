# Migración a API de Pedidos - Documentación

## 🎯 Resumen de Cambios

Se ha actualizado el sistema de pedidos de comedor para usar la **API REST con autenticación JWT** en lugar de conexión directa a SQL Server.

### Archivos Modificados

1. **config_api.php** - Configuración de endpoints
2. **api_client.php** - Cliente HTTP mejorado con funciones helper
3. **Menpedidos1.php** - Sistema de pedidos actualizado

---

## 📋 Nuevos Endpoints de Pedidos

### 1. Obtener Perfil del Usuario

```http
GET /api/pedidos/perfil
Authorization: Bearer {jwt_token}
```

**Response:**
```json
{
  "id_empleado": 123.0,
  "nombre": "Juan Pérez",
  "area": "Sistemas",
  "usuario": "jperez"
}
```

### 2. Verificar Pedidos Existentes

```http
GET /api/pedidos/verificar?fecha=2026-01-05
Authorization: Bearer {jwt_token}
```

**Response:**
```json
{
  "total": 1,
  "puede_ordenar": true,
  "mensaje": "Puede realizar pedidos"
}
```

**Regla:** Máximo 2 pedidos por semana

### 3. Crear Pedido Semanal

```http
POST /api/pedidos
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

**Body:**
```json
{
  "fecha_semana": "2026-01-05",
  "desayunos": {
    "lunes": "Desayuno",
    "martes": "",
    "miercoles": "Desayuno",
    "jueves": "",
    "viernes": "Desayuno"
  },
  "comidas": {
    "lunes": "",
    "martes": "Comida",
    "miercoles": "",
    "jueves": "Comida",
    "viernes": ""
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Pedido registrado con éxito"
}
```

---

## 🔧 Funciones Helper en api_client.php

### Obtener Perfil del Usuario

```php
$perfil = obtenerPerfilUsuario();
// Retorna: ['id_empleado' => ..., 'nombre' => ..., 'area' => ..., 'usuario' => ...]
```

### Verificar Pedidos Existentes

```php
$verificacion = verificarPedidosExistentes('2026-01-05');
// Retorna: ['total' => int, 'puede_ordenar' => bool, 'mensaje' => string]
```

### Crear Pedido Semanal

```php
$desayunos = [
    'lunes' => 'Desayuno',
    'martes' => '',
    'miercoles' => 'Desayuno',
    'jueves' => '',
    'viernes' => 'Desayuno'
];

$comidas = [
    'lunes' => '',
    'martes' => 'Comida',
    'miercoles' => '',
    'jueves' => 'Comida',
    'viernes' => ''
];

$resultado = crearPedidoSemanal('2026-01-05', $desayunos, $comidas);
// Retorna: ['success' => bool, 'message' => string]
```

### Validar Autenticación

```php
// Redirige automáticamente a login si no hay sesión JWT
requireAuthentication('Login2.php');
```

---

## 🔄 Cambios en Menpedidos1.php

### Antes (SQL Server directo)

```php
// Conectar a SQL Server
$conn = sqlsrv_connect($serverName, $connectionInfo);

// Query para obtener usuario
$sql = "SELECT Id_Empleado, nombre, area, usuario FROM ConPed WHERE nombre LIKE ?";
$stmt = sqlsrv_query($conn, $sql, $params);

// Query para verificar pedidos
$sql3 = "SELECT COUNT(*) AS Total FROM PedidosComida WHERE Fecha = ?";

// Insert de pedidos
$sql = "INSERT INTO PedidosComida (...) VALUES (...)";
```

### Después (API con JWT)

```php
// Cargar configuración de API
require_once __DIR__ . '/config_api.php';
require_once __DIR__ . '/api_client.php';

// Validar autenticación JWT
requireAuthentication('Login2.php');

// Obtener perfil desde API
$perfil = obtenerPerfilUsuario();

// Verificar pedidos desde API
$verificacion = verificarPedidosExistentes($fecha);

// Crear pedido desde API
$resultado = crearPedidoSemanal($fecha, $desayunos, $comidas);
```

---

## ✅ Mejoras de Seguridad

1. **Autenticación JWT:** Ya no se envían credenciales en cada request
2. **Sin contraseñas en formularios:** Eliminados campos hidden con contraseñas
3. **Token en sesión:** El JWT se almacena en `$_SESSION['jwt_token']`
4. **Validación automática:** La función `requireAuthentication()` protege todas las páginas

---

## 🚀 Cómo Usar

### 1. Asegurar que el usuario tiene JWT token

El usuario debe haber iniciado sesión en **Login2.php** y obtenido un JWT token válido.

### 2. Acceder a Menpedidos1.php

```
http://desarollo-bacros/Comedor/Menpedidos1.php
```

- Si hay sesión JWT válida → Se carga el perfil automáticamente
- Si NO hay sesión → Redirige a Login2.php

### 3. El sistema ahora:

✅ Obtiene el perfil del usuario desde `/api/pedidos/perfil`  
✅ Verifica pedidos existentes antes de permitir nuevos pedidos  
✅ Crea pedidos usando `/api/pedidos` (desayunos y comidas en una sola petición)  
✅ No necesita credenciales en el formulario (usa JWT)  

---

## 📝 Notas Importantes

### Configuración de Entorno

El archivo `config_api.php` detecta automáticamente el entorno:

- **Local:** `http://localhost:3000` (API) + `http://localhost:8000` (Frontend)
- **Desarrollo:** `http://desarollo-bacros:3000` (API) + `http://desarollo-bacros/Comedor` (Frontend)
- **Producción:** URLs de producción (configurar en config_api.php)

### Campos Eliminados del Formulario

❌ Ya NO se envían:
- `Nempleado` (ID Empleado)
- `Usuar` (Usuario)
- `contrase` (Contraseña)

✅ Se envía únicamente:
- `Fecha2` (Fecha de la semana)
- `gender1-10` (Selecciones de desayuno/comida)

El usuario se identifica automáticamente desde el JWT token.

---

## 🔍 Depuración

### Ver información de la API

```php
$info = getApiEnvironmentInfo();
print_r($info);
```

### Ver logs de debug (si API_DEBUG = true)

```php
apiDebugLog('Mensaje de prueba', ['data' => 'valor']);
```

Los logs se escriben en el `error_log` de PHP.

---

## ⚠️ Posibles Problemas

### Error: "Sesión expirada"

**Causa:** El JWT token expiró o no existe  
**Solución:** Volver a iniciar sesión en Login2.php

### Error: "No autenticado"

**Causa:** No se pudo validar el token JWT  
**Solución:** Verificar que la API está corriendo en el puerto correcto

### Error: "Error de conexión"

**Causa:** La API no está disponible  
**Solución:** 
1. Verificar que la API Node.js está corriendo: `http://localhost:3000` o `http://desarollo-bacros:3000`
2. Revisar configuración en `config_api.php`

---

## 📞 Soporte

Para reportar problemas o dudas:
1. Revisar logs de la API Node.js
2. Revisar logs de PHP (`error_log`)
3. Verificar que el entorno esté correctamente configurado en `config_api.php`

---

**Fecha de migración:** 7 de Enero 2026  
**Versión:** 1.0  
**Estado:** ✅ Producción
