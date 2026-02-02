<?php
/**
 * Formulario de configuración
 */

$cf_key = Replanta_Ghost_Orders_Settings::get_option('cloudflare_api_key');
$cf_zone = Replanta_Ghost_Orders_Settings::get_option('cloudflare_zone_id');
$mode = Replanta_Ghost_Orders_Settings::get_mode();
$detection_enabled = Replanta_Ghost_Orders_Settings::get_option('detection_enabled');

?>
<div class="replanta-god-settings">
    <form id="replanta_god_settings_form">
        
        <!-- SECCIÓN: CLOUDFLARE -->
        <div class="replanta-god-section">
            <h3>☁️ Configuración de Cloudflare</h3>
            <p class="section-description">Cloudflare protege tu sitio de ataques, pero puede bloquear las notificaciones de Redsys. Configura esto para permitir los pagos.</p>
            
            <div class="form-group">
                <label for="replanta_cf_api_key">
                    API Key de Cloudflare
                    <span class="required">*</span>
                </label>
                <input type="password" id="replanta_cf_api_key" name="cloudflare_api_key" value="<?php echo esc_attr($cf_key); ?>" placeholder="c5f6g7h8i9j0k1l2m3n4o5p6">
                <p class="help-text">Obtén tu API Key en Cloudflare > My Profile > API Tokens (usa el token de "Global API Key")</p>
            </div>
            
            <div class="form-group">
                <label for="replanta_cf_zone">
                    Zone ID de Cloudflare
                    <span class="required">*</span>
                </label>
                <input type="text" id="replanta_cf_zone" name="cloudflare_zone_id" value="<?php echo esc_attr($cf_zone); ?>" placeholder="1234567890abcdef1234567890abcdef">
                <p class="help-text">Obtén tu Zone ID en Cloudflare > Resumen del dominio (esquina derecha abajo)</p>
            </div>
            
            <div class="form-actions">
                <button type="button" class="button button-secondary" id="replanta_test_cf">
                    🧪 Probar Conexión
                </button>
                
                <span id="replanta_test_cf_result"></span>
            </div>
            
            <div class="replanta-god-divider"></div>
            
            <h4>Aplicar Reglas de Cloudflare</h4>
            <p>Haz clic abajo para permitir automáticamente que Redsys envíe notificaciones de pago:</p>
            
            <div class="form-actions">
                <button type="button" class="button button-primary button-large" id="replanta_apply_cf_rules">
                    ✅ Aplicar Reglas de Cloudflare
                </button>
                <span id="replanta_apply_cf_status"></span>
            </div>
        </div>
        
        <!-- SECCIÓN: LITESPEED CACHE -->
        <?php if (Replanta_Ghost_Orders_LSWC::is_lswc_active()) { ?>
            <div class="replanta-god-section">
                <h3>⚡ Configuración de LiteSpeed Cache</h3>
                <p class="section-description">LiteSpeed Cache acelera tu sitio, pero no debe cachear las URLs de Redsys. Aplica estas reglas automáticamente.</p>
                
                <div class="form-actions">
                    <button type="button" class="button button-primary button-large" id="replanta_apply_lswc_rules">
                        ✅ Aplicar Reglas de LiteSpeed Cache
                    </button>
                    <span id="replanta_apply_lswc_status"></span>
                </div>
                
                <details class="replanta-god-details">
                    <summary>🔍 Ver reglas que se aplicarán</summary>
                    <div class="details-content">
                        <h5>Rutas excluidas:</h5>
                        <code>
                            /carrito,
                            /finalizar-compra,
                            /pedido-recibido,
                            /pagar,
                            /wc-api/*,
                            /wp-content/plugins/woocommerce-redsys-*
                        </code>
                        
                        <h5>Query Strings excluidas:</h5>
                        <code>wc-api=WC_redsys, coupon=, apply_coupon=</code>
                        
                        <h5>Cookies excluidas:</h5>
                        <code>
                            woocommerce_cart_hash,
                            wp_woocommerce_session_*,
                            wordpress_logged_in_*
                        </code>
                    </div>
                </details>
            </div>
        <?php } ?>
        
        <!-- SECCIÓN: MODO DE OPERACIÓN -->
        <div class="replanta-god-section">
            <h3>🎯 Modo de Operación</h3>
            
            <div class="form-group">
                <label>
                    <input type="radio" name="replanta_mode" value="vigilant" <?php checked($mode, 'vigilant'); ?>> 
                    <strong>Modo Vigilante</strong> (Recomendado)
                </label>
                <p class="help-text">El sistema detecta pedidos fantasma pero no los modifica. Tú decides qué hacer manualmente.</p>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="radio" name="replanta_mode" value="auto" <?php checked($mode, 'auto'); ?>> 
                    <strong>Modo Automático</strong>
                </label>
                <p class="help-text">El sistema detecta Y CORRIGE automáticamente los pedidos fantasma (cambiar estado a "En proceso").</p>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="detection_enabled" value="1" <?php checked($detection_enabled); ?>> 
                    <strong>Habilitar detección automática</strong>
                </label>
                <p class="help-text">Si está deshabilitado, tendrás que buscar pedidos manualmente.</p>
            </div>
        </div>
        
        <!-- BOTONES DE GUARDAR -->
        <div class="replanta-god-section replanta-god-actions">
            <?php wp_nonce_field('replanta_god_settings', 'replanta_god_nonce'); ?>
            
            <button type="submit" class="button button-primary button-large">
                💾 Guardar Configuración
            </button>
            
            <span id="replanta_save_result" class="replanta-message"></span>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Test Cloudflare connection
    $('#replanta_test_cf').click(function() {
        var apiKey = $('#replanta_cf_api_key').val();
        var zoneId = $('#replanta_cf_zone').val();
        
        if (!apiKey || !zoneId) {
            $('#replanta_test_cf_result').html('<span class="error">⚠️ Completa los campos</span>');
            return;
        }
        
        $.post(ajaxurl, {
            action: 'replanta_god_test_cf',
            nonce: '<?php echo wp_create_nonce('replanta_god_nonce'); ?>',
            api_key: apiKey,
            zone_id: zoneId
        }, function(response) {
            if (response.success) {
                $('#replanta_test_cf_result').html('<span class="success">✅ ' + response.data + '</span>');
            } else {
                $('#replanta_test_cf_result').html('<span class="error">❌ ' + response.data + '</span>');
            }
        });
    });
    
    // Apply Cloudflare rules
    $('#replanta_apply_cf_rules').click(function() {
        var apiKey = $('#replanta_cf_api_key').val();
        var zoneId = $('#replanta_cf_zone').val();
        
        if (!apiKey || !zoneId) {
            alert('⚠️ Configura Cloudflare primero');
            return;
        }
        
        $(this).prop('disabled', true);
        $('#replanta_apply_cf_status').html('⏳ Aplicando...');
        
        $.post(ajaxurl, {
            action: 'replanta_god_apply_cf_rules',
            nonce: '<?php echo wp_create_nonce('replanta_god_nonce'); ?>',
            api_key: apiKey,
            zone_id: zoneId
        }, function(response) {
            $('#replanta_apply_cf_rules').prop('disabled', false);
            
            if (response.success) {
                $('#replanta_apply_cf_status').html('<span class="success">✅ Reglas aplicadas correctamente</span>');
            } else {
                $('#replanta_apply_cf_status').html('<span class="error">❌ Error: ' + response.data + '</span>');
            }
        });
    });
    
    // Apply LSWC rules
    $('#replanta_apply_lswc_rules').click(function() {
        $(this).prop('disabled', true);
        $('#replanta_apply_lswc_status').html('⏳ Aplicando...');
        
        $.post(ajaxurl, {
            action: 'replanta_god_apply_lswc_rules',
            nonce: '<?php echo wp_create_nonce('replanta_god_nonce'); ?>'
        }, function(response) {
            $('#replanta_apply_lswc_rules').prop('disabled', false);
            
            if (response.success) {
                $('#replanta_apply_lswc_status').html('<span class="success">✅ Reglas aplicadas correctamente</span>');
            } else {
                $('#replanta_apply_lswc_status').html('<span class="error">❌ Error: ' + response.data + '</span>');
            }
        });
    });
    
    // Save settings
    $('#replanta_god_settings_form').submit(function(e) {
        e.preventDefault();
        
        $.post(ajaxurl, {
            action: 'replanta_god_save_settings',
            nonce: '<?php echo wp_create_nonce('replanta_god_nonce'); ?>',
            cloudflare_api_key: $('#replanta_cf_api_key').val(),
            cloudflare_zone_id: $('#replanta_cf_zone').val(),
            replanta_mode: $('input[name="replanta_mode"]:checked').val(),
            detection_enabled: $('input[name="detection_enabled"]').is(':checked') ? 1 : 0
        }, function(response) {
            if (response.success) {
                $('#replanta_save_result').html('<span class="success">✅ Configuración guardada</span>');
                setTimeout(function() {
                    $('#replanta_save_result').html('');
                }, 3000);
            } else {
                $('#replanta_save_result').html('<span class="error">❌ Error al guardar</span>');
            }
        });
    });
});
</script>
<?php
