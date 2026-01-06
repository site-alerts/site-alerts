/**
 * Prefix Configuration (Single Source of Truth)
 *
 * This file defines all prefix values used throughout the plugin.
 * The build process generates corresponding files for SCSS, JS, and PHP.
 *
 * After changing values here, run: npm run build
 *
 * Generated files (gitignored):
 *   - assets-src/scss/_prefix.scss
 *   - assets-src/scripts/core/namespace.js
 *   - src/Config/PrefixConfig.php
 */
module.exports = {
    // Core prefix (used for CSS classes, data attrs, handles)
    prefix: 'sa',

    // Uppercase constant prefix (used for PHP constants)
    constantPrefix: 'SA',

    // JavaScript global namespace (e.g., window.SA)
    namespace: 'SA',

    // Localized config object name (e.g., window.saConfig)
    configObject: 'saConfig',

    // Custom events prefix (e.g., 'sa:ready')
    eventPrefix: 'sa',

    // localStorage prefix (e.g., 'sa-theme')
    storagePrefix: 'sa',

    // Derived values (computed automatically)
    get cssPrefix() { return this.prefix + '-'; },
    get dataAttr() { return 'data-' + this.prefix; },
    get cssVar() { return '--' + this.prefix; },
    get phpPrefix() { return this.prefix + '_'; },
    get handlePrefix() { return this.prefix + '-'; },
};
