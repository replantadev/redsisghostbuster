<?php
/**
 * Uninstall script para Replanta Ghost Orders Detector
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Eliminar opciones
delete_option('replanta_god_settings');

// Eliminar eventos cron
wp_clear_scheduled_hook('replanta_god_check_ghost_orders');
wp_clear_scheduled_hook('replanta_god_process_logs');

// Eliminar tabla de logs
global $wpdb;
$table_name = $wpdb->prefix . 'replanta_god_logs';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Log de desinstalación
error_log('Replanta Ghost Orders Detector - Desinstalado correctamente');
