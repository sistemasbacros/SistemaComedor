# 📋 Endpoints de API Necesarios para el Sistema Comedor

## Análisis de Consultas Actuales a la Base de Datos

Basado en el análisis del código, estos son los **endpoints de API que necesitas implementar** en tu backend (`http://localhost:3000`):

---

## 🔐 1. AUTENTICACIÓN

### ✅ `POST /auth/login` (Ya implementado)
```json
Request:
{
    "usuario": "adrian.ibarra",
    "contrasena": "Adriiba1029"
}

Response:
{
    "token": "eyJ0eXAiOiJKV1Qi...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user_info": {
        "id_empleado": 1029,
        "nombre": "IBARRA HERNANDEZ ADRIAN",
        "area": "Auditoria",
        "usuario": "adrian.ibarra"
    }
}
```

---

## 📊 2. DASHBOARD/ESTADÍSTICAS (admicome4.php)

### `GET /api/estadisticas/usuarios`
**Usado en:** admicome4.php (línea 204)
```sql
-- Consulta actual:
SELECT COUNT(*) as Total_Usuarios 
FROM (SELECT DISTINCT Usuario FROM PedidosComida WHERE Fecha >= ? AND Fecha <= ?)
```

**Endpoint necesario:**
```
GET /api/estadisticas/usuarios?fecha_inicio=2026-01-01&fecha_fin=2026-01-31
```

**Response esperado:**
```json
{
    "total_usuarios": 150,
    "fecha_inicio": "2026-01-01",
    "fecha_fin": "2026-01-31"
}
```

---

### `GET /api/estadisticas/exentos-desglose`
**Usado en:** admicome4.php (línea 219-253)
```sql
-- Consulta actual:
SELECT IdExento, Descripcion, COUNT(*) as Total
FROM Entradas
WHERE Fecha >= ? AND Fecha <= ?
GROUP BY IdExento, Descripcion
```

**Endpoint necesario:**
```
GET /api/estadisticas/exentos-desglose?fecha_inicio=2026-01-01&fecha_fin=2026-01-31
```

**Response esperado:**
```json
{
    "desglose": [
        {
            "id_exento": 1,
            "descripcion": "Personal Administrativo",
            "total": 45
        },
        {
            "id_exento": 2,
            "descripcion": "Personal de Planta",
            "total": 105
        }
    ],
    "total_general": 150
}
```

---

### `GET /api/estadisticas/comidas-por-tipo`
**Usado en:** admicome4.php (línea 272-284)
```sql
-- Consulta actual:
SELECT Tipo_Comida, COUNT(*) as Total
FROM Entradas
WHERE Fecha >= ? AND Fecha <= ?
GROUP BY Tipo_Comida
```

**Endpoint necesario:**
```
GET /api/estadisticas/comidas-por-tipo?fecha_inicio=2026-01-01&fecha_fin=2026-01-31
```

**Response esperado:**
```json
{
    "comidas": [
        {
            "tipo": "Desayuno",
            "total": 300
        },
        {
            "tipo": "Comida",
            "total": 450
        },
        {
            "tipo": "Cena",
            "total": 200
        }
    ]
}
```

---

### `GET /api/pedidos/agendados`
**Usado en:** admicome4.php (línea 304-343)
```sql
-- Consulta actual:
SELECT Id_Empleado, Nombre, Lunes, Martes, Miercoles, Jueves, Viernes
FROM PedidosComida
WHERE Fecha >= ? AND Fecha <= ?
```

**Endpoint necesario:**
```
GET /api/pedidos/agendados?fecha_inicio=2026-01-06&fecha_fin=2026-01-10
```

**Response esperado:**
```json
{
    "pedidos": [
        {
            "id_empleado": 1029,
            "nombre": "IBARRA HERNANDEZ ADRIAN",
            "lunes": "Comida",
            "martes": "Comida",
            "miercoles": "Desayuno",
            "jueves": "Comida",
            "viernes": "Desayuno"
        }
    ],
    "total_pedidos": 150
}
```

---

### `GET /api/cancelaciones/estadisticas`
**Usado en:** admicome4.php (línea 362-379)
```sql
-- Consulta actual:
SELECT Tipo_Cancelacion, COUNT(*) as Total
FROM cancelaciones
WHERE Fecha >= ? AND Fecha <= ?
GROUP BY Tipo_Cancelacion
```

**Endpoint necesario:**
```
GET /api/cancelaciones/estadisticas?fecha_inicio=2026-01-01&fecha_fin=2026-01-31
```

**Response esperado:**
```json
{
    "cancelaciones": [
        {
            "tipo": "Por empleado",
            "total": 25
        },
        {
            "tipo": "Por enfermedad",
            "total": 10
        },
        {
            "tipo": "Otras razones",
            "total": 5
        }
    ],
    "total_cancelaciones": 40
}
```

---

## 📅 3. AGENDA DE PEDIDOS (AgendaPedidos.php)

### `GET /api/pedidos/resumen-semanal`
**Usado en:** AgendaPedidos.php (línea 9-50)
```sql
-- Consulta actual:
SELECT Fecha, fecha_dia, 
       CLunes, DLunes, CMartes, DMartes, 
       CMiercoles, DMiercoles, CJueves, DJueves, 
       CViernes, DViernes
FROM (consulta compleja con UNION de todos los días)
```

**Endpoint necesario:**
```
GET /api/pedidos/resumen-semanal?fecha=2026-01-06
```

**Response esperado:**
```json
{
    "fecha_inicio": "2026-01-06",
    "resumen": [
        {
            "dia": "Lunes",
            "fecha": "2026-01-06",
            "comida": 45,
            "desayuno": 30
        },
        {
            "dia": "Martes",
            "fecha": "2026-01-07",
            "comida": 50,
            "desayuno": 28
        }
        // ... resto de días
    ]
}
```

---

### `GET /api/pedidos/detalle-empleados`
**Usado en:** AgendaPedidos.php (línea 51-60)
```sql
-- Consulta actual:
SELECT c.Id_Empleado, Nombre, 
       Lunes, Martes, Miercoles, Jueves, Viernes
FROM Catalogo_EmpArea a
LEFT JOIN PedidosComida c ON a.Id_Empleado = c.Id_Empleado
WHERE Fecha BETWEEN ? AND ?
```

**Endpoint necesario:**
```
GET /api/pedidos/detalle-empleados?fecha_inicio=2026-01-06&fecha_fin=2026-01-10
```

**Response esperado:**
```json
{
    "pedidos": [
        {
            "id_empleado": 1029,
            "nombre": "IBARRA HERNANDEZ ADRIAN",
            "lunes": "Comida",
            "martes": "Comida",
            "miercoles": "Desayuno",
            "jueves": "",
            "viernes": "Comida"
        }
    ]
}
```

---

## 👥 4. CATÁLOGOS

### `GET /api/empleados`
**Usado en:** Múltiples archivos
```sql
-- Consulta actual:
SELECT Id_Empleado, Nombre, Area 
FROM Catalogo_EmpArea
WHERE Area = ?  -- Opcional
```

**Endpoint necesario:**
```
GET /api/empleados
GET /api/empleados?area=Auditoria
GET /api/empleados/{id}
```

**Response esperado:**
```json
{
    "empleados": [
        {
            "id_empleado": 1029,
            "nombre": "IBARRA HERNANDEZ ADRIAN",
            "area": "Auditoria"
        }
    ]
}
```

---

### `GET /api/tipos-comida`
**Para seleccionar tipo de comida en pedidos**

**Response esperado:**
```json
{
    "tipos": [
        {
            "id": 1,
            "nombre": "Desayuno",
            "descripcion": "7:00 AM - 9:00 AM"
        },
        {
            "id": 2,
            "nombre": "Comida",
            "descripcion": "1:00 PM - 3:00 PM"
        },
        {
            "id": 3,
            "nombre": "Cena",
            "descripcion": "7:00 PM - 9:00 PM"
        }
    ]
}
```

---

## 📝 5. GESTIÓN DE PEDIDOS

### `POST /api/pedidos`
**Crear nuevo pedido semanal**

**Request:**
```json
{
    "id_empleado": 1029,
    "fecha_inicio": "2026-01-06",
    "pedidos": {
        "lunes": "Comida",
        "martes": "Comida",
        "miercoles": "Desayuno",
        "jueves": "Comida",
        "viernes": "Desayuno"
    }
}
```

**Response:**
```json
{
    "success": true,
    "id_pedido": 12345,
    "mensaje": "Pedidos registrados correctamente"
}
```

---

### `PUT /api/pedidos/{id}`
**Actualizar pedido existente**

**Request:**
```json
{
    "pedidos": {
        "lunes": "Desayuno",  // Cambio
        "martes": "Comida"
    }
}
```

---

### `DELETE /api/pedidos/{id}`
**Cancelar pedido**

**Request:**
```json
{
    "motivo": "Enfermedad",
    "tipo_cancelacion": "Por empleado"
}
```

---

## 🔍 6. ENTRADAS/REGISTROS

### `GET /api/entradas`
**Obtener registros de entrada al comedor**

```
GET /api/entradas?fecha=2026-01-06
GET /api/entradas?fecha_inicio=2026-01-01&fecha_fin=2026-01-31
GET /api/entradas?id_empleado=1029
```

**Response:**
```json
{
    "entradas": [
        {
            "id": 1,
            "id_empleado": 1029,
            "nombre": "IBARRA HERNANDEZ ADRIAN",
            "fecha": "2026-01-06",
            "hora": "13:45:00",
            "tipo_comida": "Comida",
            "id_exento": 1,
            "descripcion_exento": "Personal Administrativo"
        }
    ],
    "total": 150
}
```

---

### `POST /api/entradas`
**Registrar entrada (checador)**

**Request:**
```json
{
    "id_empleado": 1029,
    "tipo_comida": "Comida",
    "fecha": "2026-01-06",
    "hora": "13:45:00"
}
```

---

## ❌ 7. CANCELACIONES

### `GET /api/cancelaciones`
```
GET /api/cancelaciones?fecha=2026-01-06
GET /api/cancelaciones?id_empleado=1029
```

**Response:**
```json
{
    "cancelaciones": [
        {
            "id": 1,
            "id_empleado": 1029,
            "nombre": "IBARRA HERNANDEZ ADRIAN",
            "fecha_pedido": "2026-01-06",
            "tipo_comida": "Comida",
            "fecha_cancelacion": "2026-01-05",
            "motivo": "Enfermedad",
            "tipo_cancelacion": "Por empleado",
            "estado": "Aprobada"
        }
    ]
}
```

---

### `POST /api/cancelaciones`
**Crear cancelación**

**Request:**
```json
{
    "id_empleado": 1029,
    "fecha_pedido": "2026-01-06",
    "tipo_comida": "Comida",
    "motivo": "Enfermedad",
    "tipo_cancelacion": "Por empleado"
}
```

---

### `PUT /api/cancelaciones/{id}/aprobar`
**Aprobar/rechazar cancelación**

**Request:**
```json
{
    "estado": "Aprobada",
    "comentarios": "Aprobado por dirección"
}
```

---

## 🏢 8. INTEGRACIONES EXTERNAS (Opcional)

### `GET /api/integracion/contpaq-alquimista`
**Usado en:** admicome4.php (línea 459-620)
```sql
-- Consulta actual a bases externas:
SELECT * FROM ALQUIMISTA2024.[dbo].docDocument
SELECT * FROM BASENUEVA.[dbo].docDocument
```

**Endpoint necesario:**
```
GET /api/integracion/contpaq-alquimista?fecha=2026-01-06
```

**Response:**
```json
{
    "documentos_alquimista": [
        {
            "id": 123,
            "fecha": "2026-01-06",
            "tipo": "Factura"
        }
    ],
    "documentos_basenueva": [
        {
            "id": 456,
            "fecha": "2026-01-06",
            "tipo": "Nota"
        }
    ]
}
```

---

## 📋 RESUMEN DE ENDPOINTS NECESARIOS

| Método | Endpoint | Descripción | Prioridad |
|--------|----------|-------------|-----------|
| ✅ POST | `/auth/login` | Autenticación | **Alta** (Ya existe) |
| GET | `/api/empleados` | Catálogo de empleados | **Alta** |
| GET | `/api/pedidos/agendados` | Pedidos semanales | **Alta** |
| GET | `/api/pedidos/detalle-empleados` | Detalle de pedidos | **Alta** |
| POST | `/api/pedidos` | Crear pedido | **Alta** |
| PUT | `/api/pedidos/{id}` | Actualizar pedido | **Alta** |
| DELETE | `/api/pedidos/{id}` | Cancelar pedido | **Alta** |
| GET | `/api/estadisticas/usuarios` | Total usuarios | **Media** |
| GET | `/api/estadisticas/comidas-por-tipo` | Estadísticas comidas | **Media** |
| GET | `/api/cancelaciones` | Listado cancelaciones | **Media** |
| POST | `/api/cancelaciones` | Crear cancelación | **Media** |
| GET | `/api/entradas` | Registros de entrada | **Media** |
| POST | `/api/entradas` | Registrar entrada | **Media** |
| GET | `/api/tipos-comida` | Catálogo tipos de comida | **Baja** |
| GET | `/api/integracion/contpaq-alquimista` | Integración CONTPAQi | **Baja** |

---

## 🚀 PLAN DE IMPLEMENTACIÓN RECOMENDADO

### **Fase 1: Funcionalidad Básica (Alta prioridad)**
1. ✅ `/auth/login` (Ya existe)
2. `/api/empleados` 
3. `/api/pedidos` (GET, POST, PUT, DELETE)
4. `/api/pedidos/agendados`

### **Fase 2: Estadísticas y Reportes (Media prioridad)**
5. `/api/estadisticas/*`
6. `/api/cancelaciones` (GET, POST)
7. `/api/entradas` (GET, POST)

### **Fase 3: Integraciones (Baja prioridad)**
8. `/api/integracion/contpaq-alquimista`
9. Optimizaciones y mejoras

---

## 💡 NOTAS IMPORTANTES

1. **Autenticación:** Todos los endpoints (excepto `/auth/login`) requieren el header:
   ```
   Authorization: Bearer {token}
   ```

2. **Paginación:** Para endpoints que retornan muchos datos, considera agregar:
   ```
   GET /api/pedidos?page=1&limit=50
   ```

3. **Filtros comunes:**
   - `fecha_inicio` y `fecha_fin`
   - `id_empleado`
   - `area`
   - `tipo_comida`

4. **Formato de fechas:** Usar ISO 8601: `YYYY-MM-DD`

5. **Formato de horas:** Usar formato 24h: `HH:MM:SS`

---

¿Necesitas que te ayude a modificar algún archivo PHP específico para que consuma estos endpoints?
