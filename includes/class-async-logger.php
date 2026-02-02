<?php
/**
 * Logger asíncrono sin ralentizar la web
 */

class Replanta_Ghost_Orders_Logger {
    
    private static $queue = [];
    private static $table_name;
    
    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'replanta_god_logs';
    }
    
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'replanta_god_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT AUTO_INCREMENT,
            order_id BIGINT,
            event_type VARCHAR(50),
            status VARCHAR(20),
            message LONGTEXT,
            redsys_data JSON,
            user_id BIGINT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Añadir evento a la cola (no bloquea)
     */
    public static function add_event($order_id, $event_type, $message, $redsys_data = [], $status = 'pending') {
        self::$queue[] = [
            'order_id' => $order_id,
            'event_type' => $event_type,
            'message' => $message,
            'redsys_data' => $redsys_data,
            'status' => $status,
            'user_id' => get_current_user_id(),
        ];
        
        // Si hay eventos pendientes, programar procesamiento
        if (count(self::$queue) >= 10) {
            wp_schedule_single_event(time() + 2, 'replanta_god_process_logs_immediate');
        }
    }
    
    /**
     * Procesar cola de manera asíncrona
     */
    public static function process_queue() {
        global $wpdb;
        
        if (empty(self::$queue)) {
            return;
        }
        
        $table_name = $wpdb->prefix . 'replanta_god_logs';
        
        foreach (self::$queue as $event) {
            $wpdb->insert($table_name, [
                'order_id' => $event['order_id'],
                'event_type' => $event['event_type'],
                'message' => $event['message'],
                'redsys_data' => wp_json_encode($event['redsys_data']),
                'status' => $event['status'],
                'user_id' => $event['user_id'],
            ], ['%d', '%s', '%s', '%s', '%s', '%d']);
        }
        
        self::$queue = [];
        
        // Limpiar logs antiguos
        self::cleanup_old_logs();
    }
    
    /**
     * Obtener eventos de un pedido
     */
    public static function get_order_logs($order_id, $limit = 50) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'replanta_god_logs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE order_id = %d ORDER BY created_at DESC LIMIT %d",
            $order_id,
            $limit
        ));
    }
    
    /**
     * Obtener eventos por tipo
     */
    public static function get_events_by_type($event_type, $limit = 100, $offset = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'replanta_god_logs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE event_type = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $event_type,
            $limit,
            $offset
        ));
    }
    
    /**
     * Limpiar logs antiguos
     */
    private static function cleanup_old_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'replanta_god_logs';
        $retention_days = Replanta_Ghost_Orders_Settings::get_option('log_retention_days', 90);
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            $cutoff_date
        ));
    }
    
    /**
     * Estadísticas de logs
     */
    public static function get_stats($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'replanta_god_logs';
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE created_at > %s",
            $date_from
        ));
        
        $by_type = $wpdb->get_results($wpdb->prepare(
            "SELECT event_type, COUNT(*) as count FROM $table_name WHERE created_at > %s GROUP BY event_type",
            $date_from
        ));
        
        return [
            'total' => $total,
            'by_type' => $by_type,
        ];
    }
}
