/**
 * Admin UI - Theme Switcher
 *
 * Requires: namespace.js, config.js, helpers.js
 */
(function (window, document) {
    'use strict';

    var PREFIX_CONFIG = window.__PREFIX_CONFIG__;
    if (!PREFIX_CONFIG) throw new Error('ThemeSwitcher requires namespace.js (__PREFIX_CONFIG__).');

    var SA = window[PREFIX_CONFIG.namespace];
    if (!SA) throw new Error('ThemeSwitcher requires global namespace.');

    var Helpers = SA.Helpers;
    var Config = SA.Config;
    if (!Helpers || !Config) throw new Error('ThemeSwitcher requires helpers.js and config.js.');

    function storageGet(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    function storageSet(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch (e) {
        }
    }

    var ThemeSwitcher = {
        wrapper: null,
        storageKey: null,

        init: function () {
            this.storageKey = SA.storageKey('theme');
            this.wrapper = document.querySelector(SA.selector('wrap'));
            this.bindEvents();
            this.applyStoredTheme();
        },

        bindEvents: function () {
            var self = this;

            document.addEventListener('click', function (e) {
                var target = Helpers.getElement(e.target);
                if (!target) return;

                if (!target.closest(SA.dataSelector('theme-toggle'))) return;

                e.preventDefault();
                self.toggle();
            });
        },

        getTheme: function () {
            var stored = storageGet(this.storageKey);
            if (stored === 'light' || stored === 'dark') return stored;

            if (this.wrapper) {
                var domTheme = this.wrapper.getAttribute(SA.dataAttr('theme'));
                if (domTheme === 'light' || domTheme === 'dark') return domTheme;
            }

            return 'light';
        },

        setTheme: function (theme) {
            if (theme !== 'light' && theme !== 'dark') theme = 'light';

            if (this.wrapper) this.wrapper.setAttribute(SA.dataAttr('theme'), theme);
            storageSet(this.storageKey, theme);

            this.saveToServer(theme);
            this.updateToggleIcons(theme);

            SA.dispatch('themeChanged', {theme: theme}, document);
        },

        toggle: function () {
            this.setTheme(this.getTheme() === 'light' ? 'dark' : 'light');
        },

        applyStoredTheme: function () {
            var stored = storageGet(this.storageKey);
            var theme = (stored === 'light' || stored === 'dark') ? stored : this.getTheme();

            if (stored === 'light' || stored === 'dark') {
                if (this.wrapper) this.wrapper.setAttribute(SA.dataAttr('theme'), stored);
            }

            this.updateToggleIcons(theme);
        },

        updateToggleIcons: function (theme) {
            var toggles = document.querySelectorAll(SA.dataSelector('theme-toggle'));

            for (var i = 0; i < toggles.length; i++) {
                var toggle = toggles[i];
                var lightIcon = toggle.querySelector(SA.selector('theme-icon-light'));
                var darkIcon = toggle.querySelector(SA.selector('theme-icon-dark'));
                if (!lightIcon || !darkIcon) continue;

                if (theme === 'dark') {
                    lightIcon.style.display = 'inline-block';
                    darkIcon.style.display = 'none';
                } else {
                    lightIcon.style.display = 'none';
                    darkIcon.style.display = 'inline-block';
                }
            }
        },

        saveToServer: function (theme) {
            if (typeof window.fetch !== 'function') return;

            var ajaxUrl = Config.getAjaxUrl();
            var nonce = Config.getNonce();
            if (!ajaxUrl || !nonce) return;

            var formData = new FormData();
            formData.append('action', PREFIX_CONFIG.prefix + '_switch_theme');
            formData.append('security', nonce);
            formData.append('theme', theme);

            window.fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            }).catch(function () {
            });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            ThemeSwitcher.init();
        });
    } else {
        ThemeSwitcher.init();
    }

    SA.ThemeSwitcher = ThemeSwitcher;

})(window, document);
