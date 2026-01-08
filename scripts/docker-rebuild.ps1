# Script para reconstruir las imágenes de Docker

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet('test', 'production')]
    [string]$Environment = 'production',
    
    [Parameter(Mandatory=$false)]
    [switch]$NoCache
)

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  Reconstruyendo Imágenes - Entorno: $Environment" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Determinar el archivo de compose a usar
$composeFile = if ($Environment -eq 'test') { 'docker-compose.test.yml' } else { 'docker-compose.yml' }

# Detener contenedores si están corriendo
Write-Host "[INFO] Deteniendo contenedores..." -ForegroundColor Yellow
docker compose -f $composeFile down

# Construir comando de build
$buildCmd = "docker compose -f $composeFile build"
if ($NoCache) {
    $buildCmd += " --no-cache"
}

# Reconstruir imágenes
Write-Host "[INFO] Reconstruyendo imágenes..." -ForegroundColor Yellow
Invoke-Expression $buildCmd

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "[OK] Imágenes reconstruidas exitosamente" -ForegroundColor Green
    Write-Host ""
    
    # Preguntar si desea iniciar los contenedores
    $response = Read-Host "¿Desea iniciar los contenedores ahora? (S/N)"
    if ($response -eq 'S' -or $response -eq 's') {
        Write-Host ""
        Write-Host "[INFO] Iniciando contenedores..." -ForegroundColor Yellow
        docker compose -f $composeFile up -d
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host ""
            Write-Host "[OK] Contenedores iniciados exitosamente" -ForegroundColor Green
            Write-Host ""
            docker compose -f $composeFile ps
        }
    }
} else {
    Write-Host ""
    Write-Host "[ERROR] Error al reconstruir imágenes" -ForegroundColor Red
    exit 1
}
