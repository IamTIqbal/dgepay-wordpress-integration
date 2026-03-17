( function() {
    const settings = window.wc && window.wc.wcSettings
        ? window.wc.wcSettings.getSetting( 'bkash_data', {} )
        : {};

    const label = settings.title || 'bKash';
    const description = settings.description || 'Pay securely using bKash.';

    const Content = () => window.wp.element.createElement(
        'div',
        { className: 'bkash-blocks-description' },
        description
    );

    const BkashBlock = {
        name: 'bkash',
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
        window.wc.wcBlocksRegistry.registerPaymentMethod( BkashBlock );
    }
} )();
