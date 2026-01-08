# 🐳 Sistema Comedor - Docker & CI/CD

Sistema de gestión de comedor dockerizado con deployment automático usando GitHub Actions.

## 📋 Tabla de Contenidos

- [Arquitectura](#arquitectura)
- [Requisitos Previos](#requisitos-previos)
- [Configuración Inicial](#configuración-inicial)
- [Uso Local](#uso-local)
- [Deployment Automático](#deployment-automático)
- [Scripts de Utilidad](#scripts-de-utilidad)
- [Troubleshooting](#troubleshooting)

## 🏗️ Arquitectura

El sistema está compuesto por dos servicios principales:

```
┌─────────────────────────────────────┐
│         Nginx (Puerto 80/8080)      │
│      (Reverse Proxy / Web Server)   │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│       PHP-FPM 8.2 (Puerto 9000)     │
│  (PHP + SQL Server Extensions)      │
└─────────────────────────────────────┘
```

## 📦 Requisitos Previos

### Para Desarrollo Local:
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado y corriendo
- PowerShell 5.1 o superior
- Git

### Para Deployment Automático:
- Self-hosted GitHub Runner configurado con:
  - Windows OS
  - Docker Desktop instalado
  - Labels: `Windows`, `X64`, `test` o `production`

## ⚙️ Configuración Inicial

### 1. Configurar Variables de Entorno

Copia el archivo de ejemplo y configura tus credenciales:

```powershell
Copy-Item .env.example .env
```

Edita el archivo `.env` con tus configuraciones:

```env
# Entorno (test|production)
APP_ENV=production

# Puertos
HTTP_PORT=80
HTTP_PORT_TEST=8080

# Base de datos PRODUCCIÓN
DB_HOST=tu_servidor_produccion
DB_PORT=1433
DB_DATABASE=tu_base_datos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña

# Base de datos TEST
DB_HOST_TEST=tu_servidor_test
DB_PORT_TEST=1433
DB_DATABASE_TEST=tu_base_datos_test
DB_USERNAME_TEST=tu_usuario_test
DB_PASSWORD_TEST=tu_contraseña_test
```

### 2. Estructura de Directorios

El proyecto se organiza de la siguiente manera:

```
Comedor/
├── .github/
│   └── workflows/
│       ├── deploy-test.yml          # CI/CD para TEST
│       └── deploy-production.yml    # CI/CD para PRODUCCIÓN
├── nginx/
│   ├── nginx.conf                   # Configuración de Nginx
│   └── logs/                        # Logs de Nginx (generados)
├── php/
│   └── custom.ini                   # Configuración personalizada de PHP
├── scripts/
│   ├── docker-start.ps1             # Iniciar contenedores
│   ├── docker-stop.ps1              # Detener contenedores
│   ├── docker-restart.ps1           # Reiniciar contenedores
│   ├── docker-logs.ps1              # Ver logs
│   ├── docker-rebuild.ps1           # Reconstruir imágenes
│   └── docker-status.ps1            # Ver estado
├── Dockerfile                       # Imagen PHP personalizada
├── docker-compose.yml               # Compose para PRODUCCIÓN
├── docker-compose.test.yml          # Compose para TEST
├── .env.example                     # Plantilla de variables de entorno
└── [archivos PHP de la aplicación]
```

## 🚀 Uso Local

### Iniciar el Sistema

**Producción (puerto 80):**
```powershell
.\scripts\docker-start.ps1 -Environment production
```

**Test (puerto 8080):**
```powershell
.\scripts\docker-start.ps1 -Environment test
```

### Detener el Sistema

```powershell
.\scripts\docker-stop.ps1 -Environment production
# o
.\scripts\docker-stop.ps1 -Environment test
```

### Ver Logs

Ver todos los logs:
```powershell
.\scripts\docker-logs.ps1 -Environment production
```

Ver logs de un servicio específico:
```powershell
.\scripts\docker-logs.ps1 -Environment production -Service nginx
.\scripts\docker-logs.ps1 -Environment production -Service php
```

Seguir logs en tiempo real:
```powershell
.\scripts\docker-logs.ps1 -Environment production -Follow
```

### Reiniciar Contenedores

```powershell
.\scripts\docker-restart.ps1 -Environment production
```

### Reconstruir Imágenes

Cuando modificas el Dockerfile o necesitas actualizar dependencias:

```powershell
.\scripts\docker-rebuild.ps1 -Environment production -NoCache
```

### Ver Estado del Sistema

```powershell
.\scripts\docker-status.ps1 -Environment all
```

## 🔄 Deployment Automático

### Configuración de GitHub Runners

#### Para Servidor de TEST:

1. Instala el GitHub Runner en tu servidor de test
2. Configura los labels: `self-hosted`, `Windows`, `X64`, `test`
3. Asegúrate que Docker Desktop esté instalado y corriendo

#### Para Servidor de PRODUCCIÓN:

1. Instala el GitHub Runner en tu servidor de producción
2. Configura los labels: `self-hosted`, `Windows`, `X64`, `production`
3. Asegúrate que Docker Desktop esté instalado y corriendo

### Flujo de Deployment

#### Ambiente de TEST (develop → test server)

1. Haz push a la rama `develop`:
   ```bash
   git checkout develop
   git add .
   git commit -m "Nueva funcionalidad"
   git push origin develop
   ```

2. GitHub Actions automáticamente:
   - ✅ Descarga el código
   - ✅ Crea backup del deployment anterior
   - ✅ Despliega archivos en `C:\deploy\ComedorTest`
   - ✅ Detiene contenedores antiguos
   - ✅ Construye nuevas imágenes Docker
   - ✅ Inicia contenedores
   - ✅ Verifica salud del servidor (health check)
   - ✅ Limpia imágenes antiguas

3. La aplicación estará disponible en el puerto configurado (default: 8080)

#### Ambiente de PRODUCCIÓN (main → production server)

1. Haz merge a `main` después de probar en test:
   ```bash
   git checkout main
   git merge develop
   git push origin main
   ```

2. GitHub Actions automáticamente:
   - ✅ Descarga el código
   - ✅ **Crea backup completo** con timestamp
   - ✅ Despliega archivos en `C:\deploy\ComedorProduccion`
   - ✅ Detiene contenedores antiguos
   - ✅ Construye nuevas imágenes Docker
   - ✅ Inicia contenedores
   - ✅ Verifica salud del servidor (health check extendido)
   - ✅ Limpia imágenes antiguas
   - 🔄 **En caso de fallo**: Muestra instrucciones para restaurar backup

### Deployment Manual

Puedes ejecutar el deployment manualmente desde GitHub:

1. Ve a **Actions** en tu repositorio
2. Selecciona el workflow deseado:
   - `Deploy to Test Server`
   - `Deploy to Production Server`
3. Click en **Run workflow**
4. Selecciona la rama
5. Click en **Run workflow**

## 🛠️ Scripts de Utilidad

### docker-start.ps1
Inicia los contenedores del entorno especificado.

**Parámetros:**
- `-Environment`: `test` o `production` (default: `production`)

**Ejemplo:**
```powershell
.\scripts\docker-start.ps1 -Environment test
```

### docker-stop.ps1
Detiene los contenedores del entorno especificado.

**Parámetros:**
- `-Environment`: `test` o `production` (default: `production`)

**Ejemplo:**
```powershell
.\scripts\docker-stop.ps1 -Environment production
```

### docker-logs.ps1
Muestra los logs de los contenedores.

**Parámetros:**
- `-Environment`: `test` o `production` (default: `production`)
- `-Service`: `nginx`, `php` o `all` (default: `all`)
- `-Lines`: Número de líneas a mostrar (default: `50`)
- `-Follow`: Seguir logs en tiempo real (switch)

**Ejemplos:**
```powershell
# Ver últimas 50 líneas de todos los servicios
.\scripts\docker-logs.ps1

# Ver logs de nginx en tiempo real
.\scripts\docker-logs.ps1 -Service nginx -Follow

# Ver últimas 100 líneas de PHP en test
.\scripts\docker-logs.ps1 -Environment test -Service php -Lines 100
```

### docker-restart.ps1
Reinicia los contenedores.

**Parámetros:**
- `-Environment`: `test` o `production` (default: `production`)

**Ejemplo:**
```powershell
.\scripts\docker-restart.ps1 -Environment production
```

### docker-rebuild.ps1
Reconstruye las imágenes Docker.

**Parámetros:**
- `-Environment`: `test` o `production` (default: `production`)
- `-NoCache`: Fuerza reconstrucción sin usar cache (switch)

**Ejemplos:**
```powershell
# Reconstruir con cache
.\scripts\docker-rebuild.ps1

# Reconstruir sin cache (limpio)
.\scripts\docker-rebuild.ps1 -NoCache
```

### docker-status.ps1
Muestra el estado de los contenedores y uso de recursos.

**Parámetros:**
- `-Environment`: `test`, `production` o `all` (default: `all`)

**Ejemplo:**
```powershell
.\scripts\docker-status.ps1
```

## 🔧 Troubleshooting

### Error: "Docker no está corriendo"

**Solución:**
1. Abre Docker Desktop
2. Espera a que inicie completamente
3. Vuelve a ejecutar el comando

### Error: "Puerto ya en uso"

**Solución:**
1. Verifica qué está usando el puerto:
   ```powershell
   netstat -ano | findstr :80
   ```
2. Detén el proceso o cambia el puerto en `.env`

### Error: "No se puede conectar a la base de datos"

**Solución:**
1. Verifica las credenciales en `.env`
2. Asegúrate que el servidor de SQL Server esté accesible
3. Verifica los logs de PHP:
   ```powershell
   .\scripts\docker-logs.ps1 -Service php
   ```

### Los contenedores se reinician constantemente

**Solución:**
1. Ver los logs para identificar el error:
   ```powershell
   .\scripts\docker-logs.ps1 -Follow
   ```
2. Revisa la configuración de PHP en `php/custom.ini`
3. Verifica que todos los archivos necesarios existen

### Deployment falló en GitHub Actions

**Solución:**
1. Revisa los logs del workflow en GitHub Actions
2. Verifica que el runner esté online
3. Asegúrate que Docker esté corriendo en el servidor
4. Para producción, restaura el backup si es necesario:
   ```powershell
   cd C:\deploy
   # Ver backups disponibles
   Get-ChildItem -Directory -Filter "ComedorProduccion_backup_*"
   
   # Restaurar backup
   Remove-Item -Path "C:\deploy\ComedorProduccion" -Recurse -Force
   Copy-Item -Path "C:\deploy\ComedorProduccion_backup_YYYYMMDD_HHMMSS" `
             -Destination "C:\deploy\ComedorProduccion" -Recurse
   cd C:\deploy\ComedorProduccion
   docker compose up -d
   ```

### Limpiar espacio en disco

**Solución:**
```powershell
# Limpiar contenedores detenidos
docker container prune -f

# Limpiar imágenes no usadas
docker image prune -a -f

# Limpiar todo (cuidado!)
docker system prune -a -f --volumes
```

## 📊 Monitoreo

### Ver uso de recursos en tiempo real

```powershell
docker stats
```

### Ver logs de Nginx

```powershell
# Directamente desde el contenedor
docker exec -it comedor_nginx tail -f /var/log/nginx/access.log
docker exec -it comedor_nginx tail -f /var/log/nginx/error.log

# Desde el host (si están mapeados)
Get-Content .\nginx\logs\access.log -Wait
Get-Content .\nginx\logs\error.log -Wait
```

### Verificar configuración de PHP

```powershell
docker exec -it comedor_php php -i
```

## 🔒 Seguridad

- ✅ Las contraseñas están en `.env` (ignorado por git)
- ✅ Archivos sensibles bloqueados en nginx (`.env`, `.log`, `.md`, etc.)
- ✅ PHP configurado en modo producción
- ✅ Backups automáticos antes de cada deployment en producción

## 📝 Notas Importantes

1. **Nunca commitees el archivo `.env`** - Contiene credenciales sensibles
2. **Siempre prueba en TEST antes de desplegar a PRODUCCIÓN**
3. **Los backups de producción se crean automáticamente** - Puedes restaurarlos si algo falla
4. **Los logs de nginx se almacenan en** `nginx/logs/`
5. **PHP está configurado con timezone America/Mexico_City** - Cambia en `php/custom.ini` si es necesario

## 🤝 Contribuir

1. Crea una rama para tu feature: `git checkout -b feature/nueva-funcionalidad`
2. Haz tus cambios y commit: `git commit -m "Agrega nueva funcionalidad"`
3. Push a la rama: `git push origin feature/nueva-funcionalidad`
4. Crea un Pull Request a `develop`
5. Después de aprobar y mergear a `develop`, se desplegará automáticamente a TEST
6. Una vez probado en TEST, mergea a `main` para desplegar a PRODUCCIÓN

## 📞 Soporte

Para problemas o preguntas:
1. Revisa la sección de [Troubleshooting](#troubleshooting)
2. Consulta los logs con los scripts de utilidad
3. Crea un issue en el repositorio

---

**Última actualización:** Enero 2026
**Versión:** 1.0.0
