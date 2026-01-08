# API Endpoint: Consulta de Consumos Semanales

## Descripción
Este endpoint permite consultar los consumos de un empleado para una semana específica (identificada por el lunes de esa semana).

---

## **GET** `/api/pedidos/mis-consumos`

### Autenticación
✅ **Requiere JWT Token** (Bearer Token en header `Authorization`)

### Headers
```http
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

### Query Parameters

| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
|-----------|------|-----------|-------------|---------|
| `fecha` | string | No | Fecha del lunes de la semana a consultar (formato: YYYY-MM-DD). Si no se proporciona, retorna la semana actual. | `2026-01-05` |

### Ejemplo de Request

```http
GET /api/pedidos/mis-consumos?fecha=2026-01-05
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## Respuesta Exitosa

### Status Code: `200 OK`

```json
{
  "success": true,
  "data": {
    "fecha_consulta": "2026-01-05",
    "fecha_formateada": "05/01/2026",
    "empleado": {
      "id_empleado": "12345",
      "nombre": "Juan Pérez",
      "area": "Sistemas"
    },
    "consumos": {
      "lunes": "Desayuno",
      "martes": "Comida",
      "miercoles": "",
      "jueves": "Desayuno",
      "viernes": "Comida"
    },
    "total_consumos": 4,
    "desglose": [
      {
        "dia": "lunes",
        "tipo": "Desayuno"
      },
      {
        "dia": "martes",
        "tipo": "Comida"
      },
      {
        "dia": "jueves",
        "tipo": "Desayuno"
      },
      {
        "dia": "viernes",
        "tipo": "Comida"
      }
    ]
  }
}
```

---

## Respuestas de Error

### 401 Unauthorized - Token inválido o expirado
```json
{
  "success": false,
  "error": "Token inválido o expirado"
}
```

### 400 Bad Request - Fecha inválida
```json
{
  "success": false,
  "error": "Formato de fecha inválido. Use YYYY-MM-DD"
}
```

### 404 Not Found - No hay consumos para esa semana
```json
{
  "success": true,
  "data": {
    "fecha_consulta": "2026-01-12",
    "fecha_formateada": "12/01/2026",
    "empleado": {
      "id_empleado": "12345",
      "nombre": "Juan Pérez",
      "area": "Sistemas"
    },
    "consumos": {
      "lunes": "",
      "martes": "",
      "miercoles": "",
      "jueves": "",
      "viernes": ""
    },
    "total_consumos": 0,
    "desglose": []
  }
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "error": "Error al consultar la base de datos"
}
```

---

## Lógica del Backend (Rust)

### 1. Extracción del Usuario del JWT
```rust
// El JWT contiene:
{
  "id_empleado": "12345",
  "nombre": "Juan Pérez",
  "area": "Sistemas",
  "usuario": "jperez"
}
```

### 2. SQL Query a Ejecutar

```sql
SELECT 
    Fecha, 
    c.Id_Empleado, 
    Nombre, 
    ISNULL(Lunes, '') as Lunes, 
    ISNULL(Martes, '') as Martes, 
    ISNULL(Miercoles, '') as Miercoles,
    ISNULL(Jueves, '') as Jueves,
    ISNULL(Viernes, '') as Viernes 
FROM (
    SELECT Id_Empleado, Nombre, Area 
    FROM [dbo].[Catalogo_EmpArea] 
    WHERE Id_Empleado = @id_empleado
) as a
LEFT JOIN (
    SELECT * 
    FROM [dbo].[PedidosComida] 
    WHERE Fecha = @fecha
) as c
ON a.Id_Empleado = c.Id_Empleado
```

**Parámetros:**
- `@id_empleado`: Obtenido del JWT (claims.id_empleado)
- `@fecha`: Del query parameter o lunes de la semana actual

### 3. Procesamiento de Resultados

```rust
// Pseudocódigo Rust
async fn obtener_consumos_semanales(
    claims: JwtClaims,
    fecha_opcional: Option<String>
) -> Result<ConsumosResponse, Error> {
    
    // 1. Determinar fecha a consultar
    let fecha_consulta = match fecha_opcional {
        Some(f) => validar_y_parsear_fecha(f)?,
        None => obtener_lunes_semana_actual()
    };
    
    // 2. Ejecutar query
    let result = db.query(
        SQL_QUERY,
        &[&claims.id_empleado, &fecha_consulta]
    ).await?;
    
    // 3. Procesar resultados
    let mut total_consumos = 0;
    let mut desglose = Vec::new();
    
    if let Some(row) = result.first() {
        let dias = ["lunes", "martes", "miercoles", "jueves", "viernes"];
        
        for dia in dias {
            let valor: String = row.get(dia)?;
            if valor == "Desayuno" || valor == "Comida" {
                total_consumos += 1;
                desglose.push(DesgloseDia {
                    dia: dia.to_string(),
                    tipo: valor
                });
            }
        }
        
        Ok(ConsumosResponse {
            success: true,
            data: ConsumosData {
                fecha_consulta: fecha_consulta.clone(),
                fecha_formateada: formatear_fecha(&fecha_consulta),
                empleado: EmpleadoInfo {
                    id_empleado: row.get("Id_Empleado")?,
                    nombre: row.get("Nombre")?,
                    area: claims.area.clone()
                },
                consumos: Consumos {
                    lunes: row.get("Lunes")?,
                    martes: row.get("Martes")?,
                    miercoles: row.get("Miercoles")?,
                    jueves: row.get("Jueves")?,
                    viernes: row.get("Viernes")?
                },
                total_consumos,
                desglose
            }
        })
    } else {
        // No hay datos, retornar consumos vacíos
        Ok(consumos_vacios(claims, fecha_consulta))
    }
}
```

---

## Endpoint Adicional: Obtener Semanas Disponibles

### **GET** `/api/pedidos/semanas-disponibles`

Retorna la lista de semanas (lunes) disponibles para consulta.

#### Autenticación
✅ **Requiere JWT Token**

#### Respuesta
```json
{
  "success": true,
  "data": {
    "semanas": [
      {
        "fecha": "2026-01-05",
        "fecha_formateada": "05/01/2026",
        "es_semana_actual": true
      },
      {
        "fecha": "2026-01-12",
        "fecha_formateada": "12/01/2026",
        "es_semana_actual": false
      },
      {
        "fecha": "2026-01-19",
        "fecha_formateada": "19/01/2026",
        "es_semana_actual": false
      }
    ],
    "semana_actual": "2026-01-05"
  }
}
```

#### Lógica Rust
```rust
async fn obtener_semanas_disponibles() -> Result<SemanasResponse, Error> {
    let hoy = Local::now().date_naive();
    let lunes_actual = obtener_lunes_de_semana(hoy);
    
    let mut semanas = Vec::new();
    let mut fecha_iteracion = lunes_actual;
    
    // Semanas desde la actual hasta 2 meses después
    for _ in 0..8 {
        semanas.push(SemanaInfo {
            fecha: fecha_iteracion.format("%Y-%m-%d").to_string(),
            fecha_formateada: fecha_iteracion.format("%d/%m/%Y").to_string(),
            es_semana_actual: fecha_iteracion == lunes_actual
        });
        
        fecha_iteracion += Duration::weeks(1);
    }
    
    Ok(SemanasResponse {
        success: true,
        data: SemanasData {
            semanas,
            semana_actual: lunes_actual.format("%Y-%m-%d").to_string()
        }
    })
}
```

---

## Estructuras de Datos (Rust)

```rust
use serde::{Deserialize, Serialize};
use chrono::NaiveDate;

#[derive(Debug, Serialize, Deserialize)]
pub struct ConsumosResponse {
    pub success: bool,
    pub data: ConsumosData,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct ConsumosData {
    pub fecha_consulta: String,
    pub fecha_formateada: String,
    pub empleado: EmpleadoInfo,
    pub consumos: Consumos,
    pub total_consumos: u32,
    pub desglose: Vec<DesgloseDia>,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct EmpleadoInfo {
    pub id_empleado: String,
    pub nombre: String,
    pub area: String,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct Consumos {
    pub lunes: String,
    pub martes: String,
    pub miercoles: String,
    pub jueves: String,
    pub viernes: String,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct DesgloseDia {
    pub dia: String,
    pub tipo: String, // "Desayuno" o "Comida"
}

#[derive(Debug, Serialize, Deserialize)]
pub struct SemanasResponse {
    pub success: bool,
    pub data: SemanasData,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct SemanasData {
    pub semanas: Vec<SemanaInfo>,
    pub semana_actual: String,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct SemanaInfo {
    pub fecha: String,
    pub fecha_formateada: String,
    pub es_semana_actual: bool,
}
```

---

## Base de Datos

### Tablas Involucradas

#### **Catalogo_EmpArea**
```sql
CREATE TABLE [dbo].[Catalogo_EmpArea] (
    [Id_Empleado] VARCHAR(50) PRIMARY KEY,
    [Nombre] VARCHAR(200),
    [Area] VARCHAR(200)
)
```

#### **PedidosComida**
```sql
CREATE TABLE [dbo].[PedidosComida] (
    [Id] INT IDENTITY(1,1) PRIMARY KEY,
    [Id_Empleado] VARCHAR(50),
    [Fecha] DATE,
    [Lunes] VARCHAR(50),
    [Martes] VARCHAR(50),
    [Miercoles] VARCHAR(50),
    [Jueves] VARCHAR(50),
    [Viernes] VARCHAR(50),
    [Nom_Pedido] VARCHAR(200),
    [Usuario] VARCHAR(100),
    [Contrasena] VARCHAR(100),
    [Costo] DECIMAL(10,2)
)
```

---

## Notas de Implementación

1. **Seguridad**: El endpoint SOLO debe retornar datos del empleado autenticado (obtenido del JWT). Nunca permitir consultar datos de otros empleados.

2. **Validación de Fecha**: 
   - Validar formato YYYY-MM-DD
   - Validar que sea un lunes (día 1 de la semana)
   - Si no es lunes, retornar error o ajustar al lunes más cercano

3. **Caché**: Considerar cachear las semanas disponibles ya que no cambian frecuentemente

4. **Performance**: El LEFT JOIN asegura que siempre se retorne información del empleado aunque no tenga pedidos

5. **Valores NULL**: Convertir NULL a string vacío "" en los días sin pedido

6. **Zona Horaria**: Usar la zona horaria del servidor para calcular "semana actual"

---

## Migración del Frontend

El archivo `AgendaPedidos1.php` debe modificarse para:

1. Reemplazar la conexión SQL directa por llamadas a la API
2. Usar el JWT token de la sesión
3. Manejar errores de la API

### Ejemplo de Migración PHP:

```php
<?php
require_once __DIR__ . '/config_api.php';
require_once __DIR__ . '/api_client.php';

session_start();

// Verificar autenticación
$api = getAPIClient();
if (!$api->isAuthenticated()) {
    header("Location: Login2.php");
    exit;
}

// Obtener semanas disponibles
$semanas_response = $api->get('api/pedidos/semanas-disponibles');
$semanas = $semanas_response['success'] ? $semanas_response['data']['semanas'] : [];

// Obtener consumos
$fecha_consulta = $_POST['fec'] ?? $semanas_response['data']['semana_actual'] ?? null;
$consumos_response = $api->get('api/pedidos/mis-consumos', ['fecha' => $fecha_consulta]);

if ($consumos_response['success']) {
    $datos = $consumos_response['data'];
    $empleado = $datos['empleado'];
    $consumos = $datos['consumos'];
    $total_consumos = $datos['total_consumos'];
} else {
    $error = $consumos_response['error'];
}
?>
```
