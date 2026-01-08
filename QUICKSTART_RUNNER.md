# 🚀 Guía Rápida: Configurar GitHub Runner

## Para Servidor de TEST:

```powershell
# 1. Abrir PowerShell como Administrador

# 2. Crear directorio
New-Item -Path "C:\actions-runner-comedor-test" -ItemType Directory -Force
cd C:\actions-runner-comedor-test

# 3. Ir a GitHub:
# - Abre https://github.com/sistemasbacros/SistemaComedor
# - Ve a Settings → Actions → Runners → New self-hosted runner
# - Selecciona Windows
# - COPIA los comandos que GitHub te muestre

# 4. Ejecutar comandos de GitHub (ejemplo):
Invoke-WebRequest -Uri https://github.com/actions/runner/releases/download/v2.311.0/actions-runner-win-x64-2.311.0.zip -OutFile actions-runner.zip
Expand-Archive -Path actions-runner.zip -DestinationPath . -Force

# 5. Configurar runner con el comando de GitHub:
.\config.cmd --url https://github.com/sistemasbacros/SistemaComedor --token TU_TOKEN_AQUI

# Cuando pregunte:
# - Runner name: comedor-test-runner
# - Additional labels: test,windows

# 6. Instalar como servicio
.\svc.install.ps1
.\svc.start.ps1

# 7. Verificar
Get-Service actions.runner.*
```

## Para Servidor de PRODUCCIÓN:

```powershell
# Mismo proceso pero con estos cambios:

cd C:\actions-runner-comedor-production  # Directorio diferente

# Al configurar:
# - Runner name: comedor-production-runner
# - Additional labels: production,windows
```

## ✅ Verificar que funciona:

1. **En GitHub**: Ve a Settings → Actions → Runners
   - Debes ver tus runners en verde (Idle)

2. **Probar deployment**:
```bash
# En tu computadora
git checkout develop
echo "<!-- test -->" >> admicome4.php
git add .
git commit -m "Test deployment"
git push origin develop
```

3. **Ver el deployment**: Ve a Actions en GitHub y observa el workflow ejecutándose

## 📋 Checklist:

- [ ] Docker Desktop instalado en el servidor
- [ ] Runner configurado con label correcto (`test` o `production`)
- [ ] Servicio corriendo (verde en GitHub)
- [ ] Archivo `.env` configurado en `C:\deploy\ComedorTest` o `C:\deploy\ComedorProduccion`
- [ ] Primer deployment exitoso

## 🆘 Problemas comunes:

**Runner no aparece en GitHub:**
```powershell
cd C:\actions-runner-comedor-test
.\svc.start.ps1
```

**Docker no funciona en el workflow:**
```powershell
# Verificar que Docker está corriendo
docker --version

# Reiniciar runner
cd C:\actions-runner-comedor-test
.\svc.stop.ps1
.\svc.start.ps1
```

---
Ver guía completa en: [GITHUB_RUNNER_SETUP.md](GITHUB_RUNNER_SETUP.md)
