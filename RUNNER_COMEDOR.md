# 🚀 Guía Específica: Sistema Comedor - GitHub Runner

## 📍 Información del Proyecto

- **Repositorio**: https://github.com/sistemasbacros/SistemaComedor
- **Organización**: sistemasbacros
- **Proyecto**: SistemaComedor

## ⚡ Instalación Rápida

### 1. Obtener Token de GitHub

1. Ve a: https://github.com/sistemasbacros/SistemaComedor/settings/actions/runners/new
2. Selecciona **Windows** como sistema operativo
3. Copia el **token** que aparece en el comando de configuración

### 2. Instalar Runner en Servidor de TEST

```powershell
# Ejecutar como Administrador

# Crear directorio
New-Item -Path "C:\actions-runner-comedor-test" -ItemType Directory -Force
cd C:\actions-runner-comedor-test

# Descargar (copia el comando exacto de GitHub)
Invoke-WebRequest -Uri https://github.com/actions/runner/releases/download/v2.311.0/actions-runner-win-x64-2.311.0.zip -OutFile actions-runner.zip
Expand-Archive -Path actions-runner.zip -DestinationPath . -Force

# Configurar (reemplaza TU_TOKEN con el token de GitHub)
.\config.cmd --url https://github.com/sistemasbacros/SistemaComedor --token TU_TOKEN --labels test,windows --name comedor-test-runner

# Instalar como servicio
.\svc.install.ps1
.\svc.start.ps1

# Verificar
Get-Service actions.runner.*
```

### 3. Instalar Runner en Servidor de PRODUCCIÓN

```powershell
# Ejecutar como Administrador

# Crear directorio
New-Item -Path "C:\actions-runner-comedor-production" -ItemType Directory -Force
cd C:\actions-runner-comedor-production

# Descargar (copia el comando exacto de GitHub)
Invoke-WebRequest -Uri https://github.com/actions/runner/releases/download/v2.311.0/actions-runner-win-x64-2.311.0.zip -OutFile actions-runner.zip
Expand-Archive -Path actions-runner.zip -DestinationPath . -Force

# Configurar (obtén un NUEVO token de GitHub)
.\config.cmd --url https://github.com/sistemasbacros/SistemaComedor --token TU_TOKEN --labels production,windows --name comedor-production-runner

# Instalar como servicio
.\svc.install.ps1
.\svc.start.ps1

# Verificar
Get-Service actions.runner.*
```

## ✅ Verificación en GitHub

Ve a: https://github.com/sistemasbacros/SistemaComedor/settings/actions/runners

Deberías ver:
- ✅ **comedor-test-runner** - Labels: `self-hosted`, `Windows`, `X64`, `test`, `windows`
- ✅ **comedor-production-runner** - Labels: `self-hosted`, `Windows`, `X64`, `production`, `windows`

Ambos con estado **Idle** (verde) = ¡Listo para usar!

## 🧪 Probar Deployment

### Test (rama develop):
```bash
git checkout develop
echo "<!-- Test deployment -->" >> admicome4.php
git add .
git commit -m "Test: Verificar deployment automático"
git push origin develop
```

Ver el deployment en: https://github.com/sistemasbacros/SistemaComedor/actions

### Producción (rama main):
```bash
git checkout main
git merge develop
git push origin main
```

Ver el deployment en: https://github.com/sistemasbacros/SistemaComedor/actions

## 🔗 Enlaces Útiles

- **Repositorio**: https://github.com/sistemasbacros/SistemaComedor
- **Actions**: https://github.com/sistemasbacros/SistemaComedor/actions
- **Runners**: https://github.com/sistemasbacros/SistemaComedor/settings/actions/runners
- **Workflows**: https://github.com/sistemasbacros/SistemaComedor/actions/workflows

## 📊 Estructura de Deployment

```
Servidor TEST:
├─ Runner: C:\actions-runner-comedor-test
└─ Deployment: C:\deploy\ComedorTest
   └─ URL: http://localhost:8080

Servidor PRODUCCIÓN:
├─ Runner: C:\actions-runner-comedor-production
└─ Deployment: C:\deploy\ComedorProduccion
   └─ URL: http://localhost:80
```

## 🔧 Comandos Frecuentes

### Ver logs del runner:
```powershell
# TEST
cd C:\actions-runner-comedor-test
Get-Content _diag\Runner_*.log -Tail 50

# PRODUCCIÓN
cd C:\actions-runner-comedor-production
Get-Content _diag\Runner_*.log -Tail 50
```

### Reiniciar runner:
```powershell
# TEST
cd C:\actions-runner-comedor-test
.\svc.stop.ps1
.\svc.start.ps1

# PRODUCCIÓN
cd C:\actions-runner-comedor-production
.\svc.stop.ps1
.\svc.start.ps1
```

### Ver estado de servicios:
```powershell
Get-Service actions.runner.* | Select-Object Name, Status, DisplayName
```

## 🎯 Flujo de Trabajo

1. **Desarrollo** → Commit y push a `develop`
2. **GitHub Actions** → Detecta push automáticamente
3. **Runner TEST** → Ejecuta deployment en servidor de test
4. **Verificación** → Pruebas en ambiente de test
5. **Merge a main** → Push a producción
6. **Runner PRODUCCIÓN** → Deployment automático a producción

---

**Siguiente paso**: Configura el archivo `.env` en cada servidor y haz tu primer deployment! 🚀
