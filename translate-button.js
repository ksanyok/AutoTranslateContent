(function() {
    jQuery(function ($) {
        if ($('body').hasClass('post-php') || $('body').hasClass('post-new-php')) {
            $('div#titlediv').after('<button id="translate-content" class="button button-primary button-large">Перевести</button>');
        }

        function translateContent() {
            var postId = $('#post_ID').val();

            $.ajax({
                url: translate_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'translate_content',
                    nonce: translate_object.nonce,
                    post_id: postId
                },
                beforeSend: function () {
                    $('#translate-content').prop('disabled', true).text('Перевод...');
                },
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        if (response.data && response.data.message) {
                            alert('Ошибка перевода: ' + response.data.message);
                        } else {
                            alert('Ошибка перевода: Неизвестная ошибка');
                        }
                    }
                },
                complete: function () {
                    $('#translate-content').prop('disabled', false).text('Перевести');
                }
            });
        }

        $('#translate-content').on('click', translateContent);

        if (window.wp && wp.plugins && wp.editPost && wp.components) {
            const registerPlugin = wp.plugins.registerPlugin;
            const PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
            const Button = wp.components.Button;

            function TranslateButton() {
                return wp.element.createElement(
                    PluginPostStatusInfo,
                    {},
                    wp.element.createElement(
                        Button,
                        {
                            isPrimary: true,
                            onClick: translateContent,
                        },
                        'Перевести'
                    )
                );
            }

            registerPlugin('translate-button', {
                render: function() {
                    return TranslateButton();
                },
            });
        }
    });
})();
