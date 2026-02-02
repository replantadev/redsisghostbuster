<?php
/**
 * Detector de pedidos fantasma - Lógica central
 */

class Replanta_Ghost_Orders_Detector {
    
    public static function init() {
        add_action('wp_ajax_replanta_god_get_ghost_orders', [__CLASS__, 'ajax_get_ghost_orders']);
        add_action('wp_ajax_replanta_god_process_bulk', [__CLASS__, 'ajax_process_bulk']);
        add_action('wp_ajax_replanta_god_sync_order', [__CLASS__, 'ajax_sync_order']);
    }
    
    /**
     * Obtener dato de autorización de Redsys
     */
    public static function get_redsys_data($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }
        
        $data = [
            'is_authorized' => false,
            'auth_code' => '',
            'ds_response' => '',
            'transaction_id' => '',
            'sources' => [],
        ];
        
        // Buscar código de autorización
        $auth_codes = [
            get_post_meta($order_id, '_authorisation_code_redsys', true),
            get_post_meta($order_id, '_redsys_authorisation_code', true),
            get_post_meta($order_id, 'Ds_AuthorisationCode', true),
        ];
        
        foreach ($auth_codes as $code) {
            if (!empty($code)) {
                $data['auth_code'] = $code;
                $data['is_authorized'] = true;
                $data['sources'][] = 'authorization_code_meta';
                break;
            }
        }
        
        // Buscar Ds_Response
        $responses = [
            get_post_meta($order_id, '_redsys_Ds_Response', true),
            get_post_meta($order_id, 'Ds_Response', true),
        ];
        
        foreach ($responses as $response) {
            if ($response === '0000' || $response === '000') {
                $data['ds_response'] = $response;
                $data['is_authorized'] = true;
                $data['sources'][] = 'ds_response_meta';
                break;
            }
        }
        
        // Buscar en notas del pedido
        if (!$data['is_authorized']) {
            $notes = wc_get_order_notes(['order_id' => $order_id, 'limit' => 20]);
            foreach ($notes as $note) {
                $content = strtolower($note->content);
                if (preg_match('/autorizado|authorized|pago completado|payment completed/', $content) ||
                    preg_match('/ds_response.*0000/', $content)) {
                    $data['is_authorized'] = true;
                    $data['sources'][] = 'order_notes';
                    break;
                }
            }
        }
        
        // Obtener más datos de meta
        $data['transaction_id'] = get_post_meta($order_id, '_payment_order_number_redsys', true) ?: 
                                  get_post_meta($order_id, '_redsys_transaction_id', true);
        
        return $data;
    }
    
    /**
     * Detectar si es un pedido fantasma
     */
    public static function is_ghost_order($order) {
        $order_id = $order->get_id();
        $status = $order->get_status();
        
        // Solo considerar pedidos con estatus sospechosos
        $suspicious_statuses = ['cancelled', 'pending', 'failed', 'on-hold'];
        if (!in_array($status, $suspicious_statuses)) {
            return false;
        }
        
        // Verificar si tiene datos de Redsys
        $redsys_data = self::get_redsys_data($order_id);
        
        return $redsys_data['is_authorized'];
    }
    
    /**
     * Obtener todos los pedidos fantasma
     */
    public static function get_ghost_orders($days = 30) {
        $args = [
            'status' => ['cancelled', 'pending', 'failed', 'on-hold'],
            'date_created' => '>' . strtotime("-{$days} days"),
            'limit' => 200,
            'orderby' => 'date',
            'order' => 'DESC',
        ];
        
        $orders = wc_get_orders($args);
        $ghost_orders = [];
        
        foreach ($orders as $order) {
            if (self::is_ghost_order($order)) {
                $redsys_data = self::get_redsys_data($order->get_id());
                $ghost_orders[] = [
                    'order' => $order,
                    'order_id' => $order->get_id(),
                    'status' => $order->get_status(),
                    'total' => $order->get_total(),
                    'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                    'customer' => $order->get_formatted_billing_full_name(),
                    'email' => $order->get_billing_email(),
                    'redsys_data' => $redsys_data,
                ];
            }
        }
        
        return $ghost_orders;
    }
    
    /**
     * Chequeo programado de pedidos fantasma
     */
    public static function check_ghost_orders() {
        if (!Replanta_Ghost_Orders_Settings::get_option('detection_enabled')) {
            return;
        }
        
        $ghost_orders = self::get_ghost_orders(7); // Últimos 7 días
        
        foreach ($ghost_orders as $ghost) {
            Replanta_Ghost_Orders_Logger::add_event(
                $ghost['order_id'],
                'ghost_order_detected',
                sprintf(
                    'Pedido fantasma detectado: %s (%s)',
                    $ghost['order']->get_order_number(),
                    wc_price($ghost['total'])
                ),
                $ghost['redsys_data'],
                'detected'
            );
        }
    }
    
    /**
     * Procesar pedido fantasma (cambiar estado)
     */
    public static function process_ghost_order($order_id, $new_status = 'processing', $note = '') {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }
        
        if (Replanta_Ghost_Orders_Settings::is_vigilant_mode()) {
            // En modo vigilante, solo registrar, no procesar
            Replanta_Ghost_Orders_Logger::add_event(
                $order_id,
                'ghost_order_vigilant',
                'Pedido fantasma verificado manualmente (modo vigilante)',
                self::get_redsys_data($order_id),
                'vigilant_verified'
            );
            return true;
        }
        
        // Cambiar estado
        $order->update_status($new_status, $note ?: 'Pedido fantasma procesado automáticamente - Pago verificado en Redsys');
        
        Replanta_Ghost_Orders_Logger::add_event(
            $order_id,
            'ghost_order_processed',
            sprintf('Pedido procesado: %s → %s', $order->get_status(), $new_status),
            self::get_redsys_data($order_id),
            'completed'
        );
        
        return true;
    }
    
    /**
     * AJAX - Obtener pedidos fantasma
     */
    public static function ajax_get_ghost_orders() {
        check_ajax_referer('replanta_god_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('No autorizado');
        }
        
        $days = intval($_POST['days'] ?? 30);
        $page = intval($_POST['page'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $ghost_orders = self::get_ghost_orders($days);
        $total = count($ghost_orders);
        $paginated = array_slice($ghost_orders, $offset, $per_page);
        
        // Formatos para la tabla
        $formatted = array_map(function($ghost) {
            return [
                'id' => $ghost['order_id'],
                'number' => $ghost['order']->get_order_number(),
                'status' => wc_get_order_status_name($ghost['status']),
                'status_raw' => $ghost['status'],
                'total' => wc_price($ghost['total']),
                'date' => $ghost['date'],
                'customer' => $ghost['customer'],
                'email' => $ghost['email'],
                'auth_code' => $ghost['redsys_data']['auth_code'],
            ];
        }, $paginated);
        
        wp_send_json_success([
            'orders' => $formatted,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
        ]);
    }
    
    /**
     * AJAX - Procesar en lote
     */
    public static function ajax_process_bulk() {
        check_ajax_referer('replanta_god_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('No autorizado');
        }
        
        $order_ids = array_map('intval', $_POST['order_ids'] ?? []);
        $new_status = sanitize_text_field($_POST['status'] ?? 'processing');
        
        if (empty($order_ids)) {
            wp_send_json_error('No hay pedidos seleccionados');
        }
        
        $processed = 0;
        foreach ($order_ids as $order_id) {
            if (self::process_ghost_order($order_id, $new_status)) {
                $processed++;
            }
        }
        
        wp_send_json_success([
            'message' => sprintf('%d pedidos procesados correctamente', $processed),
            'processed' => $processed,
        ]);
    }
    
    /**
     * AJAX - Sincronizar un pedido individual
     */
    public static function ajax_sync_order() {
        check_ajax_referer('replanta_god_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('No autorizado');
        }
        
        $order_id = intval($_POST['order_id'] ?? 0);
        
        if (!$order_id) {
            wp_send_json_error('Order ID inválido');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('Pedido no encontrado');
        }
        
        $is_ghost = self::is_ghost_order($order);
        $redsys_data = self::get_redsys_data($order_id);
        
        wp_send_json_success([
            'is_ghost' => $is_ghost,
            'redsys_data' => $redsys_data,
            'status' => $order->get_status(),
        ]);
    }
}
