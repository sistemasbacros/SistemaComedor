# Guía Rápida de Comandos Docker

## 🚀 Comandos Básicos

### Iniciar la aplicación
```powershell
# Producción (puerto 80)
.\scripts\docker-start.ps1

# Test (puerto 8080)
.\scripts\docker-start.ps1 -Environment test
```

### Detener la aplicación
```powershell
.\scripts\docker-stop.ps1
```

### Reiniciar
```powershell
.\scripts\docker-restart.ps1
```

### Ver estado
```powershell
.\scripts\docker-status.ps1
```

### Ver logs
```powershell
# Todos los logs
.\scripts\docker-logs.ps1

# Solo nginx
.\scripts\docker-logs.ps1 -Service nginx

# En tiempo real
.\scripts\docker-logs.ps1 -Follow
```

### Reconstruir imágenes
```powershell
# Con cache
.\scripts\docker-rebuild.ps1

# Sin cache (limpio)
.\scripts\docker-rebuild.ps1 -NoCache
```

## 🔧 Comandos Docker Directos

### Ver contenedores corriendo
```powershell
docker ps
```

### Ver todos los contenedores (incluyendo detenidos)
```powershell
docker ps -a
```

### Ver logs de un contenedor específico
```powershell
docker logs comedor_nginx
docker logs comedor_php
```

### Entrar a un contenedor (bash/shell)
```powershell
# Nginx
docker exec -it comedor_nginx sh

# PHP
docker exec -it comedor_php bash
```

### Ver uso de recursos
```powershell
docker stats
```

### Limpiar sistema
```powershell
# Contenedores detenidos
docker container prune -f

# Imágenes no usadas
docker image prune -f

# Todo (⚠️ cuidado!)
docker system prune -a -f
```

## 📁 Acceso Rápido

### Archivos de configuración importantes
- **Variables de entorno:** `.env`
- **Configuración PHP:** `php\custom.ini`
- **Configuración Nginx:** `nginx\nginx.conf`
- **Docker Compose Prod:** `docker-compose.yml`
- **Docker Compose Test:** `docker-compose.test.yml`

### Ubicaciones de deployment
- **Producción:** `C:\deploy\ComedorProduccion`
- **Test:** `C:\deploy\ComedorTest`

## 🆘 Troubleshooting Rápido

### No funciona después de iniciar
```powershell
# Ver logs para identificar el problema
.\scripts\docker-logs.ps1 -Follow
```

### Error de conexión a base de datos
```powershell
# Verificar variables de entorno
Get-Content .env

# Ver logs de PHP
.\scripts\docker-logs.ps1 -Service php
```

### Puerto ocupado
```powershell
# Ver qué está usando el puerto 80
netstat -ano | findstr :80

# Cambiar puerto en .env
# HTTP_PORT=8080
```

### Contenedores no inician
```powershell
# Reconstruir sin cache
.\scripts\docker-rebuild.ps1 -NoCache

# Ver estado de Docker
docker info
```
