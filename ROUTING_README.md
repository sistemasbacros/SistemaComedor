# Sistema de Routing PHP - Sin iframes

## 🎯 Descripción

Sistema de routing moderno para PHP, similar a Express.js (Node.js), que elimina los iframes y usa inclusión dinámica de componentes para mejorar el rendimiento y la experiencia de usuario.

## 📁 Estructura del Proyecto

```
Comedor/
├── MenUsuario_v2.php      # Página principal con routing
├── router.php             # Sistema de routing (clase Router)
├── components/            # Componentes de cada sección
│   ├── pedidos.php       # Módulo de pedidos
│   ├── consulta.php      # Módulo de consulta
│   ├── reporte.php       # Módulo de reportes
│   └── generar-qr.php    # Módulo de generación QR
└── [archivos originales] # Se mantienen para compatibilidad
```

## 🚀 Características

### ✅ Ventajas sobre iframes:

1. **Mejor rendimiento**: No se cargan múltiples páginas completas
2. **SEO mejorado**: Contenido renderizado en la misma página
3. **Gestión de sesiones más simple**: Una sola sesión para toda la app
4. **URLs limpias**: `MenUsuario_v2.php?page=pedidos`
5. **Navegación más rápida**: Sin recargas de página completa
6. **Compartir enlaces**: URLs directas a cada sección
7. **Sin problemas de CORS**: Todo en el mismo dominio
8. **Historial del navegador**: Funciona correctamente con atrás/adelante

### 🔧 Cómo funciona:

```php
// 1. Se define la ruta actual desde el parámetro GET
$currentRoute = $_GET['page'] ?? 'pedidos';

// 2. El router busca el componente correspondiente
$router->addRoute('pedidos', 'pedidos.php', 'Sistema de Pedidos', 'clipboard-list');

// 3. Se incluye dinámicamente el componente
include __DIR__ . '/components/pedidos.php';
```

## 📝 Uso

### Acceder a una sección:

```
# Pedidos (default)
MenUsuario_v2.php
MenUsuario_v2.php?page=pedidos

# Consulta
MenUsuario_v2.php?page=consulta

# Reportes
MenUsuario_v2.php?page=reporte

# Generador QR
MenUsuario_v2.php?page=qr
```

### Generar URLs desde PHP:

```php
// En cualquier componente o página
$url = $router->url('pedidos');                    // MenUsuario_v2.php?page=pedidos
$url = $router->url('consulta', ['id' => 123]);    // MenUsuario_v2.php?page=consulta&id=123
```

### Verificar ruta activa:

```php
<?php if ($router->isActive('pedidos')): ?>
    <a class="active">Pedidos</a>
<?php endif; ?>
```

## 🔒 Seguridad

- ✅ Previene acceso directo a componentes (define `ROUTER_ACCESS`)
- ✅ Valida que las rutas existan antes de renderizar
- ✅ Mantiene la autenticación de sesión
- ✅ Limpia el output de archivos incluidos (remueve HTML duplicado)

## 🎨 Componentes

Cada componente es un archivo PHP independiente que:

1. Verifica acceso (`ROUTER_ACCESS`)
2. Incluye el archivo original (Menpedidos1.php, etc.)
3. Limpia el HTML duplicado
4. Renderiza solo el contenido necesario

### Ejemplo de componente:

```php
<?php
if (!defined('ROUTER_ACCESS')) {
    die('Acceso directo no permitido');
}
?>

<div class="component-container">
    <div class="component-header">
        <h2>Mi Componente</h2>
    </div>
    <div class="component-content">
        <?php include __DIR__ . '/../archivo-original.php'; ?>
    </div>
</div>
```

## 🔄 Migración desde iframes

### Antes (con iframes):
```html
<iframe src="Menpedidos1.php"></iframe>
```

### Ahora (con routing):
```php
<?php $router->renderComponent(); ?>
```

## 📊 Comparación de Performance

| Aspecto | Con iframes | Con routing |
|---------|-------------|-------------|
| Peticiones HTTP | 5+ por página | 1 por navegación |
| Memoria | Alta (múltiples DOM) | Baja (un solo DOM) |
| Sesiones | Múltiples contextos | Un solo contexto |
| Velocidad de carga | Lenta | Rápida |
| SEO | ❌ Malo | ✅ Bueno |

## 🛠️ Personalización

### Agregar una nueva ruta:

1. **Crear el componente** en `components/mi-nueva-seccion.php`
2. **Registrar la ruta** en `router.php`:

```php
$router->addRoute(
    'mi-seccion',              // Nombre de la ruta
    'mi-nueva-seccion.php',    // Archivo del componente
    'Mi Nueva Sección',        // Título
    'star',                    // Ícono de Font Awesome
    true                       // Habilitado (true/false)
);
```

3. **Agregar enlace** en el sidebar (opcional, se genera automáticamente)

## 🐛 Troubleshooting

### Problema: "Acceso directo no permitido"
**Solución**: Asegúrate de que `ROUTER_ACCESS` esté definido en `MenUsuario_v2.php`

### Problema: Componente no se muestra
**Solución**: Verifica que el archivo existe en `components/` y que la ruta está registrada

### Problema: CSS/JS duplicado
**Solución**: Los componentes ya limpian las etiquetas `<html>`, `<head>`, `<body>`

## 📖 Clase Router - API

### Métodos principales:

```php
// Agregar ruta
$router->addRoute($name, $component, $title, $icon, $enabled);

// Obtener ruta actual
$router->getCurrentRoute();

// Verificar si existe una ruta
$router->routeExists($routeName);

// Generar URL
$router->url($routeName, $params);

// Verificar si es la ruta activa
$router->isActive($routeName);

// Renderizar componente
$router->renderComponent();
```

## 🚀 Próximos pasos (opcional)

- [ ] Implementar AJAX para navegación sin recarga completa
- [ ] Agregar cache de componentes
- [ ] Implementar breadcrumbs automáticos
- [ ] Sistema de permisos por ruta
- [ ] Middleware para pre-procesamiento

## 📞 Soporte

Si encuentras problemas o tienes preguntas sobre el sistema de routing, revisa este documento primero.

## ✨ Ventajas finales

- ✅ Código más limpio y mantenible
- ✅ Mejor experiencia de usuario
- ✅ Mayor rendimiento
- ✅ URLs compartibles
- ✅ Historial del navegador funcional
- ✅ Sin problemas de sesiones entre iframes
- ✅ Fácil de extender con nuevas rutas
