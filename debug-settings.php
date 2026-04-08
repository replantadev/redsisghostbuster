<?php
/**
 * DEBUG: Test settings form loading
 * 
 * Para usar: http://tudominio.com/wp-content/plugins/replanta-rgb/debug-settings.php
 * O ejecutar: php debug-settings.php
 */

// Simular WordPress si no está cargado
if (!defined('ABSPATH')) {
    // Intentar cargar WordPress
    $wp_load_paths = [
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php',
    ];
    
    foreach ($wp_load_paths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            require_once __DIR__ . '/' . $path;
            break;
        }
    }
}

echo "=== DEBUG REPLANTA GHOST BUSTER ===\n\n";

// 1. Verificar constantes
echo "1. CONSTANTES:\n";
echo "   REPLANTA_GOD_VERSION: " . (defined('REPLANTA_GOD_VERSION') ? REPLANTA_GOD_VERSION : 'NO DEFINIDA') . "\n";
echo "   REPLANTA_GOD_DIR: " . (defined('REPLANTA_GOD_DIR') ? REPLANTA_GOD_DIR : 'NO DEFINIDA') . "\n";
echo "\n";

// 2. Verificar clases cargadas
echo "2. CLASES:\n";
$classes = [
    'Replanta_Ghost_Orders_Settings',
    'Replanta_Ghost_Orders_Logger',
    'Replanta_Ghost_Orders_LSWC',
    'Replanta_Ghost_Orders_Cloudflare_API',
    'Replanta_Ghost_Orders_Admin_Page',
];

foreach ($classes as $class) {
    echo "   $class: " . (class_exists($class) ? 'EXISTE' : 'NO EXISTE') . "\n";
}
echo "\n";

// 3. Verificar métodos específicos
echo "3. METODOS:\n";
if (class_exists('Replanta_Ghost_Orders_Settings')) {
    echo "   Settings::get_option(): " . (method_exists('Replanta_Ghost_Orders_Settings', 'get_option') ? 'EXISTE' : 'NO EXISTE') . "\n";
}
if (class_exists('Replanta_Ghost_Orders_LSWC')) {
    echo "   LSWC::is_lswc_active(): " . (method_exists('Replanta_Ghost_Orders_LSWC', 'is_lswc_active') ? 'EXISTE' : 'NO EXISTE') . "\n";
}
echo "\n";

// 4. Intentar obtener settings
echo "4. SETTINGS:\n";
try {
    if (class_exists('Replanta_Ghost_Orders_Settings')) {
        $settings = Replanta_Ghost_Orders_Settings::get_option();
        echo "   get_option() ejecutado OK\n";
        echo "   Claves: " . implode(', ', array_keys($settings)) . "\n";
    } else {
        echo "   [ERROR] Clase Settings no existe\n";
    }
} catch (Exception $e) {
    echo "   [ERROR] " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Intentar cargar template
echo "5. TEMPLATE:\n";
$template_path = __DIR__ . '/templates/settings-form.php';
echo "   Path: $template_path\n";
echo "   Existe: " . (file_exists($template_path) ? 'SI' : 'NO') . "\n";

if (file_exists($template_path)) {
    echo "   Intentando cargar...\n";
    try {
        ob_start();
        include $template_path;
        $output = ob_get_clean();
        echo "   [OK] Template cargado sin errores fatales\n";
        echo "   Output length: " . strlen($output) . " bytes\n";
        
        // Verificar si hay errores de PHP en el output
        if (strpos($output, 'Fatal error') !== false || strpos($output, 'Parse error') !== false) {
            echo "   [ADVERTENCIA] Se detectaron errores en el output:\n";
            echo "   " . substr($output, 0, 500) . "...\n";
        }
    } catch (Exception $e) {
        echo "   [ERROR] " . $e->getMessage() . "\n";
        echo "   Stack: " . $e->getTraceAsString() . "\n";
    }
}
echo "\n";

// 6. Verificar archivo JS
echo "6. JAVASCRIPT:\n";
$js_path = __DIR__ . '/assets/js/admin-script.js';
echo "   Path: $js_path\n";
echo "   Existe: " . (file_exists($js_path) ? 'SI' : 'NO') . "\n";
if (file_exists($js_path)) {
    echo "   Size: " . filesize($js_path) . " bytes\n";
}
echo "\n";

echo "=== FIN DEBUG ===\n";
