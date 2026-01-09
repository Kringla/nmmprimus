/**
 * primus_main.js
 * JavaScript for Primus main/landing page
 */

(function(window) {
    'use strict';

    // Expose initialization function globally
    window.initPrimusMain = function(config) {
        var baseUrl = config.baseUrl;
        var isAdmin = config.isAdmin;

        // Dobbeltklikk på eksisterende foto -> H1 modus
        document.querySelectorAll('.row-clickable').forEach(function (row) {
            row.addEventListener('dblclick', function () {
                var fotoId = this.dataset.fotoId;
                if (!fotoId) return;

                fetch(baseUrl + '/modules/primus/api/sett_session.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: 'primus_h2=0'
                }).catch(function(){});

                window.location.href = 'primus_detalj.php?Foto_ID=' + fotoId;
            });
        });

        // Admin: Toggle Transferred checkbox via AJAX
        if (isAdmin) {
            document.querySelectorAll('.transferred-checkbox').forEach(function (checkbox) {
                checkbox.addEventListener('change', function (e) {
                    e.stopPropagation(); // Prevent row double-click

                    var fotoId = this.dataset.fotoId;
                    var checkboxEl = this;

                    fetch(baseUrl + '/modules/primus/api/toggle_transferred.php', {
                        method: 'POST',
                        headers: {'Content-Type':'application/x-www-form-urlencoded'},
                        body: 'foto_id=' + fotoId
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success) {
                            checkboxEl.checked = data.transferred;
                        } else {
                            // Revert on error
                            checkboxEl.checked = !checkboxEl.checked;
                            alert('Kunne ikke oppdatere status: ' + (data.error || 'Ukjent feil'));
                        }
                    })
                    .catch(function(err) {
                        // Revert on error
                        checkboxEl.checked = !checkboxEl.checked;
                        alert('Feil ved oppdatering');
                    });
                });
            });

            // Prevent checkbox cell from triggering row double-click
            document.querySelectorAll('.transferred-cell').forEach(function(cell) {
                cell.addEventListener('dblclick', function(e) {
                    e.stopPropagation();
                });
            });

            // Export dialog
            window.openExportDialog = function() {
                var dialog = document.getElementById('exportDialog');
                if (dialog) dialog.style.display = 'block';
            };

            window.closeExportDialog = function() {
                var dialog = document.getElementById('exportDialog');
                if (dialog) dialog.style.display = 'none';
            };
        }
    };

})(window);
