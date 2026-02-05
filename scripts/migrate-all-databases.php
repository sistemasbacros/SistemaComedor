<?php
/**
 * ============================================================================
 * SCRIPT DE MIGRACIÓN COMPLETA - Todas las conexiones hardcodeadas
 * ============================================================================
 *
 * Este script migra TODAS las conexiones hardcodeadas sin excepción
 *
 * Ejecutar: php scripts/migrate-all-databases.php
 */

$rootDir = dirname(__DIR__);

echo "============================================================================\n";
echo "  MIGRACIÓN COMPLETA DE TODAS LAS CONEXIONES\n";
echo "============================================================================\n\n";

// Buscar todos los archivos PHP recursivamente
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$updated = 0;
$skipped = 0;
$errors = 0;

foreach ($files as $file) {
    if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $filePath = $file->getPathname();

        // Saltar ciertos directorios
        if (strpos($filePath, 'vendor') !== false ||
            strpos($filePath, 'node_modules') !== false ||
            strpos($filePath, 'config' . DIRECTORY_SEPARATOR . 'database.php') !== false ||
            strpos($filePath, 'scripts' . DIRECTORY_SEPARATOR . 'migrate') !== false ||
            strpos($filePath, 'deprecated') !== false ||
            strpos($filePath, 'notepadpp-backups') !== false) {
            continue;
        }

        try {
            $content = file_get_contents($filePath);
            $originalContent = $content;
            $changes = 0;

            // 1. Agregar require si no existe
            if (strpos($content, "require_once __DIR__ . '/config/database.php'") === false &&
                strpos($content, 'require_once __DIR__ . "/config/database.php"') === false &&
                preg_match('/\$(?:serverName|connectionInfo|connectionOptions).*(?:Database|Comedor|ALQUIMISTA|BASENUEVA)/is', $content)) {

                if (preg_match('/^<\?php\s*/m', $content)) {
                    // Calcular la ruta relativa correcta
                    $depth = substr_count(str_replace($rootDir, '', $filePath), DIRECTORY_SEPARATOR) - 1;
                    $relativePath = str_repeat('../', $depth) . 'config/database.php';

                    $content = preg_replace(
                        '/^(<\?php)\s*/m',
                        "$1\n// Cargar configuración de base de datos desde .env\nrequire_once __DIR__ . '/$relativePath';\n\n",
                        $content,
                        1
                    );
                    $changes++;
                }
            }

            // 2. Reemplazar patrones de conexión COMEDOR (múltiples variantes)
            // Patrón 1: $serverName + $connectionOptions/connectionInfo (array())
            $pattern1 = '/\$serverName\s*=\s*["\'](?:DESAROLLO-BACRO|LUISROMERO)\\\\*(?:SQLEXPRESS|SQL)["\'];\s*\$connection(?:Options|Info)\s*=\s*array\s*\(\s*["\']Database["\'](?:\s*=>\s*|\s*=>)\s*["\']Comedor["\'].*?["\']PWD["\'](?:\s*=>\s*|\s*=>)\s*["\'][^"\']*["\'][^)]*\);/is';
            if (preg_match($pattern1, $content)) {
                $content = preg_replace(
                    $pattern1,
                    '$dbConfig = getComedorConfig(); $serverName = $dbConfig[\'serverName\']; $connectionOptions = $dbConfig[\'connectionOptions\'];',
                    $content
                );
                $changes++;
            }

            // Patrón 2: Solo $connectionInfo/Options con Database => Comedor
            $pattern2 = '/\$connection(?:Options|Info)\s*=\s*array\s*\(\s*["\']Database["\'](?:\s*=>\s*|\s*=>)\s*["\']Comedor["\'].*?["\'](?:PWD|Password)["\'](?:\s*=>\s*|\s*=>)\s*["\'][^"\']*["\'][^)]*\);/is';
            if (preg_match($pattern2, $content)) {
                $content = preg_replace(
                    $pattern2,
                    '$dbConfig = getComedorConfig(); $connectionInfo = $dbConfig[\'connectionOptions\'];',
                    $content
                );
                $changes++;
            }

            // Patrón 3: array literal inline sin variable
            $pattern3 = '/array\s*\(\s*["\']Database["\']\s*=>\s*["\']Comedor["\'].*?["\'](?:PWD|Password)["\']\s*=>\s*["\']Larome03["\'][^)]*\)/is';
            if (preg_match($pattern3, $content) && !preg_match('/getComedorConfig/', $content)) {
                $content = preg_replace(
                    $pattern3,
                    'getComedorConfig()[\'connectionOptions\']',
                    $content
                );
                $changes++;
            }

            // 3. Reemplazar ALQUIMISTA2024
            $patternAlq = '/\$serverNameContpaq\s*=\s*["\']WIN-44O80L37Q7M\\\\*COMERCIAL["\'];\s*\$connectionOptionsContpaq\s*=\s*array\s*\([^)]*ALQUIMISTA2024[^)]*\);/is';
            if (preg_match($patternAlq, $content)) {
                $content = preg_replace(
                    $patternAlq,
                    '$dbConfigAlq = getAlquimistaConfig(); $serverNameContpaq = $dbConfigAlq[\'serverName\']; $connectionOptionsContpaq = $dbConfigAlq[\'connectionOptions\'];',
                    $content
                );
                $changes++;
            }

            // 4. Reemplazar BASENUEVA
            $patternBase = '/\$serverNameBaseNueva\s*=\s*["\']WIN-44O80L37Q7M\\\\*COMERCIAL["\'];\s*\$connectionOptionsBaseNueva\s*=\s*array\s*\([^)]*BASENUEVA[^)]*\);/is';
            if (preg_match($patternBase, $content)) {
                $content = preg_replace(
                    $patternBase,
                    '$dbConfigBase = getBaseNuevaConfig(); $serverNameBaseNueva = $dbConfigBase[\'serverName\']; $connectionOptionsBaseNueva = $dbConfigBase[\'connectionOptions\'];',
                    $content
                );
                $changes++;
            }

            // 5. Base de datos KPI (nueva detección)
            $patternKPI = '/\$connection(?:Options|Info)\s*=\s*array\s*\(\s*["\']Database["\'].*?["\']KPI["\'].*?["\'](?:PWD|Password)["\'].*?["\'][^"\']*["\'][^)]*\);/is';
            if (preg_match($patternKPI, $content)) {
                echo "⚠️  KPI DB encontrada en: " . basename($filePath) . " (requiere configuración manual)\n";
            }

            // 6. Base de datos Ticket (nueva detección)
            $patternTicket = '/\$connection(?:Options|Info)\s*=\s*array\s*\(\s*["\']Database["\'].*?["\']Ticket["\'].*?["\'](?:PWD|Password)["\'].*?["\'][^"\']*["\'][^)]*\);/is';
            if (preg_match($patternTicket, $content)) {
                echo "⚠️  Ticket DB encontrada en: " . basename($filePath) . " (requiere configuración manual)\n";
            }

            // Verificar si hubo cambios
            if ($content !== $originalContent) {
                file_put_contents($filePath, $content);
                $relativePath = str_replace($rootDir . DIRECTORY_SEPARATOR, '', $filePath);
                echo "✅ MIGRADO: $relativePath ($changes cambios)\n";
                $updated++;
            }

        } catch (Exception $e) {
            echo "❌ ERROR: " . basename($filePath) . " - " . $e->getMessage() . "\n";
            $errors++;
        }
    }
}

echo "\n============================================================================\n";
echo "  RESUMEN\n";
echo "============================================================================\n";
echo "✅ Migrados:     $updated archivos\n";
echo "❌ Errores:      $errors archivos\n";
echo "\n";

if ($updated > 0) {
    echo "🎉 Migración completada!\n";
} else {
    echo "ℹ️  No se encontraron archivos para migrar.\n";
}

echo "\n";
?>
