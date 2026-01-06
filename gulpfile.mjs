/**
 * Gulp Build Configuration
 *
 * Build pipeline with automatic prefix generation from config/prefix.config.cjs
 *
 * Commands:
 *   npm run build    - Full production build
 *   npm run watch    - Development mode with file watching
 *   gulp prefix      - Generate prefix files only
 *
 * @package SiteAlerts
 * @version 2.0.0
 */

import gulp from 'gulp';
import cleanCSS from 'gulp-clean-css';
import terser from 'gulp-terser';
import rename from 'gulp-rename';
import concat from 'gulp-concat';
import {deleteAsync} from 'del';
import gulpSass from 'gulp-sass';
import * as dartSass from 'sass';
import fs from 'fs';
import path from 'path';
import {fileURLToPath} from 'url';
import {createRequire} from 'module';

// ES module dirname equivalent
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Initialize Sass compiler
const sass = gulpSass(dartSass);

// Create require function for CommonJS module loading in ESM
const require = createRequire(import.meta.url);

/**
 * Load prefix configuration
 * Using createRequire for CommonJS compatibility in ESM
 */
function loadPrefixConfig() {
    try {
        const configPath = path.resolve(__dirname, 'config/prefix.config.cjs');

        // Clear require cache to get fresh config
        delete require.cache[configPath];

        // Use require for CommonJS module
        return require(configPath);
    } catch (error) {
        console.error('Error loading prefix config:', error);
        // Return defaults if config not found
        return {
            prefix: 'sa',
            constantPrefix: 'SA',
            namespace: 'SA',
            configObject: 'saConfig',
            eventPrefix: 'sa',
            storagePrefix: 'sa',
            cssPrefix: 'sa-',
            dataAttr: 'data-sa',
            cssVar: '--sa',
            scssVar: '$sa',
            phpPrefix: 'sa_',
            handlePrefix: 'sa-',
        };
    }
}

/**
 * Build paths configuration
 */
const paths = {
    config: {
        src: 'config/prefix.config.cjs',
        scssOut: 'assets-src/scss/_prefix.scss',
        jsOut: 'assets-src/scripts/core/namespace.js',
        phpOut: 'src/Config/PrefixConfig.php',
    },
    scss: {
        src: 'assets-src/scss/admin.scss',
        watch: 'assets-src/scss/**/*.scss',
        dest: 'assets/css/',
    },
    js: {
        // Order matters: namespace first, then core, components, vendors, main
        src: [
            'assets-src/scripts/core/namespace.js', // Generated - must be first
            'assets-src/scripts/core/config.js',
            'assets-src/scripts/core/helpers.js',
            'assets-src/scripts/components/theme-switcher.js',
            'assets-src/scripts/components/alert-card.js',
            'assets-src/scripts/components/promo-banner.js',
            'assets-src/scripts/components/admin-notices.js',
            'assets-src/scripts/admin.js',
        ],
        watch: 'assets-src/scripts/**/*.js',
        dest: 'assets/js/',
    },
};

/**
 * Generate prefix files from configuration
 *
 * Creates:
 * - SCSS prefix variables (_prefix.scss)
 * - JavaScript namespace configuration (namespace.js)
 * - PHP prefix constants (PrefixConfig.php)
 */
export async function generatePrefixFiles(cb) {
    const p = await loadPrefixConfig();

    console.log(`\n  Generating prefix files with prefix: "${p.prefix}"\n`);

    // Compute derived values if not present (for plain object)
    const cssPrefix = p.cssPrefix || p.prefix + '-';
    const dataAttr = p.dataAttr || 'data-' + p.prefix;
    const cssVar = p.cssVar || '--' + p.prefix;
    const phpPrefix = p.phpPrefix || p.prefix + '_';
    const handlePrefix = p.handlePrefix || p.prefix + '-';

    // =========================================
    // 1. Generate SCSS prefix file
    // =========================================
    const scssContent = `// ============================================
// AUTO-GENERATED FILE - DO NOT EDIT DIRECTLY
// ============================================
// Generated from: config/prefix.config.cjs
// Regenerate with: npm run build
// ============================================

// Core prefix (e.g., 'sa')
$prefix: '${p.prefix}' !default;

// CSS custom property prefix (e.g., '--sa')
$css-var-prefix: '${cssVar}' !default;

// Data attribute prefix (e.g., 'data-sa')
$data-attr-prefix: '${dataAttr}' !default;

// Event prefix for JavaScript events (e.g., 'sa')
$event-prefix: '${p.eventPrefix}' !default;

// Storage prefix for localStorage (e.g., 'sa')
$storage-prefix: '${p.storagePrefix}' !default;

// ============================================
// Helper function to build prefixed class name
// Usage: #{prefix('btn')} outputs '.sa-btn'
// ============================================
@function prefix($name) {
    @return $prefix + '-' + $name;
}

// ============================================
// Helper function to build CSS variable reference
// Usage: #{css-var('primary')} outputs 'var(--sa-primary)'
// ============================================
@function css-var($name) {
    @return var(#{$css-var-prefix}-#{$name});
}

// ============================================
// Helper function to build data attribute selector
// Usage: #{data-attr('theme', 'dark')} outputs '[data-sa-theme="dark"]'
// ============================================
@function data-attr($name, $value: null) {
    @if $value {
        @return '[#{$data-attr-prefix}-#{$name}="#{$value}"]';
    }
    @return '[#{$data-attr-prefix}-#{$name}]';
}
`;

    fs.writeFileSync(paths.config.scssOut, scssContent);
    console.log(`  [OK] Generated: ${paths.config.scssOut}`);

    // =========================================
    // 2. Generate JavaScript namespace file
    // =========================================
    const jsContent = `/**
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
        prefix: '${p.prefix}',

        // JavaScript namespace (e.g., 'SA')
        namespace: '${p.namespace}',

        // Localized config object name (e.g., 'saConfig')
        configObject: '${p.configObject}',

        // Event prefix (e.g., 'sa')
        eventPrefix: '${p.eventPrefix}',

        // Storage prefix (e.g., 'sa')
        storagePrefix: '${p.storagePrefix}',

        // CSS class prefix (e.g., 'sa-')
        cssPrefix: '${cssPrefix}',

        // Data attribute prefix (e.g., 'data-sa')
        dataAttr: '${dataAttr}',

        // CSS variable prefix (e.g., '--sa')
        cssVar: '${cssVar}'
    });

    /**
     * Initialize Global Namespace
     *
     * Creates the plugin's global namespace object.
     * All modules attach to this namespace.
     */
    var NS = '${p.namespace}';
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
`;

    fs.writeFileSync(paths.config.jsOut, jsContent);
    console.log(`  [OK] Generated: ${paths.config.jsOut}`);

    // =========================================
    // 3. Generate PHP PrefixConfig class
    // =========================================
    const phpContent = `<?php
/**
 * ============================================
 * AUTO-GENERATED FILE - DO NOT EDIT DIRECTLY
 * ============================================
 * Generated from: config/prefix.config.cjs
 * Regenerate with: npm run build
 * ============================================
 */

namespace SiteAlerts\\Config;

/**
 * Prefix Configuration Class
 *
 * Provides centralized access to all prefix values.
 * Used by PHP components for consistent naming.
 *
 * @package SiteAlerts\\Config
 * @version 1.0.0
 */
final class PrefixConfig
{
    /**
     * Core prefix (e.g., 'sa')
     */
    public const PREFIX = '${p.prefix}';

    /**
     * Uppercase constant prefix (e.g., 'SA')
     */
    public const CONSTANT_PREFIX = '${p.constantPrefix}';

    /**
     * JavaScript namespace (e.g., 'SA')
     */
    public const JS_NAMESPACE = '${p.namespace}';

    /**
     * CSS class prefix with hyphen (e.g., 'sa-')
     */
    public const CSS_PREFIX = '${cssPrefix}';

    /**
     * Data attribute prefix (e.g., 'data-sa')
     */
    public const DATA_ATTR = '${dataAttr}';

    /**
     * CSS custom property prefix (e.g., '--sa')
     */
    public const CSS_VAR = '${cssVar}';

    /**
     * PHP function/option prefix with underscore (e.g., 'sa_')
     */
    public const PHP_PREFIX = '${phpPrefix}';

    /**
     * WordPress handle prefix (e.g., 'sa-')
     */
    public const HANDLE_PREFIX = '${handlePrefix}';

    /**
     * Event prefix for JavaScript events (e.g., 'sa')
     */
    public const EVENT_PREFIX = '${p.eventPrefix}';

    /**
     * Localized config object name (e.g., 'saConfig')
     */
    public const CONFIG_OBJECT = '${p.configObject}';

    /**
     * Storage prefix for localStorage (e.g., 'sa')
     */
    public const STORAGE_PREFIX = '${p.storagePrefix}';

    /**
     * Prevent instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get prefixed CSS class name
     *
     * @param string \$name Class name without prefix
     * @return string Prefixed class name (e.g., 'sa-btn')
     */
    public static function cssClass(string \$name): string
    {
        return self::CSS_PREFIX . \$name;
    }

    /**
     * Get prefixed data attribute name
     *
     * @param string \$name Attribute name without prefix
     * @return string Prefixed attribute (e.g., 'data-sa-toggle')
     */
    public static function dataAttr(string \$name): string
    {
        return self::DATA_ATTR . '-' . \$name;
    }

    /**
     * Get prefixed CSS variable name
     *
     * @param string \$name Variable name without prefix
     * @return string Prefixed variable (e.g., '--sa-primary')
     */
    public static function cssVar(string \$name): string
    {
        return self::CSS_VAR . '-' . \$name;
    }

    /**
     * Get prefixed option/meta key
     *
     * @param string \$name Key name without prefix
     * @return string Prefixed key (e.g., 'sa_settings')
     */
    public static function optionKey(string \$name): string
    {
        return self::PHP_PREFIX . \$name;
    }

    /**
     * Get prefixed WordPress handle
     *
     * @param string \$name Handle name without prefix
     * @return string Prefixed handle
     */
    public static function handle(string \$name): string
    {
        return self::HANDLE_PREFIX . \$name;
    }

    /**
     * Get prefixed AJAX action name
     *
     * @param string \$name Action name without prefix
     * @return string Prefixed action (e.g., 'sa_save_settings')
     */
    public static function ajaxAction(string \$name): string
    {
        return self::PHP_PREFIX . \$name;
    }

    /**
     * Get prefixed nonce name
     *
     * @param string \$name Nonce name without prefix
     * @return string Prefixed nonce (e.g., 'sa_nonce')
     */
    public static function nonce(string \$name = 'nonce'): string
    {
        return self::PHP_PREFIX . \$name;
    }
}
`;

    // Ensure directory exists
    const phpDir = path.dirname(paths.config.phpOut);
    if (!fs.existsSync(phpDir)) {
        fs.mkdirSync(phpDir, {recursive: true});
    }
    fs.writeFileSync(paths.config.phpOut, phpContent);
    console.log(`  [OK] Generated: ${paths.config.phpOut}`);

    console.log('\n  Prefix files generated successfully!\n');

    cb();
}

/**
 * Clean compiled assets
 */
export async function clean() {
    await deleteAsync([paths.scss.dest, paths.js.dest]);
}

/**
 * Compile SCSS to minified CSS
 */
export function compileScss() {
    return gulp
            .src(paths.scss.src, {allowEmpty: true})
            .pipe(sass().on('error', sass.logError))
            .pipe(cleanCSS({compatibility: 'ie11'}))
            .pipe(rename({suffix: '.min'}))
            .pipe(gulp.dest(paths.scss.dest));
}

/**
 * Concatenate and minify JavaScript
 */
export function compileJs() {
    return gulp
            .src(paths.js.src, {allowEmpty: true})
            .pipe(concat('admin.js'))
            .pipe(
                    terser({
                        format: {
                            comments: false,
                        },
                        compress: {
                            drop_console: false, // Keep console for debugging
                        },
                    })
            )
            .pipe(rename({suffix: '.min'}))
            .pipe(gulp.dest(paths.js.dest));
}

/**
 * Watch files for changes
 */
export function watchFiles() {
    // Watch config file - regenerate prefix files and rebuild all
    gulp.watch(
            paths.config.src,
            gulp.series(generatePrefixFiles, gulp.parallel(compileScss, compileJs))
    );

    // Watch SCSS files
    gulp.watch(paths.scss.watch, compileScss);

    // Watch JS files (excluding generated namespace.js to prevent loops)
    gulp.watch(
            [paths.js.watch, '!' + paths.config.jsOut],
            compileJs
    );
}

// =========================================
// Task Definitions
// =========================================

// Generate prefix files only
export const prefix = generatePrefixFiles;

// Compile SCSS
export const scss = compileScss;

// Compile JS
export const js = compileJs;

// Full build: generate prefix files, then compile in parallel
export const build = gulp.series(
        generatePrefixFiles,
        clean,
        gulp.parallel(compileScss, compileJs)
);

// Watch mode: build first, then watch for changes
export const watch = gulp.series(build, watchFiles);

// Default task
export default build;
