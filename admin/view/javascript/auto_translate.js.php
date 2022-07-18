<?php
header('Content-type: application/javascript');

if (isset($_GET['language_ids'])) {
    $language_ids = $_GET['language_ids'];
} else {
    $language_ids = '';
}

if (isset($_GET['default_id'])) {
    $default_id = $_GET['default_id'];
} else {
    $default_id = '';
}

if (isset($_GET['user_token'])) {
    $user_token = $_GET['user_token'];
} else {
    $user_token = '';
}

if (isset($_GET['version'])) {
    $version = $_GET['version'];
} else {
    $version = '';
}
?>
var fields = [
    'article_description[@][title]',
    'article_description[@][meta_title]',
    'article_description[@][description]',
    'article_description[@][meta_description]',
    'article_description[@][meta_keyword]',
    'article_tag[@]',
    'attribute_description[@][name]',
    'attribute_group_description[@][name]',
    '[banner_image_description][@][title]',
    'category_description[@][name]',
    'category_description[@][meta_description]',
    'category_description[@][meta_keyword]',
    'category_description[@][description]',
    'customer_group_description[@][name]',
    'customer_group_description[@][description]',
    'download_description[@][name]',
    'filter_group_description[@][name]',
    '[filter_description][@][name]',
    'information_description[@][title]',
    'information_description[@][description]',
    'length_class_description[@][title]',
    'length_class_description[@][unit]',
    'weight_class_description[@][title]',
    'weight_class_description[@][unit]',
    'option_description[@][name]',
    '[option_value_description][@][name]',
    'order_status[@][name]',
    'product_description[@][name]', 
    'product_description[@][meta_title]', 
    'product_description[@][meta_description]', 
    'product_description[@][meta_keyword]', 
    'product_description[@][description]', 
    'product_description[@][tag]',
    '[product_attribute_description][@][text]',
    'profile_description[@][name]',
    'return_action[@][name]',
    'return_reason[@][name]',
    'return_status[@][name]',
    'stock_status[@][name]',
    'voucher_theme_description[@][name]'
];

$(document).ready(function() {
    var language_ids = '<?php echo $language_ids; ?>';
    
    language_ids = language_ids.split('_');
    
    // Each fields
    $.each(fields, function(key, field) {
        // Each languages
        $.each(language_ids, function(key1, language_id) {
            var html = '<a class="auto-translate" data-current="' + field.replace('@', language_id) + '" data-default="' + field.replace('@', <?php echo $default_id; ?>) + '" data-to="' + language_id + '">Translate</a>';

            $('[name*=\'' + field.replace('@', language_id) + '\']').after(html);
        });
    });

    $('.auto-translate').on('click', function() {
        var element = $(this);
        
        var text = $('[name*=\'' + element.attr('data-default') + '\']').val();
        
        var default_element = $('[name*=\'' + element.attr('data-default') + '\']').attr('id');
        
        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[$('[name*=\'' + element.attr('data-default') + '\']').attr('id')]) {
            text = CKEDITOR.instances[$('[name*=\'' + element.attr('data-default') + '\']').attr('id')].getData();
        } else if (typeof $('#' + default_element).attr('data-toggle') !== 'undefined' && $('#' + default_element).attr('data-toggle') == 'summernote') {
            text = $('#' + default_element).summernote('code');
        }

        $.ajax({
            url: 'index.php?route=extension/module/auto_translate/translate&user_token=<?php echo $user_token; ?>',
            type: 'post',
            data: {to: element.attr('data-to'), text: text},
            dataType: 'json',
            beforeSend: function() {
                element.html('Translating...');
            },
            success: function(json) {
                element.html('Translate');
                
                if (json['text']) {
                    var current_element = $('[name*=\'' + element.attr('data-current') + '\']').attr('id');
                    
                    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[$('[name*=\'' + element.attr('data-current') + '\']').attr('id')]) {
                        CKEDITOR.instances[$('[name*=\'' + element.attr('data-current') + '\']').attr('id')].setData(json['text']);
                    } else if (typeof $('#' + current_element).attr('data-toggle') !== 'undefined' && $('#' + current_element).attr('data-toggle') == 'summernote') {
                        $('#' + current_element).summernote('code', json['text']);
                    }
                    
                    $('[name*=\'' + element.attr('data-current') + '\']').val(json['text']);
                } else {
                    alert('No translation available');
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
        
        return false;
    });
});