<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/layout_start.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../foto/foto_modell.php';

require_login();
$db = db();

$fotoId = filter_input(INPUT_GET, 'Foto_ID', FILTER_VALIDATE_INT);
if (!$fotoId) redirect('primus_main.php');

$foto = foto_hent_en($db, $fotoId);
if (!$foto) redirect('primus_main.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $data['Foto_ID'] = $fotoId;
    $data['NMM_ID']  = $foto['NMM_ID'];
    foto_lagre($db, $data);
    $foto = foto_hent_en($db, $fotoId);
}

/* ---------- helpers ---------- */
function txt($n,$l,$v=''){
    echo "<label for='$n'>$l</label>
          <input type='text' name='$n' id='$n' value='".h($v)."'>";
}
function num($n,$l,$v=0){
    echo "<label for='$n'>$l</label>
          <input type='number' name='$n' id='$n' value='".h((string)$v)."'>";
}
function area($n,$l,$v='',$r=3){
    echo "<label for='$n'>$l</label>
          <textarea name='$n' id='$n' rows='$r'>".h($v)."</textarea>";
}
function chk($n,$l,$v=false){
    $c=$v?'checked':'';
    echo "<label class='form-check' for='$n'>
          <input type='checkbox' name='$n' id='$n' value='1' $c> $l</label>";
}

$iCh = (int)($foto['iCh'] ?? 1);
if ($iCh < 1 || $iCh > 6) $iCh = 1;
?>

<div class="container">
<h1>Primus – foto</h1>

<div class="card"><div class="card-body">
<form method="post" id="foto-form">

<?php num('SerNr','SerNr',$foto['SerNr']); ?>
<?php txt('Bilde_Fil','Bilde_Fil',$foto['Bilde_Fil']); ?>

<div class="primus-tabs" data-tabs>
<div class="primus-tabbar">
  <button type="button" class="primus-tab is-active" data-tab="motiv">Motiv</button>
  <button type="button" class="primus-tab" data-tab="historikk">Bildehistorikk</button>
  <button type="button" class="primus-tab" data-tab="ovrige">Øvrige</button>
</div>

<div class="primus-pane is-active" id="motiv">

    <!-- Hovedbeskrivelser – full bredde -->
    <?php
    area('MotivBeskr','MotivBeskr',$foto['MotivBeskr'],4);
    area('MotivBeskrTillegg','MotivBeskrTillegg',$foto['MotivBeskrTillegg'],3);
    area('Avbildet','Avbildet',$foto['Avbildet'],3);
    ?>

    <hr>

    <!-- Klassifikasjon / metadata -->
    <?php
    area('MotivType','MotivType',$foto['MotivType'],3);
    area('MotivEmne','MotivEmne',$foto['MotivEmne'],3);
    area('MotivKriteria','MotivKriteria',$foto['MotivKriteria'],3);
    ?>

</div>

<div class="primus-pane" id="historikk">
<strong>Hendelsesmodus</strong>
<div class="hendelser-rad">
<?php
$lbl=[1=>'Ingen',2=>'Fotografi',3=>'Samling',4=>'Foto+Saml',5=>'Annet',6=>'Alle'];
foreach($lbl as $v=>$t):
?>
<label class="form-check">
<input type="radio" name="iCh" value="<?= $v ?>" <?= $iCh===$v?'checked':'' ?>>
<?= $v ?> – <?= h($t) ?>
</label>
<?php endforeach; ?>
</div>

<?php
area('Hendelse','Hendelse',$foto['Hendelse'],2);
txt('Samling','Samling',$foto['Samling']);
chk('FriKopi','FriKopi',(bool)$foto['FriKopi']);
txt('Fotograf','Fotograf',$foto['Fotograf']);
txt('FotoFirma','FotoFirma',$foto['FotoFirma']);
txt('FotoTidFra','FotoTidFra',$foto['FotoTidFra']);
txt('FotoTidTil','FotoTidTil',$foto['FotoTidTil']);
txt('FotoSted','FotoSted',$foto['FotoSted']);
?>
</div>

<div class="primus-pane" id="ovrige">

    <?php
    txt('ReferNeg','ReferNeg',$foto['ReferNeg']);
    txt('ReferFArk','ReferFArk',$foto['ReferFArk']);
    ?>

    <hr>

    <!-- Arkiv / plassering -->
    <?php
    txt('Plassering','Plassering',$foto['Plassering']);
    txt('Prosess','Prosess',$foto['Prosess']);
    ?>

    <hr>

    <!-- Status -->
    <?php
    chk('Svarthvitt','Svarthvitt',(bool)$foto['Svarthvitt']);
    txt('Status','Status',$foto['Status']);
    txt('Tilstand','Tilstand',$foto['Tilstand']);
    ?>

    <hr>

    <!-- Merknad – full bredde -->
    <?php
    area('Merknad','Merknad',$foto['Merknad'],4);
    ?>

</div>

</div>

<br>
<button class="btn btn-primary">Oppdater</button>
<a href="primus_main.php" class="btn btn-secondary">Tilbake</a>

</form>
</div></div>
</div>

<script>
(function(){
/* -------- tabs -------- */
const root=document.querySelector('[data-tabs]');
if(root){
  const tabs=root.querySelectorAll('.primus-tab');
  const panes=root.querySelectorAll('.primus-pane');
  tabs.forEach(t=>{
    t.addEventListener('click',()=>{
      tabs.forEach(x=>x.classList.remove('is-active'));
      panes.forEach(p=>p.classList.remove('is-active'));
      t.classList.add('is-active');
      document.getElementById(t.dataset.tab).classList.add('is-active');
    });
  });
}

/* -------- iCh → foto_state -------- */
function oppdaterFotoState(){
  const valgt=document.querySelector('input[name="iCh"]:checked');
  if(!valgt) return;

  fetch('/nmmprimus/modules/foto/api/foto_state.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'fmeHendelse='+encodeURIComponent(valgt.value)
    })

  .then(r=>r.json())
  .then(json=>{
    if(!json || !json.ok) return;

    document.querySelectorAll('input,textarea,select').forEach(el=>{
      el.removeAttribute('data-foto-state');
      el.disabled=false;
    });

    Object.entries(json.data.felter).forEach(([id,enabled])=>{
      const el=document.getElementById(id);
      if(!el) return;
      el.setAttribute('data-foto-state', enabled?'aktiv':'inaktiv');
      el.disabled=!enabled;
    });
  });
}

document.querySelectorAll('input[name="iCh"]').forEach(r=>{
  r.addEventListener('change', oppdaterFotoState);
});

oppdaterFotoState();
})();
</script>

<?php require_once __DIR__ . '/../../includes/layout_slutt.php'; ?>
