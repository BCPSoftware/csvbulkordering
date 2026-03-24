define([
    'jquery',
    'jquery-ui-modules/widget',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, widget, modal) {
    'use strict';

    $.widget('oporteo.orderUpload', {
        options: {
            validateUrl: '',
            proceedUrl: '',
            formSelector: '.order-upload__form',
            dropzoneSelector: '.order-upload__dropzone',
            inputSelector: '.order-upload__input',
            fileNameSelector: '[data-role="selected-file"]',
            modalSelector: '[data-role="validation-modal"]',
            summaryTextSelector: '[data-role="summary-text"]',
            successTableSelector: '[data-role="validation-successes"]',
            failureTableSelector: '[data-role="validation-failures"]',
            successSectionSelector: '[data-role="summary-successes-section"]',
            failureSectionSelector: '[data-role="summary-failures-section"]',
            processSummarySelector: '[data-role="process-summary"]',
            activeClass: 'is-active',
            hasFileClass: 'has-file',
            mobileBreakpoint: 767
        },

        _create: function () {
            this.validationToken = '';
            this.$form = this.element.find(this.options.formSelector);
            this.$dropzone = this.element.find(this.options.dropzoneSelector);
            this.$input = this.element.find(this.options.inputSelector);
            this.$fileName = this.element.find(this.options.fileNameSelector);
            this.$modal = this.element.find(this.options.modalSelector);
            this.$summaryText = this.element.find(this.options.summaryTextSelector);
            this.$successTableBody = this.element.find(this.options.successTableSelector);
            this.$failureTableBody = this.element.find(this.options.failureTableSelector);
            this.$successSection = this.element.find(this.options.successSectionSelector);
            this.$failureSection = this.element.find(this.options.failureSectionSelector);
            this.$processSummary = this.element.find(this.options.processSummarySelector);

            if (!this.$dropzone.length || !this.$input.length || !this.$form.length) {
                return;
            }

            this._initModal();
            this._bind();
        },

        _initModal: function () {
            if (!this.$modal.length) {
                return;
            }

            modal({
                type: 'popup',
                responsive: false,
                modalClass: 'order-upload__validation-modal',
                innerScroll: true,
                closed: this._resetSelectedFile.bind(this),
                title: $.mage.__('Validation Summary'),
                buttons: [
                    {
                        text: $.mage.__('Cancel'),
                        class: 'action secondary',
                        click: this._cancelValidation.bind(this)
                    },
                    {
                        text: $.mage.__('Proceed'),
                        class: 'action primary',
                        click: this._proceed.bind(this)
                    }
                ]
            }, this.$modal);
        },

        _bind: function () {
            this._on(this.$dropzone, {
                click: '_onDropzoneClick',
                dragenter: '_onDragEnter',
                dragover: '_onDragOver',
                dragleave: '_onDragLeave',
                drop: '_onDrop'
            });

            this._on(this.$input, {
                change: '_onInputChange'
            });
        },

        _onDropzoneClick: function (event) {
            if ($(event.target).is('a, button, input, select, textarea')) {
                return;
            }

            event.preventDefault();
            this.$input.trigger('click');
        },

        _onDragEnter: function (event) {
            event.preventDefault();
            this.$dropzone.addClass(this.options.activeClass);
        },

        _onDragOver: function (event) {
            event.preventDefault();
            this.$dropzone.addClass(this.options.activeClass);
        },

        _onDragLeave: function (event) {
            event.preventDefault();
            this.$dropzone.removeClass(this.options.activeClass);
        },

        _onDrop: function (event) {
            var nativeEvent = event.originalEvent,
                files = nativeEvent && nativeEvent.dataTransfer ? nativeEvent.dataTransfer.files : null;

            event.preventDefault();
            this.$dropzone.removeClass(this.options.activeClass);

            if (!files || !files.length) {
                return;
            }

            this.$input[0].files = files;
            this._updateSelectedFile(files[0].name);
            this.$input.trigger('change');
        },

        _onInputChange: function () {
            var files = this.$input[0].files;

            if (!files || !files.length) {
                this._updateSelectedFile('');

                return;
            }

            this._updateSelectedFile(files[0].name);
            this._validateFile();
        },

        _validateFile: function () {
            var formData;

            if (!this.options.validateUrl) {
                return;
            }

            formData = new FormData(this.$form[0]);
            this.validationToken = '';
            $('body').trigger('processStart');

            $.ajax({
                url: this.options.validateUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            }).done(function (response) {
                if (!response || !response.success) {
                    this._showError(response && response.message ? response.message : $.mage.__('Validation failed.'));

                    return;
                }

                this.validationToken = response.summary.token || '';
                this._renderValidationSummary(response.summary || {});
                this.$modal.modal('openModal');
            }.bind(this)).fail(function () {
                this._showError($.mage.__('Validation request failed. Please try again.'));
            }.bind(this)).always(function () {
                $('body').trigger('processStop');
            });
        },

        _renderValidationSummary: function (summary) {
            var successHtml = '',
                failureHtml = '';

            this.$summaryText.text(
                $.mage.__('%1 product(s) passed validation. %2 product(s) failed validation.')
                    .replace('%1', summary.success_count || 0)
                    .replace('%2', summary.failure_count || 0)
            );

            $.each(summary.successes || [], function (index, item) {
                successHtml += '<tr>' +
                    '<td>' + this._escapeHtml(item.row) + '</td>' +
                    '<td>' + this._escapeHtml(item.sku) + '</td>' +
                    '<td>' + this._escapeHtml(item.name) + '</td>' +
                    '<td>' + this._escapeHtml(item.qty) + '</td>' +
                    '</tr>';
            }.bind(this));

            $.each(summary.failures || [], function (index, item) {
                failureHtml += '<tr>' +
                    '<td>' + this._escapeHtml(item.row) + '</td>' +
                    '<td>' + this._escapeHtml(item.sku) + '</td>' +
                    '<td>' + this._escapeHtml(item.reason) + '</td>' +
                    '</tr>';
            }.bind(this));

            this.$successTableBody.html(successHtml);
            this.$failureTableBody.html(failureHtml);
            this.$successSection.toggle((summary.successes || []).length > 0);
            this.$failureSection.toggle((summary.failures || []).length > 0);
        },

        _proceed: function () {
            var data;

            if (!this.validationToken || !this.options.proceedUrl) {
                this._showError($.mage.__('Validation session has expired. Please upload the file again.'));

                return;
            }

            data = {
                form_key: this.$form.find('[name="form_key"]').val(),
                token: this.validationToken
            };

            $('body').trigger('processStart');

            $.ajax({
                url: this.options.proceedUrl,
                type: 'POST',
                data: data
            }).done(function (response) {
                if (!response || !response.success) {
                    this._showError(response && response.message ? response.message : $.mage.__('Processing failed.'));

                    return;
                }

                this._renderProcessSummary(response.summary || {});
                this._cancelValidation();
            }.bind(this)).fail(function () {
                this._showError($.mage.__('Processing request failed. Please try again.'));
            }.bind(this)).always(function () {
                $('body').trigger('processStop');
            });
        },

        _cancelValidation: function () {
            this.validationToken = '';
            this.$modal.modal('closeModal');
        },

        _resetSelectedFile: function () {
            this.$input.val('');
            this._updateSelectedFile('');
        },

        _showError: function (message) {
            window.alert(message);
        },

        _renderProcessSummary: function (summary) {
            var html = '';

            if (summary.success) {
                html += '<details class="order-upload__status order-upload__status--success">' +
                    '<summary class="order-upload__status-title">' + this._escapeHtml(summary.success.title) + '</summary>' +
                    this._buildSuccessTable(summary.success.items || []) +
                    '</details>';
            }

            if (summary.error) {
                html += '<details class="order-upload__status order-upload__status--error">' +
                    '<summary class="order-upload__status-title">' + this._escapeHtml(summary.error.title) + '</summary>' +
                    '<div class="order-upload__status-body">' + this._escapeHtml(summary.error.message || '') + '</div>' +
                    '</details>';
            }

            this.$processSummary.html(html);
        },

        _buildSuccessTable: function (items) {
            var rows = '';

            $.each(items, function (index, item) {
                rows += '<tr>' +
                    '<td>' + this._escapeHtml(item.row) + '</td>' +
                    '<td>' + this._escapeHtml(item.sku) + '</td>' +
                    '<td>' + this._escapeHtml(item.name) + '</td>' +
                    '<td>' + this._escapeHtml(item.qty) + '</td>' +
                    '</tr>';
            }.bind(this));

            if (!rows) {
                return '';
            }

            return '<div class="table-wrapper"><table class="data table"><thead><tr>' +
                '<th>' + this._escapeHtml($.mage.__('Row')) + '</th>' +
                '<th>' + this._escapeHtml($.mage.__('SKU')) + '</th>' +
                '<th>' + this._escapeHtml($.mage.__('Product')) + '</th>' +
                '<th>' + this._escapeHtml($.mage.__('Qty')) + '</th>' +
                '</tr></thead><tbody>' + rows + '</tbody></table></div>';
        },

        _escapeHtml: function (value) {
            return $('<div/>').text(value || '').html();
        },

        _updateSelectedFile: function (fileName) {
            if (!this.$fileName.length) {
                return;
            }

            if (!fileName) {
                this.$dropzone.removeClass(this.options.hasFileClass);
                this.$fileName.text($.mage.__('No file selected'));

                return;
            }

            this.$dropzone.addClass(this.options.hasFileClass);
            this.$fileName.text(fileName);
        }
    });

    return $.oporteo.orderUpload;
});
