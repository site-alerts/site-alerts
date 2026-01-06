/**
 * Admin UI - Helper Functions
 *
 * Requires: namespace.js
 */
(function (window, document) {
    'use strict';

    var PREFIX_CONFIG = window.__PREFIX_CONFIG__;
    if (!PREFIX_CONFIG) throw new Error('Helpers requires namespace.js (__PREFIX_CONFIG__).');

    var SA = window[PREFIX_CONFIG.namespace];
    if (!SA) throw new Error('Helpers requires global namespace.');

    var cssPrefix = PREFIX_CONFIG.cssPrefix;

    var Helpers = {
        resolveEl: function (el) {
            if (!el) return null;
            return (typeof el === 'string') ? document.querySelector(el) : el;
        },

        isPlainObject: function (obj) {
            return Object.prototype.toString.call(obj) === '[object Object]';
        },

        getElement: function (t) {
            if (!t) return null;
            if (t.nodeType === 3) return t.parentElement;
            if (typeof t.closest !== 'function') return null;
            return t;
        },

        show: function (el) {
            el = Helpers.resolveEl(el);
            if (!el) return;
            el.classList.remove(cssPrefix + 'd-none');
            el.classList.add(cssPrefix + 'd-block');
        },

        hide: function (el) {
            el = Helpers.resolveEl(el);
            if (!el) return;
            el.classList.remove(cssPrefix + 'd-block');
            el.classList.add(cssPrefix + 'd-none');
        },

        toggle: function (el) {
            el = Helpers.resolveEl(el);
            if (!el) return;

            var hidden = cssPrefix + 'd-none';
            var block = cssPrefix + 'd-block';

            if (el.classList.contains(hidden)) {
                el.classList.remove(hidden);
                el.classList.add(block);
            } else {
                el.classList.remove(block);
                el.classList.add(hidden);
            }
        },

        addClass: function (el, cls) {
            el = Helpers.resolveEl(el);
            if (el && cls) el.classList.add(cls);
        },

        removeClass: function (el, cls) {
            el = Helpers.resolveEl(el);
            if (el && cls) el.classList.remove(cls);
        },

        hasClass: function (el, cls) {
            el = Helpers.resolveEl(el);
            return !!(el && cls && el.classList.contains(cls));
        },

        siblings: function (el) {
            el = Helpers.resolveEl(el);
            if (!el || !el.parentNode) return [];
            return Array.prototype.filter.call(el.parentNode.children, function (c) {
                return c !== el;
            });
        },

        debounce: function (fn, wait, immediate) {
            var t;
            return function () {
                var ctx = this, args = arguments;

                var later = function () {
                    t = null;
                    if (!immediate) fn.apply(ctx, args);
                };

                var callNow = immediate && !t;
                clearTimeout(t);
                t = setTimeout(later, wait);
                if (callNow) fn.apply(ctx, args);
            };
        },

        throttle: function (fn, limit) {
            var inThrottle = false;
            return function () {
                if (inThrottle) return;
                fn.apply(this, arguments);
                inThrottle = true;
                setTimeout(function () {
                    inThrottle = false;
                }, limit);
            };
        },

        formatNumber: function (num) {
            if (num === null || num === undefined) return '';
            return String(num).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },

        uniqueId: function (prefix) {
            prefix = prefix || cssPrefix;
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return prefix + window.crypto.randomUUID();
            }
            return prefix + Date.now().toString(36) + Math.random().toString(36).slice(2);
        },

        escapeHtml: function (text) {
            var div = document.createElement('div');
            div.textContent = (text === null || text === undefined) ? '' : String(text);
            return div.innerHTML;
        },

        merge: function (target, source) {
            target = target || {};
            if (!Helpers.isPlainObject(source)) return target;

            for (var k in source) {
                if (!Object.prototype.hasOwnProperty.call(source, k)) continue;

                var v = source[k];
                if (Helpers.isPlainObject(v)) {
                    target[k] = target[k] || {};
                    Helpers.merge(target[k], v);
                } else {
                    target[k] = v;
                }
            }
            return target;
        },

        prefixClass: function (name) {
            return cssPrefix + name;
        },

        getCssPrefix: function () {
            return cssPrefix;
        }
    };

    SA.Helpers = Helpers;

})(window, document);
