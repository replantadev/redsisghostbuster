<?php
/**
 * Health check y diagnóstico del sistema
 */

class Replanta_Ghost_Orders_Health {
    
    public static function check_requirements() {
        $issues = [];
        
        // WordPress version
        if (version_compare($GLOBALS['wp_version'], '5.0', '<')) {
            $issues[] = [
                'level' => 'error',
                'message' => 'WordPress 5.0+ requerido',
            ];
        }
        
        // PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $issues[] = [
                'level' => 'error',
                'message' => 'PHP 7.4+ requerido',
            ];
        }
        
        // WooCommerce active
        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            $issues[] = [
                'level' => 'error',
                'message' => 'WooCommerce debe estar activado',
            ];
        }
        
        // Cloudflare configured
        if (!Replanta_Ghost_Orders_Settings::is_configured()) {
            $issues[] = [
                'level' => 'warning',
                'message' => 'Cloudflare no está configurado',
            ];
        }
        
        // LiteSpeed Cache
        if (defined('LSWC_PLUGIN_NAME')) {
            $status = Replanta_Ghost_Orders_LSWC::get_status();
            if (!$status['configured']) {
                $issues[] = [
                    'level' => 'warning',
                    'message' => 'LiteSpeed Cache no tiene las reglas de Redsys aplicadas',
                ];
            }
        }
        
        return $issues;
    }
    
    public static function get_system_info() {
        return [
            'wordpress_version' => $GLOBALS['wp_version'],
            'php_version' => PHP_VERSION,
            'plugin_version' => REPLANTA_GOD_VERSION,
            'woocommerce_version' => WC()->version,
            'mysql_version' => self::get_mysql_version(),
            'timezone' => get_option('timezone_string'),
            'multisite' => is_multisite() ? 'Sí' : 'No',
        ];
    }
    
    private static function get_mysql_version() {
        global $wpdb;
        return $wpdb->db_version();
    }
}
