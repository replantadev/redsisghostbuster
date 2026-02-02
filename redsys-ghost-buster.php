<?php
/**
 * Plugin Name: Redsys Ghost Buster
 * Plugin URI: https://github.com/replantadev/redsisghostbuster
 * Description: Detecta y corrige pedidos fantasma pagados en Redsys pero cancelados en WooCommerce. Integración automática con Cloudflare y LiteSpeed Cache. Actualizaciones automáticas desde GitHub.
 * Version: 1.0.0
 * Author: Replanta
 * Author URI: https://replanta.es
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Text Domain: redsys-ghost-buster
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * GitHub Plugin URI: https://github.com/replantadev/redsisghostbuster
 * GitHub Branch: main
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('REPLANTA_GOD_VERSION', '1.0.0');
define('REPLANTA_GOD_FILE', __FILE__);
define('REPLANTA_GOD_DIR', plugin_dir_path(__FILE__));
define('REPLANTA_GOD_URL', plugin_dir_url(__FILE__));
define('REPLANTA_GOD_ASSETS', REPLANTA_GOD_URL . 'assets/');
define('REPLANTA_GOD_GITHUB_REPO', 'replantadev/redsisghostbuster');

// Verificar WooCommerce
require_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (!is_plugin_active('woocommerce/woocommerce.php')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>Redsys Ghost Buster</strong> requiere WooCommerce activo.</p></div>';
    });
    return;
}

// Cargar archivos del plugin
require_once REPLANTA_GOD_DIR . 'includes/class-settings.php';
require_once REPLANTA_GOD_DIR . 'includes/class-async-logger.php';
require_once REPLANTA_GOD_DIR . 'includes/class-cloudflare-api.php';
require_once REPLANTA_GOD_DIR . 'includes/class-lswc-config.php';
require_once REPLANTA_GOD_DIR . 'includes/class-detector.php';
require_once REPLANTA_GOD_DIR . 'includes/class-admin-page.php';
require_once REPLANTA_GOD_DIR . 'includes/class-github-updater.php';

// Hook de activación
register_activation_hook(__FILE__, 'replanta_god_activate');
function replanta_god_activate() {
    // Crear tabla de logging
    Replanta_Ghost_Orders_Logger::create_table();
    
    // Crear opciones por defecto
    if (!get_option('replanta_god_settings')) {
        update_option('replanta_god_settings', [
            'mode' => 'vigilant',
            'detection_enabled' => true,
            'lswc_auto_config' => false,
            'cloudflare_auto_config' => false,
            'days_back' => 30,
        ]);
    }
    
    // Limpiar caché de updates para forzar verificación
    delete_transient('replanta_god_github_update');
}

// Hook de desactivación
register_deactivation_hook(__FILE__, 'replanta_god_deactivate');
function replanta_god_deactivate() {
    // Limpiar eventos programados
    wp_clear_scheduled_hook('replanta_god_check_ghost_orders');
    wp_clear_scheduled_hook('replanta_god_process_logs');
}

// Inicializar el plugin
add_action('plugins_loaded', 'replanta_god_init');
function replanta_god_init() {
    // Cargar idiomas
    load_plugin_textdomain('redsys-ghost-buster', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Inicializar clases
    Replanta_Ghost_Orders_Settings::init();
    Replanta_Ghost_Orders_Logger::init();
    Replanta_Ghost_Orders_Cloudflare_API::init();
    Replanta_Ghost_Orders_LSWC::init();
    Replanta_Ghost_Orders_Detector::init();
    Replanta_Ghost_Orders_Admin_Page::init();
    
    // Inicializar sistema de actualizaciones desde GitHub
    Replanta_Ghost_Orders_GitHub_Updater::init();
}

// Encolar estilos y scripts del admin
add_action('admin_enqueue_scripts', 'replanta_god_enqueue_assets');
function replanta_god_enqueue_assets($hook) {
    // Solo en nuestras páginas
    if (strpos($hook, 'replanta-ghost-orders') === false) {
        return;
    }
    
    wp_enqueue_style(
        'replanta-god-admin',
        REPLANTA_GOD_ASSETS . 'css/admin-style.css',
        [],
        REPLANTA_GOD_VERSION
    );
    
    wp_enqueue_script(
        'replanta-god-admin',
        REPLANTA_GOD_ASSETS . 'js/admin-script.js',
        ['jquery'],
        REPLANTA_GOD_VERSION,
        true
    );
    
    wp_localize_script('replanta-god-admin', 'repl_god_obj', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('replanta_god_nonce'),
        'version' => REPLANTA_GOD_VERSION,
        'strings' => [
            'processing' => __('Procesando...', 'redsys-ghost-buster'),
            'success' => __('¡Éxito!', 'redsys-ghost-buster'),
            'error' => __('Error', 'redsys-ghost-buster'),
        ],
    ]);
}

// Hook para verificar pedidos fantasma (cada 6 horas)
if (!wp_next_scheduled('replanta_god_check_ghost_orders')) {
    wp_schedule_event(time(), 'twicedaily', 'replanta_god_check_ghost_orders');
}

add_action('replanta_god_check_ghost_orders', function() {
    Replanta_Ghost_Orders_Detector::check_ghost_orders();
});

// Procesar logs (cada hora, async)
if (!wp_next_scheduled('replanta_god_process_logs')) {
    wp_schedule_event(time(), 'hourly', 'replanta_god_process_logs');
}

add_action('replanta_god_process_logs', function() {
    Replanta_Ghost_Orders_Logger::process_queue();
});

// Añadir enlace de configuración en la lista de plugins
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=replanta-ghost-orders') . '">' . __('Configuración', 'redsys-ghost-buster') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});
