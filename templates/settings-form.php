<?php
/**
 * Template del formulario de configuracion
 */

// Procesar guardado
if (isset($_POST['replanta_god_save_settings']) && wp_verify_nonce($_POST['_wpnonce'], 'replanta_god_settings')) {
    Replanta_Ghost_Orders_Settings::save_from_post($_POST);
    echo '<div class="notice notice-success"><p>Configuracion guardada correctamente.</p></div>';
}

// Procesar aplicar reglas Cloudflare
if (isset($_POST['replanta_god_apply_cf_rules']) && wp_verify_nonce($_POST['_wpnonce'], 'replanta_god_settings')) {
    $cf_api = new Replanta_Ghost_Orders_Cloudflare_API();
    $result = $cf_api->apply_redsys_rules();
    
    if (is_wp_error($result)) {
        echo '<div class="notice notice-error"><p>Error: ' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>Reglas de Cloudflare aplicadas correctamente.</p></div>';
    }
}

// Procesar aplicar LSWC
if (isset($_POST['replanta_god_apply_lswc']) && wp_verify_nonce($_POST['_wpnonce'], 'replanta_god_settings')) {
    $result = Replanta_Ghost_Orders_LSWC::apply_exclusion_rules();
    
    if (is_wp_error($result)) {
        echo '<div class="notice notice-error"><p>Error: ' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>Reglas de LiteSpeed Cache aplicadas correctamente.</p></div>';
    }
}

$settings = Replanta_Ghost_Orders_Settings::get_all();
?>

<div class="replanta-god-settings">
    <form method="post">
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
                    <th><label for="cloudflare_api_key">API Token</label></th>
                    <td>
                        <input type="password" name="cloudflare_api_key" id="cloudflare_api_key" value="<?php echo esc_attr($settings['cloudflare_api_key']); ?>" class="regular-text">
                        <p class="description">Token con permisos de edicion de reglas WAF</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="cloudflare_zone_id">Zone ID</label></th>
                    <td>
                        <input type="text" name="cloudflare_zone_id" id="cloudflare_zone_id" value="<?php echo esc_attr($settings['cloudflare_zone_id']); ?>" class="regular-text">
                        <p class="description">ID de la zona de Cloudflare para tu dominio</p>
                    </td>
                </tr>
            </table>
            
            <p>
                <button type="submit" name="replanta_god_apply_cf_rules" class="button button-primary">
                    Aplicar Reglas
                </button>
                <span class="description">Crea una regla WAF para omitir Browser Integrity Check en IPNs de Redsys</span>
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
                <button type="submit" name="replanta_god_apply_lswc" class="button button-primary">
                    Aplicar Exclusiones
                </button>
                <span class="description">Anade exclusiones de cache para las URLs de Redsys</span>
            </p>
        </div>
        <?php endif; ?>
        
        <div class="settings-section">
            <h2>Actualizaciones</h2>
            <p class="description">El plugin se actualiza automaticamente desde GitHub.</p>
            
            <table class="form-table">
                <tr>
                    <th>Version actual</th>
                    <td><code><?php echo REPLANTA_GOD_VERSION; ?></code></td>
                </tr>
                <tr>
                    <th>Repositorio</th>
                    <td><a href="https://github.com/replantadev/redsisghostbuster" target="_blank">github.com/replantadev/redsisghostbuster</a></td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <button type="submit" name="replanta_god_save_settings" class="button button-primary button-large">
                Guardar Configuracion
            </button>
        </p>
    </form>
</div>
