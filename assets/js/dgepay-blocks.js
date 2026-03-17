( function() {
    const settings = window.wc && window.wc.wcSettings
        ? window.wc.wcSettings.getSetting( 'dgepay_data', {} )
        : {};

    const label = settings.title || 'DgePay';
    const description = settings.description || 'Pay securely using DgePay.';

    const Content = () => window.wp.element.createElement(
        'div',
        { className: 'dgepay-blocks-description' },
        description
    );

    const DgePayBlock = {
        name: 'dgepay',
        label: window.wp.element.createElement( 'span', null, label ),
        content: window.wp.element.createElement( Content ),
        edit: window.wp.element.createElement( Content ),
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: [ 'products' ],
        },
    };

    if ( window.wc && window.wc.wcBlocksRegistry ) {
        window.wc.wcBlocksRegistry.registerPaymentMethod( DgePayBlock );
    }
} )();
