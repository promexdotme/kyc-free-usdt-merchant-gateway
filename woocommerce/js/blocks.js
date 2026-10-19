(function() {
    if (!window.wc || !window.wc.wcBlocksRegistry || !window.wc.wcSettings) {
        return;
    }

    var settings = window.wc.wcSettings.getSetting('kyc_free_crypto_data', {});
    var defaultLabel = 'Crypto Payment (USDT / USDC)';
    var label = window.wp.htmlEntities.decodeEntities(settings.title || defaultLabel);

    var Content = function() {
        return window.wp.element.createElement(
            'div',
            { 
                className: 'wc-block-components-payment-method-description',
                style: { marginTop: '8px', fontSize: '13px', color: '#4b5563', lineHeight: '1.5' }
            },
            window.wp.htmlEntities.decodeEntities(settings.description || '')
        );
    };

    var BlockGateway = {
        name: 'kyc_free_crypto',
        label: label,
        content: window.wp.element.createElement(Content, null),
        edit: window.wp.element.createElement(Content, null),
        canMakePayment: function() {
            return true;
        },
        ariaLabel: label,
        supports: {
            features: settings.supports || ['products']
        }
    };

    window.wc.wcBlocksRegistry.registerPaymentMethod(BlockGateway);
})();
