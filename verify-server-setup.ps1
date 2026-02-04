<![CDATA[# Script de Verificación del Servidor para Despliegue de Comedor
# Este script verifica que todos los pre-requisitos estén configurados correctamente

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  VERIFICACIÓN DE CONFIGURACIÓN" -ForegroundColor Cyan
Write-Host "  Proyecto: Comedor" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

$allChecksPass = $true

# ===== 1. VERIFICAR DOCKER =====
Write-Host "[1/8] Verificando Docker Engine..." -ForegroundColor Yellow

$dockerCmd = Get-Command docker -ErrorAction SilentlyContinue
if (-not $dockerCmd) {
    Write-Host "  ❌ ERROR: Docker no está instalado o no está en el PATH" -ForegroundColor Red
    Write-Host "     Instala Docker Engine (no Docker Desktop)" -ForegroundColor Red
    $allChecksPass = $false
} else {
    try {
        $dockerVersion = docker --version 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  ✅ Docker instalado: $dockerVersion" -ForegroundColor Green

            # Verificar que NO sea Docker Desktop
            $dockerInfo = docker info 2>&1 | Out-String
            if ($dockerInfo -match "Docker Desktop") {
                Write-Host "  ⚠️  WARNING: Estás usando Docker Desktop" -ForegroundColor Yellow
                Write-Host "     Se recomienda usar Docker Engine en Windows Server" -ForegroundColor Yellow
            }
        } else {
            Write-Host "  ❌ ERROR: Docker instalado pero no responde" -ForegroundColor Red
            $allChecksPass = $false
        }
    } catch {
        Write-Host "  ❌ ERROR: No se pudo verificar Docker: $_" -ForegroundColor Red
        $allChecksPass = $false
    }
}

# ===== 2. VERIFICAR SERVICIO DOCKER =====
Write-Host ""
Write-Host "[2/8] Verificando servicio Docker..." -ForegroundColor Yellow

try {
    $dockerService = Get-Service docker -ErrorAction SilentlyContinue
    if ($dockerService) {
        if ($dockerService.Status -eq "Running") {
            Write-Host "  ✅ Servicio Docker está corriendo" -ForegroundColor Green
        } else {
            Write-Host "  ⚠️  WARNING: Servicio Docker no está corriendo. Estado: $($dockerService.Status)" -ForegroundColor Yellow
            Write-Host "     Iniciar con: Start-Service docker" -ForegroundColor Yellow
            $allChecksPass = $false
        }

        if ($dockerService.StartType -ne "Automatic") {
            Write-Host "  ⚠️  WARNING: Servicio Docker no está configurado para inicio automático" -ForegroundColor Yellow
            Write-Host "     Configurar con: Set-Service docker -StartupType Automatic" -ForegroundColor Yellow
        }
    } else {
        Write-Host "  ℹ️  INFO: Servicio 'docker' no encontrado (puede ser normal en algunas configuraciones)" -ForegroundColor Cyan
    }
} catch {
    Write-Host "  ℹ️  INFO: No se pudo verificar el servicio Docker" -ForegroundColor Cyan
}

# ===== 3. VERIFICAR DOCKER COMPOSE =====
Write-Host ""
Write-Host "[3/8] Verificando Docker Compose..." -ForegroundColor Yellow

try {
    $composeVersion = docker compose version 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  ✅ Docker Compose disponible: $composeVersion" -ForegroundColor Green
    } else {
        Write-Host "  ❌ ERROR: Docker Compose no está disponible" -ForegroundColor Red
        Write-Host "     Instala el plugin de Docker Compose" -ForegroundColor Red
        $allChecksPass = $false
    }
} catch {
    Write-Host "  ❌ ERROR: No se pudo verificar Docker Compose: $_" -ForegroundColor Red
    $allChecksPass = $false
}

# ===== 4. VERIFICAR CONECTIVIDAD DOCKER DAEMON =====
Write-Host ""
Write-Host "[4/8] Verificando conectividad con Docker daemon..." -ForegroundColor Yellow

try {
    docker ps > $null 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  ✅ Docker daemon accesible" -ForegroundColor Green

        # Mostrar contenedores actuales de Comedor
        $comedorContainers = docker ps -a --filter "name=comedor_" --format "{{.Names}}: {{.Status}}" 2>&1
        if ($comedorContainers) {
            Write-Host "     Contenedores de Comedor existentes:" -ForegroundColor Cyan
            $comedorContainers | ForEach-Object {
                Write-Host "       - $_" -ForegroundColor Cyan
            }
        }
    } else {
        Write-Host "  ❌ ERROR: No se puede conectar al daemon de Docker" -ForegroundColor Red
        $allChecksPass = $false
    }
} catch {
    Write-Host "  ❌ ERROR: No se pudo conectar al daemon de Docker" -ForegroundColor Red
    $allChecksPass = $false
}

# ===== 5. VERIFICAR DIRECTORIOS DE DESPLIEGUE =====
Write-Host ""
Write-Host "[5/8] Verificando directorios de despliegue..." -ForegroundColor Yellow

$testPath = "C:\deploy\ComedorTest"
$prodPath = "C:\deploy\ComedorProduccion"

if (Test-Path $testPath) {
    Write-Host "  ✅ Directorio TEST existe: $testPath" -ForegroundColor Green
} else {
    Write-Host "  ❌ ERROR: Directorio TEST no existe: $testPath" -ForegroundColor Red
    Write-Host "     Crear con: New-Item -Path '$testPath' -ItemType Directory -Force" -ForegroundColor Red
    $allChecksPass = $false
}

if (Test-Path $prodPath) {
    Write-Host "  ✅ Directorio PRODUCCIÓN existe: $prodPath" -ForegroundColor Green
} else {
    Write-Host "  ❌ ERROR: Directorio PRODUCCIÓN no existe: $prodPath" -ForegroundColor Red
    Write-Host "     Crear con: New-Item -Path '$prodPath' -ItemType Directory -Force" -ForegroundColor Red
    $allChecksPass = $false
}

# ===== 6. VERIFICAR ARCHIVOS .env =====
Write-Host ""
Write-Host "[6/8] Verificando archivos .env..." -ForegroundColor Yellow

$testEnvPath = Join-Path $testPath ".env"
$prodEnvPath = Join-Path $prodPath ".env"

if (Test-Path $testEnvPath) {
    Write-Host "  ✅ Archivo .env de TEST existe" -ForegroundColor Green

    # Verificar variables importantes
    $testEnvContent = Get-Content $testEnvPath -Raw
    $hasHttpPort = $testEnvContent -match "HTTP_PORT_TEST"
    $hasDbHost = $testEnvContent -match "DB_HOST_TEST"

    if ($hasHttpPort -and $hasDbHost) {
        Write-Host "     Variables principales configuradas" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️  WARNING: Faltan variables importantes en .env de TEST" -ForegroundColor Yellow
        if (-not $hasHttpPort) { Write-Host "     Falta: HTTP_PORT_TEST" -ForegroundColor Yellow }
        if (-not $hasDbHost) { Write-Host "     Falta: DB_HOST_TEST" -ForegroundColor Yellow }
    }
} else {
    Write-Host "  ⚠️  WARNING: Archivo .env de TEST no existe" -ForegroundColor Yellow
    Write-Host "     Se creará desde template en el primer despliegue" -ForegroundColor Yellow
    Write-Host "     IMPORTANTE: Configurar manualmente antes de desplegar" -ForegroundColor Yellow
}

if (Test-Path $prodEnvPath) {
    Write-Host "  ✅ Archivo .env de PRODUCCIÓN existe" -ForegroundColor Green

    # Verificar variables importantes
    $prodEnvContent = Get-Content $prodEnvPath -Raw
    $hasHttpPort = $prodEnvContent -match "HTTP_PORT="
    $hasDbHost = $prodEnvContent -match "DB_HOST="

    if ($hasHttpPort -and $hasDbHost) {
        Write-Host "     Variables principales configuradas" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️  WARNING: Faltan variables importantes en .env de PRODUCCIÓN" -ForegroundColor Yellow
        if (-not $hasHttpPort) { Write-Host "     Falta: HTTP_PORT" -ForegroundColor Yellow }
        if (-not $hasDbHost) { Write-Host "     Falta: DB_HOST" -ForegroundColor Yellow }
    }
} else {
    Write-Host "  ❌ ERROR: Archivo .env de PRODUCCIÓN no existe" -ForegroundColor Red
    Write-Host "     DEBE crearse manualmente antes de desplegar a PRODUCCIÓN" -ForegroundColor Red
    $allChecksPass = $false
}

# ===== 7. VERIFICAR PUERTOS =====
Write-Host ""
Write-Host "[7/8] Verificando puertos..." -ForegroundColor Yellow

# Función para verificar si un puerto está en uso
function Test-Port {
    param($Port)
    $connections = Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue
    return $connections.Count -gt 0
}

$testPort = 8080  # Puerto por defecto de TEST
$prodPort = 80    # Puerto por defecto de PRODUCCIÓN

# Leer puertos reales de los .env si existen
if (Test-Path $testEnvPath) {
    $envContent = Get-Content $testEnvPath
    foreach ($line in $envContent) {
        if ($line -match "HTTP_PORT_TEST=(\d+)") {
            $testPort = [int]$matches[1]
            break
        }
    }
}

if (Test-Path $prodEnvPath) {
    $envContent = Get-Content $prodEnvPath
    foreach ($line in $envContent) {
        if ($line -match "HTTP_PORT=(\d+)") {
            $prodPort = [int]$matches[1]
            break
        }
    }
}

Write-Host "  Puerto TEST: $testPort" -ForegroundColor Cyan
if (Test-Port $testPort) {
    Write-Host "    ⚠️  Puerto $testPort está en uso" -ForegroundColor Yellow
    $processUsingPort = Get-NetTCPConnection -LocalPort $testPort -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($processUsingPort) {
        $process = Get-Process -Id $processUsingPort.OwningProcess -ErrorAction SilentlyContinue
        if ($process) {
            Write-Host "       Proceso: $($process.ProcessName) (PID: $($process.Id))" -ForegroundColor Yellow
        }
    }
} else {
    Write-Host "    ✅ Puerto disponible" -ForegroundColor Green
}

Write-Host ""
Write-Host "  Puerto PRODUCCIÓN: $prodPort" -ForegroundColor Cyan
if (Test-Port $prodPort) {
    Write-Host "    ⚠️  Puerto $prodPort está en uso" -ForegroundColor Yellow
    $processUsingPort = Get-NetTCPConnection -LocalPort $prodPort -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($processUsingPort) {
        $process = Get-Process -Id $processUsingPort.OwningProcess -ErrorAction SilentlyContinue
        if ($process) {
            Write-Host "       Proceso: $($process.ProcessName) (PID: $($process.Id))" -ForegroundColor Yellow
        }
    }
} else {
    Write-Host "    ✅ Puerto disponible" -ForegroundColor Green
}

# ===== 8. VERIFICAR GITHUB ACTIONS RUNNERS =====
Write-Host ""
Write-Host "[8/8] Verificando GitHub Actions Runners..." -ForegroundColor Yellow

$testRunnerPath = "C:\actions-runners\actions-runner-comedor-test"
$prodRunnerPath = "C:\actions-runners\actions-runner-comedor-production"

$testRunnerExists = Test-Path $testRunnerPath
$prodRunnerExists = Test-Path $prodRunnerPath

if ($testRunnerExists) {
    Write-Host "  ✅ Directorio del runner TEST existe: $testRunnerPath" -ForegroundColor Green

    # Verificar si hay un proceso del runner corriendo
    $runnerProcess = Get-Process | Where-Object { $_.Path -like "*$testRunnerPath*" }
    if ($runnerProcess) {
        Write-Host "     Runner TEST está corriendo" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️  WARNING: Runner TEST no parece estar corriendo" -ForegroundColor Yellow
        Write-Host "     Verificar el servicio del runner o iniciarlo manualmente" -ForegroundColor Yellow
    }
} else {
    Write-Host "  ⚠️  WARNING: Directorio del runner TEST no existe: $testRunnerPath" -ForegroundColor Yellow
    Write-Host "     El runner debe ser configurado para que funcione el despliegue automático" -ForegroundColor Yellow
}

if ($prodRunnerExists) {
    Write-Host "  ✅ Directorio del runner PRODUCCIÓN existe: $prodRunnerPath" -ForegroundColor Green

    # Verificar si hay un proceso del runner corriendo
    $runnerProcess = Get-Process | Where-Object { $_.Path -like "*$prodRunnerPath*" }
    if ($runnerProcess) {
        Write-Host "     Runner PRODUCCIÓN está corriendo" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️  WARNING: Runner PRODUCCIÓN no parece estar corriendo" -ForegroundColor Yellow
        Write-Host "     Verificar el servicio del runner o iniciarlo manualmente" -ForegroundColor Yellow
    }
} else {
    Write-Host "  ⚠️  WARNING: Directorio del runner PRODUCCIÓN no existe: $prodRunnerPath" -ForegroundColor Yellow
    Write-Host "     El runner debe ser configurado para que funcione el despliegue automático" -ForegroundColor Yellow
}

# ===== RESUMEN =====
Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  RESUMEN" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

if ($allChecksPass) {
    Write-Host "✅ TODAS LAS VERIFICACIONES CRÍTICAS PASARON" -ForegroundColor Green
    Write-Host ""
    Write-Host "El servidor está listo para el despliegue." -ForegroundColor Green
    Write-Host ""
    Write-Host "Próximos pasos:" -ForegroundColor Cyan
    Write-Host "  1. Asegúrate de que los archivos .env estén configurados correctamente" -ForegroundColor White
    Write-Host "  2. Verifica que los runners de GitHub Actions estén corriendo" -ForegroundColor White
    Write-Host "  3. Haz push a las ramas 'dev' (TEST) o 'main' (PRODUCCIÓN)" -ForegroundColor White
    Write-Host ""
} else {
    Write-Host "❌ HAY PROBLEMAS QUE DEBEN SER CORREGIDOS" -ForegroundColor Red
    Write-Host ""
    Write-Host "Revisa los errores arriba y corrígelos antes de intentar el despliegue." -ForegroundColor Red
    Write-Host ""
    Write-Host "Documentación: Ver SETUP-SERVIDOR.md para instrucciones detalladas" -ForegroundColor Yellow
    Write-Host ""
    exit 1
}
]]