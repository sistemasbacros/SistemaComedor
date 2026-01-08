# 🤖 Guía de Configuración de GitHub Self-Hosted Runners

Esta guía te ayudará a configurar GitHub Runners en tus servidores Windows para automatizar el deployment cada vez que subas cambios a GitHub.

## 📋 Tabla de Contenidos

- [¿Qué es un GitHub Runner?](#qué-es-un-github-runner)
- [Requisitos Previos](#requisitos-previos)
- [Configuración del Servidor de TEST](#configuración-del-servidor-de-test)
- [Configuración del Servidor de PRODUCCIÓN](#configuración-del-servidor-de-producción)
- [Verificación](#verificación)
- [Troubleshooting](#troubleshooting)

## 🤔 ¿Qué es un GitHub Runner?

Un GitHub Runner es un servidor que ejecuta tus workflows de GitHub Actions. En lugar de usar los runners compartidos de GitHub (que son Linux), usaremos **self-hosted runners** en tus propios servidores Windows.

**Beneficios:**
- ✅ Acceso directo a tus servidores
- ✅ No hay límites de minutos
- ✅ Puedes acceder a recursos locales (bases de datos, archivos, etc.)
- ✅ Compatible con Windows y Docker

## 📦 Requisitos Previos

Antes de comenzar, asegúrate de tener en cada servidor:

### Software Necesario:
- ✅ Windows 10/11 o Windows Server 2019/2022
- ✅ PowerShell 5.1 o superior
- ✅ [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado
- ✅ Git instalado
- ✅ Permisos de administrador
- ✅ Acceso a Internet

### Verificar Requisitos:

```powershell
# Verificar versión de PowerShell
$PSVersionTable.PSVersion

# Verificar Docker
docker --version
docker compose version

# Verificar Git
git --version
```

## 🧪 Configuración del Servidor de TEST

### Paso 1: Acceder a la Configuración de Runners

1. Ve a tu repositorio en GitHub
2. Click en **Settings** (Configuración)
3. En el menú lateral, click en **Actions** → **Runners**
4. Click en **New self-hosted runner**
5. Selecciona **Windows** como sistema operativo

### Paso 2: Descargar el Runner (en tu servidor de TEST)

Abre PowerShell como **Administrador** y ejecuta:

```powershell
# Crear directorio para el runner
New-Item -Path "C:\actions-runner-comedor-test" -ItemType Directory -Force
cd C:\actions-runner-comedor-test

# Descargar el runner (GitHub te mostrará el comando actualizado)
# Ejemplo (usa el comando que GitHub te muestre):
Invoke-WebRequest -Uri https://github.com/actions/runner/releases/download/v2.311.0/actions-runner-win-x64-2.311.0.zip -OutFile actions-runner-win-x64-2.311.0.zip

# Extraer
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::ExtractToDirectory("$PWD/actions-runner-win-x64-2.311.0.zip", "$PWD")
```

### Paso 3: Configurar el Runner para TEST

```powershell
# Ejecutar la configuración (GitHub te dará el comando con tu token)
.\config.cmd --url https://github.com/sistemasbacros/SistemaComedor --token TU_TOKEN_AQUI
```

Cuando te pregunte:

1. **Enter the name of the runner group** → Presiona Enter (usa default)
2. **Enter the name of runner** → Escribe: `comedor-test-runner`
3. **Enter any additional labels** → Escribe: `test,windows` (MUY IMPORTANTE)
4. **Enter name of work folder** → Presiona Enter (usa _work)

### Paso 4: Instalar como Servicio de Windows (TEST)

```powershell
# Instalar como servicio
.\svc.install.ps1

# Iniciar el servicio
.\svc.start.ps1

# Verificar que está corriendo
Get-Service actions.runner.*
```

### Paso 5: Verificar Configuración de TEST

```powershell
# Ver el estado del servicio
.\svc.status.ps1

# Ver los labels del runner
Get-Content .runner
```

Deberías ver que el runner tiene los labels: `self-hosted`, `Windows`, `X64`, `test`, `windows`

## 🏭 Configuración del Servidor de PRODUCCIÓN

Repite los mismos pasos en tu servidor de PRODUCCIÓN, pero con estos cambios:

### Diferencias Importantes:

```powershell
# Paso 2: Usar un directorio diferente
New-Item -Path "C:\actions-runner-comedor-production" -ItemType Directory -Force
cd C:\actions-runner-comedor-production

# Paso 3: Al configurar, usa estos valores:
# - Name of runner: comedor-production-runner
# - Additional labels: production,windows (MUY IMPORTANTE)
```

## 🔄 Configuración Completa Paso a Paso

### Para el Servidor de TEST:

```powershell
# ============================================
# EJECUTAR EN SERVIDOR DE TEST
# ============================================

# 1. Crear directorio
New-Item -Path "C:\actions-runner-comedor-test" -ItemType Directory -Force
cd C:\actions-runner-comedor-test

# 2. Descargar runner (copia el comando de GitHub)
# El comando será algo como:
Invoke-WebRequest -Uri https://github.com/actions/runner/releases/download/vX.XXX.X/actions-runner-win-x64-X.XXX.X.zip -OutFile actions-runner.zip

# 3. Extraer
Expand-Archive -Path actions-runner.zip -DestinationPath . -Force

# 4. Configurar (usa el comando con token que GitHub te dé)
.\config.cmd --url https://github.com/sistemasbacros/SistemaComedor --token TU_TOKEN --labels test,windows --name comedor-test-runner

# 5. Instalar como servicio
.\svc.install.ps1

# 6. Iniciar servicio
.\svc.start.ps1

# 7. Verificar
Get-Service actions.runner.*
```

### Para el Servidor de PRODUCCIÓN:

```powershell
# ============================================
# EJECUTAR EN SERVIDOR DE PRODUCCIÓN
# ============================================

# 1. Crear directorio
New-Item -Path "C:\actions-runner-comedor-production" -ItemType Directory -Force
cd C:\actions-runner-comedor-production

# 2. Descargar runner (copia el comando de GitHub)
Invoke-WebRequest -Uri https://github.com/actions/runner/releases/download/vX.XXX.X/actions-runner-win-x64-X.XXX.X.zip -OutFile actions-runner.zip

# 3. Extraer
Expand-Archive -Path actions-runner.zip -DestinationPath . -Force

# 4. Configurar (usa el comando con token que GitHub te dé)
.\config.cmd --url https://github.com/sistemasbacros/SistemaComedor --token TU_TOKEN --labels production,windows --name comedor-production-runner

# 5. Instalar como servicio
.\svc.install.ps1

# 6. Iniciar servicio
.\svc.start.ps1

# 7. Verificar
Get-Service actions.runner.*
```

## ✅ Verificación

### Verificar en GitHub:

1. Ve a tu repositorio → **Settings** → **Actions** → **Runners**
2. Deberías ver tus runners con estado **Idle** (verde)
3. Verifica que cada runner tenga los labels correctos:
   - Runner de TEST: `self-hosted`, `Windows`, `X64`, `test`, `windows`
   - Runner de PRODUCCIÓN: `self-hosted`, `Windows`, `X64`, `production`, `windows`

### Verificar en el Servidor:

```powershell
# Ver estado del servicio
Get-Service actions.runner.* | Select-Object Name, Status, DisplayName

# Ver procesos del runner
Get-Process | Where-Object { $_.Name -like "*Runner*" }

# Ver logs del runner (TEST)
Get-Content "C:\actions-runner-comedor-test\_diag\Runner_*.log" -Tail 20

# Ver logs del runner (PRODUCCIÓN)
Get-Content "C:\actions-runner-comedor-production\_diag\Runner_*.log" -Tail 20
```

## 🧪 Probar el Deployment Automático

### Test de Deployment a TEST:

```bash
# En tu máquina local
git checkout develop
git pull

# Hacer un cambio pequeño
echo "<!-- Test -->" >> index.php

# Commit y push
git add .
git commit -m "Test: Verificar deployment automático"
git push origin develop
```

### Observar el Deployment:

1. Ve a tu repositorio en GitHub
2. Click en **Actions**
3. Deberías ver el workflow "Deploy to Test Server" ejecutándose
4. Click en el workflow para ver los logs en tiempo real

### Test de Deployment a PRODUCCIÓN:

```bash
# En tu máquina local
git checkout main
git merge develop
git push origin main
```

Observa el workflow "Deploy to Production Server" en GitHub Actions.

## 🔧 Comandos Útiles para Administrar Runners

### Iniciar/Detener Servicio:

```powershell
# Detener runner
cd C:\actions-runner-comedor-test  # o C:\actions-runner-comedor-production
.\svc.stop.ps1

# Iniciar runner
.\svc.start.ps1

# Reiniciar runner
.\svc.stop.ps1
.\svc.start.ps1

# Ver estado
.\svc.status.ps1
```

### Desinstalar Runner:

```powershell
# Detener servicio
.\svc.stop.ps1

# Desinstalar servicio
.\svc.uninstall.ps1

# Quitar configuración
.\config.cmd remove --token TU_TOKEN
```

### Actualizar Runner:

```powershell
# Detener servicio
.\svc.stop.ps1

# Descargar nueva versión
Invoke-WebRequest -Uri https://github.com/actions/runner/releases/download/vX.XXX.X/actions-runner-win-x64-X.XXX.X.zip -OutFile actions-runner-new.zip

# Extraer (sobrescribir archivos)
Expand-Archive -Path actions-runner-new.zip -DestinationPath . -Force

# Iniciar servicio
.\svc.start.ps1
```

## 🚨 Troubleshooting

### El Runner no aparece en GitHub

**Solución:**
```powershell
# Verificar que el servicio está corriendo
Get-Service actions.runner.*

# Si no está corriendo, iniciarlo
cd C:\actions-runner-comedor-test  # o comedor-production
.\svc.start.ps1

# Ver logs
Get-Content _diag\Runner_*.log -Tail 50
```

### Error: "Unable to connect to GitHub"

**Causas comunes:**
- Firewall bloqueando conexión
- Proxy no configurado
- Internet no disponible

**Solución:**
```powershell
# Verificar conectividad
Test-NetConnection -ComputerName github.com -Port 443

# Si usas proxy, configurarlo
.\config.cmd remove
.\config.cmd --url ... --token ... --proxyurl http://proxy:port
```

### El Workflow no se ejecuta

**Verificar:**
1. Que el runner tenga los labels correctos
2. Que el workflow tenga el label correcto en `runs-on`

```yaml
# Para TEST
runs-on: [self-hosted, Windows, X64, test]

# Para PRODUCCIÓN
runs-on: [self-hosted, Windows, X64, production]
```

### Docker no está disponible en el Runner

**Solución:**
```powershell
# Agregar Docker al PATH del sistema
$env:PATH += ";C:\Program Files\Docker\Docker\resources\bin"

# O reiniciar el servicio del runner después de instalar Docker
cd C:\actions-runner-comedor-test
.\svc.stop.ps1
.\svc.start.ps1
```

### El Deployment falla con error de permisos

**Solución:**
```powershell
# El servicio debe correr con una cuenta que tenga permisos
# Detener servicio
cd C:\actions-runner-comedor-test
.\svc.stop.ps1
.\svc.uninstall.ps1

# Reinstalar con cuenta específica
.\svc.install.ps1 --user "DOMAIN\Usuario" --password "Password"
.\svc.start.ps1
```

### Ver logs en tiempo real

```powershell
# Logs del runner
Get-Content "C:\actions-runner-comedor-test\_diag\Worker_*.log" -Wait

# Logs del deployment
Get-Content "C:\deploy\ComedorTest\nginx\logs\error.log" -Wait
```

## 📊 Monitoreo de Runners

### Script para verificar estado de todos los runners:

```powershell
# check-runners.ps1
Write-Host "Estado de GitHub Runners:" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan

# Runner de TEST
if (Test-Path "C:\actions-runner-comedor-test") {
    cd C:\actions-runner-comedor-test
    Write-Host "`nRunner de TEST:" -ForegroundColor Yellow
    .\svc.status.ps1
    $testService = Get-Service actions.runner.* -ErrorAction SilentlyContinue
    if ($testService) {
        Write-Host "Estado: $($testService.Status)" -ForegroundColor $(if ($testService.Status -eq 'Running') { 'Green' } else { 'Red' })
    }
}

# Runner de PRODUCCIÓN
if (Test-Path "C:\actions-runner-comedor-production") {
    cd C:\actions-runner-comedor-production
    Write-Host "`nRunner de PRODUCCIÓN:" -ForegroundColor Yellow
    .\svc.status.ps1
    $prodService = Get-Service actions.runner.* -ErrorAction SilentlyContinue
    if ($prodService) {
        Write-Host "Estado: $($prodService.Status)" -ForegroundColor $(if ($prodService.Status -eq 'Running') { 'Green' } else { 'Red' })
    }
}

Write-Host "`n======================================" -ForegroundColor Cyan
```

## 🔄 Flujo Completo de Trabajo

### 1. Desarrollo Local:
```bash
git checkout develop
# ... hacer cambios ...
git add .
git commit -m "Nueva funcionalidad"
git push origin develop
```

### 2. Deploy Automático a TEST:
- GitHub Actions detecta el push a `develop`
- Ejecuta el workflow en el runner de TEST
- Despliega en `C:\deploy\ComedorTest`
- Aplica disponible en `http://servidor-test:8080`

### 3. Pruebas en TEST:
- Verificar que todo funciona correctamente
- Hacer ajustes si es necesario (repetir paso 1)

### 4. Deploy a PRODUCCIÓN:
```bash
git checkout main
git merge develop
git push origin main
```

### 5. Deploy Automático a PRODUCCIÓN:
- GitHub Actions detecta el push a `main`
- Crea backup automático
- Ejecuta el workflow en el runner de PRODUCCIÓN
- Despliega en `C:\deploy\ComedorProduccion`
- App disponible en `http://servidor-produccion:80`

## 📝 Checklist de Configuración

### Servidor de TEST:
- [ ] Docker Desktop instalado y corriendo
- [ ] Git instalado
- [ ] Runner descargado en `C:\actions-runner-comedor-test`
- [ ] Runner configurado con label `test`
- [ ] Servicio instalado y corriendo
- [ ] Runner visible en GitHub (estado Idle/verde)
- [ ] Archivo `.env` configurado en `C:\deploy\ComedorTest`
- [ ] Test de deployment exitoso

### Servidor de PRODUCCIÓN:
- [ ] Docker Desktop instalado y corriendo
- [ ] Git instalado
- [ ] Runner descargado en `C:\actions-runner-comedor-production`
- [ ] Runner configurado con label `production`
- [ ] Servicio instalado y corriendo
- [ ] Runner visible en GitHub (estado Idle/verde)
- [ ] Archivo `.env` configurado en `C:\deploy\ComedorProduccion`
- [ ] Test de deployment exitoso

## 🎯 Próximos Pasos

Una vez configurados los runners:

1. **Configura las variables de entorno** en cada servidor
2. **Haz un push de prueba** a `develop` para verificar TEST
3. **Verifica el deployment** en el servidor de TEST
4. **Haz merge a main** para verificar PRODUCCIÓN
5. **Monitorea los workflows** en GitHub Actions

## 📞 Recursos Adicionales

- [Documentación oficial de GitHub Actions](https://docs.github.com/en/actions)
- [Self-hosted runners](https://docs.github.com/en/actions/hosting-your-own-runners)
- [Docker Desktop para Windows](https://docs.docker.com/desktop/install/windows-install/)

---

**¡Listo!** Ahora cada vez que hagas push a GitHub, tu aplicación se desplegará automáticamente 🚀
