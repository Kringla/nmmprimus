/**
 * index.js
 * JavaScript for index page (auto-redirect for non-admin users)
 */

(function(window) {
    'use strict';

    window.initIndexRedirect = function(redirectTarget) {
        setTimeout(function () {
            window.location.href = redirectTarget;
        }, 3000);
    };

})(window);
