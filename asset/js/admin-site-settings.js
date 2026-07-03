// Enable CKEditor on the help_text field in site settings
(function($) {
    $(document).ready(function() {
        var $helpField = $('textarea[name="audioplayer_help_text"]');
        if ($helpField.length && typeof CKEDITOR !== 'undefined') {
            CKEDITOR.replace($helpField.attr('id') || 'audioplayer_help_text');
        }
    });
})(jQuery);
