(function() {
    tinymce.PluginManager.add('custom_mce_button', function(editor, url) {
        editor.addButton('custom_mce_button', {
            icon: 'hmak-facebook-album',
            text: 'Hura Photos',
            onclick: function() {
                editor.windowManager.open({
                    title: 'HuraApps Photos Properties',
                    body: [
                        {
                            type: 'textbox',
                            name: 'fbID',
                            label: 'ID',
                            value: ''
                        },
                        {
                            type: 'listbox',
                            name: 'fbType',
                            label: 'Type',
                            values: [{
                                text: 'Album',
                                value: 'hmakfbalbum'
                            }, {
                                text: 'Photo',
                                value: 'hmakfbphoto'
                            }]
                        },
                        {
                            type: 'listbox',
                            name: 'fbLightbox',
                            label: 'Lightbox',
                            values: [{
                                text: 'Yes',
                                value: '1'
                            }, {
                                text: 'No',
                                value: '0'
                            }]
                        },
                    ],
                    onsubmit: function(e) {
                        editor.insertContent(
                            '[' +e.data.fbType +' id=' + e.data.fbID+' lightbox=' + e.data.fbLightbox+']'
                        );
                    }
                });
            }
        });
    });
})();