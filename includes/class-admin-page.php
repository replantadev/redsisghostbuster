<?php
/**
 * Pagina de administracion del plugin
 */

class Replanta_Ghost_Orders_Admin_Page {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_notices', [__CLASS__, 'show_notices']);
    }
    
    public static function add_menu() {
        add_submenu_page(
            'woocommerce',
            'Replanta - Pedidos Fantasma',
            'Pedidos Fantasma',
            'manage_woocommerce',
            'replanta-ghost-orders',
            [__CLASS__, 'render_page']
        );
    }
    
    public static function show_notices() {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'woocommerce_page_replanta-ghost-orders') {
            return;
        }
        
        // Check de Cloudflare
        if (!Replanta_Ghost_Orders_Settings::get_option('cloudflare_api_key')) {
            echo '<div class="notice notice-warning"><p><strong>Configura Cloudflare</strong> para permitir las notificaciones IPN de Redsys.</p></div>';
        }
        
        // Check de LSWC
        if (Replanta_Ghost_Orders_LSWC::is_lswc_active() && !Replanta_Ghost_Orders_Settings::get_option('lswc_auto_config')) {
            echo '<div class="notice notice-warning"><p><strong>Configura LiteSpeed Cache</strong> para excluir URLs de Redsys.</p></div>';
        }
    }
    
    public static function render_page() {
        ?>
        <div class="replanta-god-container">
            <div class="replanta-god-header">
                <h1>Replanta - Detector de Pedidos Fantasma</h1>
                <p class="subtitle">Sistema inteligente para detectar y corregir pedidos pagados en Redsys pero cancelados en WooCommerce</p>
            </div>
            
            <?php self::render_tabs(); ?>
        </div>
        <?php
    }
    
    private static function render_tabs() {
        $tab = sanitize_text_field($_GET['tab'] ?? 'dashboard');
        $tabs = [
            'dashboard' => 'Dashboard',
            'settings' => 'Configuracion',
            'orders' => 'Pedidos Fantasma',
            'logs' => 'Registros',
        ];
        
        echo '<nav class="replanta-god-tabs">';
        foreach ($tabs as $tab_key => $tab_label) {
            $active = ($tab === $tab_key) ? 'active' : '';
            $url = add_query_arg('tab', $tab_key);
            echo '<a href="' . esc_url($url) . '" class="replanta-god-tab ' . $active . '">' . esc_html($tab_label) . '</a>';
        }
        echo '</nav>';
        
        echo '<div class="replanta-god-content">';
        
        switch ($tab) {
            case 'settings':
                self::render_settings_tab();
                break;
            case 'orders':
                self::render_orders_tab();
                break;
            case 'logs':
                self::render_logs_tab();
                break;
            case 'dashboard':
            default:
                self::render_dashboard_tab();
                break;
        }
        
        echo '</div>';
    }
    
    private static function render_dashboard_tab() {
        $stats = Replanta_Ghost_Orders_Logger::get_stats(30);
        $ghost_count = count(Replanta_Ghost_Orders_Detector::get_ghost_orders(7));
        $cf_configured = !empty(Replanta_Ghost_Orders_Settings::get_option('cloudflare_api_key'));
        $lswc_active = Replanta_Ghost_Orders_LSWC::is_lswc_active();
        
        ?>
        <div class="replanta-god-dashboard">
            
            <div class="replanta-god-stats">
                <div class="stat-box">
                    <h3>Pedidos Fantasma</h3>
                    <p class="stat-number"><?php echo $ghost_count; ?></p>
                    <p class="stat-subtitle">Ultimos 7 dias</p>
                </div>
                
                <div class="stat-box">
                    <h3>Eventos Registrados</h3>
                    <p class="stat-number"><?php echo $stats['total']; ?></p>
                    <p class="stat-subtitle">Ultimos 30 dias</p>
                </div>
                
                <div class="stat-box <?php echo $cf_configured ? 'ok' : 'warning'; ?>">
                    <h3>Cloudflare</h3>
                    <p class="stat-number"><?php echo $cf_configured ? 'OK' : 'X'; ?></p>
                    <p class="stat-subtitle"><?php echo $cf_configured ? 'Configurado' : 'No configurado'; ?></p>
                </div>
                
                <div class="stat-box <?php echo $lswc_active ? 'ok' : 'info'; ?>">
                    <h3>LiteSpeed Cache</h3>
                    <p class="stat-number"><?php echo $lswc_active ? 'OK' : '-'; ?></p>
                    <p class="stat-subtitle"><?php echo $lswc_active ? 'Activo' : 'No instalado'; ?></p>
                </div>
            </div>
            
            <div class="replanta-god-info-box">
                <h3>Estado del Sistema</h3>
                <ul>
                    <li>
                        <strong>Modo de operacion:</strong>
                        <?php
                        $mode = Replanta_Ghost_Orders_Settings::get_mode();
                        if ($mode === 'vigilant') {
                            echo '<span class="badge badge-info">Vigilante</span> (Detectar, no corregir)';
                        } else {
                            echo '<span class="badge badge-warning">Automatico</span> (Detectar y corregir)';
                        }
                        ?>
                    </li>
                    <li>
                        <strong>Deteccion activa:</strong>
                        <?php
                        echo Replanta_Ghost_Orders_Settings::get_option('detection_enabled') ?
                            '<span class="badge badge-success">Si</span>' :
                            '<span class="badge badge-danger">No</span>';
                        ?>
                    </li>
                    <li>
                        <strong>Proxima verificacion:</strong>
                        <?php echo date('d/m/Y H:i', wp_next_scheduled('replanta_god_check_ghost_orders')); ?>
                    </li>
                </ul>
            </div>
            
            <div class="replanta-god-quick-start">
                <h3>Inicio Rapido</h3>
                <ol>
                    <li>Ve a <strong>Configuracion</strong> e ingresa tus credenciales de Cloudflare</li>
                    <li>Aplica las reglas automaticamente con los botones</li>
                    <li>El sistema detectara pedidos fantasma automaticamente</li>
                    <li>Procesarlos en lote desde la pestana <strong>Pedidos Fantasma</strong></li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    private static function render_settings_tab() {
        include REPLANTA_GOD_DIR . 'templates/settings-form.php';
    }
    
    private static function render_orders_tab() {
        ?>
        <div id="replanta-god-orders-container">
            <div class="replanta-god-toolbar">
                <label for="replanta_god_days">Buscar pedidos de:</label>
                <select id="replanta_god_days">
                    <option value="7">Ultimos 7 dias</option>
                    <option value="14">Ultimos 14 dias</option>
                    <option value="30" selected>Ultimos 30 dias</option>
                    <option value="60">Ultimos 60 dias</option>
                </select>
                
                <button class="button button-primary" id="replanta_god_refresh_orders">
                    Actualizar
                </button>
                
                <select id="replanta_god_bulk_action">
                    <option value="">-- Accion en lote --</option>
                    <option value="processing">Marcar como Procesando</option>
                    <option value="completed">Marcar como Completado</option>
                    <option value="on-hold">Marcar como En espera</option>
                </select>
                
                <button class="button button-secondary" id="replanta_god_apply_bulk">
                    Aplicar
                </button>
            </div>
            
            <table class="wp-list-table widefat fixed striped" id="replanta_god_orders_table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="replanta_god_select_all"></th>
                        <th>Pedido</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Codigo Auth Redsys</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="replanta_god_orders_body">
                    <tr><td colspan="8" style="text-align: center; padding: 20px;">Cargando...</td></tr>
                </tbody>
            </table>
            
            <div id="replanta_god_orders_pagination" class="replanta-god-pagination"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var currentPage = 1;
            
            function loadGhostOrders() {
                var days = $('#replanta_god_days').val();
                $.post(ajaxurl, {
                    action: 'replanta_god_get_ghost_orders',
                    nonce: '<?php echo wp_create_nonce('replanta_god_nonce'); ?>',
                    days: days,
                    page: currentPage
                }, function(response) {
                    if (response.success) {
                        displayOrders(response.data);
                    }
                });
            }
            
            function displayOrders(data) {
                var tbody = $('#replanta_god_orders_body');
                tbody.empty();
                
                if (data.orders.length === 0) {
                    tbody.html('<tr><td colspan="8" style="text-align: center; padding: 20px;">No se encontraron pedidos fantasma</td></tr>');
                    return;
                }
                
                $.each(data.orders, function(i, order) {
                    var row = '<tr>' +
                        '<td><input type="checkbox" class="replanta_god_order_check" value="' + order.id + '"></td>' +
                        '<td><a href="' + ajaxurl.replace('admin-ajax.php', '') + 'post.php?post=' + order.id + '&action=edit" target="_blank">#' + order.number + '</a></td>' +
                        '<td><span class="status-' + order.status_raw + '">' + order.status + '</span></td>' +
                        '<td>' + order.total + '</td>' +
                        '<td>' + order.date + '</td>' +
                        '<td>' + order.customer + '</td>' +
                        '<td><code>' + order.auth_code + '</code></td>' +
                        '<td><a href="#" class="replanta-sync-order" data-order-id="' + order.id + '">Sincronizar</a></td>' +
                        '</tr>';
                    tbody.append(row);
                });
            }
            
            $('#replanta_god_refresh_orders').click(function() {
                currentPage = 1;
                loadGhostOrders();
            });
            
            $('#replanta_god_apply_bulk').click(function() {
                var action = $('#replanta_god_bulk_action').val();
                if (!action) {
                    alert('Selecciona una accion');
                    return;
                }
                
                var orderIds = $('.replanta_god_order_check:checked').map(function() {
                    return $(this).val();
                }).get();
                
                if (orderIds.length === 0) {
                    alert('Selecciona al menos un pedido');
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'replanta_god_process_bulk',
                    nonce: '<?php echo wp_create_nonce('replanta_god_nonce'); ?>',
                    order_ids: orderIds,
                    status: action
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        loadGhostOrders();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
            
            $('#replanta_god_select_all').change(function() {
                $('.replanta_god_order_check').prop('checked', $(this).prop('checked'));
            });
            
            loadGhostOrders();
        });
        </script>
        <?php
    }
    
    private static function render_logs_tab() {
        $stats = Replanta_Ghost_Orders_Logger::get_stats(30);
        ?>
        <div class="replanta-god-logs">
            <h3>Estadisticas de Eventos (30 dias)</h3>
            <table class="widefat">
                <tr>
                    <th>Tipo de Evento</th>
                    <th>Cantidad</th>
                </tr>
                <?php foreach ($stats['by_type'] as $event): ?>
                    <tr>
                        <td><?php echo esc_html($event->event_type); ?></td>
                        <td><?php echo $event->count; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php
    }
}
