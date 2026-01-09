/**
 * bruker_admin.js
 * JavaScript for User Admin page
 */

(function(window) {
    'use strict';

    window.visRedigerModal = function(userId, email, rolle) {
        document.getElementById('edit_user_id').value = userId;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_rolle').value = rolle;
        document.getElementById('redigerModal').style.display = 'block';
    };

    window.skjulRedigerModal = function() {
        document.getElementById('redigerModal').style.display = 'none';
    };

    window.visPassordModal = function(userId, email) {
        document.getElementById('passord_user_id').value = userId;
        document.getElementById('passord_email').textContent = email;
        document.getElementById('nytt_passord').value = '';
        document.getElementById('passordModal').style.display = 'block';
    };

    window.skjulPassordModal = function() {
        document.getElementById('passordModal').style.display = 'none';
    };

})(window);
