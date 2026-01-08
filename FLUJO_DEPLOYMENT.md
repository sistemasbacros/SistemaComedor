# 🔄 Flujo de Deployment - Sistema Comedor

## 📊 Diferenciación de Runners

### ¿Cómo se distinguen los runners?

Los runners se diferencian mediante **labels** que se configuran durante la instalación:

```powershell
# Runner de TEST
.\config.cmd --url https://github.com/sistemasbacros/SistemaComedor --token TOKEN --labels test,windows --name comedor-test-runner

# Runner de PRODUCCIÓN  
.\config.cmd --url https://github.com/sistemasbacros/SistemaComedor --token TOKEN --labels production,windows --name comedor-production-runner
```

### Labels que tendrá cada runner:

**Runner de TEST:**
- `self-hosted` (automático)
- `Windows` (automático)
- `X64` (automático)
- `test` (manual) ← **CLAVE para diferenciar**
- `windows` (manual)

**Runner de PRODUCCIÓN:**
- `self-hosted` (automático)
- `Windows` (automático)
- `X64` (automático)
- `production` (manual) ← **CLAVE para diferenciar**
- `windows` (manual)

## 🌿 Flujo de Ramas y Deployment

### Rama `develop` → Servidor de TEST

```yaml
# En .github/workflows/deploy-test.yml
on:
  push:
    branches:
      - develop  # ← Se activa al hacer push a develop

jobs:
  deploy:
    runs-on: [self-hosted, Windows, X64, test]  # ← Busca runner con label "test"
```

**Flujo:**
1. Haces cambios en tu código
2. Commit y push a `develop`:
   ```bash
   git checkout develop
   git add .
   git commit -m "Nueva funcionalidad"
   git push origin develop
   ```
3. GitHub Actions detecta el push a `develop`
4. Busca un runner con labels `[self-hosted, Windows, X64, test]`
5. Encuentra el **comedor-test-runner**
6. Ejecuta el deployment en el servidor de TEST
7. App disponible en `http://servidor-test:8080`

### Rama `main` → Servidor de PRODUCCIÓN

```yaml
# En .github/workflows/deploy-production.yml
on:
  push:
    branches:
      - main  # ← Se activa al hacer push a main

jobs:
  deploy:
    runs-on: [self-hosted, Windows, X64, production]  # ← Busca runner con label "production"
```

**Flujo:**
1. Después de probar en TEST, haces merge a `main`:
   ```bash
   git checkout main
   git merge develop
   git push origin main
   ```
2. GitHub Actions detecta el push a `main`
3. Busca un runner con labels `[self-hosted, Windows, X64, production]`
4. Encuentra el **comedor-production-runner**
5. Ejecuta el deployment en el servidor de PRODUCCIÓN
6. App disponible en `http://servidor-produccion:80`

## 🎯 Ejemplo Visual del Flujo

```
┌─────────────────────────────────────────────────────────────┐
│                    TU COMPUTADORA                           │
└─────────────────────────────────────────────────────────────┘
                           │
                           │ git push origin develop
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                       GITHUB                                │
│  Repositorio: sistemasbacros/SistemaComedor                 │
│  Rama: develop                                              │
└─────────────────────────────────────────────────────────────┘
                           │
                           │ Workflow: deploy-test.yml
                           │ runs-on: [self-hosted, Windows, X64, test]
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              SERVIDOR DE TEST                               │
│  Runner: comedor-test-runner                                │
│  Labels: [self-hosted, Windows, X64, test, windows]         │
│  Deployment: C:\deploy\ComedorTest                          │
│  Puerto: 8080                                               │
└─────────────────────────────────────────────────────────────┘

                  ✅ Pruebas exitosas ✅
                           │
                           │ git merge develop
                           │ git push origin main
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                       GITHUB                                │
│  Repositorio: sistemasbacros/SistemaComedor                 │
│  Rama: main                                                 │
└─────────────────────────────────────────────────────────────┘
                           │
                           │ Workflow: deploy-production.yml
                           │ runs-on: [self-hosted, Windows, X64, production]
                           ▼
┌─────────────────────────────────────────────────────────────┐
│           SERVIDOR DE PRODUCCIÓN                            │
│  Runner: comedor-production-runner                          │
│  Labels: [self-hosted, Windows, X64, production, windows]   │
│  Deployment: C:\deploy\ComedorProduccion                    │
│  Puerto: 80                                                 │
└─────────────────────────────────────────────────────────────┘
```

## 🔍 Verificación de Labels

### Ver labels de tus runners en GitHub:

1. Ve a: https://github.com/sistemasbacros/SistemaComedor/settings/actions/runners
2. Verás algo como:

```
✅ comedor-test-runner
   Status: Idle
   Labels: self-hosted, Windows, X64, test, windows
   ↑ Este runner solo ejecutará workflows que pidan "test"

✅ comedor-production-runner
   Status: Idle
   Labels: self-hosted, Windows, X64, production, windows
   ↑ Este runner solo ejecutará workflows que pidan "production"
```

### Ver labels desde el servidor:

```powershell
# En el servidor de TEST
cd C:\actions-runner-comedor-test
Get-Content .runner | Select-String "labels"

# En el servidor de PRODUCCIÓN
cd C:\actions-runner-comedor-production
Get-Content .runner | Select-String "labels"
```

## 📝 Resumen de Configuración

### Comandos Completos:

**TEST:**
```powershell
cd C:\actions-runner-comedor-test
.\config.cmd --url https://github.com/sistemasbacros/SistemaComedor `
             --token TU_TOKEN `
             --labels test,windows `
             --name comedor-test-runner
```

**PRODUCCIÓN:**
```powershell
cd C:\actions-runner-comedor-production
.\config.cmd --url https://github.com/sistemasbacros/SistemaComedor `
             --token TU_TOKEN `
             --labels production,windows `
             --name comedor-production-runner
```

## ⚠️ Importante

1. **Cada runner necesita su propio token** - Genera un token nuevo para cada uno
2. **Los labels son case-sensitive** - Usa `test` y `production` en minúsculas
3. **Los labels deben coincidir** con los workflows en `.github/workflows/`
4. **No puedes tener dos runners con los mismos labels** en el mismo servidor (causaría confusión)

## 🧪 Probar que Funciona

### 1. Verificar en GitHub:
```
https://github.com/sistemasbacros/SistemaComedor/settings/actions/runners
```
Ambos runners deben estar en verde (Idle)

### 2. Probar deployment a TEST:
```bash
git checkout develop
echo "<!-- Test $(date) -->" >> admicome4.php
git add .
git commit -m "Test: Verificar runner de test"
git push origin develop
```

Observa en: https://github.com/sistemasbacros/SistemaComedor/actions
- Debe ejecutarse en el runner **comedor-test-runner**

### 3. Probar deployment a PRODUCCIÓN:
```bash
git checkout main
git merge develop
git push origin main
```

Observa en: https://github.com/sistemasbacros/SistemaComedor/actions
- Debe ejecutarse en el runner **comedor-production-runner**

## 🎯 Checklist Final

- [ ] Runner de TEST instalado con labels: `test,windows`
- [ ] Runner de PRODUCCIÓN instalado con labels: `production,windows`
- [ ] Ambos runners visibles en GitHub (estado Idle/verde)
- [ ] Workflow de test se ejecuta solo en runner de TEST
- [ ] Workflow de producción se ejecuta solo en runner de PRODUCCIÓN
- [ ] Push a `develop` despliega en TEST
- [ ] Push a `main` despliega en PRODUCCIÓN

---

**¡Listo!** Ahora tienes deployment automático diferenciado por ambiente 🚀
