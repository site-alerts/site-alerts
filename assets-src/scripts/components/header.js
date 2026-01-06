/**
 * Admin UI - Header Component
 *
 * Requires: namespace.js, helpers.js
 */
(function (window, document) {
    'use strict';

    var PREFIX_CONFIG = window.__PREFIX_CONFIG__;
    if (!PREFIX_CONFIG) throw new Error('Header requires namespace.js (__PREFIX_CONFIG__).');

    var SA = window[PREFIX_CONFIG.namespace];
    if (!SA) throw new Error('Header requires global namespace.');

    var Helpers = SA.Helpers;
    if (!Helpers) throw new Error('Header requires helpers.js.');

    var Header = {
        toggleBtn: null,
        nav: null,
        wrapper: null,
        isOpen: false,

        init: function () {
            this.toggleBtn = document.querySelector(SA.selector('header-toggle'));
            this.nav = document.getElementById(PREFIX_CONFIG.cssPrefix + 'header-nav');
            this.wrapper = document.querySelector(SA.selector('header-nav-wrapper'));
            if (!this.toggleBtn || !this.nav) return;
            this.bindEvents();
        },

        bindEvents: function () {
            var self = this;

            this.toggleBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                self.toggle();
            });

            document.addEventListener('click', function (e) {
                if (!self.isOpen) return;

                var target = Helpers.getElement(e.target) || e.target;
                var inside =
                    (self.wrapper && self.wrapper.contains(target)) ||
                    (self.toggleBtn && self.toggleBtn.contains(target));

                if (!inside) self.close();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && self.isOpen) {
                    self.close();
                    self.toggleBtn.focus();
                }
            });

            this.nav.addEventListener('click', function (e) {
                var target = Helpers.getElement(e.target) || e.target;
                if (target && target.closest(SA.selector('header-nav-link'))) self.close();
            });

            var onResize = Helpers.debounce(function () {
                if (window.innerWidth > 991.98 && self.isOpen) self.close();
            }, 100);

            window.addEventListener('resize', onResize);
        },

        toggle: function () {
            this.isOpen ? this.close() : this.open();
        },

        open: function () {
            this.toggleBtn.setAttribute('aria-expanded', 'true');
            this.nav.classList.add(SA.selector('show').slice(1));
            this.isOpen = true;

            var first = this.nav.querySelector('a, button, [tabindex]:not([tabindex="-1"])');
            if (first) first.focus();

            SA.dispatch('header:opened', {nav: this.nav}, document);
        },

        close: function () {
            this.toggleBtn.setAttribute('aria-expanded', 'false');
            this.nav.classList.remove(SA.selector('show').slice(1));
            this.isOpen = false;

            SA.dispatch('header:closed', {nav: this.nav}, document);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            Header.init();
        });
    } else {
        Header.init();
    }

    SA.Header = Header;

})(window, document);
