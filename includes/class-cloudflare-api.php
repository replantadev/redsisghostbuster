<?php
/**
 * API de Cloudflare - Crear y actualizar reglas
 */

class Replanta_Ghost_Orders_Cloudflare_API {
    
    private static $api_url = 'https://api.cloudflare.com/client/v4';
    private static $timeout = 15;
    
    public static function init() {
        add_action('wp_ajax_replanta_god_apply_cf_rules', [__CLASS__, 'ajax_apply_rules']);
        add_action('wp_ajax_replanta_god_test_cf', [__CLASS__, 'ajax_test_connection']);
    }
    
    /**
     * Obtener credenciales de Cloudflare
     */
    private static function get_credentials() {
        $api_key = Replanta_Ghost_Orders_Settings::get_option('cloudflare_api_key');
        $zone_id = Replanta_Ghost_Orders_Settings::get_option('cloudflare_zone_id');
        
        if (empty($api_key) || empty($zone_id)) {
            return false;
        }
        
        return [
            'api_key' => $api_key,
            'zone_id' => $zone_id,
        ];
    }
    
    /**
     * Hacer request a Cloudflare API
     */
    private static function api_request($method, $endpoint, $data = null) {
        $creds = self::get_credentials();
        if (!$creds) {
            return [
                'success' => false,
                'message' => 'Credenciales de Cloudflare no configuradas',
            ];
        }
        
        $url = self::$api_url . $endpoint;
        
        $headers = [
            'X-Auth-Key' => $creds['api_key'],
            'X-Auth-Email' => get_option('admin_email'),
            'Content-Type' => 'application/json',
        ];
        
        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => self::$timeout,
        ];
        
        if ($data !== null) {
            $args['body'] = wp_json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return $body;
    }
    
    /**
     * Probar conexión con Cloudflare
     */
    public static function test_connection() {
        $response = self::api_request('GET', '/zones');
        return isset($response['success']) && $response['success'];
    }
    
    /**
     * Crear regla WAF para Redsys
     */
    public static function create_redsys_waf_rule() {
        $creds = self::get_credentials();
        if (!$creds) {
            return [
                'success' => false,
                'message' => 'Cloudflare no configurado',
            ];
        }
        
        $rule_data = [
            'name' => 'Allow Redsys IPN - Replanta',
            'description' => 'Permite notificaciones IPN de Redsys sin bloqueos de seguridad',
            'expression' => '(http.request.uri.query contains "wc-api=WC_redsys") or (ip.geoip.asnum eq 31627)',
            'action' => 'skip',
            'action_parameters' => [
                'ruleset' => [
                    'execute_phases' => [
                        'phase4',  // Browser Integrity Check
                        'phase1',  // Super Bot Fight Mode
                    ],
                ],
            ],
            'enabled' => true,
        ];
        
        $response = self::api_request(
            'POST',
            "/zones/{$creds['zone_id']}/rulesets/phases/http_request_firewall_custom/rules",
            $rule_data
        );
        
        Replanta_Ghost_Orders_Logger::add_event(
            0,
            'cloudflare_rule',
            'Intento de crear regla WAF en Cloudflare',
            $response,
            $response['success'] ? 'completed' : 'failed'
        );
        
        return $response;
    }
    
    /**
     * Crear regla de bypass de caché para IPN
     */
    public static function create_cache_bypass_rule() {
        $creds = self::get_credentials();
        if (!$creds) {
            return [
                'success' => false,
                'message' => 'Cloudflare no configurado',
            ];
        }
        
        $rule_data = [
            'actions' => [
                [
                    'id' => 'disable_cache',
                ],
            ],
            'condition' => '(http.request.uri.query contains "wc-api=WC_redsys")',
            'description' => 'Bypass de caché para notificaciones IPN de Redsys',
            'priority' => 1,
            'enabled' => true,
        ];
        
        $response = self::api_request(
            'POST',
            "/zones/{$creds['zone_id']}/page_rules",
            $rule_data
        );
        
        return $response;
    }
    
    /**
     * AJAX - Aplicar reglas
     */
    public static function ajax_apply_rules() {
        check_ajax_referer('replanta_god_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('No autorizado');
        }
        
        $response = self::create_redsys_waf_rule();
        
        if ($response['success']) {
            Replanta_Ghost_Orders_Settings::update_option('cloudflare_auto_config', true);
            wp_send_json_success([
                'message' => '✅ Regla de Cloudflare aplicada correctamente',
            ]);
        } else {
            wp_send_json_error($response['message'] ?? 'Error al aplicar regla');
        }
    }
    
    /**
     * AJAX - Probar conexión
     */
    public static function ajax_test_connection() {
        check_ajax_referer('replanta_god_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('No autorizado');
        }
        
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $zone_id = sanitize_text_field($_POST['zone_id'] ?? '');
        
        if (empty($api_key) || empty($zone_id)) {
            wp_send_json_error('API Key y Zone ID requeridos');
        }
        
        // Guardar temporalmente para la prueba
        $original_key = Replanta_Ghost_Orders_Settings::get_option('cloudflare_api_key');
        $original_zone = Replanta_Ghost_Orders_Settings::get_option('cloudflare_zone_id');
        
        Replanta_Ghost_Orders_Settings::update_option('cloudflare_api_key', $api_key);
        Replanta_Ghost_Orders_Settings::update_option('cloudflare_zone_id', $zone_id);
        
        $test = self::test_connection();
        
        // Restaurar si falla
        if (!$test) {
            Replanta_Ghost_Orders_Settings::update_option('cloudflare_api_key', $original_key);
            Replanta_Ghost_Orders_Settings::update_option('cloudflare_zone_id', $original_zone);
            wp_send_json_error('Error de conexión con Cloudflare');
        }
        
        wp_send_json_success('✅ Conexión exitosa con Cloudflare');
    }
}
