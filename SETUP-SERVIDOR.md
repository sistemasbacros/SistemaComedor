# Configuración del Servidor para Despliegue TEST y PRODUCCIÓN

Este documento describe cómo configurar el servidor Windows para ejecutar tanto el ambiente de TEST como PRODUCCIÓN en la misma máquina.

## ⚠️ PROBLEMA COMÚN: Docker Desktop vs Docker Engine

**IMPORTANTE**: Si ves este error:
```
ERROR: No se puede conectar al daemon de Docker
Docker Compose version v2.40.3-desktop.1
```

Significa que tienes **Docker Desktop** instalado pero NO está corriendo.

### Opciones:

**OPCIÓN A - Usar Docker Desktop (más simple pero no ideal para servidores)**:
1. Abre Docker Desktop manualmente
2. Espera a que inicie completamente (ícono verde en la bandeja)
3. Los runners de GitHub Actions podrán usarlo MIENTRAS la app esté abierta
4. **PROBLEMA**: Si la app se cierra, los despliegues fallarán

**OPCIÓN B - Instalar Docker Engine (RECOMENDADO para servidores)**:
1. Desinstala Docker Desktop completamente
2. Sigue las instrucciones de "Pre-requisitos del Servidor" abajo
3. Docker Engine corre como servicio de Windows
4. **VENTAJA**: Funciona 24/7 sin necesidad de tener una app abierta

## 📋 Pre-requisitos del Servidor

### 1. Docker Engine para Windows Server

El servidor **DEBE** tener Docker Engine instalado (NO Docker Desktop). Docker Desktop usa un subsistema Linux que no funciona correctamente con GitHub Actions runners self-hosted.

#### Instalación de Docker Engine en Windows Server:

```powershell
# Instalar módulo de DockerMsftProvider
Install-Module -Name DockerMsftProvider -Repository PSGallery -Force

# Instalar Docker
Install-Package -Name docker -ProviderName DockerMsftProvider -Force

# Iniciar el servicio Docker
Start-Service docker

# Configurar inicio automático
Set-Service docker -StartupType Automatic

# Verificar instalación
docker --version
docker info
```

#### Instalar Docker Compose Plugin:

```powershell
# Descargar Docker Compose v2
$composeVersion = "v2.24.5"
$composeUrl = "https://github.com/docker/compose/releases/download/$composeVersion/docker-compose-windows-x86_64.exe"
$composePath = "$env:ProgramFiles\Docker\cli-plugins"

# Crear directorio si no existe
New-Item -ItemType Directory -Path $composePath -Force

# Descargar
Invoke-WebRequest -Uri $composeUrl -OutFile "$composePath\docker-compose.exe"

# Verificar
docker compose version
```

### 2. GitHub Actions Runner

Debes tener **DOS runners** configurados en el mismo servidor con diferentes labels:

#### Runner para TEST:
- Labels: `self-hosted`, `Windows`, `X64`, `test`, `comedor`
- Directorio: `C:\actions-runners\actions-runner-comedor-test`

#### Runner para PRODUCCIÓN:
- Labels: `self-hosted`, `Windows`, `X64`, `production`, `comedor`
- Directorio: `C:\actions-runners\actions-runner-comedor-production`

#### Instalación de runners:

```powershell
# Crear directorios
New-Item -Path "C:\actions-runners" -ItemType Directory -Force
cd C:\actions-runners

# TEST Runner
mkdir actions-runner-comedor-test
cd actions-runner-comedor-test
# Descargar desde GitHub: Settings > Actions > Runners > New self-hosted runner
# Agregar labels: test,comedor durante la configuración

# PRODUCTION Runner
cd C:\actions-runners
mkdir actions-runner-comedor-production
cd actions-runner-comedor-production
# Descargar desde GitHub: Settings > Actions > Runners > New self-hosted runner
# Agregar labels: production,comedor durante la configuración
```

### 3. Estructura de Directorios

Los directorios de despliegue deben existir:

```powershell
# Crear directorios de despliegue
New-Item -Path "C:\deploy\ComedorTest" -ItemType Directory -Force
New-Item -Path "C:\deploy\ComedorProduccion" -ItemType Directory -Force
```

### 4. Archivos .env

#### TEST: `C:\deploy\ComedorTest\.env`
```env
# Puerto HTTP para TEST (no debe colisionar con producción)
HTTP_PORT_TEST=8080

# Base de datos TEST
DB_HOST_TEST=servidor-sql-test
DB_PORT_TEST=1433
DB_DATABASE_TEST=comedor_test
DB_USERNAME_TEST=usuario_test
DB_PASSWORD_TEST=password_test
```

#### PRODUCCIÓN: `C:\deploy\ComedorProduccion\.env`
```env
# Puerto HTTP para PRODUCCIÓN
HTTP_PORT=80
HTTPS_PORT=443

# Base de datos PRODUCCIÓN
DB_HOST=servidor-sql-prod
DB_PORT=1433
DB_DATABASE=comedor_produccion
DB_USERNAME=usuario_prod
DB_PASSWORD=password_prod
```

## 🔧 Verificación Pre-Despliegue

He creado un script de verificación que puedes ejecutar antes de hacer el despliegue:

```powershell
# Ejecutar desde el directorio del proyecto
.\verify-server-setup.ps1
```

Este script verificará:
- ✅ Docker Engine está instalado y corriendo
- ✅ Docker Compose está disponible
- ✅ Directorios de despliegue existen
- ✅ Archivos .env están configurados
- ✅ Puertos no están en conflicto
- ✅ Runners están corriendo

## 🚀 Proceso de Despliegue

### TEST (rama `dev`)
1. Haz push a la rama `dev`
2. El runner con label `test` ejecutará el workflow
3. Se desplegará en `C:\deploy\ComedorTest`
4. Los contenedores serán:
   - `comedor_php_test`
   - `comedor_nginx_test`
5. Puerto: **8080** (por defecto)
6. Red: `comedor_network_test`

### PRODUCCIÓN (rama `main`)
1. Haz push a la rama `main`
2. El runner con label `production` ejecutará el workflow
3. Se desplegará en `C:\deploy\ComedorProduccion`
4. Los contenedores serán:
   - `comedor_php`
   - `comedor_nginx`
5. Puerto: **80** (por defecto)
6. Red: `comedor_network`

## 🔍 Solución de Problemas

### Error: "Falta la cadena en el terminador" en PowerShell

**Causa**: Error de sintaxis en el script de PowerShell.

**Estado**: ✅ **YA CORREGIDO** en todos los workflows

### Error: "No se puede conectar al daemon de Docker"

**Si tienes Docker Desktop** (`docker compose version` muestra "desktop"):
```powershell
# Verificar si Docker Desktop está corriendo
Get-Process "Docker Desktop" -ErrorAction SilentlyContinue

# Si no está corriendo, ábrelo manualmente o instala Docker Engine
```

**Si tienes Docker Engine**:
```powershell
# Verificar el servicio
Get-Service docker

# Si está detenido, iniciarlo
Start-Service docker

# Configurar inicio automático
Set-Service docker -StartupType Automatic

# Verificar que funciona
docker ps
```

### Error: "Process completed with exit code 1" en build

**Verificar**:
```powershell
# ¿Docker está corriendo?
docker ps

# Si Docker Desktop, asegúrate de que la aplicación está abierta
# Si Docker Engine, verifica el servicio
Get-Service docker

# Si está detenido, iniciarlo
Start-Service docker

# Ver logs de un contenedor específico
docker logs comedor_php_test
```

### Conflicto de Puertos

Si tienes error de puertos en uso:

```powershell
# Ver qué está usando el puerto 8080 (TEST)
netstat -ano | findstr :8080

# Ver qué está usando el puerto 80 (PRODUCCIÓN)
netstat -ano | findstr :80

# Cambiar puerto en el archivo .env correspondiente
```

### Ver Logs de los Contenedores

```powershell
# TEST
cd C:\deploy\ComedorTest
docker compose -f docker-compose.test.yml logs --tail=100 -f

# PRODUCCIÓN
cd C:\deploy\ComedorProduccion
docker compose -f docker-compose.yml logs --tail=100 -f
```

## 📊 Comandos Útiles

```powershell
# Ver todos los contenedores de Comedor
docker ps -a --filter "name=comedor_"

# Ver solo contenedores de TEST
docker ps -a --filter "name=comedor_*_test"

# Ver solo contenedores de PRODUCCIÓN (excluye test)
docker ps -a --filter "name=comedor_" | Where-Object { $_ -notmatch "test" }

# Reiniciar TEST
cd C:\deploy\ComedorTest
docker compose -f docker-compose.test.yml restart

# Reiniciar PRODUCCIÓN
cd C:\deploy\ComedorProduccion
docker compose -f docker-compose.yml restart

# Limpiar imágenes antiguas
docker image prune -f
docker system prune -f
```

## 🔒 Seguridad

- Los archivos `.env` **NUNCA** deben estar en el repositorio
- Cada ambiente (TEST y PRODUCCIÓN) debe tener credenciales diferentes
- Los puertos deben estar configurados en el firewall correctamente
- Solo el puerto de PRODUCCIÓN (80/443) debe ser accesible externamente
- El puerto de TEST (8080) debe ser solo interno

## 🎯 Resumen de Correcciones Aplicadas

### ✅ Correcciones Completadas:

1. **Error de sintaxis PowerShell** - CORREGIDO
   - Eliminados `$()` en variables dentro de strings de Write-Host
   - Aplicado a todos los 7 proyectos (14 workflows)

2. **Verificación de Docker mejorada** - IMPLEMENTADO
   - Detecta Docker Desktop vs Docker Engine
   - Intenta iniciar el servicio automáticamente
   - Mejor diagnóstico de errores

3. **Manejo de errores mejorado** - IMPLEMENTADO
   - Limpieza forzada de contenedores si falla `docker compose down`
   - Verificación del Dockerfile antes de construir
   - Mensajes de error más claros

## 📞 Próximos Pasos

1. **Decisión Docker**:
   - Si usas Docker Desktop: Ábrelo antes de cada despliegue
   - **RECOMENDADO**: Instala Docker Engine para servidores

2. **Verificar setup**:
   ```powershell
   cd Comedor
   .\verify-server-setup.ps1
   ```

3. **Configurar .env** en cada directorio de despliegue

4. **Probar despliegue**:
   - Push a `dev` para TEST
   - Push a `main` para PRODUCCIÓN

Si tienes problemas:
1. Ejecuta `.\verify-server-setup.ps1` para diagnóstico
2. Revisa los logs del workflow en GitHub Actions
3. Verifica los logs de contenedores con `docker compose logs`
