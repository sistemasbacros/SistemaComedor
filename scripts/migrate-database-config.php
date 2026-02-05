<?php
/**
 * ============================================================================
 * SCRIPT DE MIGRACIÓN - Conexiones de Base de Datos a .env
 * ============================================================================
 *
 * Este script actualiza automáticamente todos los archivos PHP que tienen
 * conexiones hardcodeadas para usar el archivo config/database.php
 *
 * Ejecutar: php scripts/migrate-database-config.php
 */

$rootDir = dirname(__DIR__);
$configInclude = "require_once __DIR__ . '/config/database.php';";

// Archivos a actualizar (encontrados con grep)
$filesToUpdate = [
    'Admiin.php',              // Ya actualizado manualmente
    'AgendaPedidos.php',
    'AgendaPedidos1.php',
    'Consultadedatos.php',
    'Compras01.php',
    'Compras.php',
    'am.php',
    'am2.php',
    'CocinaTotalPedidos.php',
    'check_pending_cancelations.php',
    'dchef.php',
    'admicome4.php',
    'CHECADORF.php',
    'FormatCancel.php',
    'Formacancel123456.php',
    'EstadCancelaciones.php',
    'd1.php',
    'EstadisticasCancelaciones.php',
    'Estformcancel.php',
    'LoginFormCancel.php',
    'GenerarQRNuevoRegistro.php',
    'GenerarQR.php',
    'Desglosechecador.php',
    'Descrip_Consumo.php',
    'demolecturaQR.php',
    'gestusu.php',
    'Login2.php',
    'MenComprasCocina.php',
    'LoginValidarOrdenes.php',
    'LoingValidarOrdenes.php',
    'KPI_anacomp.php',
    'FormCanAprobUpdate.php',
    'DEMENU.php',
    'descUsuario.php',
    'dem1.php',
    'aparta_consumo_modificado.php',
    'Menpedidos.php',
];

echo "============================================================================\n";
echo "  MIGRACIÓN DE CONEXIONES DE BASE DE DATOS A .env\n";
echo "============================================================================\n\n";

$updated = 0;
$skipped = 0;
$errors = 0;

foreach ($filesToUpdate as $file) {
    $filePath = $rootDir . '/' . $file;

    if (!file_exists($filePath)) {
        echo "⏭️  SKIP: $file (no existe)\n";
        $skipped++;
        continue;
    }

    try {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // Verificar si ya tiene el require de config/database.php
        if (strpos($content, "require_once __DIR__ . '/config/database.php'") !== false ||
            strpos($content, 'require_once __DIR__ . "/config/database.php"') !== false) {
            echo "✅ OK: $file (ya migrado)\n";
            $skipped++;
            continue;
        }

        // 1. Agregar el require al inicio del archivo PHP
        if (preg_match('/^<\?php\s*/m', $content)) {
            $content = preg_replace(
                '/^(<\?php)\s*/m',
                "$1\n// Cargar configuración de base de datos desde .env\nrequire_once __DIR__ . '/config/database.php';\n\n",
                $content,
                1
            );
        }

        // 2. Reemplazar conexiones hardcodeadas a COMEDOR
        $content = preg_replace(
            '/\$serverName\s*=\s*["\']DESAROLLO-BACRO\\\\+SQLEXPRESS["\'];?\s*\n\s*\$connectionOptions\s*=\s*array\s*\(\s*["\']Database["\']\s*=>\s*["\']Comedor["\']\s*,\s*["\'](?:UID|Uid)["\']\s*=>\s*["\']Larome03["\']\s*,\s*["\']PWD["\']\s*=>\s*["\']Larome03["\']\s*,?\s*(?:["\']CharacterSet["\']\s*=>\s*["\']UTF-8["\']\s*,?\s*)?\);?/i',
            '$dbConfig = getComedorConfig(); $serverName = $dbConfig[\'serverName\']; $connectionOptions = $dbConfig[\'connectionOptions\'];',
            $content
        );

        $content = preg_replace(
            '/\$serverName\s*=\s*["\']DESAROLLO-BACRO\\\\+SQLEXPRESS["\'];?\s*\n\s*\$connectionInfo\s*=\s*array\s*\(\s*["\']Database["\']\s*=>\s*["\']Comedor["\']\s*,\s*["\'](?:UID|Uid)["\']\s*=>\s*["\']Larome03["\']\s*,\s*["\']PWD["\']\s*=>\s*["\']Larome03["\']\s*,?\s*(?:["\']CharacterSet["\']\s*=>\s*["\']UTF-8["\']\s*,?\s*)?\);?/i',
            '$dbConfig = getComedorConfig(); $serverName = $dbConfig[\'serverName\']; $connectionInfo = $dbConfig[\'connectionOptions\'];',
            $content
        );

        // 3. Reemplazar conexiones a ALQUIMISTA2024
        $content = preg_replace(
            '/\$serverNameContpaq\s*=\s*["\']WIN-44O80L37Q7M\\\\+COMERCIAL["\'];?\s*\n\s*\$connectionOptionsContpaq\s*=\s*array\s*\(\s*["\']Database["\']\s*=>\s*["\']ALQUIMISTA2024["\']\s*,\s*["\'](?:UID|Uid)["\']\s*=>\s*["\']sa["\']\s*,\s*["\']PWD["\']\s*=>\s*["\']Administrador1\*["\']\s*,?\s*(?:["\']CharacterSet["\']\s*=>\s*["\']UTF-8["\']\s*,?\s*)?\);?/i',
            '$dbConfigAlq = getAlquimistaConfig(); $serverNameContpaq = $dbConfigAlq[\'serverName\']; $connectionOptionsContpaq = $dbConfigAlq[\'connectionOptions\'];',
            $content
        );

        // 4. Reemplazar conexiones a BASENUEVA
        $content = preg_replace(
            '/\$serverNameBaseNueva\s*=\s*["\']WIN-44O80L37Q7M\\\\+COMERCIAL["\'];?\s*\n\s*\$connectionOptionsBaseNueva\s*=\s*array\s*\(\s*["\']Database["\']\s*=>\s*["\']BASENUEVA["\']\s*,\s*["\'](?:UID|Uid)["\']\s*=>\s*["\']sa["\']\s*,\s*["\']PWD["\']\s*=>\s*["\']Administrador1\*["\']\s*,?\s*(?:["\']CharacterSet["\']\s*=>\s*["\']UTF-8["\']\s*,?\s*)?\);?/i',
            '$dbConfigBase = getBaseNuevaConfig(); $serverNameBaseNueva = $dbConfigBase[\'serverName\']; $connectionOptionsBaseNueva = $dbConfigBase[\'connectionOptions\'];',
            $content
        );

        // Verificar si hubo cambios
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "✅ MIGRADO: $file\n";
            $updated++;
        } else {
            echo "⏭️  NO CAMBIOS: $file (no se encontraron conexiones para migrar)\n";
            $skipped++;
        }

    } catch (Exception $e) {
        echo "❌ ERROR: $file - " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n============================================================================\n";
echo "  RESUMEN\n";
echo "============================================================================\n";
echo "✅ Migrados:     $updated archivos\n";
echo "⏭️  Omitidos:     $skipped archivos\n";
echo "❌ Errores:      $errors archivos\n";
echo "\n";

if ($updated > 0) {
    echo "🎉 Migración completada exitosamente!\n";
    echo "\nPróximos pasos:\n";
    echo "1. Verificar que todos los archivos funcionen correctamente\n";
    echo "2. Probar las conexiones en desarrollo\n";
    echo "3. Hacer commit de los cambios\n";
    echo "4. NUNCA commitear el archivo .env (ya está en .gitignore)\n";
} else {
    echo "⚠️  No se actualizó ningún archivo.\n";
}

echo "\n";
?>
