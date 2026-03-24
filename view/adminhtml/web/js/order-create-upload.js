define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'prototype',
    'Magento_Ui/js/lib/view/utils/async'
], function ($, alert) {
    'use strict';

    var buttonId = 'oporteo-orderupload-admin-button';
    var inputId = 'oporteo-orderupload-admin-file';
    var buttonLabel = $.mage.__('Upload File (CSV, XLS, XLSX)');
    var reloadAreas = ['sidebar', 'items', 'shipping_method', 'billing_method', 'totals', 'giftmessage'];

    function getButtonHtml()
    {
        return '<button id="' + buttonId + '" type="button" class="action-secondary">' +
            '<span>' + buttonLabel + '</span>' +
            '</button>';
    }

    function showError(message)
    {
        alert({
            content: message
        });
    }

    function uploadFile(config, file)
    {
        var formData = new FormData();

        formData.append('form_key', config.formKey);
        formData.append('file', file);

        $('#' + buttonId).prop('disabled', true).addClass('disabled');

        $.ajax({
            url: config.uploadUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (response) {
            if (!response || response.success !== true) {
                window.order.loadArea(['message'], true);
                showError(response && response.message ? response.message : $.mage.__('File upload failed.'));

                return;
            }

            window.order.loadArea(reloadAreas, true);
        }).fail(function () {
            showError($.mage.__('File upload failed.'));
        }).always(function () {
            $('#' + inputId).val('');
            $('#' + buttonId).prop('disabled', false).removeClass('disabled');
        });
    }

    function bindButton(config)
    {
        var $actions = $('#order-items .admin__page-section-title .actions');

        if ($actions.length === 0 || $('#' + buttonId).length > 0) {
            return;
        }

        $actions.append(getButtonHtml());

        $('#' + buttonId).on('click', function () {
            $('#' + inputId).trigger('click');
        });

        $('#' + inputId).off('change.oporteoOrderUpload').on('change.oporteoOrderUpload', function () {
            if (!this.files || !this.files.length) {
                return;
            }

            uploadFile(config, this.files[0]);
        });
    }

    function init(config)
    {
        $.async('#order-items .admin__page-section-title', function () {
            if (!window.order || !window.order.itemsArea || window.order.itemsArea.oporteoOrderUploadBound) {
                return;
            }

            window.order.itemsArea.onLoad = window.order.itemsArea.onLoad.wrap(function (proceed) {
                proceed();
                bindButton(config);
            });
            window.order.itemsArea.oporteoOrderUploadBound = true;

            bindButton(config);
        });
    }

    return function (config) {
        init(config);
    };
});
