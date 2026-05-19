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
        var nyRad = config.nyRad || false;
        var h2 = config.h2 || false;

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

        // ------------------- Kandidatsøk (AJAX, ingen side-reload) -------------------
        function initKandidatSok() {
            var sokBtn = document.getElementById('btn-kandidat-sok');
            var sokInput = document.getElementById('k_sok');
            if (!sokBtn) return;

            function doKandidatSok() {
                if (!sokInput) return;
                var val = sokInput.value.trim();

                // Lagre søk i session
                fetch(baseUrl + '/modules/primus/api/sett_session.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: 'primus_k_sok=' + encodeURIComponent(val)
                }).catch(function(){});

                // Hent kandidater via AJAX (ingen side-reload)
                fetch(baseUrl + '/modules/primus/api/kandidat_sok.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: 'sok=' + encodeURIComponent(val)
                })
                .then(function(r) { return r.json(); })
                .then(function(resp) {
                    if (!resp.ok) return;
                    oppdaterKandidatTabell(resp.data || []);
                })
                .catch(function(err) {
                    console.error('Kandidatsøk feilet:', err);
                });
            }

            sokBtn.addEventListener('click', doKandidatSok);

            // Enter i søkefeltet: forhindre form-submit, utfør søk via AJAX
            if (sokInput) {
                sokInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        doKandidatSok();
                    }
                });
            }
        }

        // Oppdater kandidattabellen med nye data (DOM-manipulasjon)
        function oppdaterKandidatTabell(kandidater) {
            var tbody = document.querySelector('.table-scroll-520 tbody');
            if (!tbody) return;

            tbody.innerHTML = '';

            kandidater.forEach(function(k) {
                var tr = document.createElement('tr');
                tr.className = 'kandidat-rad';
                tr.style.fontSize = 'calc(1em - 1px)';
                tr.dataset.nmmId = String(k.NMM_ID);
                var fty = (k.FTY || '').trim();
                var fna = (k.FNA || '').trim();
                var navn = (fty + ' ' + fna).trim();
                tr.dataset.navn = navn;

                var td1 = document.createElement('td');
                td1.style.cssText = 'width:20ch; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;';
                td1.textContent = navn;
                var td2 = document.createElement('td');
                td2.className = 'nowrap';
                td2.textContent = k.BYG || '';
                var td3 = document.createElement('td');
                td3.style.cssText = 'overflow:hidden; text-overflow:ellipsis; white-space:nowrap;';
                td3.textContent = k.VER || '';

                tr.appendChild(td1);
                tr.appendChild(td2);
                tr.appendChild(td3);
                tbody.appendChild(tr);
            });
        }

        // ------------------- iCh → foto_state -------------------
        function initIChState() {
            // erBrukerEndring: true når bruker bytter iCh, false ved sidelast
            function oppdaterFotoState(erBrukerEndring){
                var valgt = document.querySelector('input[name="iCh"]:checked');
                if(!valgt) return;

                // Lagre iCh til session
                fetch(baseUrl + '/modules/primus/api/sett_session.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:'primus_iCh=' + encodeURIComponent(valgt.value)
                }).catch(function(){});

                // Hent felt-tilstander fra foto_state.php
                fetch(baseUrl + '/modules/foto/api/foto_state.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:'foto_id=' + fotoId + '&iCh=' + encodeURIComponent(valgt.value)
                })
                .then(function(r) { return r.json(); })
                .then(function(resp) {
                    if (!resp.ok || !resp.data) return;

                    var data = resp.data;
                    var felter = data.felter || {};
                    var verdier = data.verdier || {};
                    var skalTommes = data.skalTommes || [];

                    // Oppdater felt-tilstander (readonly for inaktive, redigerbart for aktive)
                    // VIKTIG: Bruk readonly (ikke disabled) så felt sender data i POST
                    for (var feltId in felter) {
                        var el = document.getElementById(feltId);
                        if (!el) continue;

                        var erAktiv = felter[feltId];

                        // Hendelse er ALLTID readonly (auto-fylles, ikke redigerbart av bruker)
                        if (feltId === 'Hendelse') {
                            el.readOnly = true;
                            el.style.setProperty('border', '2px solid #dc3545', 'important');
                            el.style.setProperty('background-color', '#ffe6e6', 'important');
                        }
                        // Andre felt: readonly hvis inaktiv, redigerbart hvis aktiv
                        else if (el.tagName === 'TEXTAREA' || (el.tagName === 'INPUT' && el.type === 'text')) {
                            el.readOnly = !erAktiv;
                            if (erAktiv) {
                                el.style.borderColor = '#28a745';
                                el.style.backgroundColor = '';
                            } else {
                                el.style.borderColor = '#dc3545';
                                el.style.backgroundColor = '#ffe6e6';
                            }
                        }
                        // Select/checkbox: styling + pointer-events hvis inaktiv
                        // VIKTIG: Ikke disable (disabled fields sender ikke data i POST)
                        else {
                            if (erAktiv) {
                                el.style.borderColor = '#28a745';
                                el.style.backgroundColor = '';
                                el.style.pointerEvents = '';
                            } else {
                                el.style.borderColor = '#dc3545';
                                el.style.backgroundColor = '#ffe6e6';
                                el.style.pointerEvents = 'none';
                            }
                        }
                    }

                    // Verdier og tømming: KUN ved brukerendring av iCh
                    // Ved sidelast vises DB-verdier uendret
                    if (!erBrukerEndring) return;

                    // 1) Tøm felt som skal tømmes FØRST
                    skalTommes.forEach(function(feltId) {
                        var tEl = document.getElementById(feltId);
                        if (tEl && tEl.type === 'checkbox') {
                            tEl.checked = false;
                        } else {
                            setVal(feltId, '');
                        }
                    });

                    // 2) Deretter fyll inn default-verdier
                    for (var feltNavn in verdier) {
                        var verdierEl = document.getElementById(feltNavn);
                        if (!verdierEl) continue;

                        if (verdierEl.type === 'checkbox') {
                            verdierEl.checked = !!verdier[feltNavn];
                        } else {
                            // Sett default kun hvis feltet er tomt (etter tømming)
                            if (!verdierEl.value || verdierEl.value.trim() === '') {
                                setVal(feltNavn, verdier[feltNavn]);
                            }
                        }
                    }
                })
                .catch(function(err){
                    console.error('Feil ved oppdatering av foto state:', err);
                });
            }

            // Sidelast: kun visuell styling, ikke endre verdier
            oppdaterFotoState(false);

            // Brukerendring: styling + oppdater verdier/tøm felt
            document.querySelectorAll('input[name="iCh"]').forEach(function(rb){
                rb.addEventListener('change', function() { oppdaterFotoState(true); });
            });
        }

        // ------------------- Kandidat rad klikk (event delegation) -------------------
        function initKandidatRadKlikk() {
            var container = document.querySelector('.table-scroll-520');
            if (!container) return;

            // Event delegation: Fungerer for både initielle og AJAX-laddede rader
            container.addEventListener('click', function(e) {
                var rad = e.target.closest('.kandidat-rad');
                if (!rad) return;

                var nmmId = rad.dataset.nmmId;
                var navn = rad.dataset.navn || '';
                if (!nmmId) return;

                // H1-modus: Bekreftelse før endring
                if (!h2) {
                    if (!confirm('Vil du endre fartøy til "' + navn + '"?\n\nDette vil overskrive nåværende fartøyinformasjon.')) {
                        return;
                    }
                }

                setVal('NMM_ID', nmmId);

                // Lås opp skjemaet nå som kandidat er valgt
                if (typeof window._unlockForm === 'function') {
                    window._unlockForm();
                }

                fetch(baseUrl + '/modules/primus/api/kandidat_data.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'NMM_ID=' + encodeURIComponent(nmmId)
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.ok) return;

                    var d = data.data;

                    // Oppdater visuelle felt
                    setVal('ValgtFartoy_vis', d.ValgtFartoy || '');
                    setVal('FTO_vis', d.FTO || '');
                    setVal('Avbildet', d.Avbildet || '');

                    // Bygg MotivBeskr fra kandidatfelter (Access-paritet)
                    var fty = (d.FTY || '').trim();
                    var fna = (d.FNA || '').trim();
                    var byg = (d.BYG || '').trim();
                    var ver = (d.VER || '').trim();
                    var xna = (d.XNA || '').trim();

                    if (fty === '' || fna === '') {
                        var fto = (d.FTO || '').trim();
                        setVal('MotivBeskr', fto !== '' ? fto : '');
                    } else {
                        var mb = '';
                        var bygInfo = 'b. ' + byg;
                        if (ver !== '') {
                            bygInfo += ', ' + ver;
                        }

                        if (xna !== '') {
                            mb = fty + ' ' + fna + ' (ex. ' + xna + ') (' + bygInfo + ')';
                        } else {
                            mb = fty + ' ' + fna + ' (' + bygInfo + ')';
                        }
                        setVal('MotivBeskr', mb);
                    }

                    setVal('MotivType', d.MotivType || '-');
                    setVal('MotivEmne', d.MotivEmne || '-');
                    setVal('MotivKriteria', d.MotivKriteria || '-');

                    rad.style.background = '#d4edda';
                    setTimeout(function() { rad.style.background = ''; }, 600);
                })
                .catch(function(){});
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

        // ------------------- Lås skjema til kandidat er valgt -------------------
        function initFormLock() {
            var nmmIdEl = document.getElementById('NMM_ID');
            if (!nmmIdEl) return;

            function erLaast() {
                var val = (nmmIdEl.value || '').trim();
                return val === '' || val === '0';
            }

            function settLaas(laas) {
                var form = document.getElementById('foto-form');
                if (!form) return;

                // Finn alle redigerbare felt UTENFOR kandidatpanelet
                var kandidatPanel = document.querySelector('.flex-fixed-420');
                var felt = form.querySelectorAll('input, textarea, select');

                felt.forEach(function(el) {
                    // Hopp over felt i kandidatpanelet (søk etc.)
                    if (kandidatPanel && kandidatPanel.contains(el)) return;
                    // Hopp over hidden fields og submit-knapper
                    if (el.type === 'hidden' || el.type === 'submit') return;
                    // Hopp over NMMSerie og SerNr (alltid redigerbare)
                    if (el.id === 'NMMSerie' || el.id === 'SerNr') return;

                    if (laas) {
                        if (el.tagName === 'TEXTAREA' || el.tagName === 'INPUT') {
                            el.readOnly = true;
                        }
                        if (el.tagName === 'SELECT' || el.type === 'checkbox' || el.type === 'radio') {
                            el.style.pointerEvents = 'none';
                        }
                        el.style.opacity = '0.5';
                    } else {
                        // Opphev lås (iCh-logikken overstyrer etterpå for relevante felt)
                        if (el.tagName === 'TEXTAREA' || el.tagName === 'INPUT') {
                            // Ikke opphev readonly for felt som alltid er readonly
                            if (el.id !== 'ValgtFartoy_vis' && el.id !== 'FTO_vis' && el.id !== 'Bilde_Fil') {
                                el.readOnly = false;
                            }
                        }
                        if (el.tagName === 'SELECT' || el.type === 'checkbox' || el.type === 'radio') {
                            el.style.pointerEvents = '';
                        }
                        el.style.opacity = '';
                    }
                });

                // Lås også Oppdater-knappen
                var submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = laas;
                }
            }

            // Ved sidelast
            if (erLaast()) {
                settLaas(true);
            }

            // Eksponér unlock-funksjon for kandidat-klikk
            window._unlockForm = function() {
                settLaas(false);
            };
        }

        // ------------------- Helper -------------------
        function setVal(id, val) {
            var el = document.getElementById(id);
            if (el) el.value = val;
        }

        // ------------------- Initialize all -------------------
        initTabs();
        initKandidatSok();
        initFormLock();
        initIChState();
        initKandidatRadKlikk();
        initSkipsportrettButton();
    };

})(window);
