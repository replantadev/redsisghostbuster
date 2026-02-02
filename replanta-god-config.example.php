<?php
/**
 * EJEMPLO DE CONFIGURACIÓN PERSONALIZADA
 * 
 * Copia este archivo a tu tema de WordPress como "replanta-god-config.php"
 * e incluye lo siguiente en tu functions.php:
 * 
 * require_once get_stylesheet_directory() . '/replanta-god-config.php';
 */

// Ejemplo 1: Configurar automáticamente en activación
add_action('replanta_god_activated', function() {
    // Configurar modo automático desde el principio
    Replanta_Ghost_Orders_Settings::update_option('mode', 'auto');
    
    // Habilitar detección desde el principio
    Replanta_Ghost_Orders_Settings::update_option('detection_enabled', true);
    
    // Configurar días a buscar
    Replanta_Ghost_Orders_Settings::update_option('days_back', 60);
});

// Ejemplo 2: Agregar pedidos personalizados al hook
add_filter('replanta_god_excluded_statuses', function($statuses) {
    // Excluir también los pedidos con estado 'draft'
    $statuses[] = 'draft';
    return $statuses;
});

// Ejemplo 3: Custom log viewer
function replanta_god_get_recent_events($limit = 20) {
    global $wpdb;
    $table = $wpdb->prefix . 'replanta_god_logs';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
        $limit
    ));
}

// Ejemplo 4: Envío de email cuando se detecta un fantasma
add_action('replanta_god_ghost_detected', function($order_id) {
    $order = wc_get_order($order_id);
    $admin_email = get_option('admin_email');
    
    wp_mail(
        $admin_email,
        sprintf('[ALERTA] Pedido fantasma detectado: #%d', $order->get_order_number()),
        sprintf(
            "Se detectó un pedido fantasma:\n\n" .
            "Pedido: #%d\n" .
            "Cliente: %s\n" .
            "Total: %s\n" .
            "Fecha: %s\n\n" .
            "Por favor revisa el dashboard de Replanta Ghost Orders Detector.",
            $order->get_id(),
            $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            $order->get_formatted_order_total(),
            wc_format_datetime($order->get_date_created())
        )
    );
});

// Ejemplo 5: Webhook personalizado
add_action('replanta_god_ghost_processed', function($order_id, $new_status) {
    // Aquí puedes enviar datos a tu servidor, CRM, etc.
    $order = wc_get_order($order_id);
    
    wp_remote_post('https://tu-servidor.com/webhook', [
        'body' => wp_json_encode([
            'event' => 'ghost_order_processed',
            'order_id' => $order_id,
            'new_status' => $new_status,
            'timestamp' => current_time('mysql'),
        ]),
        'headers' => ['Content-Type' => 'application/json'],
    ]);
}, 10, 2);

// Ejemplo 6: Personalizar mensaje de Cloudflare
add_filter('replanta_god_cf_rule_description', function() {
    return 'Regla WAF personalizada para Redsys en ' . get_bloginfo('name');
});

// Ejemplo 7: Agregar campos personalizados al log
add_filter('replanta_god_log_extra_fields', function($extra) {
    $extra['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $extra['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return $extra;
});
