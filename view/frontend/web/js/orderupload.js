require([
    "jquery",
    "accordion",
    "Magento_Customer/js/customer-data"
], function ($j, accordion, customerData) {

    var checkout_btn    = $j('#checkout_btn');
    var emptycart       = $j('#emptycart');
    var sections = ['cart'];
    var myform  = $j('#myform');
    var url     = myform.attr('action');
    var loader  = $j('#loader');
    var file;
    var dropbox = $j('#dropbox');

    $j('html').on('dragover', function (e) {
        e.preventDefault();
        e.stopPropagation();
    });

    $j('html').on('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
    });

    dropbox.on('dragenter', function (e) {
        e.stopPropagation();
        e.preventDefault();
        dropbox.addClass('dragover');
    });

    dropbox.on('dragleave', function (e) {
        e.stopPropagation();
        e.preventDefault();
        dropbox.removeClass('dragover');
    });

    dropbox.on('drop', function (e) {
        e.stopPropagation();
        e.preventDefault();
        dropbox.removeClass('dragover');

        var file = e.originalEvent.dataTransfer.files;
        var fd = new FormData();

        fd.append('file', file[0]);

        clearMessages();

        showLoading();

        sendAjax(fd);
    });

    function minicartRefresh()
    {
        customerData.invalidate(sections);
        customerData.reload(sections, true);
    }

    function prepareUpload(event)
    {
        file = event.target.files[0];
    }

    function clearMessages()
    {
        $j('#notifications').html('');
    }

    function showMessages(data)
    {
        var html = '';
        var msgContainer = $j('#notifications');

        $j.each(data, function (index, value) {
            switch (index) {
                case 'csv':
                    html += getFileMsg(value);
                    break;
                case 'product':
                    html += getProductMsg(value);
                    break;
            }
        });

        msgContainer.append(html);
        msgContainer.accordion({
            openedState:    "active",
            collapsible:    true,
            active:         false,
            multipleCollapsible: true
        });
    }

    function getFileMsg(messages)
    {
        var result = '';
        if (messages.length != 0) {
            $j.each(messages, function (status, value) {
                result += '<div class="collapsibleTab orderupload-msg'+' '+ status +'" data-role="collapsible"><div data-role="trigger"><span>CSV Status</span></div></div><div class="collapsibleContent msg-content'+' '+ status +'" data-role="content">'+ value +'</div>';
            });
        }

        return result;
    }

    function getProductMsg(messages)
    {
        var result = '';
        if (messages.length != 0) {
            $j.each(messages, function (status, value) {
                switch (status) {
                    case 'ok':
                        result += '<div class="collapsibleTab orderupload-msg'+' '+ status +'" data-role="collapsible"><div data-role="trigger"><span>Succesfully added '+ value.length +' SKUs to basket.</span></div></div><div class="collapsibleContent msg-content'+' '+ status +'" data-role="content"><ul>';
                        $j.each(value, function (msgindex, msgvalue) {
                            result += '<li>'+ msgvalue +'</li>';
                        });
                        result += '</ul></div>';
                        break;
                    case 'fail':
                        result += '<div class="collapsibleTab orderupload-msg'+' '+ status +'" data-role="collapsible"><div data-role="trigger"><span>Unable to add '+ messages.qty +' SKUs to basket.</span></div></div><div class="collapsibleContent msg-content'+' '+ status +'" data-role="content"><ul>';
                        $j.each(value, function (msgindex, msgvalue) {
                            result += '<li>'+ msgvalue +'</li>';
                        });
                        result += '</ul></div>';
                        break;
                }
            });
        }

        return result;
    }

    function showCheckoutBtn(qty)
    {
        if (qty > 0) {
            checkout_btn.removeClass('btn-no-display');
            emptycart.removeClass('btn-no-display');
        }
    }

    function showLoading()
    {
        loader.fadeIn('fast');
        $j('#dropbox').addClass('loading');
    }

    function hideLoading()
    {
        loader.fadeOut('fast');
        $j('#dropbox').removeClass('loading');
    }

    function sendAjax(data)
    {
        $j.ajax({
            url: url,
            type: 'POST',
            data: data,
            cache: false,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function (data, textStatus, jqXHR) {
            
                hideLoading();
                if (!$j.isEmptyObject(data['messages'])) {
                    showMessages(data['messages']);
                }
                minicartRefresh();
                showCheckoutBtn(data['cart_items_qty']);
            },
            error: function (jqXHR, textStatus, errorThrown) {
            
                alert('ERROR: ' + textStatus);
                console.log('ERRORS: ' + textStatus);
                hideLoading();
            }
        });
    }

    function uploadFiles(event)
    {
        event.stopPropagation();
        event.preventDefault();

        if (!file) {
            alert('You must choose file first!');
            return false;
        }

        clearMessages();

        showLoading();

        var data = new FormData();
        data.append('file',file);

        sendAjax(data);

        $j('#file').prop('value', '');
    }

    $j(document).ready(function () {
        myform.on('change', 'input[type=file]', function (e) {
            prepareUpload(e);
            myform.submit();
        });

        myform.on('submit',uploadFiles);

        myform.on('click tap', '#dropbox', function (e) {
            $j('#file').click();
        });
    });

});
