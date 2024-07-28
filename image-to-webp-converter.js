jQuery(document).ready(function ($) {
    $('#convert-image').on('click', function () {
        var file_data = $('#image-upload').prop('files')[0];
        if (!file_data) {
            alert('Please select an image.');
            return;
        }

        var form_data = new FormData();
        form_data.append('action', 'convert_single_image');
        form_data.append('image', file_data);

        $.ajax({
            url: webpConverter.ajax_url,
            type: 'POST',
            data: form_data,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    $('#conversion-result').html('<p>' + response.data.message + '</p><p>Converted Image: <a href="' + response.data.webp_url + '" target="_blank">View WebP Image</a></p>');
                } else {
                    $('#conversion-result').html('<p>' + response.data.message + '</p>');
                }
            }
        });
    });

    $('#convert-all-images').on('click', function () {
        $.ajax({
            url: webpConverter.ajax_url,
            type: 'POST',
            data: { action: 'convert_all_images' },
            success: function (response) {
                if (response.success) {
                    $('#conversion-result').html('<p>' + response.data.message + '</p>');
                } else {
                    $('#conversion-result').html('<p>Failed to convert images.</p>');
                }
            }
        });
    });
});
