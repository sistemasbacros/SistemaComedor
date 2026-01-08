<?php
/**
 * VISOR DE LOGS EN TIEMPO REAL
 * Ejecutar: http://localhost:8000/ver_logs.php
 */

header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Logs - Debug</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            margin: 0;
        }
        h1 {
            color: #4ec9b0;
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 10px;
        }
        .log-container {
            background: #252526;
            border: 1px solid #3c3c3c;
            border-radius: 5px;
            padding: 20px;
            max-height: 800px;
            overflow-y: auto;
        }
        .log-entry {
            margin-bottom: 5px;
            padding: 5px;
            border-left: 3px solid transparent;
        }
        .log-api {
            color: #4ec9b0;
            border-left-color: #4ec9b0;
        }
        .log-login {
            color: #569cd6;
            border-left-color: #569cd6;
        }
        .log-area {
            color: #dcdcaa;
            border-left-color: #dcdcaa;
        }
        .log-redirect {
            color: #c586c0;
            border-left-color: #c586c0;
        }
        .log-error {
            color: #f48771;
            border-left-color: #f48771;
        }
        .refresh-btn {
            background: #0e639c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
            font-size: 16px;
        }
        .refresh-btn:hover {
            background: #1177bb;
        }
        .clear-btn {
            background: #c5352d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
            margin-left: 10px;
            font-size: 16px;
        }
        .clear-btn:hover {
            background: #e04039;
        }
        .info-box {
            background: #1e3a8a;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .timestamp {
            color: #808080;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>🔍 Visor de Logs - Sistema de Comedor</h1>
    
    <div class="info-box">
        <strong>📋 Instrucciones:</strong><br>
        1. Haz login en otra pestaña: <a href="Admiin.php" target="_blank" style="color: #60a5fa;">Admiin.php</a><br>
        2. Actualiza esta página para ver los logs generados<br>
        3. Los logs se colorean según el tipo de evento
    </div>
    
    <button class="refresh-btn" onclick="location.reload()">🔄 Actualizar Logs</button>
    <button class="clear-btn" onclick="clearLogs()">🗑️ Limpiar Logs (Solo Vista)</button>
    
    <div class="log-container" id="logContainer">
        <?php
        // Intentar leer el error log de PHP
        $phpErrorLog = ini_get('error_log');
        
        if (!$phpErrorLog || $phpErrorLog === 'syslog') {
            // En Windows con PHP built-in server, los logs van a stderr
            echo "<div class='log-error'>⚠️ Los logs se están mostrando en la consola donde ejecutaste PHP</div>";
            echo "<div class='log-entry'>Para verlos aquí, ejecuta PHP con: <code>php -S localhost:8000 2>&1 | tee logs.txt</code></div>";
            echo "<div class='log-entry'>&nbsp;</div>";
            echo "<div class='log-entry'><strong>O revisa la terminal donde ejecutaste PHP</strong></div>";
        } else {
            echo "<div class='log-entry'><strong>📂 Ubicación del archivo de logs:</strong> $phpErrorLog</div>";
            echo "<div class='log-entry'>&nbsp;</div>";
            
            if (file_exists($phpErrorLog)) {
                $fileSize = filesize($phpErrorLog);
                $lines = file($phpErrorLog);
                $totalLines = count($lines);
                
                echo "<div class='log-login'>✅ Archivo encontrado - Tamaño: " . number_format($fileSize) . " bytes - Líneas: $totalLines</div>";
                echo "<div class='log-entry'>&nbsp;</div>";
                
                // Mostrar últimas 100 líneas
                $lines = array_slice($lines, -100);
                
                if (empty($lines)) {
                    echo "<div class='log-entry'>ℹ️ El archivo existe pero está vacío. Haz login para generar logs.</div>";
                } else {
                    foreach ($lines as $line) {
                        $line = htmlspecialchars($line);
                        
                        // Colorear según tipo de log
                        $class = 'log-entry';
                        if (strpos($line, 'API DEBUG') !== false) {
                            $class = 'log-api';
                        } elseif (strpos($line, 'LOGIN EXITOSO') !== false || strpos($line, 'Intentando login') !== false) {
                            $class = 'log-login';
                        } elseif (strpos($line, 'VALIDACIÓN DE ÁREA') !== false || strpos($line, 'Área') !== false) {
                            $class = 'log-area';
                        } elseif (strpos($line, 'REDIRIGIENDO') !== false || strpos($line, 'MOSTRANDO MODAL') !== false) {
                            $class = 'log-redirect';
                        } elseif (strpos($line, 'error') !== false || strpos($line, 'Error') !== false) {
                            $class = 'log-error';
                        }
                        
                        echo "<div class='$class'>$line</div>";
                    }
                }
            } else {
                echo "<div class='log-error'>❌ El archivo de logs NO existe todavía</div>";
                echo "<div class='log-entry'>&nbsp;</div>";
                echo "<div class='log-entry'><strong>🔧 Posibles causas:</strong></div>";
                echo "<div class='log-entry'>1. No se han generado logs aún (haz login primero)</div>";
                echo "<div class='log-entry'>2. PHP no tiene permisos para escribir en: <code>$phpErrorLog</code></div>";
                echo "<div class='log-entry'>3. Los logs están yendo a la consola (stderr)</div>";
                echo "<div class='log-entry'>&nbsp;</div>";
                echo "<div class='log-login'><strong>✅ SOLUCIÓN MÁS FÁCIL:</strong></div>";
                echo "<div class='log-entry'>Mira la <strong>terminal/consola donde ejecutaste PHP</strong></div>";
                echo "<div class='log-entry'>Los logs aparecen ahí en tiempo real con colores y todo</div>";
                echo "<div class='log-entry'>&nbsp;</div>";
                
                // Intentar crear el archivo
                $dir = dirname($phpErrorLog);
                if (is_writable($dir)) {
                    echo "<div class='log-login'>✅ La carpeta $dir es escribible</div>";
                    echo "<div class='log-entry'>El archivo se creará automáticamente al primer error_log()</div>";
                } else {
                    echo "<div class='log-error'>❌ La carpeta $dir NO es escribible</div>";
                    echo "<div class='log-entry'>Los logs irán a stderr (consola) en su lugar</div>";
                }
            }
        }
        
        // Mostrar información de configuración
        echo "<div class='log-entry'>&nbsp;</div>";
        echo "<div class='log-entry'><strong>📁 Configuración PHP:</strong></div>";
        echo "<div class='log-entry'>Error Log: " . ($phpErrorLog ?: 'stderr (consola)') . "</div>";
        echo "<div class='log-entry'>Display Errors: " . (ini_get('display_errors') ?: 'Off') . "</div>";
        echo "<div class='log-entry'>Log Errors: " . (ini_get('log_errors') ? 'On' : 'Off') . "</div>";
        echo "<div class='log-entry'>Error Reporting: " . ini_get('error_reporting') . "</div>";
        ?>
    </div>
    
    <script>
        function clearLogs() {
            if (confirm('¿Limpiar la vista de logs? (esto solo limpia la vista, no el archivo)')) {
                document.getElementById('logContainer').innerHTML = '<div class="log-entry">✅ Vista limpiada. Actualiza para ver nuevos logs.</div>';
            }
        }
        
        // Auto-scroll al final
        window.onload = function() {
            const container = document.getElementById('logContainer');
            container.scrollTop = container.scrollHeight;
        };
    </script>
</body>
</html>
