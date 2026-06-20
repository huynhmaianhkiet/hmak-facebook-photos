(function (blocks, element, components, blockEditor, i18n, serverSideRender) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var SelectControl = components.SelectControl;
    var ToggleControl = components.ToggleControl;
    var Placeholder = components.Placeholder;
    var ServerSideRender = serverSideRender;
    var __ = i18n.__;

    registerBlockType('hura/apps-photos', {
        apiVersion: 2,
        title: __('Hura Apps Photos', 'hura-apps-photos'),
        icon: 'format-gallery',
        category: 'embed',
        description: __('Display Facebook album or photo by ID.', 'hura-apps-photos'),
        attributes: {
            fbType: {
                type: 'string',
                default: 'hmakfbalbum'
            },
            fbID: {
                type: 'string',
                default: ''
            },
            lightbox: {
                type: 'boolean',
                default: false
            }
        },
        edit: function (props) {
            var attrs = props.attributes;
            var setAttributes = props.setAttributes;
            var shortcode = '[' + attrs.fbType + ' id=' + (attrs.fbID || '12345') + ' lightbox=' + (attrs.lightbox ? 1 : 0) + ']';

            var renderControls = function () {
                return el(
                    'div',
                    {},
                    el(SelectControl, {
                        label: __('Type', 'hura-apps-photos'),
                        value: attrs.fbType,
                        options: [
                            { label: __('Album', 'hura-apps-photos'), value: 'hmakfbalbum' },
                            { label: __('Photo', 'hura-apps-photos'), value: 'hmakfbphoto' }
                        ],
                        onChange: function (value) {
                            setAttributes({ fbType: value });
                        }
                    }),
                    el(TextControl, {
                        label: __('Facebook ID', 'hura-apps-photos'),
                        help: __('Use Album ID or Photo ID from Facebook URL.', 'hura-apps-photos'),
                        value: attrs.fbID,
                        onChange: function (value) {
                            setAttributes({ fbID: value.replace(/[^0-9]/g, '') });
                        }
                    }),
                    el(ToggleControl, {
                        label: __('Enable Lightbox', 'hura-apps-photos'),
                        checked: !!attrs.lightbox,
                        onChange: function (value) {
                            setAttributes({ lightbox: !!value });
                        }
                    })
                );
            };

            return el(
                'div',
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: __('Hura Apps Photos Settings', 'hura-apps-photos'), initialOpen: true },
                        renderControls()
                    )
                ),
                el(
                    Placeholder,
                    {
                        icon: 'format-gallery',
                        label: __('Hura Apps Photos', 'hura-apps-photos'),
                        instructions: __('Enter ID and options directly below, or in the block sidebar.', 'hura-apps-photos')
                    },
                    renderControls(),
                    el('p', {}, __('Preview shortcode:', 'hura-apps-photos')),
                    el('code', {}, shortcode),
                    attrs.fbID ? el(
                        'div',
                        { style: { marginTop: '12px' } },
                        el(ServerSideRender, {
                            block: 'hura/apps-photos',
                            attributes: attrs
                        })
                    ) : null
                )
            );
        },
        save: function () {
            return null;
        }
    });
})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.i18n, window.wp.serverSideRender);
