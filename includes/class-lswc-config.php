<?php
/**
 * Configuración automática de LiteSpeed Cache
 */

class Replanta_Ghost_Orders_LSWC {
    
    public static function init() {
        add_action('wp_ajax_replanta_god_apply_lswc_rules', [__CLASS__, 'ajax_apply_rules']);
        add_action('wp_ajax_replanta_god_test_lswc', [__CLASS__, 'ajax_test']);
    }
    
    /**
     * Verificar si LiteSpeed Cache está activo
     */
    public static function is_lswc_active() {
        return defined('LSWC_PLUGIN_NAME');
    }
    
    /**
     * Obtener reglas LSWC recomendadas
     */
    public static function get_required_exclusions() {
        return [
            'excl_paths' => [
                '/carrito',
                '/finalizar-compra',
                '/pedido-recibido',
                '/pagar',
                '/wc-api/*',
                '/?wc-api=*',
            ],
            'excl_qs' => [
                '?wc-api=WC_redsys',
                '?wc-api=WC_redsys_redirect',
                '?coupon=',
                '?apply_coupon=',
            ],
            'excl_cookies' => [
                'woocommerce_cart_hash',
                'woocommerce_items_in_cart',
                'wp_woocommerce_session_',
                'wordpress_logged_in_',
                'wp-postpass_',
                'store_notice',
                'woocommerce_recently_viewed',
            ],
        ];
    }
    
    /**
     * Aplicar reglas a LiteSpeed Cache
     */
    public static function apply_rules() {
        if (!self::is_lswc_active()) {
            return [
                'success' => false,
                'message' => 'LiteSpeed Cache no está activo',
            ];
        }
        
        $rules = self::get_required_exclusions();
        
        // Actualizar opciones de LSWC
        update_option('litespeed-cache-exc_uri', implode('\n', $rules['excl_paths']));
        update_option('litespeed-cache-exc_qs', implode('\n', $rules['excl_qs']));
        update_option('litespeed-cache-exc_cookies', implode('\n', $rules['excl_cookies']));
        
        Replanta_Ghost_Orders_Logger::add_event(
            0,
            'lswc_config',
            'Reglas LSWC aplicadas automáticamente',
            $rules,
            'completed'
        );
        
        Replanta_Ghost_Orders_Settings::update_option('lswc_auto_config', true);
        
        return [
            'success' => true,
            'message' => 'Reglas LSWC aplicadas correctamente',
        ];
    }
    
    /**
     * Obtener estado de la configuración
     */
    public static function get_status() {
        if (!self::is_lswc_active()) {
            return [
                'active' => false,
                'message' => 'LiteSpeed Cache no está instalado',
            ];
        }
        
        $exc_uri = get_option('litespeed-cache-exc_uri', '');
        $exc_qs = get_option('litespeed-cache-exc_qs', '');
        $exc_cookies = get_option('litespeed-cache-exc_cookies', '');
        
        $rules = self::get_required_exclusions();
        
        return [
            'active' => true,
            'configured' => Replanta_Ghost_Orders_Settings::get_option('lswc_auto_config', false),
            'rules' => $rules,
            'current' => [
                'exc_uri' => $exc_uri,
                'exc_qs' => $exc_qs,
                'exc_cookies' => $exc_cookies,
            ],
        ];
    }
    
    /**
     * AJAX - Aplicar reglas
     */
    public static function ajax_apply_rules() {
        check_ajax_referer('replanta_god_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('No autorizado');
        }
        
        $result = self::apply_rules();
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX - Probar
     */
    public static function ajax_test() {
        check_ajax_referer('replanta_god_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('No autorizado');
        }
        
        $status = self::get_status();
        wp_send_json_success($status);
    }
}
