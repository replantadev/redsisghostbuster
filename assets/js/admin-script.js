/**
 * Replanta Ghost Orders Admin Scripts
 */

(function($) {
    'use strict';

    console.log('[Replanta GOD] Script cargado');
    console.log('[Replanta GOD] jQuery version:', $.fn.jquery);
    console.log('[Replanta GOD] ajaxurl existe:', typeof ajaxurl !== 'undefined', ajaxurl);
    console.log('[Replanta GOD] repl_god_obj existe:', typeof repl_god_obj !== 'undefined', repl_god_obj);

    var ReplicaGOD = {

        init: function() {
            console.log('[Replanta GOD] Inicializando...');
            this.attachEventHandlers();
            console.log('[Replanta GOD] Event handlers attached');
        },

        attachEventHandlers: function() {
            console.log('[Replanta GOD] Registrando event handlers...');
            // REMOVED: Tab switching - let browser handle navigation naturally
            // $(document).on('click', '.replanta-god-tab', this.handleTabSwitch.bind(this));
            $(document).on('submit', '#replanta_god_settings_form', this.handleSettingsSave.bind(this));
            $(document).on('click', '#replanta_test_cf', this.testCloudflareConnection.bind(this));
            $(document).on('click', '#replanta_apply_cf_rules', this.applyCloudflareRules.bind(this));
            $(document).on('click', '#replanta_apply_lswc_rules', this.applyLSWCRules.bind(this));
            $(document).on('click', '#replanta_god_refresh_orders', this.refreshGhostOrders.bind(this));
            $(document).on('click', '#replanta_god_apply_bulk', this.applyBulkAction.bind(this));
            $(document).on('click', '.replanta-sync-order', this.syncOrder.bind(this));
            $(document).on('change', '#replanta_god_select_all', this.selectAllOrders.bind(this));
            $(document).on('click', '#replanta_test_order', this.testSpecificOrder.bind(this));
            $(document).on('click', '#replanta_check_updates', this.checkForUpdates.bind(this));
            console.log('[Replanta GOD] Event handlers registrados OK');
        },

        // REMOVED: handleTabSwitch - tabs now work with normal page navigation

        handleSettingsSave: function(e) {
            console.log('[Replanta GOD] handleSettingsSave LLAMADO');
            e.preventDefault();

            var $form = $(e.target);
            var $submit = $form.find('button[type="submit"]');
            var originalText = $submit.text();

            console.log('[Replanta GOD] Formulario:', $form);
            console.log('[Replanta GOD] Submit button:', $submit);

            $submit.prop('disabled', true).text('Guardando...');

            var postData = {
                action: 'replanta_god_save_settings',
                nonce: repl_god_obj.nonce,
                cloudflare_api_key: $('#cloudflare_api_key').val(),
                cloudflare_email: $('#cloudflare_email').val(),
                cloudflare_zone_id: $('#cloudflare_zone_id').val(),
                replanta_mode: $('input[name="replanta_mode"]:checked').val(),
                detection_enabled: $('input[name="detection_enabled"]').is(':checked') ? 1 : 0
            };

            console.log('[Replanta GOD] Enviando datos:', postData);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: postData,
                success: function(response) {
                    console.log('[Replanta GOD] AJAX success:', response);
                    if (response.success) {
                        ReplicaGOD.showMessage('Configuracion guardada correctamente', 'success', $form);
                    } else {
                        ReplicaGOD.showMessage('Error: ' + response.data, 'error', $form);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Replanta GOD] AJAX error:', xhr, status, error);
                    ReplicaGOD.showMessage('Error de conexion', 'error', $form);
                },
                complete: function() {
                    console.log('[Replanta GOD] AJAX complete');
                    $submit.prop('disabled', false).text(originalText);
                }
            });
        },

        testCloudflareConnection: function(e) {
            e.preventDefault();

            var apiKey = $('#cloudflare_api_key').val();
            var email = $('#cloudflare_email').val();
            var zoneId = $('#cloudflare_zone_id').val();

            if (!apiKey || !zoneId) {
                this.showMessage('Completa los campos de API Key y Zone ID', 'error', '#replanta_test_cf_result');
                return;
            }

            var $btn = $(e.target);
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('Probando...');
            var $result = $('#replanta_test_cf_result');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'replanta_god_test_cf',
                    nonce: repl_god_obj.nonce,
                    api_key: apiKey,
                    email: email,
                    zone_id: zoneId
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<span class="success">[OK] ' + response.data + '</span>');
                    } else {
                        $result.html('<span class="error">[ERROR] ' + response.data + '</span>');
                    }
                },
                error: function() {
                    $result.html('<span class="error">[ERROR] Error de conexion</span>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        applyCloudflareRules: function(e) {
            e.preventDefault();

            var apiKey = $('#replanta_cf_api_key').val();
            var zoneId = $('#replanta_cf_zone').val();

            if (!apiKey || !zoneId) {
                alert('Configura Cloudflare primero');
                return;
            }

            if (!confirm('Aplicar reglas de Cloudflare? Esto habilitara las notificaciones de Redsys.')) {
                return;
            }

            var $btn = $(e.target);
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('Aplicando...');
            var $status = $('#replanta_apply_cf_status');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'replanta_god_apply_cf_rules',
                    nonce: repl_god_obj.nonce,
                    api_key: apiKey,
                    zone_id: zoneId
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('<span class="success">[OK] Reglas aplicadas correctamente</span>');
                    } else {
                        $status.html('<span class="error">[ERROR] ' + response.data + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span class="error">[ERROR] Error de conexion</span>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        applyLSWCRules: function(e) {
            e.preventDefault();

            if (!confirm('Aplicar reglas de LiteSpeed Cache? Esto excluira las URLs de Redsys del cache.')) {
                return;
            }

            var $btn = $(e.target);
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('Aplicando...');
            var $status = $('#replanta_apply_lswc_status');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'replanta_god_apply_lswc_rules',
                    nonce: repl_god_obj.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('<span class="success">[OK] Reglas aplicadas correctamente</span>');
                    } else {
                        $status.html('<span class="error">[ERROR] ' + response.data + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span class="error">[ERROR] Error de conexion</span>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        refreshGhostOrders: function(e) {
            e.preventDefault();
            this.loadGhostOrders(1);
        },

        loadGhostOrders: function(page) {
            page = page || 1;
            var days = $('#replanta_god_days').val() || 7;
            var $tbody = $('#replanta_god_orders_body');

            $tbody.html('<tr><td colspan="8" style="text-align: center; padding: 20px;">Cargando...</td></tr>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'replanta_god_get_ghost_orders',
                    nonce: repl_god_obj.nonce,
                    days: days,
                    page: page
                },
                success: function(response) {
                    if (response.success) {
                        ReplicaGOD.displayGhostOrders(response.data, page);
                    } else {
                        $tbody.html('<tr><td colspan="8" style="text-align: center; padding: 20px; color: red;">[ERROR] ' + response.data + '</td></tr>');
                    }
                },
                error: function() {
                    $tbody.html('<tr><td colspan="8" style="text-align: center; padding: 20px; color: red;">[ERROR] Error de conexion</td></tr>');
                }
            });
        },

        displayGhostOrders: function(data, currentPage) {
            var $tbody = $('#replanta_god_orders_body');
            $tbody.empty();

            if (!data.orders || data.orders.length === 0) {
                $tbody.html('<tr><td colspan="8" style="text-align: center; padding: 20px;">[OK] No se encontraron pedidos fantasma</td></tr>');
                return;
            }

            $.each(data.orders, function(i, order) {
                var editUrl = ajaxurl.replace('admin-ajax.php', '') + 'post.php?post=' + order.id + '&action=edit';
                var row = '<tr>' +
                    '<td><input type="checkbox" class="replanta_god_order_check" value="' + order.id + '"></td>' +
                    '<td><a href="' + editUrl + '" target="_blank">#' + order.number + '</a></td>' +
                    '<td><span class="status-' + order.status_raw + '">' + order.status + '</span></td>' +
                    '<td>' + order.total + '</td>' +
                    '<td>' + order.date + '</td>' +
                    '<td>' + order.customer + '</td>' +
                    '<td><code style="font-size: 11px;">' + order.auth_code + '</code></td>' +
                    '<td><a href="#" class="replanta-sync-order" data-order-id="' + order.id + '">Sincronizar</a></td>' +
                    '</tr>';
                $tbody.append(row);
            });

            if (data.pagination) {
                this.displayPagination(data.pagination, currentPage);
            }
        },

        displayPagination: function(pagination, currentPage) {
            var $paginationContainer = $('#replanta_god_orders_pagination');
            $paginationContainer.empty();

            if (pagination.total_pages <= 1) {
                return;
            }

            if (currentPage > 1) {
                $paginationContainer.append(
                    '<a href="#" class="page-numbers replanta-god-page" data-page="1">Primera</a>'
                );
                $paginationContainer.append(
                    '<a href="#" class="page-numbers replanta-god-page" data-page="' + (currentPage - 1) + '">Anterior</a>'
                );
            }

            for (var i = 1; i <= pagination.total_pages; i++) {
                if (i === currentPage) {
                    $paginationContainer.append(
                        '<span class="page-numbers current">' + i + '</span>'
                    );
                } else if (i <= currentPage + 2 && i >= currentPage - 2) {
                    $paginationContainer.append(
                        '<a href="#" class="page-numbers replanta-god-page" data-page="' + i + '">' + i + '</a>'
                    );
                }
            }

            if (currentPage < pagination.total_pages) {
                $paginationContainer.append(
                    '<a href="#" class="page-numbers replanta-god-page" data-page="' + (currentPage + 1) + '">Siguiente</a>'
                );
                $paginationContainer.append(
                    '<a href="#" class="page-numbers replanta-god-page" data-page="' + pagination.total_pages + '">Ultima</a>'
                );
            }

            $paginationContainer.on('click', '.replanta-god-page', function(e) {
                e.preventDefault();
                var page = $(this).data('page');
                ReplicaGOD.loadGhostOrders(page);
            });
        },

        applyBulkAction: function(e) {
            e.preventDefault();

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

            if (!confirm('Aplicar accion a ' + orderIds.length + ' pedido(s)?')) {
                return;
            }

            var $btn = $(e.target);
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('Procesando...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'replanta_god_process_bulk',
                    nonce: repl_god_obj.nonce,
                    order_ids: orderIds,
                    status: action
                },
                success: function(response) {
                    if (response.success) {
                        alert('[OK] ' + response.data.message);
                        ReplicaGOD.refreshGhostOrders();
                    } else {
                        alert('[ERROR] ' + response.data);
                    }
                },
                error: function() {
                    alert('[ERROR] Error de conexion');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        selectAllOrders: function(e) {
            var isChecked = $(e.target).prop('checked');
            $('.replanta_god_order_check').prop('checked', isChecked);
        },

        syncOrder: function(e) {
            e.preventDefault();

            var orderId = $(e.target).data('order-id');
            var $link = $(e.target);
            var originalText = $link.text();

            $link.text('Sincronizando...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'replanta_god_sync_order',
                    nonce: repl_god_obj.nonce,
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        $link.text('[OK] Sincronizado').delay(2000).queue(function() {
                            $(this).text(originalText).dequeue();
                        });
                    } else {
                        alert('[ERROR] ' + response.data);
                        $link.text(originalText);
                    }
                },
                error: function() {
                    alert('[ERROR] Error de conexion');
                    $link.text(originalText);
                }
            });
        },

        testSpecificOrder: function(e) {
            e.preventDefault();

            var orderId = $('#test_order_id').val();
            
            if (!orderId) {
                $('#test_order_result').html('<div style="color: #dc3232; padding: 10px; background: #fef0f0; border-left: 4px solid #dc3232; margin-top: 10px;">' +
                    '<strong>Error:</strong> Ingresa un ID de pedido válido' +
                    '</div>');
                return;
            }

            var $btn = $(e.target);
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('Probando...');
            var $result = $('#test_order_result');
            
            $result.html('<div style="padding: 10px; background: #f0f0f1; border-left: 4px solid #72aee6;">Verificando pedido #' + orderId + '...</div>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'replanta_god_sync_order',
                    nonce: repl_god_obj.nonce,
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var isGhost = data.is_ghost;
                        var redsysData = data.redsys_data;
                        var status = data.status;
                        
                        var resultHtml = '<div style="padding: 15px; border-left: 4px solid ' + (isGhost ? '#dc3232' : '#46b450') + '; background: ' + (isGhost ? '#fef0f0' : '#f0f6f0') + '; margin-top: 10px;">';
                        resultHtml += '<strong>Resultado del Test - Pedido #' + orderId + '</strong><br><br>';
                        resultHtml += '<strong>Estado actual:</strong> ' + status + '<br>';
                        resultHtml += '<strong>¿Es pedido fantasma?:</strong> ' + (isGhost ? '⚠️ SÍ - REQUIERE ATENCIÓN' : '✅ NO') + '<br><br>';
                        
                        if (redsysData.is_authorized) {
                            resultHtml += '<strong>Estado en Redsys:</strong> ✅ AUTORIZADO<br>';
                            if (redsysData.auth_code) {
                                resultHtml += '<strong>Código de autorización:</strong> <code>' + redsysData.auth_code + '</code><br>';
                            }
                            if (redsysData.ds_response) {
                                resultHtml += '<strong>Ds_Response:</strong> <code>' + redsysData.ds_response + '</code><br>';
                            }
                            if (redsysData.transaction_id) {
                                resultHtml += '<strong>Transaction ID:</strong> <code>' + redsysData.transaction_id + '</code><br>';
                            }
                            if (redsysData.sources && redsysData.sources.length > 0) {
                                resultHtml += '<strong>Fuente de datos:</strong> ' + redsysData.sources.join(', ') + '<br>';
                            }
                        } else {
                            resultHtml += '<strong>Estado en Redsys:</strong> ❌ NO AUTORIZADO / SIN DATOS<br>';
                        }
                        
                        if (isGhost) {
                            resultHtml += '<br><strong>⚠️ ACCIÓN NECESARIA:</strong> Este pedido está pagado en Redsys pero marcado como "' + status + '" en WooCommerce.<br>';
                            resultHtml += 'Se recomienda cambiar el estado a "processing" o "completed" manualmente.';
                        }
                        
                        resultHtml += '</div>';
                        $result.html(resultHtml);
                    } else {
                        $result.html('<div style="color: #dc3232; padding: 10px; background: #fef0f0; border-left: 4px solid #dc3232; margin-top: 10px;">' +
                            '<strong>Error:</strong> ' + response.data +
                            '</div>');
                    }
                },
                error: function() {
                    $result.html('<div style="color: #dc3232; padding: 10px; background: #fef0f0; border-left: 4px solid #dc3232; margin-top: 10px;">' +
                        '<strong>Error:</strong> Error de conexión con el servidor' +
                        '</div>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        checkForUpdates: function(e) {
            e.preventDefault();

            var $btn = $(e.target);
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('Comprobando...');
            var $result = $('#replanta_check_updates_result');
            
            $result.html('<div style="padding: 10px; background: #f0f0f1; border-left: 4px solid #72aee6;">Comprobando actualizaciones en GitHub...</div>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'replanta_god_check_updates',
                    nonce: repl_god_obj.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var hasUpdate = data.has_update;
                        var currentVersion = data.current_version;
                        var newVersion = data.new_version;
                        
                        var resultHtml = '<div style="padding: 15px; border-left: 4px solid ' + (hasUpdate ? '#d63638' : '#46b450') + '; background: ' + (hasUpdate ? '#fef0f0' : '#f0f6f0') + '; margin-top: 10px;">';
                        
                        if (hasUpdate) {
                            resultHtml += '<strong>🎉 Nueva versión disponible</strong><br><br>';
                            resultHtml += '<strong>Versión actual:</strong> ' + currentVersion + '<br>';
                            resultHtml += '<strong>Nueva versión:</strong> ' + newVersion + '<br><br>';
                            resultHtml += '✅ WordPress ahora detectará la actualización.<br>';
                            resultHtml += '<a href="' + data.update_url + '" class="button button-primary" style="margin-top: 10px;">Ir a Actualizaciones</a>';
                        } else {
                            resultHtml += '<strong>✅ Plugin actualizado</strong><br><br>';
                            resultHtml += 'Versión actual: <strong>' + currentVersion + '</strong><br>';
                            resultHtml += 'Estás usando la última versión disponible.';
                        }
                        
                        resultHtml += '</div>';
                        $result.html(resultHtml);
                        
                        if (hasUpdate) {
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        }
                    } else {
                        $result.html('<div style="color: #dc3232; padding: 10px; background: #fef0f0; border-left: 4px solid #dc3232; margin-top: 10px;">' +
                            '<strong>Error:</strong> ' + response.data +
                            '</div>');
                    }
                },
                error: function() {
                    $result.html('<div style="color: #dc3232; padding: 10px; background: #fef0f0; border-left: 4px solid #dc3232; margin-top: 10px;">' +
                        '<strong>Error:</strong> No se pudo conectar con GitHub. Verifica tu conexión.' +
                        '</div>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        showMessage: function(message, type, container) {
            var $container = $(container || '.replanta-god-container');
            var $message = $('<div class="replanta-message replanta-message-' + type + '" style="margin-bottom: 15px;">' + message + '</div>');

            if (container && container.startsWith('#')) {
                $(container).html(message);
            } else {
                $container.prepend($message);
                setTimeout(function() {
                    $message.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        }
    };

    console.log('[Replanta GOD] Esperando document.ready...');
    $(document).ready(function() {
        console.log('[Replanta GOD] Document.ready fired!');
        try {
            ReplicaGOD.init();
            console.log('[Replanta GOD] Inicialización completada exitosamente');
        } catch(error) {
            console.error('[Replanta GOD] ERROR en inicialización:', error);
            console.error('[Replanta GOD] Stack trace:', error.stack);
        }
    });

})(jQuery);
