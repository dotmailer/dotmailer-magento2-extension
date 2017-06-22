var config = {
    'paths': {
        'mailcheck': 'Dotdigitalgroup_Email/js/node_modules/mailcheck/mailcheck',
        'trackingCode': 'Dotdigitalgroup_Email/js/trackingCode'
    },
    'shim': {
        'mailcheck': {
            exports: 'Mailcheck'
        },
        'trackingCode': {
            exports: '_dmTrack'
        }
    }
};
