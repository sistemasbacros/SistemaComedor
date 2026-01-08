# 🔐 Integración con API Externa - Sistema de Comedor

## 📋 Descripción

Sistema modificado para **consumir una API externa** en lugar de consultar directamente la base de datos. El token JWT se maneja en cache para mantener la sesión activa.

---

## 🏗️ Arquitectura

```
┌─────────────┐      1. Login (POST)           ┌──────────────────┐
│  Admiin.php │ ───────────────────────────────>│ API Externa      │
│  (Frontend) │                                 │ localhost:3000   │
└─────────────┘                                 └──────────────────┘
      │                                                 │
      │              2. Token JWT + user_info           │
      │ <───────────────────────────────────────────────┘
      │
      ▼
┌─────────────────────────────────────────┐
│  $_SESSION (Cache en Servidor)          │
│  • jwt_token                            │
│  • user_info (id, nombre, area)         │
│  • token_expires_in (86400 = 24h)       │
│  • token_created_at (timestamp)         │
└─────────────────────────────────────────┘
      │
      │ 3. Peticiones protegidas con token
      ▼
┌─────────────────────────────────────────┐
│  Otras páginas (MenUsuario.php, etc)    │
│  require 'api_client.php'               │
│  $api->get('api/pedidos')               │
└─────────────────────────────────────────┘
```

---

## 🚀 Cambios Realizados

### **1. Admiin.php (Modificado)**
- ✅ Reemplazada consulta directa a BD por cURL a API
- ✅ Endpoint: `http://localhost:3000/auth/login`
- ✅ Token JWT guardado en `$_SESSION['jwt_token']`
- ✅ Manejo de errores de API mejorado

### **2. api_client.php (Nuevo)**
Clase helper para consumir la API con el token:

```php
require_once 'api_client.php';

$api = getAPIClient();

// GET
$response = $api->get('api/usuario');

// POST
$response = $api->post('api/pedidos', ['producto' => 'Comida']);

// PUT
$response = $api->put('api/usuario/perfil', ['email' => 'nuevo@mail.com']);

// DELETE
$response = $api->delete('api/pedidos/123');
```

---

## 📡 Endpoints de la API

### **1. Endpoint de Login**

**Request:**
```http
POST http://localhost:3000/auth/login
Content-Type: application/json

{
    "usuario": "adrian.ibarra",
    "contrasena": "Adriiba1029"
}
```

**Response (200 OK):**
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJhZHJpYW4uaWJhcnJhIiwiaWRfZW1wbGVhZG8iOjEwMjkuMCwibm9tYnJlIjoiSUJBUlJBIEhFUk5BTkRFWiBBRFJJQU4iLCJhcmVhIjoiQXVkaXRvcmlhIiwiZXhwIjoxNzY3ODA1NjQ0LCJpYXQiOjE3Njc3MTkyNDR9.cmCW5_Y_OWOzYwRdvvDnNHng1BPflZch7I3CnmxyCBE",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user_info": {
        "id_empleado": 1029.0,
        "nombre": "IBARRA HERNANDEZ ADRIAN",
        "area": "Auditoria",
        "usuario": "adrian.ibarra"
    }
}
```

**Response (401 Unauthorized):**
```json
{
    "error": "Usuario o contraseña incorrectos"
}
```

---

### **2. Obtener Perfil del Usuario**

**Request:**
```http
GET http://localhost:3000/api/pedidos/perfil
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Response 200:**
```json
{
    "id_empleado": "123",
    "nombre": "Juan Pérez",
    "area": "Sistemas",
    "usuario": "jperez"
}
```

---

### **3. Verificar Pedidos Existentes**

**Request:**
```http
GET http://localhost:3000/api/pedidos/verificar?fecha=2026-01-06
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Response 200:**
```json
{
    "total": 0,
    "puede_ordenar": true,
    "mensaje": "Puedes realizar tu pedido"
}
```

---

### **4. Crear Pedido Semanal**

**Request:**
```http
POST http://localhost:3000/api/pedidos
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json

{
    "fecha_semana": "2026-01-06",
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

**Response 200:**
```json
{
    "success": true,
    "message": "Pedido creado con éxito",
    "data": {
        "fecha": "2026-01-06",
        "total_desayunos": 3,
        "total_comidas": 2
    }
}
```

**Response 400:**
```json
{
    "success": false,
    "error": "Ya tienes pedidos registrados para esta semana"
}
```

**Nota:** El campo `id` es `null` ya que la tabla `PedidosComida` no tiene columna `Id` o `PedidoId`.

---

### **5. Obtener Consumos Semanales**

**Endpoint:** `GET http://localhost:3000/api/pedidos/mis-consumos`

**Descripción:** Obtiene el resumen de consumos del usuario para una semana específica, mostrando qué días tiene pedidos y el total de consumos.

**Query Parameters:**
- `fecha` (opcional): Fecha de la semana en formato YYYY-MM-DD (lunes de la semana)
  - Si no se proporciona, usa el lunes de la semana actual

**Request:**
```http
GET /api/pedidos/mis-consumos?fecha=2026-01-06 HTTP/1.1
Host: localhost:3000
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "fecha_consulta": "2026-01-06",
    "fecha_formateada": "06/01/2026",
    "empleado": {
      "id_empleado": "123",
      "nombre": "Juan Pérez",
      "area": "Sistemas"
    },
    "consumos": {
      "lunes": "Desayuno",
      "martes": "Comida",
      "miercoles": "",
      "jueves": "Desayuno",
      "viernes": ""
    },
    "total_consumos": 3,
    "desglose": [
      {
        "dia": "Lunes",
        "tipo": "Desayuno"
      },
      {
        "dia": "Martes",
        "tipo": "Comida"
      },
      {
        "dia": "Jueves",
        "tipo": "Desayuno"
      }
    ]
  }
}
```

**Response 200 (sin consumos):**
```json
{
  "success": true,
  "data": {
    "fecha_consulta": "2026-01-13",
    "fecha_formateada": "13/01/2026",
    "empleado": {
      "id_empleado": "123",
      "nombre": "Juan Pérez",
      "area": "Sistemas"
    },
    "consumos": {
      "lunes": "",
      "martes": "",
      "miercoles": "",
      "jueves": "",
      "viernes": ""
    },
    "total_consumos": 0,
    "desglose": []
  }
}
```

**Lógica:**
- Realiza un LEFT JOIN entre `Catalogo_EmpArea` y `PedidosComida`
- Solo cuenta días con "Desayuno" o "Comida" (ignora strings vacíos)
- El campo `fecha` debe ser el **lunes** de la semana a consultar
- Si no existe pedido para esa semana, retorna consumos vacíos

---

### **6. Obtener Semanas Disponibles**

**Endpoint:** `GET http://localhost:3000/api/pedidos/semanas-disponibles`

**Descripción:** Obtiene una lista de 9 semanas disponibles para hacer pedidos, desde la semana actual hacia adelante.

**Request:**
```http
GET /api/pedidos/semanas-disponibles HTTP/1.1
Host: localhost:3000
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "semanas": [
      {
        "fecha": "2026-01-06",
        "fecha_formateada": "06/01/2026",
        "es_semana_actual": true
      },
      {
        "fecha": "2026-01-13",
        "fecha_formateada": "13/01/2026",
        "es_semana_actual": false
      },
      {
        "fecha": "2026-01-20",
        "fecha_formateada": "20/01/2026",
        "es_semana_actual": false
      },
      {
        "fecha": "2026-01-27",
        "fecha_formateada": "27/01/2026",
        "es_semana_actual": false
      },
      {
        "fecha": "2026-02-03",
        "fecha_formateada": "03/02/2026",
        "es_semana_actual": false
      },
      {
        "fecha": "2026-02-10",
        "fecha_formateada": "10/02/2026",
        "es_semana_actual": false
      },
      {
        "fecha": "2026-02-17",
        "fecha_formateada": "17/02/2026",
        "es_semana_actual": false
      },
      {
        "fecha": "2026-02-24",
        "fecha_formateada": "24/02/2026",
        "es_semana_actual": false
      },
      {
        "fecha": "2026-03-03",
        "fecha_formateada": "03/03/2026",
        "es_semana_actual": false
      }
    ],
    "semana_actual": "2026-01-06"
  }
}
```

**Uso típico:**
- Para mostrar un dropdown/selector de semanas en el frontend
- Cada fecha representa el **lunes** de esa semana
- Útil para el endpoint `/api/pedidos/mis-consumos?fecha=X`

---

## 📡 Estructura de la API Externa (Resumen)

## 💾 Manejo de Cache y Sesiones

### **Cache en Servidor (PHP Session)**

```php
// Datos guardados automáticamente en Admiin.php
$_SESSION['jwt_token']         // Token JWT completo
$_SESSION['token_type']        // "Bearer"
$_SESSION['token_expires_in']  // 86400 (24 horas)
$_SESSION['token_created_at']  // timestamp UNIX
$_SESSION['user_id']           // ID del empleado
$_SESSION['user_name']         // Nombre completo
$_SESSION['user_area']         // Área/departamento
$_SESSION['user_username']     // Usuario
```

### **Verificar Expiración**

```php
require_once 'api_client.php';

$api = getAPIClient();

if ($api->isTokenExpired()) {
    // Token expirado, redirigir a login
    header('Location: Admiin.php?error=token_expirado');
    exit;
}
```

### **Cache en Cliente (Opcional - LocalStorage)**

Si necesitas mantener la sesión en el navegador:

```javascript
// Guardar en login
localStorage.setItem('jwt_token', token);
localStorage.setItem('user_info', JSON.stringify(userInfo));
localStorage.setItem('token_expires', Date.now() + (expires_in * 1000));

// Recuperar
const token = localStorage.getItem('jwt_token');

// Verificar expiración
if (Date.now() > parseInt(localStorage.getItem('token_expires'))) {
    // Expirado
    localStorage.clear();
    window.location.href = 'Admiin.php';
}
```

---

## 🔧 Ejemplos de Uso

### **Ejemplo 1: Proteger una página**

```php
<?php
session_start();
require_once 'api_client.php';

$api = getAPIClient();

// Verificar autenticación
if (!$api->isAuthenticated()) {
    header('Location: Admiin.php');
    exit;
}

// Verificar expiración
if ($api->isTokenExpired()) {
    session_destroy();
    header('Location: Admiin.php?error=sesion_expirada');
    exit;
}

// Usuario autenticado, continuar...
?>
<!DOCTYPE html>
<html>
<head>
    <title>Página Protegida</title>
</head>
<body>
    <h1>Bienvenido, <?php echo $_SESSION['user_name']; ?></h1>
    <p>Tu token expira en: <?php 
        $resto = $_SESSION['token_created_at'] + $_SESSION['token_expires_in'] - time();
        echo gmdate("H:i:s", $resto);
    ?></p>
</body>
</html>
```

### **Ejemplo 2: Obtener datos de la API**

```php
<?php
require_once 'api_client.php';

$api = getAPIClient();

// Obtener información del usuario
$response = $api->get('api/usuario');

if ($response['success']) {
    $usuario = $response['data'];
    echo "Nombre: " . $usuario['nombre'] . "<br>";
    echo "Área: " . $usuario['area'] . "<br>";
    echo "ID: " . $usuario['id_empleado'];
} else {
    echo "Error: " . $response['error'];
}
?>
```

### **Ejemplo 3: Crear un pedido (POST)**

```php
<?php
require_once 'api_client.php';

$api = getAPIClient();

// Datos del pedido
$nuevoPedido = [
    'producto' => 'Comida del día',
    'cantidad' => 1,
    'comentarios' => 'Sin cebolla, por favor',
    'usuario_id' => $_SESSION['user_id']
];

// Enviar a la API
$response = $api->post('api/pedidos', $nuevoPedido);

if ($response['success']) {
    echo "Pedido creado con éxito: ID #" . $response['data']['id'];
} else {
    echo "Error al crear pedido: " . $response['error'];
}
?>
```

### **Ejemplo 4: Actualizar perfil (PUT)**

```php
<?php
require_once 'api_client.php';

$api = getAPIClient();

// Actualizar datos
$response = $api->put('api/usuario/perfil', [
    'telefono' => '5551234567',
    'email' => 'nuevo@email.com'
]);

if ($response['success']) {
    echo "Perfil actualizado correctamente";
}
?>
```

### **Ejemplo 5: Eliminar pedido (DELETE)**

```php
<?php
require_once 'api_client.php';

$api = getAPIClient();

$pedidoId = 123;
$response = $api->delete("api/pedidos/{$pedidoId}");

if ($response['success']) {
    echo "Pedido eliminado";
}
?>
```

---

## 🔄 Flujo Completo de Autenticación

```
1. Usuario ingresa credenciales en Admiin.php
   ↓
2. PHP hace POST a http://localhost:3000/auth/login
   ↓
3. API valida y retorna:
   {
     "token": "eyJ...",
     "user_info": {...}
   }
   ↓
4. Admiin.php guarda en sesión:
   $_SESSION['jwt_token'] = "eyJ..."
   $_SESSION['user_info'] = {...}
   $_SESSION['token_created_at'] = time()
   ↓
5. Usuario redirigido a MenUsuario.php o admicome4.php
   ↓
6. Otras páginas usan APIClient para hacer peticiones:
   $api = getAPIClient();
   $response = $api->get('api/datos');
   ↓
7. APIClient incluye automáticamente el header:
   Authorization: Bearer eyJ...
   ↓
8. La API valida el token y retorna datos
```

---

## 🛡️ Seguridad

### **Buenas Prácticas Implementadas**

✅ **Token en sesión de servidor** (no en cookies del cliente)
✅ **Validación de expiración** automática
✅ **Headers seguros** (Authorization: Bearer)
✅ **Timeout de conexión** (10 segundos)
✅ **Manejo de errores** completo
✅ **Regeneración de session_id** en login

### **Mejoras Recomendadas para Producción**

```php
// 1. Usar HTTPS en producción
$apiUrl = 'https://api.produccion.com/auth/login';

// 2. Validar certificados SSL
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

// 3. Configurar cookies seguras
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,      // Solo HTTPS
    'httponly' => true,    // No accesible desde JS
    'samesite' => 'Strict' // CSRF protection
]);

// 4. Agregar rate limiting
// 5. Implementar refresh tokens
// 6. Registrar intentos de login fallidos
```

---

## 📝 Variables de Configuración

Edita estas variables según tu entorno:

### **En Admiin.php (línea ~55)**
```php
$apiUrl = 'http://localhost:3000/auth/login'; // Cambiar a tu API
```

### **En api_client.php (línea ~9)**
```php
public function __construct($baseUrl = 'http://localhost:3000') {
    // Cambiar a: https://api.tudominio.com
}
```

---

## 🧪 Probar el Sistema

### **1. Verificar que tu API esté corriendo**
```bash
curl http://localhost:3000/auth/login
```

### **2. Probar login desde PowerShell**
```powershell
$body = @{
    usuario = "adrian.ibarra"
    contrasena = "Adriiba1029"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:3000/auth/login" `
    -Method POST `
    -Body $body `
    -ContentType "application/json"
```

### **3. Acceder al sistema**
```
http://desarollo-bacros/Comedor/Admiin.php
```

---

## 📚 Archivos del Sistema

```
Comedor/
├── Admiin.php                      # Login modificado (consume API)
├── api_client.php                  # Helper para peticiones con token
├── ejemplo_pagina_protegida.php    # Demo de página que usa la API
├── ejemplos_api.html               # Guía visual de ejemplos
└── README_API.md                   # Esta documentación
```

---

## 🆘 Solución de Problemas

### **Error: "Error de conexión con el servidor"**
```
✓ Verificar que la API esté corriendo en http://localhost:3000
✓ Verificar firewall/antivirus
✓ Comprobar que curl_exec() esté habilitado en PHP
```

### **Error: "Token expirado"**
```php
// El token expira después de 24 horas por defecto
// Puedes ajustar en tu API el valor de expires_in
```

### **Error: "Usuario o contraseña incorrectos"**
```
✓ Verificar que las credenciales sean correctas
✓ Revisar logs de la API para más detalles
✓ Verificar que el endpoint sea /auth/login
```

---

## 📞 Soporte

Para problemas con la integración, contactar al equipo de desarrollo.

**Desarrollado para**: BACROCORP - Sistema de Comedor  
**Fecha**: Enero 2026  
**Versión**: 2.0 (API Integration)
