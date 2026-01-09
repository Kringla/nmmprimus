/**
 * primus_detalj.js
 * JavaScript for Primus detail page
 */

(function(window) {
    'use strict';

    // Expose initialization function globally
    window.initPrimusDetalj = function(config) {
        var baseUrl = config.baseUrl;
        var fotoId = config.fotoId;

        // ------------------- Tabs -------------------
        function initTabs() {
            document.querySelectorAll('.primus-tab').forEach(function(tab) {
                tab.addEventListener('click', function () {
                    var valgt = this.dataset.tab;
                    if (!valgt) return;

                    document.querySelectorAll('.primus-tab')
                        .forEach(function(t) { t.classList.toggle('is-active', t === tab); });

                    document.querySelectorAll('.primus-pane')
                        .forEach(function(p) { p.classList.toggle('is-active', p.id === valgt); });

                    fetch(baseUrl + '/modules/primus/api/sett_session.php', {
                        method: 'POST',
                        headers: {'Content-Type':'application/x-www-form-urlencoded'},
                        body: 'primus_tab=' + encodeURIComponent(valgt)
                    }).catch(function(){});
                });
            });
        }

        // ------------------- Kandidatsøk -------------------
        function initKandidatSok() {
            var sokBtn = document.getElementById('btn-kandidat-sok');
            var sokInput = document.getElementById('k_sok');
            if (!sokBtn) return;

            function doKandidatSok() {
                if (!sokInput) return;
                var val = sokInput.value.trim();
                var url = 'primus_detalj.php?Foto_ID=' + fotoId;
                if (val !== '') {
                    url += '&k_sok=' + encodeURIComponent(val);
                }

                // Persist search string to session before navigating
                fetch(baseUrl + '/modules/primus/api/sett_session.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: 'primus_k_sok=' + encodeURIComponent(val)
                }).catch(function(){}).then(function(){ window.location.href = url; });
            }

            sokBtn.addEventListener('click', doKandidatSok);

            // Allow pressing Enter in the search field to trigger the same search
            // Prevent Enter from submitting the main form (avoids accidental saves/redirects)
            if (sokInput) {
                sokInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();

                        // Only perform search via Enter if user has entered more than 2 characters
                        var val = sokInput.value.trim();
                        if (val.length > 2) {
                            doKandidatSok();
                        }
                        // Otherwise ignore Enter (user can still click the Søk button)
                    }
                });
            }
        }

        // ------------------- iCh → foto_state -------------------
        function initIChState() {
            function oppdaterFotoState(){
                var valgt = document.querySelector('input[name="iCh"]:checked');
                if(!valgt) return;

                fetch(baseUrl + '/modules/primus/api/sett_session.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:'primus_iCh=' + encodeURIComponent(valgt.value)
                }).catch(function(){});

                fetch(baseUrl + '/modules/foto/api/foto_state.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:'foto_id=' + fotoId + '&iCh=' + encodeURIComponent(valgt.value)
                }).catch(function(){});
            }

            document.querySelectorAll('input[name="iCh"]').forEach(function(rb){
                rb.addEventListener('change', oppdaterFotoState);
            });
        }

        // ------------------- Kandidat rad klikk -------------------
        function initKandidatRadKlikk() {
            var inputNmmId = document.getElementById('NMM_ID');
            if (!inputNmmId) return;

            document.querySelectorAll('.kandidat-rad').forEach(function(rad) {
                rad.addEventListener('click', function() {
                    var nmmId = this.dataset.nmmId;
                    if (!nmmId) return;

                    inputNmmId.value = nmmId;

                    fetch(baseUrl + '/modules/primus/api/kandidat_data.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'NMM_ID=' + encodeURIComponent(nmmId)
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (!data.ok) return;

                        // Oppdater visuelle felt
                        setVal('ValgtFartoy_vis', data.ValgtFartoy || '');
                        setVal('FTO_vis', data.FTO || '');
                        setVal('MotivBeskr', data.MotivBeskr || '');
                        setVal('MotivType', data.MotivType || '');
                        setVal('MotivEmne', data.MotivEmne || '');
                        setVal('MotivKriteria', data.MotivKriteria || '');

                        rad.style.background = '#d4edda';
                        setTimeout(function() { rad.style.background = ''; }, 600);
                    })
                    .catch(function(){});
                });
            });
        }

        // ------------------- MotivBeskrTillegg -------------------
        function initMotivBeskrTillegg() {
            var tillegg = document.getElementById('MotivBeskrTillegg');
            var motiv = document.getElementById('MotivBeskr');

            if (!tillegg || !motiv) return;

            function appendTillegg() {
                var tilleggVal = tillegg.value.trim();
                if (tilleggVal === '') return;

                var motivVal = motiv.value.trim();
                if (motivVal === '') {
                    motiv.value = tilleggVal;
                } else {
                    motiv.value = motivVal + ' ' + tilleggVal;
                }
            }

            tillegg.addEventListener('blur', appendTillegg);

            tillegg.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    appendTillegg();
                }
            });
        }

        // ------------------- Skipsportrett button -------------------
        function initSkipsportrettButton() {
            var btn = document.getElementById('btn-leggtil-skipsportrett');
            if (!btn) return;

            btn.addEventListener('click', function() {
                var motivType = document.getElementById('MotivType');
                if (!motivType) return;

                var current = motivType.value.trim();
                var toAdd = '1060;Skipsportrett;4D9A6929-3BE1-42E4-B5F4-A2782C75A054';

                if (current.toLowerCase().includes('skipsportrett')) {
                    return; // Already present
                }

                if (current === '' || current === '-') {
                    motivType.value = toAdd;
                } else {
                    motivType.value = current + '\n' + toAdd;
                }
            });
        }

        // ------------------- Helper -------------------
        function setVal(id, val) {
            var el = document.getElementById(id);
            if (el) el.value = val;
        }

        // ------------------- Initialize all -------------------
        initTabs();
        initKandidatSok();
        initIChState();
        initKandidatRadKlikk();
        initMotivBeskrTillegg();
        initSkipsportrettButton();
    };

})(window);
