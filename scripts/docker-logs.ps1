# Script para ver los logs de los contenedores

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet('test', 'production')]
    [string]$Environment = 'production',
    
    [Parameter(Mandatory=$false)]
    [ValidateSet('nginx', 'php', 'all')]
    [string]$Service = 'all',
    
    [Parameter(Mandatory=$false)]
    [int]$Lines = 50,
    
    [Parameter(Mandatory=$false)]
    [switch]$Follow
)

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  Logs de Contenedores - Entorno: $Environment" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Determinar el archivo de compose a usar
$composeFile = if ($Environment -eq 'test') { 'docker-compose.test.yml' } else { 'docker-compose.yml' }

# Construir comando de logs
$logsCmd = "docker compose -f $composeFile logs --tail=$Lines"

if ($Follow) {
    $logsCmd += " -f"
}

if ($Service -ne 'all') {
    $logsCmd += " $Service"
}

Write-Host "[INFO] Mostrando logs..." -ForegroundColor Yellow
Write-Host ""

# Ejecutar comando
Invoke-Expression $logsCmd
