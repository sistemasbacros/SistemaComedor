# Script para iniciar los contenedores de Docker

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet('test', 'production')]
    [string]$Environment = 'production'
)

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  Iniciando Contenedores - Entorno: $Environment" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Determinar el archivo de compose a usar
$composeFile = if ($Environment -eq 'test') { 'docker-compose.test.yml' } else { 'docker-compose.yml' }

# Verificar que existe el archivo .env
if (-not (Test-Path ".env")) {
    Write-Host "[ERROR] No se encontró el archivo .env" -ForegroundColor Red
    Write-Host "[INFO] Copia .env.example a .env y configura las credenciales" -ForegroundColor Yellow
    exit 1
}

# Verificar que Docker está corriendo
try {
    docker info | Out-Null
} catch {
    Write-Host "[ERROR] Docker no está corriendo. Inicia Docker Desktop primero." -ForegroundColor Red
    exit 1
}

# Iniciar contenedores
Write-Host "[INFO] Iniciando contenedores..." -ForegroundColor Yellow
docker compose -f $composeFile up -d

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "[OK] Contenedores iniciados exitosamente" -ForegroundColor Green
    Write-Host ""
    
    # Mostrar estado de contenedores
    Write-Host "Estado de contenedores:" -ForegroundColor Cyan
    docker compose -f $composeFile ps
    
    # Mostrar URL de acceso
    Write-Host ""
    $port = if ($Environment -eq 'test') { 8080 } else { 80 }
    Write-Host "Aplicación disponible en: http://localhost:$port" -ForegroundColor Green
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "[ERROR] Error al iniciar contenedores" -ForegroundColor Red
    Write-Host "[INFO] Revisa los logs con: .\docker-logs.ps1" -ForegroundColor Yellow
    exit 1
}
