/**
 * Admin UI - Admin Notices Component
 *
 * Handles dismissing notices via AJAX.
 * Requires: namespace.js, config.js, helpers.js
 */
(function (window, document) {
    'use strict';

    var PREFIX_CONFIG = window.__PREFIX_CONFIG__;
    if (!PREFIX_CONFIG) throw new Error('AdminNotices requires namespace.js (__PREFIX_CONFIG__).');

    var SA = window[PREFIX_CONFIG.namespace];
    if (!SA) throw new Error('AdminNotices requires global namespace.');

    var Helpers = SA.Helpers;
    var Config = SA.Config;
    if (!Helpers || !Config) throw new Error('AdminNotices requires helpers.js and config.js.');

    function dismissNotice(noticeId) {
        if (!noticeId || typeof window.fetch !== 'function') return;

        var ajaxUrl = Config.getAjaxUrl();
        var nonce = Config.getNonce();
        if (!ajaxUrl || !nonce) return;

        var formData = new FormData();
        formData.append('action', PREFIX_CONFIG.prefix + '_dismiss_notice');
        formData.append('notice_id', noticeId);
        formData.append('security', nonce);

        window.fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        }).catch(function () {
        });
    }

    function init() {
        document.addEventListener('click', function (e) {
            var target = Helpers.getElement(e.target);
            if (!target) return;

            var btn = target.closest(SA.selector('dismissible-notice') + ' .notice-dismiss');
            if (!btn) return;

            var notice = btn.closest(SA.selector('notice'));
            if (!notice) return;

            dismissNotice(notice.getAttribute(SA.dataAttr('notice-id')));
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    SA.AdminNotices = {init: init};

})(window, document);
