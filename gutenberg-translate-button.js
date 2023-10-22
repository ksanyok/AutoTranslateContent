const { registerPlugin } = wp.plugins;
const { PluginMoreMenuItem } = wp.editPost;
const { withDispatch } = wp.data;
const { compose } = wp.compose;
const { __ } = wp.i18n;
const { createElement } = wp.element;

const TranslateButton = compose(
    withDispatch( ( dispatch, ownProps ) => {
        return {
            onClick: function() {
                var postId = wp.data.select('core/editor').getCurrentPostId();
                var nonce = wpApiSettings.nonce;

                wp.ajax.post('translate_content', {
                    nonce: wpApiSettings.nonce,
                    post_id: postId,
                })
                    .done(function(response) {
                        if (response.success) {
                            alert('Перевод успешно выполнен.');
                            location.reload();
                        } else {
                            alert('Ошибка при переводе: ' + response.data.message);
                        }
                    })
                    .fail(function (jqXHR, textStatus, errorThrown) {
                        alert('Ошибка при переводе: ' + textStatus + ' ' + errorThrown);
                    });
            },
        };
    } )
)( ( { onClick } ) => (
    createElement(
        PluginMoreMenuItem,
        {
            icon: "translation",
            onClick: onClick
        },
        __( 'Перевести', 'auto-translate-content' )
    )
) );

registerPlugin( 'my-translate-plugin', {
    render: TranslateButton,
    icon: 'translation'
} );
