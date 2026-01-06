/**
 * Alert Card Component
 *
 * Handles expand/collapse functionality for alert cards with details sections.
 * Requires: namespace.js, helpers.js
 */
(function (window, document) {
    'use strict';

    var PREFIX_CONFIG = window.__PREFIX_CONFIG__;
    if (!PREFIX_CONFIG) throw new Error('AlertCard requires namespace.js (__PREFIX_CONFIG__).');

    var SA = window[PREFIX_CONFIG.namespace];
    if (!SA) throw new Error('AlertCard requires global namespace.');

    var Helpers = SA.Helpers;
    if (!Helpers) throw new Error('AlertCard requires helpers.js.');

    function toggleCard(card) {
        var toggle = card.querySelector(SA.selector('alert-card__toggle'));
        var details = card.querySelector(SA.selector('alert-card__details'));
        if (!toggle || !details) return;

        var expanded = toggle.getAttribute('aria-expanded') === 'true';

        toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        details.hidden = expanded;
        card.classList.toggle(
            SA.selector('alert-card--expanded').slice(1),
            !expanded
        );
    }

    function init() {
        document.addEventListener('click', function (e) {
            var target = Helpers.getElement(e.target);
            if (!target) return;

            var toggle = target.closest(SA.selector('alert-card__toggle'));
            if (toggle) {
                var cardFromToggle = toggle.closest(SA.selector('alert-card--collapsible'));
                if (cardFromToggle) toggleCard(cardFromToggle);
                return;
            }

            var body = target.closest(SA.selector('alert-card__body'));
            if (!body) return;

            if (target.closest('a, button, input, select, textarea')) return;

            var cardFromBody = body.closest(SA.selector('alert-card--collapsible'));
            if (cardFromBody) toggleCard(cardFromBody);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    SA.AlertCard = {init: init};

})(window, document);
