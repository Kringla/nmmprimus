<?php
// modules/foto/foto_arbeidsflate.php
declare(strict_types=1);

/**
 * FOTO – arbeidsflate (partial)
 *
 * Representerer frmNMMfoto (felt).
 * Inneholder:
 *  - MOTIV
 *  - BILDEHISTORIKK (inkl. Hendelser)
 *
 * Forutsetter:
 *  - $foto er satt av kallende fil
 *  - iCh / Hendelser styres via JS i primus_detalj.php
 */
?>

<!-- =========================================================
     MOTIV
     ========================================================= -->
<fieldset class="mb-4">
    <legend><strong>Motiv</strong></legend>

    <div class="mb-3">
        <label for="FTO" class="form-label">FTO (fra valgt fartøy)</label>
        <textarea class="form-control" id="FTO" rows="2" readonly><?= h($skipFto ?? '') ?></textarea>
    </div>

    <div class="mb-3">
        <label for="MotivBeskr" class="form-label">Motivbeskrivelse</label>
        <input type="text" class="form-control" id="MotivBeskr" name="MotivBeskr"
               value="<?= h($foto['MotivBeskr'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label for="MotivBeskrTillegg" class="form-label">Tillegg, “Motivbeskr”</label>
        <input type="text" class="form-control" id="MotivBeskrTillegg" name="MotivBeskrTillegg"
               value="<?= h($foto['MotivBeskrTillegg'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label for="MotivEmne" class="form-label">Motiv emne</label>
        <textarea class="form-control" id="MotivEmne" name="MotivEmne" rows="3"><?= h($foto['MotivEmne'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
        <label for="MotivType" class="form-label">Motiv type</label>
        <textarea class="form-control" id="MotivType" name="MotivType" rows="2"><?= h($foto['MotivType'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
        <label for="MotivKriteria" class="form-label">Motiv søk</label>
        <textarea class="form-control" id="MotivKriteria" name="MotivKriteria" rows="3"><?= h($foto['MotivKriteria'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
        <label for="Avbildet" class="form-label">Avbildet</label>
        <textarea class="form-control" id="Avbildet" name="Avbildet" rows="3"><?= h($foto['Avbildet'] ?? '') ?></textarea>
    </div>
</fieldset>

<!-- =========================================================
     BILDEHISTORIKK
     ========================================================= -->
<fieldset class="mb-4">
    <legend><strong>Bildehistorikk</strong></legend>

    <!-- Hendelser (Access: fmeHendelse – option group) -->
    <div class="mb-3">
        <label class="form-label d-block">Hendelser</label>

        <div class="hendelser-rad">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="fmeHendelse" value="2">
                <label class="form-check-label">Fotografi</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="fmeHendelse" value="3">
                <label class="form-check-label">Samling</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="fmeHendelse" value="4">
                <label class="form-check-label">Foto + Samling</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="fmeHendelse" value="5">
                <label class="form-check-label">Annet</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="fmeHendelse" value="6">
                <label class="form-check-label">Alle</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="fmeHendelse" value="1" checked>
                <label class="form-check-label">Ingen</label>
            </div>
        </div>
    </div>

    <!-- Hendelse (systemstyrt) -->
    <div class="mb-3">
        <label for="Hendelse" class="form-label">Hendelse</label>
        <textarea class="form-control" id="Hendelse" name="Hendelse" rows="3" readonly><?= h($foto['Hendelse'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
        <label for="Samling" class="form-label">Samling</label>
        <input type="text" class="form-control" id="Samling" name="Samling"
               value="<?= h($foto['Samling'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label for="Fotograf" class="form-label">Fotograf</label>
        <input type="text" class="form-control" id="Fotograf" name="Fotograf"
               value="<?= h($foto['Fotograf'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label for="FotoFirma" class="form-label">Fotofirma</label>
        <input type="text" class="form-control" id="FotoFirma" name="FotoFirma"
               value="<?= h($foto['FotoFirma'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label for="FotoTidFra" class="form-label">Foto tid fra</label>
        <input type="text" class="form-control" id="FotoTidFra" name="FotoTidFra"
               value="<?= h($foto['FotoTidFra'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label for="FotoTidTil" class="form-label">Foto tid til</label>
        <input type="text" class="form-control" id="FotoTidTil" name="FotoTidTil"
               value="<?= h($foto['FotoTidTil'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label for="FotoSted" class="form-label">Fotosted</label>
        <input type="text" class="form-control" id="FotoSted" name="FotoSted"
               value="<?= h($foto['FotoSted'] ?? '') ?>">
    </div>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="FriKopi" name="FriKopi" value="1"
               <?= !empty($foto['FriKopi']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="FriKopi">Fri kopi</label>
    </div>
</fieldset>
