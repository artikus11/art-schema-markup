jQuery(document).ready(function () {
    var _custom_media = true,
        _orig_send_attachment = wp.media.editor.send.attachment;

    jQuery('.asm-upload .upload_image_button').click(function (e) {
        var button = jQuery(this).prev();
        jQuery(button).empty().val();
        _custom_media = true;
        wp.media.editor.send.attachment = function (props, attachment) {
            if (_custom_media) {
                jQuery(button).val(attachment.url);
            } else {
                return _orig_send_attachment.apply(this, [props, attachment]);
            }
        };
        wp.media.editor.open(button);
        return false;
    });

    jQuery('.asm-upload .remove_image_button').on('click', function () {
        _custom_media = false;
        jQuery(this).prev().prev().val('');
    });
});