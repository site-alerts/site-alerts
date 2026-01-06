/**
 * Admin UI - Configuration
 *
 * Requires: namespace.js
 */
(function (window, document) {
    'use strict';

    var PREFIX_CONFIG = window.__PREFIX_CONFIG__;
    if (!PREFIX_CONFIG) throw new Error('Config requires namespace.js (__PREFIX_CONFIG__).');

    var SA = window[PREFIX_CONFIG.namespace];
    if (!SA) throw new Error('Config requires global namespace.');

    var configObject = PREFIX_CONFIG.configObject;
    var cssVarPrefix = PREFIX_CONFIG.cssVar;

    var Config = {
        getCssVar: function (name) {
            return getComputedStyle(document.documentElement)
                .getPropertyValue(cssVarPrefix + '-' + name)
                .trim();
        },

        getColor: function (name) {
            var cfg = window[configObject] || {};
            return this.getCssVar(name) || (cfg.colors && cfg.colors[name]) || '';
        },

        getColors: function () {
            return {
                primary: this.getColor('primary'),
                secondary: this.getColor('secondary'),
                success: this.getColor('success'),
                info: this.getColor('info'),
                warning: this.getColor('warning'),
                danger: this.getColor('danger'),
                light: this.getColor('light'),
                dark: this.getColor('dark')
            };
        },

        getTheme: function () {
            var wrap = document.querySelector(SA.selector('wrap'));
            return (wrap && wrap.getAttribute(SA.dataAttr('theme'))) ||
                document.documentElement.getAttribute(SA.dataAttr('theme')) ||
                'light';
        },

        isDarkMode: function () {
            return this.getTheme() === 'dark';
        },

        getAjaxUrl: function () {
            var cfg = window[configObject] || {};
            return cfg.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php';
        },

        getNonce: function () {
            var cfg = window[configObject] || {};
            return cfg.nonce || '';
        },

        getPrefixConfig: function () {
            return PREFIX_CONFIG;
        }
    };

    SA.Config = Config;

})(window, document);
