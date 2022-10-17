require([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'prototype',
    'loader'
], function ($, modal) {

    /**
     * @param{String} modalSelector - modal css selector.
     * @param{Object} options - modal options.
     */
    function initModal(modalSelector, options) {
        var $resultModal = $(modalSelector);

        if (!$resultModal.length) return;

        var popup = modal(options, $resultModal);
        $resultModal.loader({texts: ''});
    }

    var successHandlers = {
        /**
         * @param{Object[]} result - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         */
        error: function (result, $container) {

            if (Array.isArray(result)) {

                var lisHtml = result.map(function (err) {
                    return '<li class="signalise-result_error-item"><strong>' + err.date + '</strong><p>' + err.msg + '</p></li>';
                }).join('');

                $container.find('.result').empty().append('<ul>' + lisHtml + '</ul>');
            } else {

                $container.find('.result').empty().append(result);
            }
        }
    }

    // init debug modal
    $(() => {
        // init error modal
        initModal('#signalise-result_error-modal', {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: $.mage.__('Last 100 error log records'),
            buttons: [
                {
                    text: $.mage.__('download as .txt file'),
                    class: 'signalise-button__download signalise-icon__download-alt',
                    click: function () {

                        var elText = document.getElementById('signalise-result_error').innerText || '';
                        var link = document.createElement('a');

                        link.setAttribute('download', 'error-log.txt');
                        link.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(elText));
                        link.click();
                    },
                },
                {
                    text: $.mage.__('ok'),
                    class: '',
                    click: function () {
                        this.closeModal();
                    },
                }
            ]
        });
    });

    /**
     * Ajax request event
     */
    $(document).on('click', '[id^=signalise-button]', function () {
        var actionName = this.id.split('_')[1];
        var $modal = $('#signalise-result_' + actionName + '-modal');
        var $result = $('#signalise-result_' + actionName);

        if (actionName === 'version') {
            $(this).fadeOut(300).addClass('signalise-disabled');
            $modal = $('.signalise-result_' + actionName + '-wrapper');
            $modal.loader('show');
        } else {
            $modal.modal('openModal').loader('show');
        }

        $result.hide();

        new Ajax.Request($modal.data('signalise-endpoind-url'), {
            loaderArea: false,
            asynchronous: true,
            onSuccess: function (response) {

                if (response.status > 200) {
                    var result = response.statusText;
                } else {
                    successHandlers[actionName](response.responseJSON.result || response.responseJSON, $result);

                    $result.fadeIn();
                    $modal.loader('hide');
                }
            }
        });
    });
});
