// scroll to top on page load
window.onbeforeunload = function () {
    window.scrollTo(0, 0);
};
jQuery(document).ready(function ($) {

    var $submenu = $('#toplevel_page_wploti-settings .wp-submenu li');

    // init tabs

    $('#wploti_tabs').tabs({

        create: function (event, ui) {
            // Adjust hashes to not affect URL when clicked.
            var widget = $('#wploti_tabs').data("uiTabs");
            widget.panels.each(function (i) {
                this.id = "uiTab_" + this.id; // Prepend a custom string to tab id.
                widget.anchors[i].hash = "#" + this.id;
                $(widget.tabs[i]).attr("aria-controls", this.id);
            });
        },
        activate: function (event, ui) {
            // Add the original "clean" tab id to the URL hash.
            window.location.hash = ui.newPanel.attr("id").replace("uiTab_", "");
            "option", "active"
        },

    }).show({ effect: "blind", duration: 800 });


    $(window).on("hashchange", function () {

        var $tab_hash = location.hash.split("#")[1];

        switch ($tab_hash) {
            case 'header':
                $active = 2
                break;

            case 'ip':
                $active = 3
                break;

            case 'keys':
                $active = 4
                break;

            case 'password':
                $active = 5
                break;

            case 'animation':
                $active = 6
                break;

            case 'message':
                $active = 7
                break;

            case 'extra':
                $active = 8
                break;

            default:
                $active = 1
                break;

        }

        $('#toplevel_page_wploti-settings .wp-submenu li.current').removeClass('current');
        $submenu[$active].classList.add('current');

    }).trigger("hashchange")


    $submenu.bind('click', function (e) {

        var $active;

        $('#toplevel_page_wploti-settings .wp-submenu li.current').removeClass('current');
        $(this).addClass('current');

        $menu_hash = $(this).find('a').attr('href').split("#")[1];

        switch ($menu_hash) {
            case 'header':
                $active = 0
                break;

            case 'ip':
                $active = 1
                break;

            case 'keys':
                $active = 2
                break;

            case 'password':
                $active = 3
                break;

            case 'animation':
                $active = 4
                break;

            case 'message':
                $active = 5
                break;

            case 'extra':
                $active = 6
                break;

            default:
                break;
        }

        // Set active tab
        $("#wploti_tabs").tabs("option", "active", $active);

    });


    function wploti_state() {
        if ($('#wploti-toggle-adminbar').hasClass('status-1')) { // on
            $('.wploti_animation_state').map(function () {
                $('.wploti_animation_state > lottie-player').remove();
                $('.wploti_animation_state').append(
                    $('<lottie-player/>')
                        .attr("autoplay", "true")
                        .attr("loop", "true")
                        .attr("src", wploti_var.IMG_path + "/green-on.json")
                        .addClass("animation-state")
                )
            })
        } else {  //off
            $('.wploti_animation_state').map(function () {
                $('.wploti_animation_state > lottie-player').remove();
                $('.wploti_animation_state').append(
                    $('<lottie-player/>')
                        .attr("autoplay", "true")
                        .attr("loop", "true")
                        .attr("src", wploti_var.IMG_path + "/red-off.json")
                        .addClass("animation-state")
                )
            })
        };
    }

    /**
     * toggle wploti activation via menu bar ajax
   */

    $('#wploti-toggle-adminbar').on('click', function (e) {
        e.preventDefault();
        var security = $(this).data('security');
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wploti_toggle_activation',
                security: security,
                payload: 'toggle_wploti_status',
            },
            type: 'post',
            success: function (result, textstatus) {

                /* console.log(result);
                console.log('sucess'); */

                $('#wploti-toggle-adminbar').toggleClass('status-1');
                wploti_state();
                $('#wploti_main_options').fadeToggle();
                $('.wploti-status input[type=radio]').prop('disabled', function (_, val) {
                    return !val;
                });
                $('#wploti-status').prop('checked', function (_, val) {
                    return !val;
                });
            },
            error: function (result) {
                /*console.log(result);

                console.log('fail');*/
            },
        })
    });

    /**
     * save header type 
     */

    $('input.wploti_header_type').on('change', function () {
        var header_type = $(this).val();
        var security = $(this).data('security');
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wploti_header_type',
                security: security,
                header_type: header_type,
            },
            type: 'post',
            success: function (result, textstatus) {
                // console.log(result);

                //  console.log('sucess');

                //window.opener.location.reload();

                $(".updated").text(result.data.message).fadeIn(1000).delay(7000).fadeOut("slow");
            },
            error: function (result) {
                // console.log(result);

                //  console.log('fail');
                $(".error").text(result.data.message).fadeIn(1000).delay(7000).fadeOut("slow");
            },
        })
    })

    /**
     * save whitelisted roles
     */

    $('input.wploti_whitelisted_roles').on('change', function () {
        var role = $(this).val();
        var security = $(this).data('security');

        if ($(this).is(':checked')) {
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'wploti_add_whitelisted_roles',
                    security: security,
                    role: role,
                },
                type: 'post',
                success: function (result, textstatus) {
                    console.log(result);

                    //console.log('sucess');

                    //window.opener.location.reload();

                    $(".updated").text(result.data.message).fadeIn(1000).delay(7000).fadeOut("slow");
                },
                error: function (result) {
                    //console.log(result);

                    //console.log('fail');
                },
            })
        } else {
            //console.log($(this).val() + ' is now unchecked');
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'wploti_remove_whitelisted_roles',
                    security: security,
                    role: role,
                },
                type: 'post',
                success: function (result, textstatus) {
                    //console.log(result);

                    //console.log('sucess');

                    //window.opener.location.reload();

                    $(".updated").text(result.data.message).fadeIn(1000).delay(7000).fadeOut("slow");
                },
                error: function (result) {
                    //console.log(result);

                    //console.log('fail');
                },
            })
        }
    })


    /**
     * save input message value 
     */


    function get_tinymce_content() {
        if (jQuery("#wp-content-wrap").hasClass("tmce-active")) {
            return tinyMCE.activeEditor.getContent();
        } else {
            return jQuery('#html_text_area_id').val();
        }
    }


    $('#wploti_text_message #wp-content-editor-tools').append(
        $('<button/>')
            .attr("type", "button")
            .attr("data-security", wploti_var.wploti_nonce)
            .attr("id", "wploti_message")
            .attr("name", "wploti_message")
            .text(wploti_var.save_content)
            .addClass("button wploti_save")
    )

    // init select2
    $('#wploti_whitelisted_users').select2({ 'placeholder': wploti_var.wploti_whitelisted_users_placeholder });

    /**
     * add whitelisted users to databse on select
     */

    $('#wploti_whitelisted_users').on('select2:select', function (e) {

        var user_id = e.params.data.id;
        var security = $(this).data('security');

        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wploti_add_whitelisted_users',
                user_id: user_id,
                security: security,
            },
            type: 'post',
            success: function (result, textstatus) {
                // console.log(result);

                //  console.log('sucess');

                $(".updated").text(result.data.message).fadeIn(1000).delay(7000).fadeOut("slow");
            },
            error: function (result) {
                // console.log(result);

                //  console.log('fail');
            },
        })

    });

    /**
     * remove whitelisted users to databse on select
     */

    $('#wploti_whitelisted_users').on('select2:unselect', function (e) {
        var user_id = e.params.data.id;
        var security = $(this).data('security');

        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wploti_remove_whitelisted_users',
                user_id: user_id,
                security: security,
            },
            type: 'post',
            success: function (result, textstatus) {
                // console.log(result);

                // console.log('sucess');

                $(".updated").fadeIn(1000).delay(7000).fadeOut("slow");
            },
            error: function (result) {
                // console.log(result);

                // console.log('fail');
            },
        })

    });

    $('#wploti-save-custom-login').on('click', function () {
        var enabled = $('#wploti-custom-login-enabled').is(':checked');
        var slug = $('#wploti-custom-login-slug').val().trim();
        var privateLoginEnabled = $('#wploti-private-login-enabled').is(':checked');
        var result = $('#wploti-custom-login-result');

        result.empty();
        $.post(ajaxurl, {
            action: 'wploti_save_custom_login_url',
            security: $('#wploti-custom-login-enabled').data('security'),
            enabled: enabled ? '1' : '0',
            slug: slug,
            private_login_enabled: privateLoginEnabled ? '1' : '0'
        }).done(function (response) {
            if (!response.success) {
                $('.notice').text(response.data.message).fadeIn(1000).delay(7000).fadeOut('slow');;
                return;
            }

            $('#wploti-custom-login-slug').val(response.data.slug);
            $('.updated').text(enabled ? response.data.login_url_saved : response.data.login_access_restored).fadeIn(1000).delay(7000).fadeOut('slow');
        }).fail(function () {
            result.empty();
        });
    });

    $('#wploti-copy-custom-login-url').on('click', function () {
        var button = $(this);
        var url = $('#wploti-custom-login-slug').data('base-url') + $('#wploti-custom-login-slug').val().trim();

        if (!navigator.clipboard) {
            return;
        }

        navigator.clipboard.writeText(url).then(function () {
            var original = button.find('.dashicons').attr('class');
            button.find('.dashicons').attr('class', 'dashicons dashicons-yes');
            setTimeout(function () {
                button.find('.dashicons').attr('class', original);
            }, 1500);
        });
    });

    $('#wploti-generate-custom-login-slug').on('click', function () {
        var chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        var slug = '';
        for (var i = 0; i < 12; i++) {
            slug += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('#wploti-custom-login-slug').val(slug);
    });

    $('button.wploti_save').on('click', function () {

        $(this).text(wploti_var.saved_content);

        var message = get_tinymce_content();
        var security = $(this).data('security');

        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wploti_ajax_message',
                security: security,
                message: message,
            },
            type: 'post',
            success: function (result, textstatus) {
                // console.log(result.data.message);
                // console.log('sucess');

                $(".updated").text(result.data.message).fadeIn(1000).delay(7000).fadeOut("slow");
            },
            error: function (result) {
                // console.log(result);
                // console.log('fail');

                $(".error").text(result.data.message).fadeIn(1000).delay(7000).fadeOut("slow");
            },
        })

        setTimeout(() => {
            $(this).text(wploti_var.save_content)
            // wploti_var.refresh_active = false;
            // console.log(wploti_var.refresh_active);
        }, 5000);
    })

    /**
     *  (js) dismiss activation notice
     */

    $(document).on('click', '.wploti-activation-dismiss', function (event) {
        event.preventDefault();
        var security = $(this).data('security');
        $.post(ajaxurl, {
            action: 'wploti_ajax_dismiss_activation_notice',
            security: security,
        });
        $('#wploti_enabled_notice').fadeOut('3000');
    });

    /**
     *  (js) dismiss notes notice
     */

    $('#wploti_note_notice .wploti-note-dismiss').on('click', function (event) {
        event.preventDefault();
        var security = $(this).data('security');
        $.post(ajaxurl, {
            action: 'wploti_ajax_dismiss_notes_notice',
            security: security,
        });
        $('#wploti_note_notice').fadeOut('3000');
    });

    /**
     * toggle wploti activation via settings button
     */

    $('.wploti-maintenance-toggle #wploti-status').on('click', function () {
        var security = $(this).data('security');
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wploti_toggle_activation',
                security: security,
                payload: 'toggle_wploti_status',
            },
            type: 'post',
            success: function (result, textstatus) {
                /* console.log(result);

                 console.log('sucess');*/
                $('.wploti-maintenance-toggle .wploti-status input[type=radio]').prop('disabled', function (_, val) {
                    return !val;
                });
                $('#wploti-toggle-adminbar').toggleClass('status-1');
                $('#wploti_main_options').fadeToggle();
                wploti_state();
                //$(".updated").fadeIn(1000).delay(7000).fadeOut("slow");
            },
            error: function (result) {
                // console.log(result);

                //  console.log('fail');
            },
        })
    })

    function isValidIpAddress(ipAddress) {
        var ipv4 = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)|\*))$/;
        var ipv6 = /^((?:[0-9a-f]{1,4}:){7}[0-9a-f]{1,4}|(?:[0-9a-f]{1,4}:){1,7}:|(?:[0-9a-f]{1,4}:){1,6}:[0-9a-f]{1,4}|(?:[0-9a-f]{1,4}:){1,5}(?::[0-9a-f]{1,4}){1,2}|(?:[0-9a-f]{1,4}:){1,4}(?::[0-9a-f]{1,4}){1,3}|(?:[0-9a-f]{1,4}:){1,3}(?::[0-9a-f]{1,4}){1,4}|(?:[0-9a-f]{1,4}:){1,2}(?::[0-9a-f]{1,4}){1,5}|[0-9a-f]{1,4}:(?:(?::[0-9a-f]{1,4}){1,6})|:(?:(?::[0-9a-f]{1,4}){1,7}|:))$/i;
        return ipv4.test(ipAddress) || ipv6.test(ipAddress);
    }

    function validateIpRecord(name, ipAddress) {
        var errors = [];
        if (!name.trim()) {
            errors.push(wploti_var.ip_name_required);
        }
        if (!ipAddress.trim()) {
            errors.push(wploti_var.ip_required);
        } else if (!isValidIpAddress(ipAddress.trim())) {
            errors.push(wploti_var.ip_invalid);
        }
        return errors;
    }

    function showIpTableLoading() {
        $('#wploti_mr_ip_tbl_container').html('<img src="' + wploti_var.ip_ajax_loader + '" alt="">');
    }

    $(document).on('click', '.wploti-mr-add-ip', function (event) {
        event.preventDefault();
        var name = $('#wploti_mr_new_ip_name').val();
        var ipAddress = $('#wploti_mr_new_ip_ip').val();
        var errors = validateIpRecord(name, ipAddress);

        if (errors.length) {
            //window.alert(errors.join('\n'));
            wploti_alert_arr(errors);
            return;
        }

        showIpTableLoading();
        $.post(ajaxurl, {
            action: 'wploti_mr_add_ip', security: wploti_var.wploti_nonce,
            wploti_mr_ip_name: name, wploti_mr_ip_ip: ipAddress
        }, function (response) {
            $('#wploti_mr_ip_tbl_container').html(response);
        });
    });

    $(document).on('click', '.wploti-mr-toggle-ip', function (event) {
        event.preventDefault();
        var $action = $(this);
        var ipId = $action.data('ip-id');
        var $status = $('#wploti_mr_ip_status_' + ipId);
        $status.html('<img src="' + wploti_var.ip_ajax_loader + '" alt="">');

        $.post(ajaxurl, {
            action: 'wploti_mr_toggle_ip', security: wploti_var.wploti_nonce,
            wploti_mr_ip_active: $action.data('ip-active'), wploti_mr_ip_id: ipId
        }, function (response) {
            var parts = response.split('|');
            if (parts[0] !== 'SUCCESS') {
                // window.alert(wploti_var.ip_database_error); //TODO : Back to this in case of errors
                wploti_alert(wploti_var.ip_database_error);
                return;
            }

            var isActive = parts[2] === '1';
            $status.html(isActive ? '<span class="green">Yes</span>' : '<span class="red">No</span>');
            $action.text(isActive ? wploti_var.disable : wploti_var.enable).data('ip-active', isActive ? 0 : 1);
        });
    });

    $(document).on('click', '.wploti-mr-delete-ip', function (event) {
        event.preventDefault();
        var $action = $(this);
        if (!window.confirm(wploti_var.delete_ip_confirmation + '\n\n' + $action.data('ip-address'))) {
            return;
        }

        showIpTableLoading();
        $.post(ajaxurl, {
            action: 'wploti_mr_delete_ip', security: wploti_var.wploti_nonce,
            wploti_mr_ip_id: $action.data('ip-id')
        }, function (response) {
            $('#wploti_mr_ip_tbl_container').html(response);
        });
    });

    $(document).on('click', '.wploti-mr-edit-ip', function (event) {
        event.preventDefault();
        var $action = $(this);
        var $row = $action.closest('tr');
        var $nameCell = $row.find('.column-wploti-ip-name');
        var $ipCell = $row.find('.column-wploti-ip-ip');
        var $actionsCell = $row.find('.column-wploti-actions');

        $row.data('original-name', $nameCell.text().trim());
        $row.data('original-ip', $ipCell.text().trim());
        $row.data('original-actions', $actionsCell.html());
        $nameCell.empty().append($('<input>', { type: 'text', class: 'wploti-mr-edit-ip-name', value: $row.data('original-name') }));
        $ipCell.empty().append($('<input>', { type: 'text', class: 'wploti-mr-edit-ip-address', value: $row.data('original-ip') }));
        $actionsCell.empty()
            .append($('<a>', { href: '#', class: 'wploti-mr-save-ip', text: wploti_var.save, 'data-ip-id': $action.data('ip-id') }))
            .append(' | ')
            .append($('<a>', { href: '#', class: 'wploti-mr-cancel-edit-ip', text: wploti_var.cancel }));
        $nameCell.find('input').trigger('focus');
    });

    $(document).on('click', '.wploti-mr-save-ip', function (event) {
        event.preventDefault();
        var $action = $(this);
        var $row = $action.closest('tr');
        var name = $row.find('.wploti-mr-edit-ip-name').val();
        var ipAddress = $row.find('.wploti-mr-edit-ip-address').val();
        var errors = validateIpRecord(name, ipAddress);

        if (errors.length) {
            window.alert(errors.join('\n'));
            return;
        }

        showIpTableLoading();
        $.post(ajaxurl, {
            action: 'wploti_mr_update_ip', security: wploti_var.wploti_nonce,
            wploti_mr_ip_id: $action.data('ip-id'), wploti_mr_ip_name: name, wploti_mr_ip_ip: ipAddress
        }, function (response) {
            $('#wploti_mr_ip_tbl_container').html(response);
        });
    });

    $(document).on('click', '.wploti-mr-cancel-edit-ip', function (event) {
        event.preventDefault();
        var $row = $(this).closest('tr');
        $row.find('.column-wploti-ip-name').text($row.data('original-name'));
        $row.find('.column-wploti-ip-ip').text($row.data('original-ip'));
        $row.find('.column-wploti-actions').html($row.data('original-actions'));
    });

    $(document).on('click', '.wploti-toggle-post-exclusion', function (event) {
        event.preventDefault();
        var $action = $(this);
        $action.addClass('disabled').attr('aria-disabled', 'true');

        $.post(ajaxurl, {
            action: 'wploti_toggle_post_exclusion',
            post_id: $action.data('post-id'),
            security: $action.data('security')
        }, function (response) {
            if (!response.success) {
                window.alert(wploti_var.page_exclusion_error);
                return;
            }

            $action.text(response.data.label)
                .data('excluded', response.data.excluded ? 1 : 0)
                .removeClass('disabled')
                .removeAttr('aria-disabled');
        }).fail(function () {
            window.alert(wploti_var.page_exclusion_error);
        }).always(function () {
            $action.removeClass('disabled').removeAttr('aria-disabled');
        });
    });

    $(document).on('click', '.wploti-toggle-maintenance-post', function (event) {
        event.preventDefault();
        var $action = $(this);
        $action.addClass('disabled').attr('aria-disabled', 'true');

        $.post(ajaxurl, {
            action: 'wploti_toggle_maintenance_post',
            post_id: $action.data('post-id'),
            security: $action.data('security')
        }, function (response) {
            if (!response.success) {
                window.alert(wploti_var.maintenance_page_error);
                return;
            }

            $('.wploti-toggle-maintenance-post').not($action).text(wploti_var.make_maintenance_page);
            $action.text(response.data.label);
        }).fail(function () {
            window.alert(wploti_var.maintenance_page_error);
        }).always(function () {
            $action.removeClass('disabled').removeAttr('aria-disabled');
        });
    });

    /**
     * load animations by infinite scroll
     */

    var start = 0;
    var limit = 7;
    const step = 7;
    var action = 'inactive';
    var animations_count = $('.animations').attr('animations-count');

    function load_animations(start, limit) {
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wploti_animation_ajax_load',
                payload: 'load_animations_payload',
                security: wploti_var.wploti_nonce,
                start: start,
                limit: limit,
            },
            type: 'post',
            success: function (data, textstatus) {
                $('.animations').append(data);
                if (limit >= animations_count) {
                    //$('#load-animations-message').html("<button type='button' class='button button-primary'>No More Data Found</button>");
                    $('#load-animations-message').html("");
                    action = 'active';
                } else {
                    $('#load-animations-message').html("<button type='button' class='button button-secondary'>" + wploti_var.pls_wait + "....</button>");
                    action = "inactive"; // user action has been completed
                }
            },
            error: function (result) {
                // console.log(result);

                // console.log('fail');
            },
        })
    }
    if (action == 'inactive') {
        action = 'active';
        load_animations(start, limit);
    }
    $(window).scroll(function () {
        if ($(window).scrollTop() + $(window).height() > $(".animations").height() && action == 'inactive') {
            action = 'active';
            limit += step; // increase limit by step
            start += step; // increase counter by step
            // if the limit counter has bypassed the number of animations
            //  reset the limit to the number of animations

            if (limit >= limit - (limit % animations_count) && limit > animations_count) {
                limit = limit - (limit % animations_count);
            }
            setTimeout(function () {
                load_animations(start, limit);
            }, 2000);
        }
    });

    /**
     * RESET SETTINGS
     */

    $('.wploti_reset_settings').click(function () {
        var security = $(this).data('security');
        wploti_confirm.open({
            message: wploti_var.be_careful + ' !<br><br>' + wploti_var.option_reset_txt,
            onok: () => {
                $.ajax({
                    url: ajaxurl,
                    data: {
                        action: 'wploti_reset_settings',
                        security: security,
                    },
                    type: 'post',
                    success: function (result, textstatus) {
                        // console.log(result);

                        // console.log('sucess');
                        window.location.reload(true);
                    },
                    error: function (result) {
                        // console.log(result);

                        // console.log('fail');
                    },
                })
            }
        })
    })
});