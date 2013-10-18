require.config({
    paths: {
        jquery: "/themes/default/assets/js/jquery",
        underscore: 'libs/underscore',
        backbone: 'libs/backbone',
        text: 'libs/text',
        templates: '../templates'
    },
    shim: {
        underscore: {
            exports: '_'
        },
        backbone: {
            deps: ["underscore"],
            exports: "Backbone"
        }
    }
});

require([
    'MyDNS'
], function (MyDNS) {
    MyDNS.initialize();
});
