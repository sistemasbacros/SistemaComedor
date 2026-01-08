# Script para detener los contenedores de Docker

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet('test', 'production')]
    [string]$Environment = 'production'
)

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  Deteniendo Contenedores - Entorno: $Environment" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Determinar el archivo de compose a usar
$composeFile = if ($Environment -eq 'test') { 'docker-compose.test.yml' } else { 'docker-compose.yml' }

# Detener contenedores
Write-Host "[INFO] Deteniendo contenedores..." -ForegroundColor Yellow
docker compose -f $composeFile down

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "[OK] Contenedores detenidos exitosamente" -ForegroundColor Green
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "[ERROR] Error al detener contenedores" -ForegroundColor Red
    exit 1
}
