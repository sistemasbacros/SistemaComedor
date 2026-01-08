# Script para mostrar el estado de los contenedores

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet('test', 'production', 'all')]
    [string]$Environment = 'all'
)

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  Estado de Contenedores" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

function Show-Status {
    param([string]$ComposeFile, [string]$EnvName)
    
    if (Test-Path $ComposeFile) {
        Write-Host "Entorno: $EnvName" -ForegroundColor Yellow
        Write-Host "----------------------------------------" -ForegroundColor Yellow
        docker compose -f $ComposeFile ps
        Write-Host ""
    } else {
        Write-Host "Archivo $ComposeFile no encontrado" -ForegroundColor Red
        Write-Host ""
    }
}

if ($Environment -eq 'production' -or $Environment -eq 'all') {
    Show-Status 'docker-compose.yml' 'PRODUCCIÓN'
}

if ($Environment -eq 'test' -or $Environment -eq 'all') {
    Show-Status 'docker-compose.test.yml' 'TEST'
}

# Mostrar uso de recursos
Write-Host "Uso de Recursos:" -ForegroundColor Cyan
Write-Host "----------------------------------------" -ForegroundColor Cyan
docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}" 2>$null
Write-Host ""
