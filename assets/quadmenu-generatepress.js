(function ($) {

    var save = false;

    $(document).on('quadmenu_compiler_end', function (e, notice) {
        $('nav#quadmenu').addClass('js');
    });

    $(document).on('quadmenu_generatepress_customizer', function (e, customized, action) {

        if (typeof (quadmenu) == 'undefined')
            return;

        if (!quadmenu.files)
            return;

        if (!action)
            return;

        if (!customized)
            return;

        if (action === 'change') {
            save = true;
            $('nav#quadmenu').removeClass('js');
        }

        console.log(6);

        $.ajax({
            type: 'post',
            dataType: 'json',
            url: ajaxurl,
            data: {
                action: 'quadmenu_generatepress_customized',
                customized: customized,
                nonce: quadmenu.nonce,
            },
            success: function (response) {

                if (!response)
                    return;

                console.log('Customized [' + JSON.stringify(response) + ']');

                try {
                    $(document).trigger('quadmenu_compiler_files', [quadmenu.files, response, action]);
                } catch (error) {
                    alert('Not JSON');
                }

            },
            error: function (response) {
                console.log(response.responseText);

                $(document).trigger('quadmenu_compiler_end');
            },
            complete: function (xhr, status, error) {
            }
        });

    });

    function monitor_events(object_path) {
        var p = eval(object_path);
        if (p) {
            var k = _.keys(p.topics);
            console.log(object_path + " has events ", k);
            _.each(k, function (a) {
                p.bind(a, function () {
                    console.log(object_path + ' event ' + a, arguments);
                });
            });
        } else {
            console.log(object_path + ' does not exist');
        }
    }

    wp.customize.bind('preview-ready', function () {
        //monitor_events('wp.customize.preview');

        _.each(quadmenu.customizer_settings, function (id) {
            wp.customize(id, function (value) {

                value.bind(_.debounce(function (to) {

                    $(document).trigger('quadmenu_generatepress_customizer', [to, 'change']);

                }, 500));
            });
        });

        wp.customize.preview.bind('saved', function (to) {
            if (save) {
                $(document).trigger('quadmenu_generatepress_customizer', [to, 'save']);
            }
        });

        wp.customize.selectiveRefresh.bind('partial-content-rendered', function (placement) {
            $('nav#quadmenu').addClass('js')
        });
    });


})(jQuery);