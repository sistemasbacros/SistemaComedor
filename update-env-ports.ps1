# ============================================
# Script para actualizar puertos en archivos .env
# ============================================

Write-Host "=== Actualizador de Puertos para Proyectos ===" -ForegroundColor Cyan
Write-Host ""

# Definir configuración de puertos para cada proyecto
$projectPorts = @{
    "Comedor" = @{ prod = 80; test = 8080 }
    "Bacrocorp" = @{ prod = 8001; test = 9001 }
    "Digitalizacion" = @{ prod = 8002; test = 9002 }
    "GESTION" = @{ prod = 8003; test = 9003 }
    "kpis" = @{ prod = 8004; test = 9004 }
    "SisBacrocorp" = @{ prod = 8005; test = 9005 }
    "SistemaDeTickets" = @{ prod = 8006; test = 9006 }
}

# Función para actualizar o agregar una variable en el archivo .env
function Update-EnvVariable {
    param(
        [string]$FilePath,
        [string]$VariableName,
        [string]$Value
    )

    if (-not (Test-Path $FilePath)) {
        Write-Host "  WARNING: Archivo no existe: $FilePath" -ForegroundColor Yellow
        return $false
    }

    # Hacer backup
    $backupPath = "$FilePath.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    Copy-Item $FilePath $backupPath -Force
    Write-Host "  Backup creado: $backupPath" -ForegroundColor Gray

    # Leer contenido
    $content = Get-Content $FilePath -Raw

    # Verificar si la variable ya existe
    if ($content -match "$VariableName=") {
        # Actualizar valor existente
        $content = $content -replace "$VariableName=.*", "$VariableName=$Value"
        Write-Host "  Actualizado: $VariableName=$Value" -ForegroundColor Green
    } else {
        # Agregar nueva variable
        $content += "`n$VariableName=$Value"
        Write-Host "  Agregado: $VariableName=$Value" -ForegroundColor Green
    }

    # Guardar cambios
    Set-Content $FilePath $content -NoNewline
    return $true
}

# Función para detener contenedores
function Stop-ProjectContainers {
    param(
        [string]$DeployPath,
        [string]$ComposeFile
    )

    if (Test-Path "$DeployPath\$ComposeFile") {
        Push-Location $DeployPath
        Write-Host "  Deteniendo contenedores..." -ForegroundColor Yellow
        docker compose -f $ComposeFile down 2>&1 | Out-Null
        Pop-Location
        Write-Host "  Contenedores detenidos" -ForegroundColor Green
    }
}

# Función para iniciar contenedores
function Start-ProjectContainers {
    param(
        [string]$DeployPath,
        [string]$ComposeFile
    )

    if (Test-Path "$DeployPath\$ComposeFile") {
        Push-Location $DeployPath
        Write-Host "  Iniciando contenedores..." -ForegroundColor Yellow
        docker compose -f $ComposeFile up -d 2>&1 | Out-Null
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  Contenedores iniciados exitosamente" -ForegroundColor Green
        } else {
            Write-Host "  ERROR: No se pudieron iniciar los contenedores" -ForegroundColor Red
        }
        Pop-Location
    }
}

Write-Host "Este script actualizará los puertos en todos los archivos .env" -ForegroundColor White
Write-Host ""
Write-Host "Configuración de puertos:" -ForegroundColor Cyan
foreach ($project in $projectPorts.Keys | Sort-Object) {
    $ports = $projectPorts[$project]
    Write-Host "  $project - Producción: $($ports.prod), Test: $($ports.test)" -ForegroundColor White
}
Write-Host ""

$confirmation = Read-Host "¿Deseas continuar? (S/N)"
if ($confirmation -ne "S" -and $confirmation -ne "s") {
    Write-Host "Operación cancelada" -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "=== Actualizando archivos .env ===" -ForegroundColor Cyan
Write-Host ""

$updatedCount = 0
$errorCount = 0

foreach ($project in $projectPorts.Keys | Sort-Object) {
    $ports = $projectPorts[$project]

    Write-Host "--- $project ---" -ForegroundColor Cyan

    # Actualizar PRODUCCION
    $prodPath = "C:\deploy\${project}Produccion"
    $prodEnvFile = "$prodPath\.env"

    if (Test-Path $prodEnvFile) {
        Write-Host "Producción ($prodPath):" -ForegroundColor White
        if (Update-EnvVariable -FilePath $prodEnvFile -VariableName "HTTP_PORT" -Value $ports.prod) {
            $updatedCount++
        } else {
            $errorCount++
        }
    } else {
        Write-Host "  WARNING: No existe $prodEnvFile" -ForegroundColor Yellow
        Write-Host "  Crea el archivo .env primero usando .env.example como plantilla" -ForegroundColor Yellow
    }

    # Actualizar TEST
    $testPath = "C:\deploy\${project}Test"
    $testEnvFile = "$testPath\.env"

    if (Test-Path $testEnvFile) {
        Write-Host "Test ($testPath):" -ForegroundColor White
        if (Update-EnvVariable -FilePath $testEnvFile -VariableName "HTTP_PORT_TEST" -Value $ports.test) {
            $updatedCount++
        } else {
            $errorCount++
        }
    } else {
        Write-Host "  WARNING: No existe $testEnvFile" -ForegroundColor Yellow
        Write-Host "  Crea el archivo .env primero usando .env.example como plantilla" -ForegroundColor Yellow
    }

    Write-Host ""
}

Write-Host "=== Resumen ===" -ForegroundColor Cyan
Write-Host "Archivos actualizados: $updatedCount" -ForegroundColor Green
if ($errorCount -gt 0) {
    Write-Host "Errores: $errorCount" -ForegroundColor Red
}
Write-Host ""

# Preguntar si reiniciar contenedores
$restart = Read-Host "¿Deseas reiniciar todos los contenedores para aplicar los cambios? (S/N)"
if ($restart -eq "S" -or $restart -eq "s") {
    Write-Host ""
    Write-Host "=== Reiniciando Contenedores ===" -ForegroundColor Cyan
    Write-Host ""

    foreach ($project in $projectPorts.Keys | Sort-Object) {
        Write-Host "--- $project ---" -ForegroundColor Cyan

        # Reiniciar PRODUCCION
        $prodPath = "C:\deploy\${project}Produccion"
        if (Test-Path $prodPath) {
            Write-Host "Producción:" -ForegroundColor White
            Stop-ProjectContainers -DeployPath $prodPath -ComposeFile "docker-compose.yml"
            Start-Sleep -Seconds 2
            Start-ProjectContainers -DeployPath $prodPath -ComposeFile "docker-compose.yml"
        }

        # Reiniciar TEST
        $testPath = "C:\deploy\${project}Test"
        if (Test-Path $testPath) {
            Write-Host "Test:" -ForegroundColor White
            Stop-ProjectContainers -DeployPath $testPath -ComposeFile "docker-compose.test.yml"
            Start-Sleep -Seconds 2
            Start-ProjectContainers -DeployPath $testPath -ComposeFile "docker-compose.test.yml"
        }

        Write-Host ""
    }

    Write-Host "=== Verificación de Contenedores ===" -ForegroundColor Cyan
    Write-Host ""
    docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | Select-String -Pattern "nginx|php"
}

Write-Host ""
Write-Host "=== Proceso Completado ===" -ForegroundColor Green
Write-Host ""
Write-Host "URLs de acceso a las aplicaciones:" -ForegroundColor Cyan
Write-Host ""
Write-Host "PRODUCCION:" -ForegroundColor Yellow
foreach ($project in $projectPorts.Keys | Sort-Object) {
    Write-Host "  $project - http://localhost:$($projectPorts[$project].prod)" -ForegroundColor White
}
Write-Host ""
Write-Host "TEST:" -ForegroundColor Yellow
foreach ($project in $projectPorts.Keys | Sort-Object) {
    Write-Host "  $project - http://localhost:$($projectPorts[$project].test)" -ForegroundColor White
}
Write-Host ""
Write-Host "NOTA: Los backups de los archivos .env originales se guardaron con extensión .backup_*" -ForegroundColor Gray
Write-Host ""
