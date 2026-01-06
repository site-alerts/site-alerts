/**
 * Admin UI - Main Entry File
 *
 * Requires: namespace.js
 */
(function (window, document) {
    'use strict';

    var PREFIX_CONFIG = window.__PREFIX_CONFIG__;
    if (!PREFIX_CONFIG) throw new Error('App requires namespace.js (__PREFIX_CONFIG__).');

    var SA = window[PREFIX_CONFIG.namespace];
    if (!SA) throw new Error('App requires global namespace.');

    var App = {
        version: '1.0.0',
        initialized: false,

        init: function () {
            if (this.initialized) return;
            this.bindGlobalEvents();
            this.initialized = true;
            SA.dispatch('ready', {version: this.version}, document);
        },

        bindGlobalEvents: function () {
            var self = this;
            document.addEventListener(PREFIX_CONFIG.eventPrefix + ':contentLoaded', function (e) {
                self.initializeContainer(e.detail && e.detail.container);
            });
        },

        initializeContainer: function (container) {
            container = container || document;
            if (SA.AdminNotices && typeof SA.AdminNotices.init === 'function') SA.AdminNotices.init(container);
        },

        contentLoaded: function (container) {
            SA.dispatch('contentLoaded', {container: container}, document);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            App.init();
        });
    } else {
        App.init();
    }

    SA.App = App;

})(window, document);
