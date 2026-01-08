# 🔧 Gestión de Múltiples Runners en el Mismo Servidor

## 🤔 El Problema: Múltiples Runners en un Servidor

Si tienes **varios proyectos** con runners en el mismo servidor, es importante organizarlos correctamente para evitar conflictos.

## ✅ Cómo Funcionan Múltiples Runners

### Cada Runner es Independiente:

```
Servidor de Producción
├── C:\actions-runner-backend-rust\         ← Runner para Backend Rust
│   ├── Labels: production, rust, windows
│   └── Despliega en: C:\deploy\BackendRustBacrosDesarrollo
│
├── C:\actions-runner-comedor-production\   ← Runner para Comedor
│   ├── Labels: production, comedor, windows
│   └── Despliega en: C:\deploy\ComedorProduccion
│
└── C:\actions-runner-otro-proyecto\        ← Runner para otro proyecto
    ├── Labels: production, otro, windows
    └── Despliega en: C:\deploy\OtroProyecto
```

### ✨ La Clave: Labels Únicos

Los **labels** determinan qué runner ejecuta cada workflow. Si configuras labels únicos, no habrá conflictos.

## 🎯 Solución Recomendada para Comedor

### Opción 1: Labels Específicos del Proyecto (RECOMENDADO)

```powershell
# Runner de TEST para Comedor
.\config.cmd --url https://github.com/sistemasbacros/SistemaComedor `
             --token TU_TOKEN `
             --labels test,comedor,windows `
             --name comedor-test-runner

# Runner de PRODUCCIÓN para Comedor  
.\config.cmd --url https://github.com/sistemasbacros/SistemaComedor `
             --token TU_TOKEN `
             --labels production,comedor,windows `
             --name comedor-production-runner
```

Luego, en tus workflows de GitHub Actions:

```yaml
# .github/workflows/deploy-test.yml
jobs:
  deploy:
    runs-on: [self-hosted, Windows, X64, test, comedor]  # ← Busca runner con "comedor"

# .github/workflows/deploy-production.yml  
jobs:
  deploy:
    runs-on: [self-hosted, Windows, X64, production, comedor]  # ← Busca runner con "comedor"
```

### ¿Por qué funciona?

- **GitHub Actions busca un runner que tenga TODOS los labels** especificados en `runs-on`
- Si especificas `[self-hosted, Windows, X64, production, comedor]`, solo el runner de Comedor lo ejecutará
- Los otros runners (Backend Rust, etc.) NO se activarán porque no tienen el label "comedor"

## 🔍 Ejemplo con Múltiples Proyectos

### Backend Rust:
```yaml
# Workflow de Backend Rust
runs-on: [self-hosted, Windows, X64, production, rust]
# ↓ Solo ejecuta el runner con label "rust"
```

### Comedor:
```yaml
# Workflow de Comedor
runs-on: [self-hosted, Windows, X64, production, comedor]
# ↓ Solo ejecuta el runner con label "comedor"
```

### Otro Proyecto:
```yaml
# Workflow de Otro Proyecto
runs-on: [self-hosted, Windows, X64, production, otro]
# ↓ Solo ejecuta el runner con label "otro"
```

## 📂 Organización de Directorios

Cada proyecto despliega en su propio directorio:

```
C:\
├── actions-runner-backend-rust\
│   └── _work\BackendRustBacrosDesarrollo\...
│
├── actions-runner-comedor-production\
│   └── _work\SistemaComedor\...
│
├── deploy\
│   ├── BackendRustBacrosDesarrollo\
│   │   └── (archivos del backend rust)
│   │
│   ├── ComedorProduccion\
│   │   └── (archivos del comedor)
│   │
│   └── ComedorTest\
│       └── (archivos del comedor test)
```

**No hay conflicto** porque:
- Cada runner tiene su propio directorio de trabajo (`_work`)
- Cada proyecto despliega en su propio directorio (`C:\deploy\NombreProyecto`)

## 🚀 Continúa con tu Instalación

Ya descargaste el archivo, continúa con estos pasos:

```powershell
# Estás aquí:
PS C:\actions-runner-comedor-production>

# 1. Extraer el archivo
Expand-Archive -Path actions-runner-win-x64-2.330.0.zip -DestinationPath . -Force

# 2. Configurar con labels específicos para Comedor
.\config.cmd --url https://github.com/sistemasbacros/SistemaComedor `
             --token TU_TOKEN `
             --labels production,comedor,windows `
             --name comedor-production-runner

# Cuando pregunte:
# - Runner group: [Enter] (default)
# - Runner name: comedor-production-runner
# - Additional labels: production,comedor,windows
# - Work folder: [Enter] (default)

# 3. Instalar como servicio
.\svc.install.ps1

# 4. Iniciar
.\svc.start.ps1

# 5. Verificar
Get-Service actions.runner.*
```

## 🔧 Actualizar Workflows de Comedor

Necesitas actualizar los workflows para usar los labels específicos:

### .github/workflows/deploy-test.yml
```yaml
jobs:
  deploy:
    name: Build and Deploy to Test Server
    runs-on: [self-hosted, Windows, X64, test, comedor]  # ← Agregado "comedor"
```

### .github/workflows/deploy-production.yml
```yaml
jobs:
  deploy:
    name: Build and Deploy to Production Server
    runs-on: [self-hosted, Windows, X64, production, comedor]  # ← Agregado "comedor"
```

## ✅ Verificación Final

### 1. Ver todos tus runners en GitHub:

Ve a cada repositorio:
- Backend Rust: https://github.com/sistemasbacros/BackendRustBacrosDesarrollo/settings/actions/runners
- Comedor: https://github.com/sistemasbacros/SistemaComedor/settings/actions/runners

### 2. Verificar labels en el servidor:

```powershell
# Ver todos los servicios de runners
Get-Service actions.runner.* | Select-Object Name, Status, DisplayName

# Ver labels de cada runner
Get-ChildItem C:\ -Filter ".runner" -Recurse -ErrorAction SilentlyContinue | 
    ForEach-Object { 
        Write-Host "`nRunner: $($_.DirectoryName)" -ForegroundColor Cyan
        Get-Content $_.FullName | Select-String "labels"
    }
```

## 📊 Resultado Esperado

```
Servidor de Producción:
├─ Runner: backend-rust-production
│  Labels: self-hosted, Windows, X64, production, rust, windows
│  Ejecuta workflows de: BackendRustBacrosDesarrollo
│  Despliega en: C:\deploy\BackendRustBacrosDesarrollo
│
├─ Runner: comedor-production-runner
│  Labels: self-hosted, Windows, X64, production, comedor, windows
│  Ejecuta workflows de: SistemaComedor (main)
│  Despliega en: C:\deploy\ComedorProduccion
│
└─ Cada uno funciona independientemente sin conflictos ✅
```

## 🎯 Resumen

1. **SÍ puedes tener múltiples runners** en el mismo servidor
2. **Usa labels únicos** para cada proyecto (ej: `comedor`, `rust`, `otro`)
3. **Actualiza los workflows** para incluir el label específico del proyecto
4. **Cada runner despliega en su propio directorio** sin conflictos
5. **Los servicios son independientes** y no se interfieren entre sí

## 🔄 Próximo Paso

Continúa con la configuración del runner de Comedor usando los labels específicos y luego actualiza los workflows en GitHub para que incluyan el label "comedor".

---

¿Necesitas ayuda para actualizar los workflows? Puedo hacerlo por ti.
