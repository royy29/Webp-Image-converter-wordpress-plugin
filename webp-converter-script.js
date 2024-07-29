jQuery(document).ready(function($) {
    $('#webp-converter-select-images').on('click', function(e) {
        e.preventDefault();

        var imageFrame;
        if (imageFrame) {
            imageFrame.open();
            return;
        }

        imageFrame = wp.media({
            title: 'Select Images',
            multiple: 'add',
            library: {
                type: 'image'
            }
        });

        imageFrame.on('select', function() {
            var imageIDs = [];
            var images = imageFrame.state().get('selection');

            images.each(function(attachment) {
                imageIDs.push(attachment.id);
            });

            $('#webp-converter-image-ids').val(imageIDs.join(','));
        });

        imageFrame.open();
    });
});
