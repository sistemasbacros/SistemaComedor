# Script para reiniciar los contenedores de Docker

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet('test', 'production')]
    [string]$Environment = 'production'
)

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  Reiniciando Contenedores - Entorno: $Environment" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Determinar el archivo de compose a usar
$composeFile = if ($Environment -eq 'test') { 'docker-compose.test.yml' } else { 'docker-compose.yml' }

# Reiniciar contenedores
Write-Host "[INFO] Reiniciando contenedores..." -ForegroundColor Yellow
docker compose -f $composeFile restart

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "[OK] Contenedores reiniciados exitosamente" -ForegroundColor Green
    Write-Host ""
    
    # Mostrar estado de contenedores
    Write-Host "Estado de contenedores:" -ForegroundColor Cyan
    docker compose -f $composeFile ps
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "[ERROR] Error al reiniciar contenedores" -ForegroundColor Red
    exit 1
}
