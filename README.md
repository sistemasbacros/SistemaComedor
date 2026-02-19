# Sistema Comedor - BacroCorp

Sistema integral de gestión de comedor empresarial para el control de pedidos, operaciones de cocina, cancelaciones, registro de asistencia mediante códigos QR y generación de reportes analíticos.

[![Docker](https://img.shields.io/badge/Docker-20.10+-blue.svg)](https://www.docker.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4.svg?logo=php)](https://www.php.net/)
[![SQL Server](https://img.shields.io/badge/SQL%20Server-2019+-CC2927.svg)](https://www.microsoft.com/sql-server)
[![Nginx](https://img.shields.io/badge/Nginx-Alpine-009639.svg?logo=nginx)](https://www.nginx.com/)

---

## Tabla de Contenidos

- [Descripción del Proyecto](#descripción-del-proyecto)
- [Objetivos](#objetivos)
- [Tecnologías Utilizadas](#tecnologías-utilizadas)
- [Requisitos Previos](#requisitos-previos)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Configuración del Entorno](#configuración-del-entorno)
  - [Variables de Entorno](#variables-de-entorno)
  - [Configuración de Base de Datos](#configuración-de-base-de-datos)
- [Instalación y Ejecución](#instalación-y-ejecución)
  - [Entorno de Desarrollo Local](#entorno-de-desarrollo-local)
  - [Entorno de Pruebas (TEST)](#entorno-de-pruebas-test)
  - [Entorno de Producción](#entorno-de-producción)
- [Arquitectura del Sistema](#arquitectura-del-sistema)
  - [Módulos Principales](#módulos-principales)
  - [Patrón de Conexión a Base de Datos](#patrón-de-conexión-a-base-de-datos)
  - [Sistema de Autenticación](#sistema-de-autenticación)
  - [Cliente API Unificado](#cliente-api-unificado)
- [CI/CD y Despliegue](#cicd-y-despliegue)
- [Buenas Prácticas](#buenas-prácticas)
- [Solución de Problemas](#solución-de-problemas)
- [Documentación Adicional](#documentación-adicional)
- [Seguridad](#seguridad)
- [Licencia](#licencia)

---

## Descripción del Proyecto

**Sistema Comedor** es una aplicación web empresarial diseñada para BacroCorp que centraliza y automatiza la gestión integral del servicio de comedor para empleados. El sistema permite:

- **Gestión de Pedidos**: Los empleados pueden realizar pedidos de alimentos con antelación según el menú disponible.
- **Operaciones de Cocina**: El personal de cocina visualiza y gestiona los pedidos en tiempo real, optimizando la preparación de alimentos.
- **Cancelaciones**: Sistema de solicitud y aprobación de cancelaciones de pedidos con notificaciones.
- **Check-in con QR**: Registro de asistencia al comedor mediante códigos QR generados dinámicamente.
- **Reportes y KPIs**: Dashboards analíticos con estadísticas de consumo, tendencias, cancelaciones y costos.
- **Control de Acceso**: Sistema de roles y permisos basado en áreas (Dirección, Cocina, Recursos Humanos, etc.).

---

## Objetivos

El sistema busca cumplir los siguientes objetivos estratégicos:

1. **Digitalizar** el proceso de solicitud y entrega de alimentos, eliminando procesos manuales.
2. **Optimizar** la planificación de compras y preparación de alimentos mediante estadísticas predictivas.
3. **Reducir** el desperdicio de alimentos a través de un mejor control de cancelaciones y asistencia.
4. **Mejorar** la experiencia del empleado con interfaces intuitivas y acceso móvil.
5. **Generar** información de valor para la toma de decisiones gerenciales mediante reportes en tiempo real.
6. **Garantizar** la seguridad de los datos mediante autenticación robusta y control de acceso granular.

---

## Tecnologías Utilizadas

### Backend
- **PHP 8.2-FPM**: Motor de ejecución del backend con soporte para extensiones modernas.
- **Microsoft SQL Server**: Sistema de gestión de bases de datos relacional (múltiples bases de datos).
- **Extensión `sqlsrv`**: Driver nativo de Microsoft para conectividad PHP-SQL Server.

### Frontend
- **HTML5 / CSS3 / JavaScript (ES6+)**: Tecnologías base del frontend.
- **jQuery 3.x**: Librería JavaScript para manipulación del DOM y peticiones AJAX.
- **DataTables**: Plugin de jQuery para tablas interactivas con paginación, búsqueda y ordenamiento.
- **ECharts**: Librería de visualización de datos para gráficos interactivos.
- **Bootstrap 4/5**: Framework CSS para diseño responsivo.

### Infraestructura
- **Docker**: Contenedorización de servicios (PHP-FPM, Nginx).
- **Docker Compose**: Orquestación de contenedores multi-servicio.
- **Nginx (Alpine)**: Servidor web y proxy inverso de alto rendimiento.
- **Traefik**: Proxy inverso y balanceador de carga con soporte para routing basado en rutas.

### CI/CD
- **GitHub Actions**: Automatización de despliegue continuo.
- **Self-hosted Runners**: Runners de Windows para despliegue en servidores internos.

### Utilidades
- **jsQR**: Librería JavaScript para lectura de códigos QR desde la cámara del dispositivo.
- **QR Code Generator (PHP)**: Generación de códigos QR en el servidor.

---

## Requisitos Previos

### Desarrollo Local

Para ejecutar el proyecto en un entorno local de desarrollo, asegúrate de contar con:

- **Docker Desktop** (Windows/macOS) o **Docker Engine** (Linux) versión 20.10 o superior.
- **Docker Compose** versión 2.0 o superior.
- **Git** para clonar el repositorio.
- **Editor de Código** (recomendado: VS Code, PHPStorm).

### Servidor de Producción (Windows Server)

Para despliegue en servidores de producción:

- **Windows Server 2019/2022** con Docker Engine instalado (NO Docker Desktop).
- **Microsoft SQL Server 2019+** con acceso de red configurado.
- **GitHub Actions Self-hosted Runners** configurados con labels específicos:
  - Runner TEST: `self-hosted`, `Windows`, `X64`, `test`, `comedor`
  - Runner PRODUCCIÓN: `self-hosted`, `Windows`, `X64`, `production`, `comedor`
- **Puertos disponibles**:
  - Producción: 80 (HTTP), 443 (HTTPS)
  - Test: 8080 (HTTP)

> **Nota**: Consulta el archivo [SETUP-SERVIDOR.md](./SETUP-SERVIDOR.md) para instrucciones detalladas de configuración del servidor.

---

## Estructura del Proyecto

```
SistemaComedor/
├── .github/
│   └── workflows/              # Pipelines de CI/CD
│       ├── deploy-production.yml
│       └── deploy-test.yml
├── api/                        # Cliente API unificado
│   └── Api.php                 # Facade para comunicación con backend Node.js
├── config/                     # Configuraciones centralizadas
│   └── database.php            # Funciones de conexión a bases de datos
├── deprecated/                 # Código legacy (no usar)
│   ├── ApiClient.php
│   ├── api_client.php
│   ├── config_api.php
│   ├── endpoint_helpers.php
│   └── token_manager.php
├── examples/                   # Ejemplos de uso
│   └── PedidosComedor_Backend.php
├── jsQR-master/                # Librería de lectura de QR
├── nginx/                      # Configuración de Nginx
│   ├── nginx.conf              # Configuración del servidor web
│   └── logs/                   # Logs de acceso y errores
├── php/                        # Configuración de PHP
│   └── custom.ini              # Configuraciones personalizadas (límites, timezone)
├── scripts/                    # Scripts de utilidades
│   ├── migrate-*.php           # Scripts de migración de DB
│   └── update-env-ports.sh     # Actualización de puertos en .env
├── tests/                      # Tests y pruebas
│   ├── test_api.php
│   └── test_api_unificada.php
├── .env.example                # Plantilla de variables de entorno
├── .gitignore                  # Archivos excluidos del control de versiones
├── Dockerfile                  # Imagen de PHP 8.2-FPM con extensión sqlsrv
├── docker-compose.yml          # Orquestación para PRODUCCIÓN
├── docker-compose.test.yml     # Orquestación para TEST
├── health.php                  # Endpoint de health check
├── README.md                   # Este archivo
├── SETUP-SERVIDOR.md           # Guía de configuración del servidor
├── CLAUDE.md                   # Instrucciones para Claude Code (no versionado)
│
├── Login2.php                  # Página de login principal
├── Menu.php                    # Menú principal del sistema
├── AgendaPedidos.php           # Módulo de agenda de pedidos
├── Menpedidos.php              # Gestión de pedidos del empleado
├── dchef.php                   # Dashboard de cocina
├── CocinaTotalPedidos.php      # Vista total de pedidos en cocina
├── FormatCancel.php            # Formulario de cancelación de pedidos
├── FormCanAprobUpdate.php      # Aprobación de cancelaciones
├── GenerarQR.php               # Generación de códigos QR
├── demolecturaQR.php           # Lectura y validación de QR
├── KPI_anacomp.php             # KPIs y análisis comparativo
├── Consultadedatos.php         # Consulta de datos históricos
├── Admiin.php                  # Panel de administración
├── gestusu.php                 # Gestión de usuarios
└── [otros archivos PHP]        # Módulos adicionales
```

### Descripción de Directorios Clave

- **`/api/`**: Contiene el cliente API unificado (`Api.php`) para comunicarse con el backend Node.js.
- **`/config/`**: Configuraciones centralizadas, especialmente conexiones a base de datos.
- **`/deprecated/`**: Código legacy que NO debe ser usado ni modificado. Mantener solo por compatibilidad histórica.
- **`/nginx/`**: Archivos de configuración del servidor web Nginx.
- **`/php/`**: Configuraciones personalizadas de PHP (memoria, timeouts, timezone).
- **`/scripts/`**: Utilidades de administración y migración.

---

## Configuración del Entorno

### Variables de Entorno

El sistema utiliza variables de entorno para configurar conexiones a bases de datos y puertos. **Nunca** se deben versionar archivos `.env` con credenciales reales.

#### Paso 1: Crear archivo `.env`

Copia el archivo de ejemplo:

```bash
cp .env.example .env
```

#### Paso 2: Editar variables

Abre `.env` y configura las variables según tu entorno:

```env
# ===========================================
# CONFIGURACIÓN DE ENTORNO
# ===========================================
APP_ENV=production              # Valores: production | test | development
HTTP_PORT=80                    # Puerto HTTP (80 para producción, 8080 para test)
HTTPS_PORT=443                  # Puerto HTTPS (solo producción)

# ===========================================
# BASE DE DATOS COMEDOR (Principal)
# ===========================================
DB_COMEDOR_SERVER=servidor-sql.ejemplo.com
DB_COMEDOR_DATABASE=Comedor
DB_COMEDOR_USERNAME=usuario_comedor
DB_COMEDOR_PASSWORD=contraseña_segura

# ===========================================
# BASE DE DATOS ALQUIMISTA
# ===========================================
DB_ALQUIMISTA_SERVER=servidor-sql.ejemplo.com
DB_ALQUIMISTA_DATABASE=Alquimista2024
DB_ALQUIMISTA_USERNAME=usuario_alquimista
DB_ALQUIMISTA_PASSWORD=contraseña_segura

# ===========================================
# BASE DE DATOS BASENEW
# ===========================================
DB_BASENEW_SERVER=servidor-sql.ejemplo.com
DB_BASENEW_DATABASE=BaseNueva
DB_BASENEW_USERNAME=usuario_basenew
DB_BASENEW_PASSWORD=contraseña_segura

# ===========================================
# BASE DE DATOS KPI
# ===========================================
DB_KPI_SERVER=servidor-sql.ejemplo.com
DB_KPI_DATABASE=KPI
DB_KPI_USERNAME=usuario_kpi
DB_KPI_PASSWORD=contraseña_segura

# ===========================================
# BASE DE DATOS TICKET
# ===========================================
DB_TICKET_SERVER=servidor-sql.ejemplo.com
DB_TICKET_DATABASE=Ticket
DB_TICKET_USERNAME=usuario_ticket
DB_TICKET_PASSWORD=contraseña_segura
```

> **Importante**: Reemplaza los valores de ejemplo con las credenciales reales de tu entorno.

### Configuración de Base de Datos

El sistema se conecta a **múltiples bases de datos** de Microsoft SQL Server. Utiliza el módulo centralizado `config/database.php` para todas las conexiones.

#### Funciones Disponibles

```php
require_once __DIR__ . '/config/database.php';

// Conexión a la base de datos principal (Comedor)
$conn = getComedorConnection();

// Conexión a Alquimista2024
$conn = getAlquimistaConnection();

// Conexión a BaseNueva
$conn = getBaseNuevaConnection();

// Conexión a KPI
$conn = getKpiConnection();

// Conexión a Ticket
$conn = getTicketConnection();

// Cerrar conexión (IMPORTANTE: siempre cerrar)
closeConnection($conn, $stmt);
```

#### Regla de Oro

**NUNCA** hardcodear credenciales de base de datos en archivos PHP. Siempre usar las funciones de `config/database.php` que leen variables de entorno.

---

## Instalación y Ejecución

### Entorno de Desarrollo Local

#### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-organizacion/SistemaComedor.git
cd SistemaComedor
```

#### 2. Configurar variables de entorno

```bash
cp .env.example .env
# Editar .env con tus credenciales de base de datos local
```

#### 3. Iniciar contenedores Docker

```bash
# Construir e iniciar contenedores en modo desarrollo
docker compose up --build -d
```

#### 4. Verificar el estado de los servicios

```bash
docker compose ps
```

Deberías ver dos contenedores en estado `healthy`:
- `comedor_php` (PHP-FPM)
- `comedor_nginx` (Nginx)

#### 5. Acceder a la aplicación

Abre tu navegador y visita:

```
http://localhost:80
```

> **Nota**: Si configuraste un puerto diferente en `.env` (ej: `HTTP_PORT=8000`), usa ese puerto.

#### 6. Ver logs en tiempo real

```bash
# Logs de todos los servicios
docker compose logs -f

# Logs solo de PHP
docker compose logs -f php

# Logs solo de Nginx
docker compose logs -f nginx
```

#### 7. Detener contenedores

```bash
docker compose down
```

---

### Entorno de Pruebas (TEST)

El entorno de TEST utiliza un archivo Docker Compose separado y contenedores con nombres distintos para evitar conflictos con producción.

#### 1. Configurar `.env` para TEST

Asegúrate de que las variables de TEST estén configuradas en `.env`:

```env
APP_ENV=test
HTTP_PORT_TEST=8080
DB_HOST_TEST=servidor-test.ejemplo.com
DB_DATABASE_TEST=Comedor_Test
DB_USERNAME_TEST=usuario_test
DB_PASSWORD_TEST=contraseña_test
```

#### 2. Iniciar entorno de TEST

```bash
docker compose -f docker-compose.test.yml up --build -d
```

#### 3. Verificar servicios

```bash
docker compose -f docker-compose.test.yml ps
```

Contenedores:
- `comedor_php_test`
- `comedor_nginx_test`

#### 4. Acceder a TEST

```
http://localhost:8080
```

#### 5. Detener TEST

```bash
docker compose -f docker-compose.test.yml down
```

---

### Entorno de Producción

El despliegue en producción se realiza **automáticamente** mediante GitHub Actions cuando se hace push a la rama `main`.

#### Despliegue Manual (si es necesario)

Si necesitas desplegar manualmente en el servidor de producción:

```bash
# En el servidor Windows, navegar al directorio de despliegue
cd C:\deploy\ComedorProduccion

# Detener contenedores actuales
docker compose down

# Actualizar código (git pull o copiar archivos)
git pull origin main

# Reconstruir e iniciar
docker compose up --build -d

# Verificar logs
docker compose logs -f
```

#### Health Check

El sistema incluye un endpoint de verificación de salud:

```
http://tu-servidor/health.php
```

Este endpoint es usado por los health checks de Docker y Traefik.

---

## Arquitectura del Sistema

### Módulos Principales

El sistema está organizado en módulos funcionales:

#### 1. **Autenticación y Sesiones**
- **Archivos**: `Login2.php`, `LoginFormCancel.php`, `LoginValidarOrdenes.php`
- **Funcionalidad**: Login con regeneración de sesión, validación de roles, fingerprinting de navegador.

#### 2. **Gestión de Pedidos**
- **Archivos**: `AgendaPedidos.php`, `Menpedidos.php`, `aparta_consumo_modificado.php`
- **Funcionalidad**: Creación, modificación y consulta de pedidos. Integración con API de backend.

#### 3. **Operaciones de Cocina**
- **Archivos**: `dchef.php`, `CocinaTotalPedidos.php`, `MenComprasCocina.php`
- **Funcionalidad**: Dashboard de cocina, visualización de pedidos por fecha, lista de compras.

#### 4. **Cancelaciones**
- **Archivos**: `FormatCancel.php`, `FormCanAprobUpdate.php`, `check_pending_cancelations.php`
- **Funcionalidad**: Solicitud de cancelaciones, aprobación por dirección, notificaciones.

#### 5. **Códigos QR**
- **Archivos**: `GenerarQR.php`, `demolecturaQR.php`
- **Funcionalidad**: Generación de QR para check-in, lectura desde cámara, registro de asistencia.

#### 6. **Reportes y KPIs**
- **Archivos**: `KPI_anacomp.php`, `Consultadedatos.php`, `Desglosechecador.php`
- **Funcionalidad**: Dashboards con ECharts, análisis de tendencias, exportación de datos.

#### 7. **Administración**
- **Archivos**: `Admiin.php`, `gestusu.php`
- **Funcionalidad**: Gestión de usuarios, configuración de permisos, auditoría.

### Patrón de Conexión a Base de Datos

Todas las conexiones a SQL Server siguen el patrón centralizado:

```php
<?php
require_once __DIR__ . '/config/database.php';

// Obtener conexión
$conn = getComedorConnection();

// Verificar conexión
if (!$conn) {
    die("Error de conexión: " . print_r(sqlsrv_errors(), true));
}

// Preparar consulta
$sql = "SELECT * FROM Pedidos WHERE UsuarioID = ?";
$params = [$usuarioId];
$stmt = sqlsrv_query($conn, $sql, $params);

// Verificar ejecución
if (!$stmt) {
    die("Error en consulta: " . print_r(sqlsrv_errors(), true));
}

// Procesar resultados
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Procesar fila
}

// IMPORTANTE: Cerrar conexión
closeConnection($conn, $stmt);
?>
```

**Buenas prácticas**:
- Siempre usar consultas preparadas (`?` placeholders) para prevenir SQL Injection.
- Cerrar explícitamente conexiones y statements con `closeConnection()`.
- Manejar errores con `sqlsrv_errors()`.

### Sistema de Autenticación

#### Patrón de Login

```php
<?php
session_start();

// Después de validar credenciales:
session_regenerate_id(true); // Previene session fixation

$_SESSION['user_id'] = $userData['ID'];
$_SESSION['logged_in'] = true;
$_SESSION['username'] = $userData['Usuario'];
$_SESSION['Area'] = $userData['Area'];
$_SESSION['LOGIN_TIME'] = time();
$_SESSION['browser_fingerprint'] = md5(
    $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']
);
?>
```

#### Control de Acceso por Roles

El sistema utiliza el campo `$_SESSION['Area']` para determinar permisos:

- **`DIRECCIÓN`**: Acceso completo, aprobación de cancelaciones.
- **`COCINA`**: Visualización de pedidos, gestión de compras.
- **`RECURSOS HUMANOS`**: Gestión de usuarios, reportes de personal.
- **`EMPLEADO`**: Creación y consulta de pedidos propios.

### Cliente API Unificado

El archivo `/api/Api.php` proporciona un facade para comunicarse con el backend Node.js:

```php
<?php
require_once __DIR__ . '/api/Api.php';

// Endpoint de autenticación
$response = Api::auth()->login($username, $password);

// Endpoint de pedidos
$misPedidos = Api::pedidos()->misPedidos();
$crearPedido = Api::pedidos()->crear($datosPedido);

// Endpoint de cancelaciones
$cancelar = Api::cancelaciones()->solicitar($pedidoId, $motivo);
?>
```

**Auto-detección de entorno**:
- `LOCAL` → `http://localhost:3000`
- `DESARROLLO` → `http://desarollo-bacros:3000`
- `PRODUCCION` → `http://host.docker.internal:3000`

---

## CI/CD y Despliegue

El proyecto utiliza GitHub Actions para despliegue continuo:

### Workflows Configurados

#### 1. **Deploy TEST** (`.github/workflows/deploy-test.yml`)
- **Trigger**: Push a rama `dev`
- **Runner**: `self-hosted`, `Windows`, `X64`, `test`
- **Destino**: `C:\deploy\ComedorTest`
- **Puerto**: 8080
- **Contenedores**: `comedor_php_test`, `comedor_nginx_test`

#### 2. **Deploy PRODUCCIÓN** (`.github/workflows/deploy-production.yml`)
- **Trigger**: Push a rama `main`
- **Runner**: `self-hosted`, `Windows`, `X64`, `production`
- **Destino**: `C:\deploy\ComedorProduccion`
- **Puerto**: 80
- **Contenedores**: `comedor_php`, `comedor_nginx`

### Flujo de Despliegue

```
git push origin dev → GitHub Actions → Runner TEST → Deploy en ComedorTest (puerto 8080)
git push origin main → GitHub Actions → Runner PRODUCCIÓN → Deploy en ComedorProduccion (puerto 80)
```

### Requisitos del Servidor

- Archivo `.env` debe existir previamente en `C:\deploy\ComedorProduccion\.env` (nunca se sobrescribe).
- Docker Engine debe estar corriendo como servicio de Windows.
- Runners deben estar activos y en estado "Idle".

---

## Buenas Prácticas

### Desarrollo

1. **Nunca** hardcodear credenciales. Usar siempre variables de entorno.
2. **Siempre** cerrar conexiones a base de datos con `closeConnection()`.
3. **Usar** consultas preparadas para prevenir SQL Injection.
4. **Validar** entradas del usuario en el lado del servidor (no confiar en validación JS).
5. **Regenerar** ID de sesión después del login (`session_regenerate_id(true)`).
6. **Evitar** modificar archivos en `/deprecated/`. Usar `/api/Api.php` en su lugar.
7. **Documentar** cambios importantes en comentarios del código.
8. **Seguir** la estructura de carpetas existente.

### Seguridad

1. **Headers de seguridad** se configuran en `nginx/nginx.conf`:
   ```nginx
   add_header X-Content-Type-Options "nosniff" always;
   add_header X-Frame-Options "SAMEORIGIN" always;
   add_header X-XSS-Protection "1; mode=block" always;
   ```

2. **No exponer** información sensible en mensajes de error.
3. **Sanitizar** salidas HTML para prevenir XSS.
4. **Limitar** intentos de login (implementar rate limiting si aplica).
5. **Logs**: No registrar contraseñas ni datos sensibles.

### Git

1. **No commitear** archivos `.env` con credenciales reales.
2. **Revisar** `.gitignore` antes de agregar archivos nuevos.
3. **Mensajes de commit** descriptivos y en español.
4. **Pull requests** para cambios críticos en producción.

---

## Solución de Problemas

### Error: "No se puede conectar al daemon de Docker"

**Causa**: Docker Desktop no está corriendo o el servicio Docker Engine está detenido.

**Solución**:

```bash
# Linux/macOS
sudo systemctl start docker
sudo systemctl enable docker

# Windows (PowerShell como Administrador)
Start-Service docker
Set-Service docker -StartupType Automatic
```

### Error: "Port already in use"

**Causa**: El puerto configurado ya está en uso por otro proceso.

**Solución**:

```bash
# Linux/macOS: Ver qué está usando el puerto 80
sudo lsof -i :80

# Windows: Ver qué está usando el puerto 80
netstat -ano | findstr :80

# Cambiar puerto en .env
HTTP_PORT=8000
```

### Error: "sqlsrv_connect() failed"

**Causa**: No se puede conectar a SQL Server.

**Verificar**:
1. Credenciales en `.env` son correctas.
2. SQL Server está en ejecución.
3. Firewall permite conexiones en puerto 1433.
4. SQL Server está configurado para aceptar conexiones TCP/IP.

### Contenedor en estado "unhealthy"

**Causa**: El health check está fallando.

**Diagnóstico**:

```bash
# Ver logs del contenedor
docker compose logs php

# Ejecutar health check manualmente
docker exec comedor_php php-fpm-healthcheck

# Ver estado detallado
docker inspect comedor_php | grep -A 10 Health
```

### Sesión expirada constantemente

**Causa**: Configuración de sesiones de PHP.

**Solución**: Editar `php/custom.ini`:

```ini
session.gc_maxlifetime = 3600
session.cookie_lifetime = 0
```

---

## Documentación Adicional

- **[SETUP-SERVIDOR.md](./SETUP-SERVIDOR.md)**: Guía completa de configuración del servidor Windows para TEST y PRODUCCIÓN.
- **[.env.example](./.env.example)**: Plantilla de variables de entorno con todas las opciones disponibles.
- **[config/database.php](./config/database.php)**: Código fuente de las funciones de conexión a base de datos.
- **[api/Api.php](./api/Api.php)**: Cliente API unificado para comunicación con backend Node.js.

---

## Seguridad

### Reporte de Vulnerabilidades

Si encuentras una vulnerabilidad de seguridad, **NO** abras un issue público. Envía un correo a:

📧 **seguridad@bacrocorp.com**

### Auditorías de Seguridad

- Revisión de código antes de merge a `main`.
- Análisis de dependencias (aunque este proyecto no usa Composer, revisar librerías JS).
- Pruebas de penetración periódicas en entorno de producción.

---

## Licencia

Este proyecto es propiedad de **BacroCorp** y es de uso interno exclusivo. Todos los derechos reservados.

**No** está permitido:
- Distribución fuera de la organización.
- Uso comercial externo.
- Modificación sin autorización del equipo de desarrollo.

---

**Desarrollado por el equipo de Tecnología de BacroCorp**

Para soporte técnico, contacta a: **soporte@bacrocorp.com**

---

**Última actualización**: 2026-02-18
