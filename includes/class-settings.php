<?php
/**
 * Gestor de configuraciones del plugin
 */

class Replanta_Ghost_Orders_Settings {
    
    const OPTION_KEY = 'replanta_god_settings';
    
    private static $defaults = [
        'mode' => 'vigilant',
        'detection_enabled' => true,
        'lswc_auto_config' => false,
        'cloudflare_auto_config' => false,
        'cloudflare_api_key' => '',
        'cloudflare_zone_id' => '',
        'days_back' => 30,
        'auto_process_orders' => false,
        'log_retention_days' => 90,
    ];
    
    public static function init() {
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('wp_ajax_replanta_god_save_settings', [__CLASS__, 'ajax_save_settings']);
    }
    
    public static function ajax_save_settings() {
        check_ajax_referer('replanta_god_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('No autorizado');
        }
        
        $settings = [
            'cloudflare_api_key' => sanitize_text_field($_POST['cloudflare_api_key'] ?? ''),
            'cloudflare_zone_id' => sanitize_text_field($_POST['cloudflare_zone_id'] ?? ''),
            'mode' => sanitize_text_field($_POST['replanta_mode'] ?? 'vigilant'),
            'detection_enabled' => !empty($_POST['detection_enabled']),
        ];
        
        // Mantener opciones existentes
        $existing = self::get_option();
        $merged = array_merge($existing, $settings);
        
        update_option(self::OPTION_KEY, $merged);
        
        Replanta_Ghost_Orders_Logger::add_event(
            0,
            'settings_updated',
            'Configuración actualizada por usuario',
            $settings
        );
        
        wp_send_json_success('Configuración guardada');
    }
    
    public static function register_settings() {
        register_setting(
            'replanta_god_settings_group',
            self::OPTION_KEY,
            [
                'type' => 'object',
                'sanitize_callback' => [__CLASS__, 'sanitize_settings'],
            ]
        );
    }
    
    public static function get_option($key = null, $default = null) {
        $settings = get_option(self::OPTION_KEY, self::$defaults);
        
        if ($key === null) {
            return array_merge(self::$defaults, $settings);
        }
        
        return isset($settings[$key]) ? $settings[$key] : ($default ?? self::$defaults[$key] ?? null);
    }
    
    public static function update_option($key, $value) {
        $settings = self::get_option();
        $settings[$key] = $value;
        update_option(self::OPTION_KEY, $settings);
    }
    
    public static function sanitize_settings($settings) {
        if (!is_array($settings)) {
            $settings = [];
        }
        
        return array_merge(self::$defaults, [
            'mode' => sanitize_text_field($settings['mode'] ?? 'vigilant'),
            'detection_enabled' => !empty($settings['detection_enabled']),
            'lswc_auto_config' => !empty($settings['lswc_auto_config']),
            'cloudflare_auto_config' => !empty($settings['cloudflare_auto_config']),
            'cloudflare_api_key' => sanitize_text_field($settings['cloudflare_api_key'] ?? ''),
            'cloudflare_zone_id' => sanitize_text_field($settings['cloudflare_zone_id'] ?? ''),
            'days_back' => absint($settings['days_back'] ?? 30),
            'auto_process_orders' => !empty($settings['auto_process_orders']),
            'log_retention_days' => absint($settings['log_retention_days'] ?? 90),
        ]);
    }
    
    public static function is_configured() {
        $cf_key = self::get_option('cloudflare_api_key');
        $cf_zone = self::get_option('cloudflare_zone_id');
        return !empty($cf_key) && !empty($cf_zone);
    }
    
    public static function get_mode() {
        return self::get_option('mode', 'vigilant');
    }
    
    public static function is_vigilant_mode() {
        return self::get_mode() === 'vigilant';
    }
}
