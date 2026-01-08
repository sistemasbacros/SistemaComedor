# 🌐 Configuración de Entornos - API

Este sistema detecta automáticamente el entorno (local, desarrollo, producción) y configura la URL de la API correspondiente.

## 📋 Resumen

- **Archivo principal**: `config_api.php`
- **Detección**: Automática basada en el dominio
- **Override**: Manual si es necesario

---

## 🔍 Detección Automática de Entornos

El sistema detecta el entorno basándose en `$_SERVER['HTTP_HOST']`:

| Condición | Entorno | API URL | APP URL |
|-----------|---------|---------|---------|
| `localhost`, `127.0.0.1`, `192.168.x.x` | **Local** | `http://localhost:3000` | `http://localhost/Comedor` |
| `desarollo`, `dev`, `desarrollo` | **Desarrollo** | `http://desarollo-bacros:3000` | `http://desarollo-bacros/Comedor` |
| Cualquier otro | **Producción** | `https://api.bacrocorp.com` | `https://bacrocorp.com/Comedor` |

---

## ⚙️ Configuración para Producción

### 1️⃣ **Editar `config_api.php`**

Abre el archivo [config_api.php](config_api.php) y busca la sección de configuración:

```php
// ========== CONFIGURACIÓN POR ENTORNO ==========
$API_CONFIG = [
    'local' => [
        'base_url' => 'http://localhost:3000',
        'app_url' => 'http://localhost/Comedor',
        'timeout' => 10,
        'connect_timeout' => 5,
        'debug' => true
    ],
    'desarrollo' => [
        'base_url' => 'http://desarollo-bacros:3000',
        'app_url' => 'http://desarollo-bacros/Comedor',
        'timeout' => 15,
        'connect_timeout' => 10,
        'debug' => true
    ],
    'produccion' => [
        'base_url' => 'https://api.bacrocorp.com', // ⬅️ CAMBIAR ESTA URL (API)
        'app_url' => 'https://bacrocorp.com/Comedor', // ⬅️ CAMBIAR ESTA URL (APP WEB)
        'timeout' => 20,
        'connect_timeout' => 10,
        'debug' => false // ⬅️ DESACTIVAR DEBUG EN PRODUCCIÓN
    ]
];
```

### 2️⃣ **Cambiar las URLs de Producción**

Reemplaza las URLs con tus valores reales de producción.

**Ejemplos para la API:**
```php
'base_url' => 'https://bacrocorp-api.azurewebsites.net',  // Azure
'base_url' => 'https://api.bacrocorp.com',                 // Dominio propio
'base_url' => 'https://192.168.1.100:3000',                // IP + Puerto
'base_url' => 'http://bacros-prod:3000',                   // Servidor interno
```

**Ejemplos para la Aplicación Web:**
```php
'app_url' => 'https://bacrocorp.com/Comedor',              // Dominio propio
'app_url' => 'https://www.bacrocorp.com/Comedor',          // Con www
'app_url' => 'https://192.168.1.100/Comedor',              // Por IP
'app_url' => 'http://bacros-prod/Comedor',                 // Servidor interno
```

### 3️⃣ **Ajustar Timeouts (Opcional)**

Si tu servidor está en otra región o red lenta:

```php
'produccion' => [
    'base_url' => 'https://api.bacrocorp.com',
    'timeout' => 30,           // ⬅️ Tiempo máximo de espera (segundos)
    'connect_timeout' => 15,   // ⬅️ Tiempo para establecer conexión
    'debug' => false
]
```

### 4️⃣ **Desactivar Debug en Producción**

**IMPORTANTE**: Asegúrate de que `debug` esté en `false` en producción:

```php
'produccion' => [
    // ...
    'debug' => false // ⬅️ CRÍTICO para seguridad
]
```

---

## 🔐 Override Manual (Opcional)

Si necesitas **forzar** un entorno específico (para testing), edita `config_api.php`:

```php
// ========== OVERRIDE MANUAL (OPCIONAL) ==========
// Descomenta la siguiente línea para forzar un entorno:
$entorno = 'produccion'; // Opciones: 'local', 'desarrollo', 'produccion'
```

**⚠️ NO OLVIDES COMENTAR ESTA LÍNEA DESPUÉS DE PROBAR:**

```php
// $entorno = 'produccion'; // ⬅️ COMENTADO = detección automática
```

---

## 🧪 Probar la Configuración

### Desde la línea de comandos:

```powershell
C:\php82\php-8.2.30-Win32-vs16-x64\php.exe test_api.php
```

### Desde el navegador:

```
http://desarollo-bacros/Comedor/test_api.php
```

**Ejemplo de salida:**

```
=== TEST DE CONEXIÓN CON API ===

📍 ENTORNO DETECTADO: PRODUCCION
🌐 URL API: https://api.bacrocorp.com
🌐 URL APP: https://bacrocorp.com/Comedor
⏱️  TIMEOUT: 20s
🔧 DEBUG: Desactivado

✅ INTEGRACIÓN FUNCIONANDO CORRECTAMENTE
```

---

## 📂 Archivos que Usan la Configuración

Todos estos archivos **ya están configurados** para usar `config_api.php`:

- ✅ [Admiin.php](Admiin.php) - Login
- ✅ [api_client.php](api_client.php) - Cliente HTTP
- ✅ [test_api.php](test_api.php) - Pruebas

---

## 🔗 Uso de Endpoints

En lugar de URLs hardcodeadas, usa las funciones helper:

### ✅ Para la API (autenticación, datos):

```php
require_once __DIR__ . '/config_api.php';

// Endpoints de la API
$loginUrl = getApiUrl('LOGIN');           // → http://localhost:3000/auth/login
$usersUrl = getApiUrl('USUARIOS_LIST');   // → http://localhost:3000/api/empleados
```

### ✅ Para redirecciones de la aplicación web:

```php
// Redirecciones internas
header("Location: " . getAppUrl('MenUsuario.php'));   // → http://desarollo-bacros/Comedor/MenUsuario.php
header("Location: " . getAppUrl('admicome4.php'));    // → http://desarollo-bacros/Comedor/admicome4.php
header("Location: " . getAppUrl());                   // → http://desarollo-bacros/Comedor (raíz)
```

### ❌ Evitar URLs hardcodeadas:

```php
// ❌ ANTES (hardcodeado)
$url = 'http://localhost:3000/auth/login';
header("Location: http://desarollo-bacros/Comedor/MenUsuario.php");

// ✅ DESPUÉS (dinámico)
$url = getApiUrl('LOGIN');
header("Location: " . getAppUrl('MenUsuario.php'));
```

### 📝 Endpoints Disponibles:

```php
// Autenticación
getApiUrl('LOGIN')          → /auth/login
getApiUrl('LOGOUT')         → /auth/logout
getApiUrl('VALIDATE_TOKEN') → /auth/validate

// Usuarios
getApiUrl('USUARIOS_LIST')  → /api/empleados
getApiUrl('USUARIO_INFO')   → /api/usuario/info

// Pedidos
getApiUrl('PEDIDOS_LIST')   → /api/pedidos
getApiUrl('PEDIDOS_CREATE') → /api/pedidos

// Con parámetros dinámicos:
getApiUrl('USUARIOS_UPDATE', ['id' => 1029]) → /api/empleados/1029
getApiUrl('PEDIDOS_DELETE', ['id' => 42])    → /api/pedidos/42
```

Ver todos los endpoints en [config_api.php](config_api.php#L60-L90)

---

## 🐛 Solución de Problemas

### Problema: "Error de conexión con el servidor de autenticación"

**Solución:**
1. Verifica que la API esté corriendo:
   ```bash
   curl http://localhost:3000/auth/login
   ```
2. Revisa los logs de debug (si está activado):
   ```powershell
   Get-Content C:\path\to\php\error.log -Tail 50
   ```

### Problema: "Endpoint no definido"

**Solución:**
- Verifica que el endpoint esté en `API_ENDPOINTS` en [config_api.php](config_api.php)
- Usa las constantes correctas (ej: `'LOGIN'` no `'login'`)

### Problema: La URL se construye incorrectamente

**Solución:**
```php
// Verificar configuración actual
print_r(getApiEnvironmentInfo());
```

---

## 📊 Variables de Entorno Disponibles

Puedes obtener información del entorno actual:

```php
$info = getApiEnvironmentInfo();
echo $info['entorno'];         // 'local', 'desarrollo', 'produccion'
echo $info['api_base_url'];    // URL base de la API
echo $info['app_base_url'];    // URL base de la aplicación web
echo $info['timeout'];         // Timeout configurado
echo $info['debug'];           // true/false
echo $info['host'];            // Dominio actual
```

---

## ✅ Checklist de Despliegue a Producción

- [ ] Editar `config_api.php` con la URL correcta de producción
- [ ] Cambiar `'debug' => false` en el entorno de producción
- [ ] Comentar cualquier override manual (`$entorno = 'produccion'`)
- [ ] Probar la conexión con `test_api.php`
- [ ] Verificar que el certificado SSL esté configurado (si usas HTTPS)
- [ ] Revisar los timeouts según la latencia de red
- [ ] Hacer backup de la configuración anterior

---

## 📞 Soporte

Para agregar nuevos endpoints, edita la constante `API_ENDPOINTS` en [config_api.php](config_api.php):

```php
define('API_ENDPOINTS', [
    // ... endpoints existentes
    
    // ⬇️ Agregar aquí tus nuevos endpoints
    'MI_NUEVO_ENDPOINT' => '/api/mi-ruta',
]);
```

Luego úsalo:

```php
$url = getApiUrl('MI_NUEVO_ENDPOINT');
```

---

**Última actualización:** Enero 2026  
**Versión:** 1.0.0
