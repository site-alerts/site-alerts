/**
 * ============================================
 * AUTO-GENERATED FILE - DO NOT EDIT DIRECTLY
 * ============================================
 * Generated from: config/prefix.config.cjs
 * Regenerate with: npm run build
 * ============================================
 */

(function(window) {
    'use strict';

    /**
     * Prefix Configuration Object
     *
     * Provides centralized access to all prefix values.
     * Available globally as window.__PREFIX_CONFIG__
     */
    window.__PREFIX_CONFIG__ = Object.freeze({
        // Core prefix (e.g., 'sa')
        prefix: 'sa',

        // JavaScript namespace (e.g., 'SA')
        namespace: 'SA',

        // Localized config object name (e.g., 'saConfig')
        configObject: 'saConfig',

        // Event prefix (e.g., 'sa')
        eventPrefix: 'sa',

        // Storage prefix (e.g., 'sa')
        storagePrefix: 'sa',

        // CSS class prefix (e.g., 'sa-')
        cssPrefix: 'sa-',

        // Data attribute prefix (e.g., 'data-sa')
        dataAttr: 'data-sa',

        // CSS variable prefix (e.g., '--sa')
        cssVar: '--sa'
    });

    /**
     * Initialize Global Namespace
     *
     * Creates the plugin's global namespace object.
     * All modules attach to this namespace.
     */
    var NS = 'SA';
    window[NS] = window[NS] || {};

    // Attach config reference to namespace
    window[NS].__config = window.__PREFIX_CONFIG__;

    /**
     * Utility: Build prefixed CSS class selector
     * @param {string} name - Class name without prefix
     * @returns {string} Selector (e.g., '.sa-btn')
     */
    window[NS].selector = function(name) {
        return '.' + window.__PREFIX_CONFIG__.cssPrefix + name;
    };

    /**
     * Utility: Build data attribute selector
     * @param {string} name - Attribute name without prefix
     * @param {string} [value] - Optional attribute value
     * @returns {string} Selector (e.g., '[data-sa-toggle="modal"]')
     */
    window[NS].dataSelector = function(name, value) {
        var attr = window.__PREFIX_CONFIG__.dataAttr + '-' + name;
        if (value !== undefined) {
            return '[' + attr + '="' + value + '"]';
        }
        return '[' + attr + ']';
    };
    
    /**
     * Utility: Build data attribute name (NOT selector)
     * @param {string} name - Attribute name without prefix
     * @returns {string} Attribute name (e.g., 'data-sa-theme')
     */
    window[NS].dataAttr = function(name) {
        return window.__PREFIX_CONFIG__.dataAttr + '-' + name;
    };

    /**
     * Utility: Dispatch prefixed custom event
     * @param {string} name - Event name without prefix
     * @param {Object} [detail] - Event detail data
     * @param {Element} [target] - Target element (default: document)
     */
    window[NS].dispatch = function(name, detail, target) {
        var eventName = window.__PREFIX_CONFIG__.eventPrefix + ':' + name;
        var event = new CustomEvent(eventName, {
            detail: detail || {},
            bubbles: true,
            cancelable: true
        });
        (target || document).dispatchEvent(event);
    };

    /**
     * Utility: Get prefixed localStorage key
     * @param {string} key - Key name without prefix
     * @returns {string} Prefixed key
     */
    window[NS].storageKey = function(key) {
        return window.__PREFIX_CONFIG__.storagePrefix + '-' + key;
    };

})(window);
