jQuery(document).ready(function($) {

    tinymce.create('tinymce.plugins.bais_plugin', {
        init : function(ed, url) {
                // Register command for when button is clicked
                ed.addCommand('bais_insert_shortcode', function() {
                    selected = tinyMCE.activeEditor.selection.getContent();

                    if( selected ){
                        //If text is selected when button is clicked
                        //Wrap shortcode around it.
                        content =  '[bais_before_after before_image="'+selected+'" after_image="'+selected+'"]';
                    }else{
                        content =  '[bais_before_after before_image="Add Image Url" after_image="Add Image Url"]';
                    }

                    tinymce.execCommand('mceInsertContent', false, content);
                });

            // Register buttons - trigger above command when clicked
            ed.addButton('bais_button', {title : 'Insert Before After shortcode', cmd : 'bais_insert_shortcode', image: url + '../../img/gfycat-btn.png' });
        },   
    });

    // Register the TinyMCE plugin
    tinymce.PluginManager.add('bais_button', tinymce.plugins.bais_plugin);
});
