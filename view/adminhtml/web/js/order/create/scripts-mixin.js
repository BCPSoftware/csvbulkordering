define([
    'jquery'
], function ($) {
    'use strict';

    return function () {
        window.AdminOrder.prototype.uploadFile = function () {
            let input = document.createElement('input');

            input.type = 'file';
            input.accept = '.csv, .xlsx';
            input.addEventListener('change', function (e) {
                if (!window.fileUploadUrl) {
                    return;
                }

                let formData = new FormData();

                formData.append('file', this.files[0]);
                formData.append('form_key', FORM_KEY);

                $.ajax({
                    url: window.fileUploadUrl,
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    cache: false,
                    processData: false,
                    contentType: false,
                    beforeSend: function () {
                        jQuery('#edit_form').trigger('processStart');
                    }
                }).done(function () {
                    jQuery('#edit_form').trigger('processStop');

                    let area = ['items', 'promo', 'shipping_method', 'totals', 'billing_method'];

                    order.loadArea(area, true);
                });
            });

            input.click();
        }
    }
});
