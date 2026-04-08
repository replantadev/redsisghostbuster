<?php
/**
 * Template del formulario de configuracion
 */

// Obtener configuracion actual usando el metodo correcto
$settings = Replanta_Ghost_Orders_Settings::get_option();
?>

<div class="replanta-god-settings">
    <form id="replanta_god_settings_form" method="post">
        <?php wp_nonce_field('replanta_god_settings'); ?>
        
        <div class="settings-section">
            <h2>Configuracion General</h2>
            
            <table class="form-table">
                <tr>
                    <th><label for="detection_enabled">Deteccion activa</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="detection_enabled" id="detection_enabled" value="1" <?php checked($settings['detection_enabled']); ?>>
                            Activar deteccion automatica de pedidos fantasma
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="mode">Modo de operacion</label></th>
                    <td>
                        <select name="mode" id="mode">
                            <option value="vigilant" <?php selected($settings['mode'], 'vigilant'); ?>>Vigilante - Solo detectar</option>
                            <option value="auto" <?php selected($settings['mode'], 'auto'); ?>>Automatico - Detectar y corregir</option>
                        </select>
                        <p class="description">En modo vigilante solo se registran los pedidos, en automatico se corrigen.</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="check_interval">Intervalo de verificacion</label></th>
                    <td>
                        <select name="check_interval" id="check_interval">
                            <option value="hourly" <?php selected($settings['check_interval'], 'hourly'); ?>>Cada hora</option>
                            <option value="twicedaily" <?php selected($settings['check_interval'], 'twicedaily'); ?>>Dos veces al dia</option>
                            <option value="daily" <?php selected($settings['check_interval'], 'daily'); ?>>Una vez al dia</option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="email_notifications">Notificaciones por email</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="email_notifications" id="email_notifications" value="1" <?php checked($settings['email_notifications']); ?>>
                            Enviar notificaciones al admin
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="settings-section">
            <h2>Cloudflare</h2>
            <p class="description">Configura las credenciales de Cloudflare para aplicar automaticamente reglas WAF que permitan las notificaciones IPN de Redsys.</p>
            
            <table class="form-table">
                <tr>
                    <th><label for="cloudflare_api_key">API Token / Global API Key</label></th>
                    <td>
                        <input type="password" name="cloudflare_api_key" id="cloudflare_api_key" value="<?php echo esc_attr($settings['cloudflare_api_key']); ?>" class="regular-text">
                        <p class="description">API Token (recomendado) o Global API Key</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="cloudflare_email">Email de Cloudflare</label></th>
                    <td>
                        <input type="email" name="cloudflare_email" id="cloudflare_email" value="<?php echo esc_attr($settings['cloudflare_email']); ?>" class="regular-text">
                        <p class="description">Solo necesario si usas Global API Key (dejar vacio si usas API Token)</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="cloudflare_zone_id">Zone ID</label></th>
                    <td>
                        <input type="text" name="cloudflare_zone_id" id="cloudflare_zone_id" value="<?php echo esc_attr($settings['cloudflare_zone_id']); ?>" class="regular-text">
                        <p class="description">ID de la zona de Cloudflare para tu dominio</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label>Test de Conexión</label></th>
                    <td>
                        <button type="button" id="replanta_test_cf" class="button button-secondary">Probar Conexión</button>
                        <p class="description">Verifica que las credenciales de Cloudflare funcionan correctamente</p>
                        <div id="replanta_test_cf_result" style="margin-top: 10px;"></div>
                    </td>
                </tr>
            </table>
            
            <p>
                <button type="button" id="replanta_apply_cf_rules" class="button button-primary">
                    Aplicar Reglas
                </button>
                <span class="description">Crea una regla WAF para omitir Browser Integrity Check en IPNs de Redsys</span>
                <div id="replanta_cf_result" style="margin-top: 10px;"></div>
            </p>
        </div>
        
        <?php if (Replanta_Ghost_Orders_LSWC::is_lswc_active()): ?>
        <div class="settings-section">
            <h2>LiteSpeed Cache</h2>
            <p class="description">Configura exclusiones en LiteSpeed Cache para evitar cache en URLs criticas de Redsys.</p>
            
            <table class="form-table">
                <tr>
                    <th><label for="lswc_auto_config">Configuracion automatica</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="lswc_auto_config" id="lswc_auto_config" value="1" <?php checked($settings['lswc_auto_config']); ?>>
                            Aplicar automaticamente exclusiones de cache
                        </label>
                    </td>
                </tr>
            </table>
            
            <p>
                <button type="button" id="replanta_apply_lswc_rules" class="button button-primary">
                    Aplicar Exclusiones
                </button>
                <span class="description">Anade exclusiones de cache para las URLs de Redsys</span>
                <div id="replanta_lswc_result" style="margin-top: 10px;"></div>
            </p>
        </div>
        <?php endif; ?>
        
        <div class="settings-section">
            <h2>Test de Pedido Específico</h2>
            <p class="description">Verifica si un pedido específico es un pedido fantasma (pagado en Redsys pero cancelado/pendiente).</p>
            
            <table class="form-table">
                <tr>
                    <th><label for="test_order_id">ID del Pedido</label></th>
                    <td>
                        <input type="number" id="test_order_id" class="regular-text" placeholder="Ej: 47632">
                        <button type="button" id="replanta_test_order" class="button button-secondary">Probar Pedido</button>
                        <p class="description">Ingresa el ID del pedido a verificar</p>
                        <div id="test_order_result" style="margin-top: 10px;"></div>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="settings-section">
            <h2>Actualizaciones</h2>
            <p class="description">El plugin se actualiza automaticamente desde GitHub.</p>
            
            <?php
            // Verificar si hay actualizaciones disponibles
            $update_cache_key = 'replanta_god_github_update';
            $release_info = get_transient($update_cache_key);
            $has_update = false;
            $new_version = '';
            
            if ($release_info && isset($release_info['version'])) {
                $new_version = $release_info['version'];
                $has_update = version_compare($new_version, REPLANTA_GOD_VERSION, '>');
            }
            ?>
            
            <table class="form-table">
                <tr>
                    <th>Version actual</th>
                    <td>
                        <code><?php echo REPLANTA_GOD_VERSION; ?></code>
                        <?php if ($has_update): ?>
                            <span style="color: #d63638; font-weight: bold; margin-left: 10px;">
                                Nueva version disponible: <?php echo esc_html($new_version); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Repositorio</th>
                    <td>
                        <a href="https://github.com/replantadev/redsisghostbuster" target="_blank">github.com/replantadev/redsisghostbuster</a>
                        <br>
                        <a href="https://github.com/replantadev/redsisghostbuster/releases" target="_blank">Ver releases</a>
                    </td>
                </tr>
                <tr>
                    <th>Comprobar actualizaciones</th>
                    <td>
                        <button type="button" id="replanta_check_updates" class="button button-secondary">Comprobar ahora</button>
                        <p class="description">Fuerza la comprobacion de nuevas versiones en GitHub</p>
                        <div id="replanta_check_updates_result" style="margin-top: 10px;"></div>
                        
                        <?php if ($has_update): ?>
                            <p style="margin-top: 15px;">
                                <a href="<?php echo admin_url('update-core.php'); ?>" class="button button-primary">
                                    Ir a Actualizaciones
                                </a>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <button type="submit" class="button button-primary button-large">
                Guardar Configuracion
            </button>
        </p>
    </form>
</div>
