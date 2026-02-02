/**
 * Replanta Ghost Orders Admin Scripts
 */

(function($) {
    'use strict';

    var ReplicaGOD = {

        init: function() {
            this.attachEventHandlers();
        },

        attachEventHandlers: function() {
            $(document).on('click', '.replanta-god-tab', this.handleTabSwitch.bind(this));
            $(document).on('submit', '#replanta_god_settings_form', this.handleSettingsSave.bind(this));
            $(document).on('click', '#replanta_test_cf', this.testCloudflareConnection.bind(this));
            $(document).on('click', '#replanta_apply_cf_rules', this.applyCloudflareRules.bind(this));
            $(document).on('click', '#replanta_apply_lswc_rules', this.applyLSWCRules.bind(this));
            $(document).on('click', '#replanta_god_refresh_orders', this.refreshGhostOrders.bind(this));
            $(document).on('click', '#replanta_god_apply_bulk', this.applyBulkAction.bind(this));
            $(document).on('click', '.replanta-sync-order', this.syncOrder.bind(this));
            $(document).on('change', '#replanta_god_select_all', this.selectAllOrders.bind(this));
        },

        handleTabSwitch: function(e) {
            if (e.preventDefault) e.preventDefault();
            var tab = $(e.target).data('tab') || $(e.target).attr('href').split('tab=')[1];
            if (!tab) return false;
            window.location.hash = 'tab=' + tab;
            return false;
        },

        handleSettingsSave: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $submit = $form.find('button[type="submit"]');
            var originalText = $submit.text();

            $submit.prop('disabled', true).text('Guardando...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'replanta_god_save_settings',
                    nonce: repl_god_obj.nonce,
                    cloudflare_api_key: $('#replanta_cf_api_key').val(),
                    cloudflare_zone_id: $('#replanta_cf_zone').val(),
                    replanta_mode: $('input[name="replanta_mode"]:checked').val(),
                    detection_enabled: $('input[name="detection_enabled"]').is(':checked') ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        ReplicaGOD.showMessage('Configuracion guardada correctamente', 'success', $form);
                    } else {
                        ReplicaGOD.showMessage('Error: ' + response.data, 'error', $form);
                    }
                },
                error: function() {
                    ReplicaGOD.showMessage('Error de conexion', 'error', $form);
                },
                complete: function() {
                    $submit.prop('disabled', false).text(originalText);
                }
            });
        },

        testCloudflareConnection: function(e) {
            e.preventDefault();

            var apiKey = $('#replanta_cf_api_key').val();
            var zoneId = $('#replanta_cf_zone').val();

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

    $(document).ready(function() {
        ReplicaGOD.init();
    });

})(jQuery);
