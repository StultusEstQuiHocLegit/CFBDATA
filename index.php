<?php
// index.php: single file app that reads CSVs and renders modern UI
header('Content-Type: text/html; charset=utf-8');

function read_csv_assoc($path){
  if (!file_exists($path)) return array();
  $out = array();
  if (($h = fopen($path, 'r')) !== false) {
    $headers = fgetcsv($h);
    while ($headers !== false) {
      $hasHeader = false;
      foreach ($headers as $header) {
        if ($header !== null && trim($header) !== '') {
          $hasHeader = true;
          break;
        }
      }
      if ($hasHeader) break;
      $headers = fgetcsv($h);
    }
    if ($headers === false) { fclose($h); return array(); }
    while (($data = fgetcsv($h)) !== false) {
      $allEmpty = true;
      foreach ($data as $value) {
        if ($value !== null && trim($value) !== '') {
          $allEmpty = false;
          break;
        }
      }
      if ($allEmpty) continue;
      $row = array();
      foreach($headers as $i => $key){
        if ($key === null || $key === '') continue;
        $row[$key] = array_key_exists($i, $data) ? $data[$i] : null;
      }
      $out[] = $row;
    }
    fclose($h);
  }
  return $out;
}

$datasetMode = (isset($_COOKIE['dataset_mode']) && $_COOKIE['dataset_mode'] === 'subset') ? 'subset' : 'whole';
$suffix = $datasetMode === 'subset' ? '_subset' : '';

$main = array_map(function($r){ $r['solvent'] = false; return $r; }, read_csv_assoc(__DIR__ . '/main' . $suffix . '.csv'));
$financials = array_map(function($r){ $r['solvent'] = false; return $r; }, read_csv_assoc(__DIR__ . '/financials' . $suffix . '.csv'));
$reports = array_map(function($r){ $r['solvent'] = false; return $r; }, read_csv_assoc(__DIR__ . '/reports' . $suffix . '.csv'));

$main_solvent = array_map(function($r){ $r['solvent'] = true; return $r; }, read_csv_assoc(__DIR__ . '/main_solvent' . $suffix . '.csv'));
$financials_solvent = array_map(function($r){ $r['solvent'] = true; return $r; }, read_csv_assoc(__DIR__ . '/financials_solvent' . $suffix . '.csv'));
$reports_solvent = array_map(function($r){ $r['solvent'] = true; return $r; }, read_csv_assoc(__DIR__ . '/reports_solvent' . $suffix . '.csv'));

$main = array_merge($main, $main_solvent);
$financials = array_merge($financials, $financials_solvent);
$reports = array_merge($reports, $reports_solvent);

$payload = array('main'=>$main, 'financials'=>$financials, 'reports'=>$reports);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>TRAMANN CFDATA</title>
  <style>
    :root{
      --bg:#000000;
      --card:#111218;
      --muted:#9aa0a6;
      --text:#f5f7fa;
      --good:#2ecc71;     /* green */
      --bad:#e74c3c;      /* red */
      --blue:#00aeef;     /* cristallblue */
      --violet:#8a2be2;   /* accents */
      --orange:#ff8c00;   /* orange */
      --brown:#8b4513;    /* brown */
      --shadow:0 8px 24px rgba(0,0,0,.35);
      --radius:16px;
      --input-bg:#0b0c10;
      --border-color:rgba(255,255,255,.08);
      --btn-bg:linear-gradient(135deg, #1b1c22, #0f0f14);
      --header-bg:rgba(0,0,0,.5);
      --card-bg:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
      --card-border:rgba(255,255,255,.08);
      --modal-bg:linear-gradient(180deg, #101119, #0b0c10);
      --modal-border:rgba(255,255,255,.12);
      --modal-shadow:0 20px 60px rgba(0,0,0,.55);
      --fin-bg:rgba(255,255,255,.04);
      --fin-border:rgba(255,255,255,.08);
      --year-btn-bg:#0f1015;
      --year-btn-color:#dfe4ea;
      --year-btn-active-bg:linear-gradient(135deg, rgba(138,43,226,.25), rgba(0,174,239,.25));
      --year-btn-active-color:#fff;
      --close-btn-bg:rgba(255,255,255,.06);
      --close-btn-border:rgba(255,255,255,.08);
      --link-card-border:rgba(255,255,255,.12);
    }
    body.lightmode{
      --bg:#ffffff;
      --card:#f1f3f5;
      --muted:#606770;
      --text:#000000;
      --good:#2ecc71;
      --bad:#e74c3c;
      --blue:#0068c9;
      --violet:#6a1bb4;
      --orange:#ff8c00;
      --brown:#8b4513;
      --shadow:0 8px 24px rgba(0,0,0,.1);
      --input-bg:#ffffff;
      --border-color:rgba(0,0,0,.12);
      --btn-bg:linear-gradient(135deg, #ffffff, #eaeaea);
      --header-bg:rgba(255,255,255,.7);
      --card-bg:linear-gradient(180deg, rgba(0,0,0,.04), rgba(0,0,0,.02));
      --card-border:rgba(0,0,0,.08);
      --modal-bg:linear-gradient(180deg, #ffffff, #f5f5f5);
      --modal-border:rgba(0,0,0,.12);
      --modal-shadow:0 20px 60px rgba(0,0,0,.15);
      --fin-bg:rgba(0,0,0,.04);
      --fin-border:rgba(0,0,0,.08);
      --year-btn-bg:#eaeaea;
      --year-btn-color:#333;
      --year-btn-active-bg:linear-gradient(135deg, rgba(138,43,226,.15), rgba(0,174,239,.15));
      --year-btn-active-color:#000;
      --close-btn-bg:rgba(0,0,0,.06);
      --close-btn-border:rgba(0,0,0,.08);
      --link-card-border:rgba(0,0,0,.12);
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      font-family: ui-sans-serif, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif;
      background:var(--bg);
      color:var(--text);
    }
    a{color:var(--blue); text-decoration:none; transition:opacity .2s ease}
    a:hover{opacity:.8}
    .wrap{
      max-width:1200px;
      margin:0 auto;
      padding:24px;
    }
    header{
      position:sticky; top:0; z-index:10;
      backdrop-filter:saturate(120%) blur(6px);
      background:var(--header-bg);
      border-bottom:1px solid var(--border-color);
    }
    .hero{
      position:relative;
      min-height:calc(100vh - 60px);
      display:flex; flex-direction:column; align-items:center; justify-content:center;
      transition: min-height .3s ease;
    }
    .hero.compact{min-height:auto; justify-content:flex-start; padding-left:40px}
    .searchbar{
      width:100%;
      max-width:900px;
      display:flex;
      align-items:center;
      gap:10px;
    }
    .searchbar input{
      flex:1;
      width:100%;
      padding:16px 18px;
      background:var(--input-bg);
      color:var(--text);
      border:1px solid var(--border-color);
      border-radius:var(--radius);
      outline:none;
      font-size:18px;
      box-shadow:var(--shadow);
      transition:border .2s ease, box-shadow .2s ease;
    }
    .searchbar input:focus{
      border-color:var(--violet);
      box-shadow:0 0 0 4px rgba(138,43,226,.25);
    }
    .swap-btn{
      padding:8px 12px;
      border-radius:var(--radius);
      border:1px solid var(--border-color);
      background:var(--btn-bg);
      color:var(--text);
      cursor:pointer;
      box-shadow:var(--shadow);
      transition:transform .06s ease, opacity .2s ease, border .2s ease;
    }
    .swap-btn:hover{opacity:.9}
    .swap-btn:active{transform:translateY(1px)}
    #mode-switch{
      position:absolute; left:0; top:50%; transform:translateY(-50%);
      display:none;
    }
    #dataset-toggle{
      position:fixed;
      top:2px;
      left:2px;
      z-index:1000;
    }
    .hero.compact #mode-switch{display:block}
    .compare-divider{
      width:50%;
      margin:74px auto;
      display:flex;
      align-items:center;
      color:var(--muted);
    }
    .compare-divider::before,
    .compare-divider::after{
      content:"";
      flex:1;
      height:1px;
      background:var(--text);
      opacity:.3;
    }
    .compare-divider::before{margin-right:10px}
    .compare-divider::after{margin-left:10px}
    .comparebar{
      display:flex; align-items:center; justify-content:center; flex-wrap:wrap;
      row-gap:10px;
    }
    .hero.compact .comparebar{justify-content:flex-start}
    .comparebar .with-text{margin:0 10px; opacity:.5}
    .comparebar .sep-text,
    .comparebar .to-text{opacity:.5}
    .comparebar button.btn{margin-left:10px}
    .compare-group{
      display:flex; align-items:center; gap:10px;
      padding:6px 10px;
      border:1px solid var(--border-color);
      border-radius:var(--radius);
    }
    .dropdown{position:relative}
    .dropdown .selected{
      padding:8px 12px;
      border:1px solid var(--border-color);
      border-radius:var(--radius);
      background:var(--input-bg);
      color:var(--text);
      cursor:pointer;
      display:flex; align-items:center; gap:6px;
      white-space:nowrap;
      transition:opacity .2s ease, border .2s ease;
    }
    .dropdown .selected:hover{opacity:.9}
    .year-static{
      padding:8px 12px;
      border:1px solid var(--border-color);
      border-radius:var(--radius);
      background:var(--input-bg);
      color:var(--text);
      display:flex; align-items:center; gap:6px;
      white-space:nowrap;
    }
    .status-static{
      padding:8px 12px;
      border:1px solid var(--border-color);
      border-radius:var(--radius);
      background:var(--input-bg);
      color:var(--text);
      display:flex; align-items:center; gap:6px;
      white-space:nowrap;
    }
    .dropdown .options{
      position:absolute; top:calc(100% + 4px); left:0;
      background:var(--card);
      border:1px solid var(--border-color);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      display:none;
      z-index:5;
      overflow:hidden;
    }
    .dropdown .options-scroll{
      display:flex;
      flex-direction:column;
      max-height:176px;
      overflow:hidden;
      overflow-y:auto;
    }
    .dropdown.open .options{display:block}
    .dropdown .option{
      padding:8px 12px;
      cursor:pointer;
      display:flex;
      align-items:center;
      gap:6px;
      white-space:nowrap;
    }
    .dropdown .option:hover{background:var(--btn-bg)}
    .status-dropdown .selected,
    .status-dropdown .options{min-width:110px;}
    .dot{width:8px;height:8px;border-radius:50%;display:inline-block}
    .dot.green{background:var(--good)}
    .dot.red{background:var(--bad)}
    .btn{
      padding:16px 20px;
      border-radius:var(--radius);
      border:1px solid var(--border-color);
      background:var(--btn-bg);
      color:var(--text);
      font-weight:600;
      letter-spacing:.2px;
      cursor:pointer;
      box-shadow:var(--shadow);
      transition:transform .06s ease, opacity .2s ease, border .2s ease;
    }
    .btn:hover{opacity:.9}
    .btn:active{transform:translateY(1px)}
    .btn.hidden{display:none}
    .pill{
      display:inline-flex; align-items:center; gap:8px;
      font-size:13px; padding:6px 10px; border-radius:999px;
      background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08);
      color:var(--muted);
    }
    .status-dot{
      display:inline-block;
      width:.5rem; height:.5rem;
      border-radius:50%;
      margin-right:4px;
      vertical-align:middle;
      position:relative;
      top:-.1em;
    }
    .status-dot.good{ background:var(--good) }
    .status-dot.bad{ background:var(--bad) }
    .grid{
      display:grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap:16px;
      padding-bottom:60px;
    }
    .card{
      background:var(--card-bg);
      border:1px solid var(--card-border);
      border-radius:var(--radius);
      padding:16px;
      box-shadow:var(--shadow);
      transition:opacity .2s ease, transform .2s ease, border .2s ease;
      cursor:pointer;
    }
    .card:hover{ opacity:.8; transform:translateY(-2px) }
    .card h3{ margin:4px 0 8px 0; font-size:18px }
    .card.skeleton{
      pointer-events:none;
      position:relative;
      overflow:hidden;
      color:transparent;
      min-height:80px;
    }
    .card.skeleton:hover{ opacity:1; transform:none }
    .card.skeleton::before{
      content:"";
      position:absolute;
      inset:0;
      background:linear-gradient(120deg, rgba(255,255,255,0), rgba(255,255,255,.1), rgba(255,255,255,0));
      animation:shimmer 1.2s infinite;
    }
    @keyframes shimmer{
      0%{transform:translateX(-100%)}
      100%{transform:translateX(100%)}
    }
    .meta{ color:var(--muted); font-size:12px; display:flex; flex-wrap:wrap; gap:10px }
    .kv{ display:grid; grid-template-columns: 160px 1fr; gap:6px 12px; font-size:14px; }
    .kv .k{ color:var(--muted) }
    .kv .v{ color:var(--text); word-break:break-word }
    /* Modal */
    .overlay{
      position:fixed; inset:0;
      background:rgba(0,0,0,.45);
      backdrop-filter: blur(8px);
      display:none; align-items:center; justify-content:center;
      z-index:50;
      padding:24px;
    }
    .overlay.show{ display:flex }
    .modal{
      max-width:1000px; width:min(100%, 1000px);
      max-height:85vh;
      background:var(--modal-bg);
      border:1px solid var(--modal-border);
      border-radius:24px;
      box-shadow:var(--modal-shadow);
      overflow:hidden;
      position:relative;
    }
    .modal-content{
      max-height:85vh;
      overflow:auto;
      padding:24px;
    }
    .modal .title{
      display:flex; flex-wrap:wrap; gap:10px; align-items:baseline; justify-content:space-between;
    }
    .years{ display:flex; flex-wrap:wrap; gap:8px; margin-top:36px }
    .year-btn{
      padding:8px 12px; border-radius:12px; border:1px solid var(--fin-border);
      background:var(--year-btn-bg); color:var(--year-btn-color); font-weight:600; cursor:pointer;
    }
    .year-btn.active{
      background:var(--year-btn-active-bg);
      border-color:var(--violet);
      color:var(--year-btn-active-color);
    }
    .fin-grid{ display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:10px; margin-top:12px }
    .fin-grid.compare-grid{ grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
    .fin-item{ background:var(--fin-bg); border:1px solid var(--fin-border); border-radius:12px; padding:10px; display:flex; flex-direction:column }
    .fin-item.compare{ padding:14px }
    .fin-item .k{ color:var(--muted); font-size:12px }
    .fin-item .v{ font-weight:600; font-size:14px; margin-top:auto; display:flex; align-items:center }
    .fin-item.compare .v{ display:block }
    .fin-item.compare .v .amt{ overflow-wrap:anywhere; min-width:0 }
    .fin-item.compare .v .amt.left{ text-align:left }
    .fin-item.compare .v .amt.right{ text-align:right }
    .fin-item.compare .v .sep{ opacity:.5; font-weight:700; text-align:center; padding:0 4px; }
    .fin-item.compare .variance{opacity:.3; font-size:12px; font-weight:600}
    .fin-item .pct{ font-weight:400; margin-left:auto; text-align:right }
    .cmp-table{ width:100%; border-collapse:collapse }
    .cmp-table tr{ display:flex; width:100% }
    .cmp-table td{ padding:0; vertical-align:bottom }
    .cmp-table td.amt{ flex:1 }
    .cmp-table td.amt.left{ text-align:left }
    .cmp-table td.amt.right{ text-align:right }
    .cmp-table td.sep{ flex:0 0 14px; text-align:center; vertical-align:middle }
    .cmp-table .amt-val{ font-weight:600 }
    .cmp-table .variance{ opacity:.3; font-size:12px; font-weight:600 }
    .fin-item.empty{ opacity: 0.3 }
    .fin-item.zero{ opacity: 0.7 }
    .fin-item.clickable{ cursor:pointer; transition:transform .2s ease, box-shadow .2s ease }
    .fin-item.clickable:hover{ transform:translateY(-2px); box-shadow:var(--shadow) }
    .chart-card{ background:var(--fin-bg); border:1px solid var(--fin-border); border-radius:12px; padding:10px; height:260px; }
    .chart-card canvas{ width:100%; height:100%; }
    .links{
      display:flex; gap:10px; flex-wrap:wrap; margin-top:14px
    }
    .close-btn{
      position:absolute; top:10px; right:10px;
      background:var(--close-btn-bg);
      border:1px solid var(--close-btn-border);
      color:var(--text);
      border-radius:8px;
      padding:4px 8px;
      cursor:pointer;
      opacity:0.5;
    }
    .close-btn:hover{opacity:.8}
    .link-card{
      width:64px; height:64px; display:flex; align-items:center; justify-content:center;
      border-radius:14px;
      background:linear-gradient(180deg, rgba(0,174,239,.12), rgba(138,43,226,.12));
      border:1px solid var(--link-card-border);
      font-weight:800; font-size:20px; color:var(--text);
      text-align:center;
      transition:opacity .2s ease, transform .2s ease;
    }
    .link-card:hover{ opacity:.8; transform:translateY(-1px) }
    .badge{ padding:4px 8px; border-radius:10px; font-size:12px; }
    .badge.good{ background:rgba(46,204,113,.15); color:var(--good); border:1px solid rgba(46,204,113,.25) }
    .badge.bad{ background:rgba(231,76,60,.15); color:var(--bad); border:1px solid rgba(231,76,60,.25) }
    footer{ color:var(--muted); font-size:12px; text-align:center; padding:20px 0 60px }
  </style>
</head>
<body>
  <button id="dataset-toggle" class="swap-btn" data-mode="<?php echo $datasetMode; ?>" title="<?php echo $datasetMode === 'subset' ? 'currently you are working with the subset, click to switch to the whole dataset' : 'currently you are working with the whole dataset, click to focus on the subset'; ?>"><?php echo $datasetMode === 'subset' ? '◰' : '□'; ?></button>
  <header>
    <div class="wrap">
      <div class="hero" id="hero">
        <button id="mode-switch" class="swap-btn" title="switch between searching companies and making mass comparisons">⇅</button>
        <div class="searchbar" id="searchbar">
          <input id="q" type="search" placeholder="search..." autocomplete="off" autofocus />
          <button id="go" class="btn hidden" title="search">search</button>
        </div>
        <div class="compare-divider" id="compare-divider"><span>or</span></div>
        <div class="comparebar" id="comparebar">
          <div class="compare-group">
<?php if ($datasetMode === 'subset'): ?>
            <span id="status1" class="status-static" data-value="bankrupt"><span class="dot red"></span><span>bankrupt</span></span>
<?php else: ?>
            <div class="dropdown status-dropdown" id="status1" data-value="bankrupt">
              <div class="selected"><span class="dot red"></span><span>bankrupt</span></div>
              <div class="options">
                <div class="options-scroll">
                  <div class="option" data-value="bankrupt"><span class="dot red"></span>bankrupt</div>
                  <div class="option" data-value="solvent"><span class="dot green"></span>solvent</div>
                </div>
              </div>
            </div>
<?php endif; ?>
            <span class="sep-text">|</span>
<?php if ($datasetMode === 'subset'): ?>
            <span id="year1" class="year-static" data-value="2024">2024</span>
<?php else: ?>
            <div class="dropdown year-dropdown" id="year1" data-value="2024">
              <div class="selected">2024</div>
              <div class="options"><div class="options-scroll"></div></div>
            </div>
<?php endif; ?>
            <span class="to-text">to</span>
<?php if ($datasetMode === 'subset'): ?>
            <span id="year2" class="year-static" data-value="2024">2024</span>
<?php else: ?>
            <div class="dropdown year-dropdown" id="year2" data-value="2024">
              <div class="selected">2024</div>
              <div class="options"><div class="options-scroll"></div></div>
            </div>
<?php endif; ?>
          </div>
          <span class="with-text">with</span>
          <div class="compare-group">
<?php if ($datasetMode === 'subset'): ?>
            <span id="status2" class="status-static" data-value="solvent"><span class="dot green"></span><span>solvent</span></span>
<?php else: ?>
            <div class="dropdown status-dropdown" id="status2" data-value="solvent">
              <div class="selected"><span class="dot green"></span><span>solvent</span></div>
              <div class="options">
                <div class="options-scroll">
                  <div class="option" data-value="bankrupt"><span class="dot red"></span>bankrupt</div>
                  <div class="option" data-value="solvent"><span class="dot green"></span>solvent</div>
                </div>
              </div>
            </div>
<?php endif; ?>
            <span class="sep-text">|</span>
<?php if ($datasetMode === 'subset'): ?>
            <span id="year3" class="year-static" data-value="2024">2024</span>
<?php else: ?>
            <div class="dropdown year-dropdown" id="year3" data-value="2024">
              <div class="selected">2024</div>
              <div class="options"><div class="options-scroll"></div></div>
            </div>
<?php endif; ?>
            <span class="to-text">to</span>
<?php if ($datasetMode === 'subset'): ?>
            <span id="year4" class="year-static" data-value="2024">2024</span>
<?php else: ?>
            <div class="dropdown year-dropdown" id="year4" data-value="2024">
              <div class="selected">2024</div>
              <div class="options"><div class="options-scroll"></div></div>
            </div>
<?php endif; ?>
          </div>
          <button id="compare" class="btn" title="compare">compare</button>
        </div>
      </div>
    </div>
  </header>

  <main class="wrap">
    <div id="stats" class="pill" style="margin-bottom:14px; display:none"></div>
    <div id="results" class="grid" aria-live="polite"></div>
  </main>

  <div id="overlay" class="overlay">
    <div id="modal" class="modal" role="dialog" aria-modal="true">
      <div class="modal-content"></div>
    </div>
  </div>

  <footer>
    <span style="opacity: 0.3"><a href="#" id="mode-toggle" title="switch between darkmode and lightmode">lightmode</a> | TRAMANN CFDATA | Stultus est, qui hoc legit.</span>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-financial"></script>
  <script>
    // --- DATA PLACEHOLDER (replaced differently in index.php vs preview.html) ---
    const DATA = <?php echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const DATASET_MODE = <?php echo json_encode($datasetMode, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const datasetToggle = document.getElementById('dataset-toggle');
    if (datasetToggle) {
      datasetToggle.addEventListener('click', () => {
        const current = datasetToggle.getAttribute('data-mode') === 'subset' ? 'subset' : 'whole';
        const next = current === 'subset' ? 'whole' : 'subset';
        document.cookie = 'dataset_mode=' + next + '; max-age=' + (30*24*60*60) + '; path=/';
        location.reload();
      });
    }

    const modeToggle = document.getElementById('mode-toggle');
    function applyMode(m){
      document.body.classList.toggle('lightmode', m === 'light');
      if(modeToggle) modeToggle.textContent = m === 'light' ? 'darkmode' : 'lightmode';
    }
    function setMode(m){
      applyMode(m);
      const expiry = new Date(Date.now() + 10*365*24*60*60*1000);
      document.cookie = 'CFDATAViewMode=' + m + '; expires=' + expiry.toUTCString() + '; path=/';
    }
    function getMode(){
      const m = document.cookie.split('; ').find(row => row.startsWith('CFDATAViewMode='));
      return m ? m.split('=')[1] : null;
    }
    const savedMode = getMode();
    applyMode(savedMode === 'light' ? 'light' : 'dark');
    if(modeToggle){
      modeToggle.addEventListener('click', (e)=>{
        e.preventDefault();
        const next = document.body.classList.contains('lightmode') ? 'dark' : 'light';
        setMode(next);
      });
    }

    // Build quick maps for lookups
    const byCIK = new Map();
    for(const row of DATA.main){
      if(!row) continue;
      byCIK.set(String(row.CIK||"").trim(), row);
    }
    const finByCIKYear = new Map(); // key: CIK|year -> record
    const yearsByCIK = new Map();
    const symbolByCIK = new Map();
    const financialCIKs = new Set();
    for(const f of DATA.financials){
      const cik = String(f.CIK||"").trim();
      const year = String(f.year||"").trim();
      if(cik && year){
        finByCIKYear.set(cik+"|"+year, f);
        if(!yearsByCIK.has(cik)) yearsByCIK.set(cik, new Set());
        yearsByCIK.get(cik).add(year);
      }
      // capture a symbol if present
      const sym = (f.TradingSymbol||"").trim();
      if(cik && sym && !symbolByCIK.has(cik)) symbolByCIK.set(cik, sym);
      if(cik) financialCIKs.add(cik);
    }

    const reportsByCIKYear = new Map();
    for(const r of DATA.reports){
      const cik = String(r.CIK||"").trim();
      const year = String(r.year||"").trim();
      if(cik && year) reportsByCIKYear.set(cik+"|"+year, r);
    }

    const qEl = document.getElementById('q');
    const goEl = document.getElementById('go');
    const hero = document.getElementById('hero');
    const resultsEl = document.getElementById('results');
    const statsEl = document.getElementById('stats');
    const overlay = document.getElementById('overlay');
    const modal = document.getElementById('modal');
    const modalContent = modal.querySelector('.modal-content');
    const searchbarEl = document.getElementById('searchbar');
    const comparebarEl = document.getElementById('comparebar');
    const compareBtn = document.getElementById('compare');
    const compareDividerEl = document.getElementById('compare-divider');
    const modeSwitch = document.getElementById('mode-switch');
    const status1El = document.getElementById('status1');
    const status2El = document.getElementById('status2');
    const year1El = document.getElementById('year1');
    const year2El = document.getElementById('year2');
    const year3El = document.getElementById('year3');
    const year4El = document.getElementById('year4');


    let currentMode = 'search';

    function switchMode(){
      if(currentMode === 'search'){
        currentMode = 'compare';
        searchbarEl.style.display = 'none';
        comparebarEl.style.display = 'flex';
      }else{
        currentMode = 'search';
        searchbarEl.style.display = 'flex';
        comparebarEl.style.display = 'none';
      }
      if(compareDividerEl) compareDividerEl.style.display = 'none';
    }
    if(modeSwitch) modeSwitch.addEventListener('click', switchMode);

    function populateYearDropdown(dd){
      const opts = dd.querySelector('.options-scroll');
      if(!opts) return;
      for(let y=2024; y>=2014; y--){
        const div = document.createElement('div');
        div.className='option';
        div.dataset.value=String(y);
        div.textContent=String(y);
        opts.appendChild(div);
      }
    }

    function setupDropdown(dd){
      const selected = dd.querySelector('.selected');
      const optionsWrap = dd.querySelector('.options');
      if(dd.classList.contains('year-dropdown')) populateYearDropdown(dd);
      selected.addEventListener('click', (e)=>{
        e.stopPropagation();
        document.querySelectorAll('.dropdown.open').forEach(other=>{
          if(other!==dd) other.classList.remove('open');
        });
        dd.classList.toggle('open');
      });
      optionsWrap.addEventListener('click', (e)=>{
        const opt = e.target.closest('.option');
        if(!opt) return;
        selected.innerHTML = opt.innerHTML;
        dd.dataset.value = opt.dataset.value;
        dd.classList.remove('open');
      });
      document.addEventListener('click', (e)=>{
        if(!dd.contains(e.target)) dd.classList.remove('open');
      });
    }
    document.querySelectorAll('.dropdown').forEach(setupDropdown);

    function averageFinancials(status, yStart, yEnd){
      const isSolvent = status === 'solvent';
      const minYear = Math.min(Number(yStart), Number(yEnd));
      const maxYear = Math.max(Number(yStart), Number(yEnd));
      const sums = Object.create(null);
      const counts = Object.create(null);
      const sumSquares = Object.create(null);
      const values = Object.create(null);
      const skip = new Set(['CIK','year','solvent','TradingSymbol','EntityRegistrantName']);
      if(DATASET_MODE === 'subset'){
        [
          'AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGPTExplanation',
          'AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGPTExplanation',
          'AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGROKExplanation',
          'AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGROKExplanation'
        ].forEach(key => skip.add(key));
      }
      for(const row of DATA.financials){
        if(!row) continue;
        if(!!row.solvent !== isSolvent) continue;
        const ry = Number(row.year);
        if(!ry || ry < minYear || ry > maxYear) continue;
        for(const [k,v] of Object.entries(row)){
          if(skip.has(k)) continue;
          const num = Number(String(v).replace(/,/g,''));
          if(!Number.isFinite(num) || num === 0) continue;
          if(!(k in sums)){
            sums[k] = 0;
            counts[k] = 0;
            sumSquares[k] = 0;
            values[k] = [];
          }
          sums[k] += num;
          counts[k]++;
          sumSquares[k] += num * num;
          values[k].push(num);
        }
      }
      const out = {};
      for(const k in sums){
        if(counts[k] > 0){
          const mean = sums[k] / counts[k];
          const variance = (sumSquares[k] / counts[k]) - mean * mean;
          const stdDev = Math.sqrt(variance);
          const sorted = values[k].slice().sort((a,b)=>a-b);
          const mid = Math.floor(sorted.length/2);
          const median = sorted.length % 2 === 0 ? (sorted[mid-1] + sorted[mid]) / 2 : sorted[mid];
          out[k] = {mean, stdDev, median, count: counts[k]};
        }
      }
      return out;
    }

    function doCompare(){
      currentMode = 'compare';
      searchbarEl.style.display = 'none';
      comparebarEl.style.display = 'flex';
      hero.classList.add('compact');
      if(compareDividerEl) compareDividerEl.style.display = 'none';
      resultsEl.innerHTML = '';
      resultsEl.className = 'fin-grid compare-grid';
      const a = averageFinancials(status1El.dataset.value, year1El.dataset.value, year2El.dataset.value);
      const b = averageFinancials(status2El.dataset.value, year3El.dataset.value, year4El.dataset.value);
      const keys = Object.keys(METRIC_SENTIMENT)
        .filter(k => k in a || k in b);
      for(const k of keys){
        if(!(k in a) || !(k in b)) continue;
        const unit = Object.prototype.hasOwnProperty.call(METRIC_UNITS, k) ? METRIC_UNITS[k] : 'USD';
        const v1 = Math.round(a[k].mean);
        const v2 = Math.round(b[k].mean);
        const med1 = Math.round(a[k].median);
        const med2 = Math.round(b[k].median);
        const sd1 = a[k].stdDev;
        const sd2 = b[k].stdDev;
        const count1 = a[k].count;
        const count2 = b[k].count;
        const item = document.createElement('div');
        item.className = 'fin-item compare clickable';
        const kDiv = document.createElement('div');
        kDiv.className = 'k';
        kDiv.innerHTML = `<span title="expected values">μ</span> ${formatKV(k)}`;
        item.appendChild(kDiv);
        const vDiv = document.createElement('div');
        vDiv.className = 'v';
        const table = document.createElement('table');
        table.className = 'cmp-table';
        const tr = document.createElement('tr');
        const td1 = document.createElement('td');
        td1.className = 'amt left';
        const amtSpan1 = document.createElement('span');
        amtSpan1.className = 'amt-val';
        amtSpan1.innerHTML = formatAmount(v1, unit);
        td1.appendChild(amtSpan1);
        const sdDiv1 = document.createElement('div');
        sdDiv1.className = 'variance';
        sdDiv1.innerHTML = `(<span title="standard deviation">σ</span>: ${sd1.toFixed(2)})`;
        sdDiv1.style.color = 'var(--text)';
        td1.appendChild(sdDiv1);
        const tdSep = document.createElement('td');
        tdSep.className = 'sep';
        tdSep.textContent = '|';
        const td2 = document.createElement('td');
        td2.className = 'amt right';
        const amtSpan2 = document.createElement('span');
        amtSpan2.className = 'amt-val';
        amtSpan2.innerHTML = formatAmount(v2, unit);
        td2.appendChild(amtSpan2);
        const sdDiv2 = document.createElement('div');
        sdDiv2.className = 'variance';
        sdDiv2.innerHTML = `(<span title="standard deviation">σ</span>: ${sd2.toFixed(2)})`;
        sdDiv2.style.color = 'var(--text)';
        td2.appendChild(sdDiv2);
        tr.appendChild(td1);
        tr.appendChild(tdSep);
        tr.appendChild(td2);
        table.appendChild(tr);
        vDiv.appendChild(table);
        item.appendChild(vDiv);
        const sentiment = METRIC_SENTIMENT[k] || 'neutral';
        if(sentiment !== 'neutral'){
          const applyColor = (span,val)=>{
            const pos = val > 0;
            const color = sentiment === 'good'
              ? (pos ? 'var(--good)' : 'var(--bad)')
              : (pos ? 'var(--bad)' : 'var(--good)');
            span.style.color = color;
          };
          if(v1 !== 0) applyColor(amtSpan1, v1);
          if(v2 !== 0) applyColor(amtSpan2, v2);
        }
        const meta = {
          status1: status1El.dataset.value,
          yStart1: year1El.dataset.value,
          yEnd1: year2El.dataset.value,
          status2: status2El.dataset.value,
          yStart2: year3El.dataset.value,
          yEnd2: year4El.dataset.value,
          count1,
          count2
        };
        item.addEventListener('click', ()=>{
          openCompareDetail(k, v1, med1, sd1, v2, med2, sd2, meta);
        });
        resultsEl.appendChild(item);
      }
      statsEl.style.display = 'none';
    }
    if(compareBtn) compareBtn.addEventListener('click', doCompare);

    const PAGE_SIZE = 50;
    let currentResults = [];
    let loadedCount = 0;
    let loading = false;

    let lastQuery = "";

    let activeChart = null;
    function hideActiveChart(){
      if(activeChart){
        if(activeChart.chart) activeChart.chart.destroy();
        activeChart.card.remove();
        activeChart.container.removeEventListener('click', activeChart.listener);
        if(activeChart.item) activeChart.item.style.borderColor = '';
        activeChart = null;
      }
    }

    // Translation map for state of incorporation codes
    const stateNameMap = {
      'AL':'Alabama','AK':'Alaska','AZ':'Arizona','AR':'Arkansas','CA':'California','CO':'Colorado','CT':'Connecticut',
      'DE':'Delaware','FL':'Florida','GA':'Georgia','HI':'Hawaii','ID':'Idaho','IL':'Illinois','IN':'Indiana',
      'IA':'Iowa','KS':'Kansas','KY':'Kentucky','LA':'Louisiana','ME':'Maine','MD':'Maryland','MA':'Massachusetts',
      'MI':'Michigan','MN':'Minnesota','MS':'Mississippi','MO':'Missouri','MT':'Montana','NE':'Nebraska','NV':'Nevada',
      'NH':'New Hampshire','NJ':'New Jersey','NM':'New Mexico','NY':'New York','NC':'North Carolina','ND':'North Dakota',
      'OH':'Ohio','OK':'Oklahoma','OR':'Oregon','PA':'Pennsylvania','RI':'Rhode Island','SC':'South Carolina',
      'SD':'South Dakota','TN':'Tennessee','TX':'Texas','UT':'Utah','VT':'Vermont','VA':'Virginia','WA':'Washington',
      'WV':'West Virginia','WI':'Wisconsin','WY':'Wyoming','DC':'District of Columbia','PR':'Puerto Rico'
    };

    // Sentiment for financial metrics: whether a higher value is generally good or bad
    const METRIC_SENTIMENT = {
      'assets':'good',
      'CurrentAssets':'good',
      'NoncurrentAssets':'good',
      'liabilities':'bad',
      'CurrentLiabilities':'bad',
      'NoncurrentLiabilities':'bad',
      'LiabilitiesAndStockholdersEquity':'neutral',
      'equity':'good',
      'CommonStockValue':'neutral',
      'RetainedEarningsAccumulatedDeficit':'good',
      'AccumulatedOtherComprehensiveIncomeLoss':'neutral',
      'MinorityInterest':'neutral',
      'revenues':'good',
      'SalesRevenueNet':'good',
      'CostOfGoodsSold':'bad',
      'GrossProfit':'good',
      'OperatingExpenses':'bad',
      'SellingGeneralAndAdministrativeExpense':'bad',
      'ResearchAndDevelopmentExpense':'neutral',
      'OperatingIncomeLoss':'good',
      'InterestExpense':'bad',
      'IncomeBeforeIncomeTaxes':'good',
      'IncomeTaxExpenseBenefit':'bad',
      'NetIncomeLoss':'good',
      'PreferredStockDividendsAndOtherAdjustments':'bad',
      'NetIncomeLossAvailableToCommonStockholdersBasic':'good',
      'EarningsPerShareBasic':'good',
      'EarningsPerShareDiluted':'good',
      'WeightedAverageNumberOfSharesOutstandingBasic':'neutral',
      'WeightedAverageNumberOfDilutedSharesOutstanding':'neutral',
      'NetCashProvidedByUsedInOperatingActivities':'good',
      'NetCashProvidedByUsedInInvestingActivities':'neutral',
      'NetCashProvidedByUsedInFinancingActivities':'neutral',
      'CashAndCashEquivalentsPeriodIncreaseDecrease':'good',
      'CashAndCashEquivalentsAtCarryingValue':'good',
      'PaymentsToAcquirePropertyPlantAndEquipment':'neutral',
      'ProceedsFromIssuanceOfCommonStock':'neutral',
      'PaymentsOfDividends':'neutral',
      'RepaymentsOfDebt':'good',
      'ProceedsFromIssuanceOfDebt':'bad',
      'DepreciationAndAmortization':'neutral',
      'InventoryNet':'neutral',
      'AccountsReceivableNetCurrent':'neutral',
      'AccountsPayableCurrent':'bad',
      'Goodwill':'neutral',
      'IntangibleAssetsNetExcludingGoodwill':'neutral',
      'PropertyPlantAndEquipmentNet':'good',
      'LongTermDebtNoncurrent':'bad',
      'ShortTermBorrowings':'bad',
      'IncomeTaxesPayableCurrent':'bad',
      // ratios and scores
      'TL_TA':'bad',
      'Debt_Assets':'bad',
      'EBIT_InterestExpense':'good',
      'EBITDA_InterestExpense':'good',
      'CFO_Liabilities':'good',
      'CFO_DebtService':'good',
      'CurrentRatio':'good',
      'QuickRatio':'good',
      'WC_TA':'good',
      'ROA':'good',
      'OperatingMargin':'good',
      'DaysAR':'bad',
      'DaysINV':'bad',
      'DaysAP':'good',
      'CashConversionCycle':'bad',
      'Accruals':'bad',
      'DividendOmission':'bad',
      'DebtIssuanceSpike':'bad',
      'DebtRepaymentSpike':'good',
      'AltmanZPrime':'good',
      'AltmanZDoublePrime':'good',
      'OhlsonOScore':'bad',
      'OhlsonOScoreProb':'bad',
      'ZmijewskiXScore':'bad',
      'SpringateSScore':'good',
      'TafflerZScore':'good',
      'FulmerHScore':'good',
      'GroverGScore':'good',
      'BeneishMScore':'bad',
      'PiotroskiFScore':'good',
      'AIExpectedLikelihoodOfBankruptcyBase':'bad',
      'AIExpectedLikelihoodOfBankruptcyExtended':'bad'
    };
    if(DATASET_MODE === 'subset'){
      Object.assign(METRIC_SENTIMENT, {
        'AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGPT':'bad',
        'AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGPT':'bad',
        'AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGROK':'bad',
        'AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGROK':'bad'
      });
    }

    function normalize(s){
      return (s||"").toString().toLowerCase().replace(/[^a-z0-9\.\- ]+/g,' ').replace(/\s+/g,' ').trim();
    }

    // Basic scoring: prioritize (1) start-with on company, (2) contains in company/ticker, (3) other fields
    function score(record, query){
      const cik = String(record.CIK||"").trim();
      const company = record.CompanyName||"";
      const sicDesc = record.SICDescription||"";
      const city = record.BusinessAddressCity||"";
      const state = record.BusinessAddressState||"";
      const exch = record.exchange||"";
      const sym  = symbolByCIK.get(cik) || "";

      const nq = normalize(query);
      if(!nq) return 0;
      let s = 0;
      const nc = normalize(company);
      const ns = normalize(sym);
      const fields = [nc, ns, normalize(cik), normalize(sicDesc), normalize(city), normalize(state), normalize(exch)];

      if(nc.startsWith(nq)) s += 200;
      if(ns && ns === nq) s += 180;
      if(nc.includes(nq)) s += 120;
      if(ns && ns.includes(nq)) s += 100;
      if(String(cik) === query.trim()) s += 160;

      // token coverage
      const tokens = nq.split(' ');
      for(const t of tokens){
        for(const f of fields){
          if(t && f.includes(t)) { s += 20; break; }
        }
      }
      // Slight boost if in same SIC bucket
      if(String(record.IndustrySICCode||"").includes(query)) s += 10;

      return s;
    }

    function formatKV(k){
      // prettify key names a bit
      return String(k).replace(/([a-z0-9])([A-Z])/g,'$1 $2')
                      .replace(/_/g,' ')
                      .replace(/\bCik\b/i,'CIK')
                      .replace(/\bUrl\b/i,'URL');
    }

    function fmt(v){
      if(v === null || v === undefined) return "";
      const s = String(v);
      return s;
    }

    const METRIC_UNITS = {
      'EarningsPerShareBasic': 'USD/share',
      'EarningsPerShareDiluted': 'USD/share',
      'WeightedAverageNumberOfSharesOutstandingBasic': 'shares',
      'WeightedAverageNumberOfDilutedSharesOutstanding': 'shares',
      // units for ratios and scores
      'TL_TA': '',
      'Debt_Assets': '',
      'EBIT_InterestExpense': '',
      'EBITDA_InterestExpense': '',
      'CFO_Liabilities': '',
      'CFO_DebtService': '',
      'CurrentRatio': '',
      'QuickRatio': '',
      'WC_TA': '',
      'ROA': '',
      'OperatingMargin': '',
      'DaysAR': 'days',
      'DaysINV': 'days',
      'DaysAP': 'days',
      'CashConversionCycle': 'days',
      'Accruals': '',
      'DividendOmission': '',
      'DebtIssuanceSpike': '',
      'DebtRepaymentSpike': '',
      'AltmanZPrime': '',
      'AltmanZDoublePrime': '',
      'OhlsonOScore': '',
      'OhlsonOScoreProb': '',
      'ZmijewskiXScore': '',
      'SpringateSScore': '',
      'TafflerZScore': '',
      'FulmerHScore': '',
      'GroverGScore': '',
      'BeneishMScore': '',
      'PiotroskiFScore': '',
      'AIExpectedLikelihoodOfBankruptcyBase': '%',
      'AIExpectedLikelihoodOfBankruptcyExtended': '%'
    };
    if(DATASET_MODE === 'subset'){
      Object.assign(METRIC_UNITS, {
        'AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGPT': '%',
        'AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGPT': '%',
        'AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGROK': '%',
        'AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGROK': '%'
      });
    }

    const METRIC_EXPLANATION_MAP = DATASET_MODE === 'subset' ? {
      'AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGPT': 'AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGPTExplanation',
      'AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGPT': 'AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGPTExplanation',
      'AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGROK': 'AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGROKExplanation',
      'AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGROK': 'AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGROKExplanation'
    } : {};

    function formatAmount(val, unit='USD'){
      const num = Number(String(val).replace(/,/g, ''));
      if(isNaN(num)) return null;
      const sign = num < 0 ? '-' : '';
      const abs = Math.abs(num);
      const intPart = Math.trunc(abs);
      const fracPart = abs - intPart;
      const parts = intPart.toLocaleString('en-US').split(',');
      const digits = parts.join('').length;
      let mainParts = parts;
      let fadedParts = [];
      if(digits > 7){
        mainParts = parts.slice(0, -2);
        fadedParts = parts.slice(-2);
      } else if(digits > 3){
        mainParts = parts.slice(0, -1);
        fadedParts = parts.slice(-1);
      }
      const prefix = mainParts.join(',');
      const faded = fadedParts.length ? ',' + fadedParts.join(',') : '';
      const fadedHTML = faded ? `<span style="opacity:.5">${faded}</span>` : '';
      const decimal = fracPart ? '.' + abs.toFixed(2).split('.')[1] : '';
      const decimalHTML = decimal ? `<span style="opacity:.7">${decimal}</span>` : '';
      const unitHTML = unit ? ` <span style="opacity:.3">${unit}</span>` : '';
      return `${sign}${prefix}${fadedHTML}${decimalHTML}${unitHTML}`;
    }

    function formatAmountPlain(val, unit='USD'){
      const num = Number(String(val).replace(/,/g, ''));
      if(isNaN(num)) return String(val);
      const sign = num < 0 ? '-' : '';
      const abs = Math.abs(num);
      const main = abs.toLocaleString('en-US', {maximumFractionDigits:2});
      return unit ? `${sign}${main} ${unit}` : `${sign}${main}`;
    }

    function renderResults(list){
      currentResults = list;
      loadedCount = 0;
      resultsEl.innerHTML = "";
      window.removeEventListener('scroll', handleScroll);
      loadMore();
      window.addEventListener('scroll', handleScroll);
    }

    function handleScroll(){
      if(loading) return;
      if(window.innerHeight + window.scrollY >= document.body.offsetHeight - 100){
        loadMore();
      }
    }

    function loadMore(){
      if(loadedCount >= currentResults.length) return;
      loading = true;
      resultsEl.querySelectorAll('.card.skeleton').forEach(el=>el.remove());
      const frag = document.createDocumentFragment();
      const chunk = currentResults.slice(loadedCount, loadedCount + PAGE_SIZE);
      for(const r of chunk){
        const card = document.createElement('div');
        card.className = 'card';
        const cik = String(r.CIK||"").trim();
        const sym = symbolByCIK.get(cik) || "";
        const statusDot = `<span class="status-dot ${r.solvent ? 'good' : 'bad'}" title="${r.solvent ? 'solvent' : 'bankrupt'}"></span>`;
        card.innerHTML = `
          <div class="meta">
            <span class="pill">CIK: ${cik||"-"}</span>
            ${sym ? `<span class="pill">Ticker: ${sym}</span>` : ``}
            ${r.exchange ? `<span class="pill">${fmt(r.exchange)}</span>` : ``}
          </div>
          <h3>${statusDot}${fmt(r.CompanyName)||"Unknown Company"}</h3>
          <div class="meta">
            ${r.SICDescription ? `<span>${fmt(r.SICDescription)}</span>`:""}
            ${r.BusinessAddressCity ? `<span> · ${fmt(r.BusinessAddressCity)}${r.BusinessAddressState? ", "+fmt(r.BusinessAddressState):""}</span>`:""}
          </div>
        `;
        card.addEventListener('click', ()=> openModal(cik));
        frag.appendChild(card);
      }
      resultsEl.appendChild(frag);
      loadedCount += chunk.length;

      const remaining = currentResults.length - loadedCount;
      if(remaining > 0){
        const pfrag = document.createDocumentFragment();
        const count = Math.min(PAGE_SIZE, remaining);
        for(let i=0;i<count;i++){
          const ph = document.createElement('div');
          ph.className = 'card skeleton';
          pfrag.appendChild(ph);
        }
        resultsEl.appendChild(pfrag);
      }else{
        window.removeEventListener('scroll', handleScroll);
      }
      loading = false;
    }

    function openModal(cik){
      const base = byCIK.get(cik) || {};
      const sym  = symbolByCIK.get(cik) || "";
      modalContent.innerHTML = "";
      const title = document.createElement('div');
      title.className = 'title';
      const statusDot = `<span class="status-dot ${base.solvent ? 'good' : 'bad'}" title="${base.solvent ? 'solvent' : 'bankrupt'}"></span>`;
      title.innerHTML = `
        <div>
          <h2 style="margin:0 0 2px 0">${statusDot}${fmt(base.CompanyName)||"Company"}</h2>
          <div class="meta" style="margin-top:4px">
            <span class="pill">CIK: ${cik}</span>
            ${sym ? `<span class="pill">Ticker: ${sym}</span>`:""}
            ${base.exchange ? `<span class="pill">${fmt(base.exchange)}</span>`:""}
            ${base.SICDescription ? `<span class="pill">${fmt(base.SICDescription)}</span>`:""}
          </div>
        </div>
        <div class="meta">
          ${base.Website ? `<a class="pill" href="${fmt(base.Website)}" target="_blank" rel="noopener">Website</a>`:""}
          ${base.InvestorWebsite ? `<a class="pill" href="${fmt(base.InvestorWebsite)}" target="_blank" rel="noopener">IR</a>`:""}
        </div>
      `;
      modalContent.appendChild(title);

      const existingClose = modal.querySelector('.close-btn');
      if(existingClose) existingClose.remove();
      const closeBtn = document.createElement('button');
      closeBtn.className = 'close-btn';
      closeBtn.innerHTML = '<strong>X</strong>';
      closeBtn.title = 'close';
      closeBtn.addEventListener('click', () => closeModal());
      modal.appendChild(closeBtn);

      // Company details (from main.csv) — show all non-empty fields
      const kv = document.createElement('div');
      kv.className = 'kv';
      kv.style.marginTop = '16px';
      for(const [k,v] of Object.entries(base)){
        if(v===undefined || v===null) continue;
        let sv = String(v).trim();
        if(!sv) continue;
        if(k === 'StateOfIncorporation'){
          const full = stateNameMap[sv.toUpperCase()];
          if(full) sv = `${sv} (${full})`;
        }
        const kEl = document.createElement('div'); kEl.className='k'; kEl.textContent = formatKV(k);
        const vEl = document.createElement('div'); vEl.className='v';
        if(k === 'solvent'){
          vEl.textContent = sv;
          vEl.style.color = v ? 'var(--good)' : 'var(--bad)';
        } else if(k === 'FilingURL'){
          const a = document.createElement('a');
          a.href = sv; a.textContent = sv; a.target='_blank'; a.rel='noopener';
          a.style.opacity = 0.5;
          vEl.appendChild(a);
        } else {
          vEl.textContent = sv;
        }
        kv.appendChild(kEl); kv.appendChild(vEl);
      }
      modalContent.appendChild(kv);

      // Years
      const years = Array.from((yearsByCIK.get(cik)||new Set())).sort((a,b)=> String(b).localeCompare(String(a)));
      if(years.length){
        const yearsWrap = document.createElement('div'); yearsWrap.className='years';
        years.forEach((y,idx)=>{
          const b = document.createElement('button');
          b.className = 'year-btn'+(idx===0?' active':'');
          b.textContent = y;
          b.addEventListener('click', ()=>{
            modalContent.querySelectorAll('.year-btn').forEach(x=>x.classList.remove('active'));
            b.classList.add('active');
            renderYear(cik, y);
          });
          yearsWrap.appendChild(b);
        });
        modalContent.appendChild(yearsWrap);
        // initial render
        renderYear(cik, years[0]);
      }

      overlay.classList.add('show');
      document.body.style.overflow='hidden';
    }

    function openCompareDetail(key, v1, med1, sd1, v2, med2, sd2, meta){
      hideActiveChart();
      modalContent.innerHTML = "";
      const title = document.createElement('div');
      title.className = 'title';
      title.innerHTML = `<h2 style="margin:0 0 16px 0"><span title="expected values">μ</span> ${formatKV(key)}</h2>`;
      modalContent.appendChild(title);

      const existingClose = modal.querySelector('.close-btn');
      if(existingClose) existingClose.remove();
      const closeBtn = document.createElement('button');
      closeBtn.className = 'close-btn';
      closeBtn.innerHTML = '<strong>X</strong>';
      closeBtn.title = 'close';
      closeBtn.addEventListener('click', () => closeModal());
      modal.appendChild(closeBtn);

      const table = document.createElement('table');
      table.className = 'cmp-table';
      const tr = document.createElement('tr');
      const unit = Object.prototype.hasOwnProperty.call(METRIC_UNITS, key) ? METRIC_UNITS[key] : 'USD';
      const td1 = document.createElement('td');
      td1.className = 'amt left';
      const amtSpan1 = document.createElement('span');
      amtSpan1.className = 'amt-val';
      amtSpan1.innerHTML = formatAmount(v1, unit);
      td1.appendChild(amtSpan1);
      const medDiv1 = document.createElement('div');
      medDiv1.className = 'variance';
      medDiv1.innerHTML = `<span class="median-label">(median: </span><span class="median-val">${formatAmount(med1, unit)}</span><span class="median-label">)</span>`;
      const medSpan1 = medDiv1.querySelector('.median-val');
      const medLabels1 = medDiv1.querySelectorAll('.median-label');
      medLabels1.forEach(span => span.style.opacity = '0.5');
      td1.appendChild(medDiv1);
      const sdDiv1 = document.createElement('div');
      sdDiv1.className = 'variance';
      sdDiv1.innerHTML = `(<span title="standard deviation">σ</span>: ${sd1.toFixed(2)})`;
      td1.appendChild(sdDiv1);
      const tdSep = document.createElement('td');
      tdSep.className = 'sep';
      tdSep.textContent = '|';
      const td2 = document.createElement('td');
      td2.className = 'amt right';
      const amtSpan2 = document.createElement('span');
      amtSpan2.className = 'amt-val';
      amtSpan2.innerHTML = formatAmount(v2, unit);
      td2.appendChild(amtSpan2);
      const medDiv2 = document.createElement('div');
      medDiv2.className = 'variance';
      medDiv2.innerHTML = `<span class="median-label">(median: </span><span class="median-val">${formatAmount(med2, unit)}</span><span class="median-label">)</span>`;
      const medSpan2 = medDiv2.querySelector('.median-val');
      const medLabels2 = medDiv2.querySelectorAll('.median-label');
      medLabels2.forEach(span => span.style.opacity = '0.5');
      td2.appendChild(medDiv2);
      const sdDiv2 = document.createElement('div');
      sdDiv2.className = 'variance';
      sdDiv2.innerHTML = `(<span title="standard deviation">σ</span>: ${sd2.toFixed(2)})`;
      td2.appendChild(sdDiv2);
      tr.appendChild(td1);
      tr.appendChild(tdSep);
      tr.appendChild(td2);
      table.appendChild(tr);
      modalContent.appendChild(table);

      const chartTypeWrap = document.createElement('div');
      chartTypeWrap.className = 'years';
      chartTypeWrap.style.marginTop = '40px';
      chartTypeWrap.style.marginBottom = '16px';
      const candleBtn = document.createElement('button');
      candleBtn.className = 'year-btn active';
      candleBtn.textContent = 'candles';
      const barBtn = document.createElement('button');
      barBtn.className = 'year-btn';
      barBtn.textContent = 'bars';
      chartTypeWrap.appendChild(candleBtn);
      chartTypeWrap.appendChild(barBtn);
      modalContent.appendChild(chartTypeWrap);

      const chartCard = document.createElement('div');
      chartCard.className = 'chart-card';
      chartCard.style.marginTop = '0';
      const canvas = document.createElement('canvas');
      chartCard.appendChild(canvas);
      modalContent.appendChild(chartCard);
      const labels = [`${meta.status1}, ${meta.yStart1}-${meta.yEnd1}`, `${meta.status2}, ${meta.yStart2}-${meta.yEnd2}`];
      const candleData = [
        {x: labels[0], o: v1, c: v1, h: v1 + sd1, l: v1 - sd1},
        {x: labels[1], o: v2, c: v2, h: v2 + sd2, l: v2 - sd2}
      ];
      const linkColor = getComputedStyle(document.documentElement).getPropertyValue('--blue').trim();
      const expectedDataset = {label:'expected value μ', data:[v1, v2], backgroundColor:[linkColor, linkColor]};
      const candleDataset = {
        label:'standard deviation σ',
        type:'candlestick',
        data: candleData,
        borderColor: linkColor,
        borderWidth: 2,
        color:{up: linkColor, down: linkColor, unchanged: linkColor},
        parsing: false
      };
      const wickCaps = {
        id: 'wickCaps',
        afterDatasetsDraw(chart){
          const idx = chart.data.datasets.findIndex(ds => ds.type === 'candlestick');
          if(idx === -1) return;
          const dataset = chart.data.datasets[idx];
          const meta = chart.getDatasetMeta(idx);
          const yScale = chart.scales.y;
          const ctx = chart.ctx;
          ctx.save();
          ctx.strokeStyle = linkColor;
          ctx.lineWidth = 2;
          meta.data.forEach((bar, i)=>{
            const x = bar.x;
            const yHigh = yScale.getPixelForValue(dataset.data[i].h);
            const yLow = yScale.getPixelForValue(dataset.data[i].l);
            const cap = 15;
            ctx.beginPath();
            ctx.moveTo(x, yHigh);
            ctx.lineTo(x, yLow);
            ctx.moveTo(x - cap, yHigh);
            ctx.lineTo(x + cap, yHigh);
            ctx.moveTo(x - cap, yLow);
            ctx.lineTo(x + cap, yLow);
            ctx.stroke();
          });
          ctx.restore();
        }
      };
      const ctx = canvas.getContext('2d');
      let candleMax = Math.max(v1 + sd1, v2 + sd2);
      let candleMin = Math.min(0, v1 - sd1, v2 - sd2);
      if(candleMax === candleMin){
        candleMax = candleMax === 0 ? 1 : candleMax * 1.1;
        candleMin = 0;
      }
      let barMax = Math.max(v1, v2);
      let barMin = Math.min(0, v1, v2);
      if(barMax === barMin){
        barMax = barMax === 0 ? 1 : barMax * 1.1;
        barMin = 0;
      }
      const chart = new Chart(ctx, {
        type:'bar',
        data:{
          labels,
          datasets:[expectedDataset, candleDataset]
        },
        options:{
          responsive:true,
          maintainAspectRatio:false,
          plugins:{legend:{display:false}},
          scales:{y:{min:candleMin, max:candleMax, ticks:{callback:(v)=>formatAmountPlain(v, unit)}}}
        },
        plugins:[wickCaps]
      });

      const showCandles = () => {
        chart.config.data.datasets = [expectedDataset, candleDataset];
        if(!chart.config.plugins.includes(wickCaps)) chart.config.plugins.push(wickCaps);
        chart.options.scales.y.min = candleMin;
        chart.options.scales.y.max = candleMax;
        chart.update();
      };
      const showBars = () => {
        chart.config.data.datasets = [expectedDataset];
        chart.config.plugins = chart.config.plugins.filter(p=>p!==wickCaps);
        chart.options.scales.y.min = barMin;
        chart.options.scales.y.max = barMax;
        chart.update();
      };
      candleBtn.addEventListener('click', () => {
        barBtn.classList.remove('active');
        candleBtn.classList.add('active');
        showCandles();
      });
      barBtn.addEventListener('click', () => {
        candleBtn.classList.remove('active');
        barBtn.classList.add('active');
        showBars();
      });

      if(meta.yStart1 !== meta.yEnd1 || meta.yStart2 !== meta.yEnd2){
        const timelineCard = document.createElement('div');
        timelineCard.className = 'chart-card';
        timelineCard.style.marginTop = '20px';
        const timelineCanvas = document.createElement('canvas');
        timelineCard.appendChild(timelineCanvas);
        modalContent.appendChild(timelineCard);

        const brownColor = getComputedStyle(document.documentElement).getPropertyValue('--brown').trim();
        const orangeColor = getComputedStyle(document.documentElement).getPropertyValue('--orange').trim();

        const startYear = Math.min(meta.yStart1, meta.yEnd1, meta.yStart2, meta.yEnd2);
        const endYear = Math.max(meta.yStart1, meta.yEnd1, meta.yStart2, meta.yEnd2);
        const min1 = Math.min(meta.yStart1, meta.yEnd1);
        const max1 = Math.max(meta.yStart1, meta.yEnd1);
        const min2 = Math.min(meta.yStart2, meta.yEnd2);
        const max2 = Math.max(meta.yStart2, meta.yEnd2);

        const series1 = {};
        const series2 = {};
        const isSolvent1 = meta.status1 === 'solvent';
        const isSolvent2 = meta.status2 === 'solvent';

        for(const row of DATA.financials){
          if(!row) continue;
          const ry = Number(row.year);
          if(!ry || ry < startYear || ry > endYear) continue;
          const raw = row[key];
          if(raw === undefined) continue;
          const val = Number(String(raw).replace(/,/g,''));
          if(!Number.isFinite(val) || val === 0) continue;
          if(!!row.solvent === isSolvent1 && ry >= min1 && ry <= max1){
            if(!(ry in series1)) series1[ry] = {sum:0,count:0};
            series1[ry].sum += val;
            series1[ry].count++;
          }
          if(!!row.solvent === isSolvent2 && ry >= min2 && ry <= max2){
            if(!(ry in series2)) series2[ry] = {sum:0,count:0};
            series2[ry].sum += val;
            series2[ry].count++;
          }
        }

        const labels2 = [];
        const d1 = [];
        const d2 = [];
        for(let y=startYear; y<=endYear; y++){
          labels2.push(String(y));
          d1.push(series1[y] ? series1[y].sum/series1[y].count : null);
          d2.push(series2[y] ? series2[y].sum/series2[y].count : null);
        }

        const values = d1.concat(d2).filter(v => v !== null);
        let maxLine = Math.max(...values);
        let minLine = Math.min(0, ...values);
        if(values.length === 0 || maxLine === minLine){
          maxLine = maxLine === 0 ? 1 : maxLine * 1.1;
          minLine = 0;
        }

        new Chart(timelineCanvas.getContext('2d'), {
          type:'line',
          data:{
            labels:labels2,
            datasets:[
              {label:`${meta.status1}, ${meta.yStart1}-${meta.yEnd1}`, data:d1, borderColor:orangeColor, backgroundColor:orangeColor, spanGaps:false, fill:false},
              {label:`${meta.status2}, ${meta.yStart2}-${meta.yEnd2}`, data:d2, borderColor:brownColor, backgroundColor:brownColor, spanGaps:false, fill:false}
            ]
          },
          options:{
            responsive:true,
            maintainAspectRatio:false,
            scales:{y:{min:minLine, max:maxLine, ticks:{callback:(v)=>formatAmountPlain(v, unit)}}}
          }
        });
      }


      const info = document.createElement('div');
      info.style.marginTop = '48px';
      info.style.opacity = '0.3';
      info.innerHTML = `(comparison: <span class="dot ${meta.status1==='solvent'?'green':'red'}"></span>&thinsp;${meta.status1}, ${meta.yStart1} to ${meta.yEnd1} (${meta.count1.toLocaleString('en-US')} observations) | <span class="dot ${meta.status2==='solvent'?'green':'red'}"></span>&thinsp;${meta.status2}, ${meta.yStart2} to ${meta.yEnd2} (${meta.count2.toLocaleString('en-US')} observations))`;
      modalContent.appendChild(info);

      const sentiment = METRIC_SENTIMENT[key] || 'neutral';
      if(sentiment !== 'neutral'){
        const applyColor = (span,val)=>{
          const pos = val > 0;
          const color = sentiment === 'good'
            ? (pos ? 'var(--good)' : 'var(--bad)')
            : (pos ? 'var(--bad)' : 'var(--good)');
          span.style.color = color;
        };
        if(v1 !== 0) applyColor(amtSpan1, v1);
        if(v2 !== 0) applyColor(amtSpan2, v2);
        if(med1 !== 0){
          applyColor(medSpan1, med1);
          medLabels1.forEach(span => applyColor(span, med1));
        }
        if(med2 !== 0){
          applyColor(medSpan2, med2);
          medLabels2.forEach(span => applyColor(span, med2));
        }
      }

      overlay.classList.add('show');
      document.body.style.overflow='hidden';
    }

    function toggleChart(cik, key, item){
      if(activeChart && activeChart.key === key){
        hideActiveChart();
        return;
      }
      hideActiveChart();
      const years = Array.from((yearsByCIK.get(cik)||new Set())).sort();
      const labels = [];
      const data = [];
      let nonZeroCount = 0;
      for(const y of years){
        const rec = finByCIKYear.get(cik+'|'+y);
        const raw = rec ? fmt(rec[key]).trim() : '';
        const num = Number(String(raw).replace(/,/g,''));
        if(raw === '' || Number.isNaN(num)) continue;
        labels.push(y);
        data.push(num);
        if(num !== 0) nonZeroCount++;
      }
      if(nonZeroCount < 2) return;
      const grid = item.closest('.fin-grid');
      if(!grid) return;
      const chartCard = document.createElement('div');
      chartCard.className = 'chart-card';
      chartCard.style.gridColumn = '1 / -1';
      const canvas = document.createElement('canvas');
      chartCard.appendChild(canvas);
      const children = Array.from(grid.children);
      const idx = children.indexOf(item);
      const cols = getComputedStyle(grid).gridTemplateColumns.split(' ').length || 1;
      const rowEnd = Math.min(children.length - 1, Math.floor(idx / cols) * cols + cols - 1);
      const anchor = children[rowEnd];
      anchor.insertAdjacentElement('afterend', chartCard);
      item.style.borderColor = 'var(--blue)';
      const ctx = canvas.getContext('2d');
      const unit = Object.prototype.hasOwnProperty.call(METRIC_UNITS, key) ? METRIC_UNITS[key] : 'USD';
      const chart = new Chart(ctx, {
        type:'line',
        data:{ labels, datasets:[{ label: formatKV(key), data, borderColor:'#00aeef', backgroundColor:'rgba(0,174,239,0.2)', tension:0.1 }] },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{ ticks:{ callback:(v)=>formatAmountPlain(v, unit) }}} }
      });
      chartCard.addEventListener('click', (ev)=>{ ev.stopPropagation(); hideActiveChart(); });
      const container = modal;
      const listener = (ev)=>{
        if(ev.target.closest('.chart-card') || ev.target.closest('.fin-item') === item) return;
        hideActiveChart();
      };
      container.addEventListener('click', listener);
      activeChart = {card: chartCard, chart, key, container, listener, item};
    }

    function renderYear(cik, year){
      // remove previous fin + links section if present
      const old = modalContent.querySelector('#year-section');
      if(old) old.remove();

      const sec = document.createElement('section');
      sec.id = 'year-section';
      sec.style.marginTop = '16px';

      const fin = finByCIKYear.get(cik+'|'+year) || null;
      const prevYear = String(Number(year) - 1);
      const prevFin = finByCIKYear.get(cik+'|'+prevYear) || null;
      if(fin){
        const grid = document.createElement('div'); grid.className='fin-grid';
        // show everything except a few metadata keys
        const omit = new Set(['idpk','CIK','year','EntityRegistrantName','EntityCentralIndexKey','TradingSymbol','EntityIncorporationStateCountryCode','EntityFilerCategory','DocumentType','AmendmentFlag','DocumentPeriodEndDate','DocumentFiscalPeriodFocus','DocumentFiscalYearFocus','CurrentFiscalYearEndDate','solvent']);
        if(DATASET_MODE === 'subset'){
          [
            'AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGPTExplanation',
            'AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGPTExplanation',
            'AIExpectedLikelihoodOfBankruptcyBaseStrongerModelGROKExplanation',
            'AIExpectedLikelihoodOfBankruptcyExtendedStrongerModelGROKExplanation'
          ].forEach(key => omit.add(key));
        }
        for(const [k,v] of Object.entries(fin)){
          if(omit.has(k)) continue;
          const val = fmt(v).trim();
          const num = Number(String(val).replace(/,/g,''));
          const item = document.createElement('div');
          item.className = 'fin-item' + (val === '' ? ' empty' : (!Number.isNaN(num) && num === 0 ? ' zero' : ''));
          const unit = Object.prototype.hasOwnProperty.call(METRIC_UNITS, k) ? METRIC_UNITS[k] : 'USD';
          const formatted = formatAmount(val, unit);
          const sentiment = METRIC_SENTIMENT[k] || 'neutral';
          let pctHtml = '';
          if(prevFin && Object.prototype.hasOwnProperty.call(prevFin, k)){
            const prevRaw = fmt(prevFin[k]).trim();
            const currNum = num;
            const prevNum = Number(String(prevRaw).replace(/,/g,''));
            if(prevRaw !== '' && !Number.isNaN(currNum) && !Number.isNaN(prevNum) && prevNum !== 0){
              const pct = Math.round((currNum - prevNum) / Math.abs(prevNum) * 100);
              const absPct = Math.abs(pct);
              let op = 0.5;
              if(absPct === 0) op = 0.3;
              else if(absPct < 10) op = 0.4;
              const sign = pct > 0 ? '+' : '';
              let pctColor = '';
              if(pct !== 0 && (sentiment === 'good' || sentiment === 'bad')){
                const pctPositive = pct > 0;
                pctColor = sentiment === 'good'
                  ? (pctPositive ? 'var(--good)' : 'var(--bad)')
                  : (pctPositive ? 'var(--bad)' : 'var(--good)');
              }
              const colorAttr = pctColor ? `; color:${pctColor}` : '';
              pctHtml = `<span class="pct" style="opacity:${op}${colorAttr}" title="percentage change relative to previous year">${sign}${pct}%</span>`;
            }
          }
          const amountHTML = formatted !== null ? formatted : val;
          item.innerHTML = `<div class="k">${formatKV(k)}</div><div class="v"><span class="amt">${amountHTML}</span>${pctHtml}</div>`;
          if(DATASET_MODE === 'subset'){
            const explanationKey = METRIC_EXPLANATION_MAP[k];
            if(explanationKey && Object.prototype.hasOwnProperty.call(fin, explanationKey)){
              const explanation = fmt(fin[explanationKey]).trim();
              if(explanation) item.title = explanation;
            }
          }
          if(sentiment !== 'neutral' && !Number.isNaN(num) && num !== 0){
            const amtPositive = num > 0;
            const amtColor = sentiment === 'good'
              ? (amtPositive ? 'var(--good)' : 'var(--bad)')
              : (amtPositive ? 'var(--bad)' : 'var(--good)');
            const amtSpan = item.querySelector('.amt');
            if(amtSpan) amtSpan.style.color = amtColor;
          }
          const allYears = Array.from((yearsByCIK.get(cik)||new Set()));
          const history = [];
          for(const y of allYears){
            const rec = finByCIKYear.get(cik+'|'+y);
            const raw = rec ? fmt(rec[k]).trim() : '';
            const numv = Number(String(raw).replace(/,/g,''));
            if(raw !== '' && !Number.isNaN(numv) && numv !== 0) history.push(numv);
          }
          if(history.length > 1){
            item.classList.add('clickable');
            item.addEventListener('click', (e)=>{ e.stopPropagation(); toggleChart(cik, k, item); });
          }
          grid.appendChild(item);
        }
        sec.appendChild(grid);
      }

      // Links (Annual first, then Q1..Q4) as square cards
      const rep = reportsByCIKYear.get(cik+'|'+year) || null;
      const links = [];
      if(rep){
        if(rep.AnnualReportLink) links.push({label:'AR', url: rep.AnnualReportLink, title:'open annual report'});
        if(rep.QuarterlyReportLinkQ1) links.push({label:'Q1', url: rep.QuarterlyReportLinkQ1, title:'open quarterly report for quarter Q1'});
        if(rep.QuarterlyReportLinkQ2) links.push({label:'Q2', url: rep.QuarterlyReportLinkQ2, title:'open quarterly report for quarter Q2'});
        if(rep.QuarterlyReportLinkQ3) links.push({label:'Q3', url: rep.QuarterlyReportLinkQ3, title:'open quarterly report for quarter Q3'});
        if(rep.QuarterlyReportLinkQ4) links.push({label:'Q4', url: rep.QuarterlyReportLinkQ4, title:'open quarterly report for quarter Q4'});
      }
      if(links.length){
        const lwrap = document.createElement('div'); lwrap.className='links';
        for(const L of links){
          const a = document.createElement('a'); a.className='link-card'; a.href = L.url; a.target='_blank'; a.rel='noopener';
          a.textContent = L.label;
          if(L.title) a.title = L.title;
          lwrap.appendChild(a);
        }
        sec.appendChild(lwrap);
      }

      modalContent.appendChild(sec);
    }

    function closeModal(ev){
      if(ev && ev.target && ev.target.closest && ev.target.closest('.modal')) return;
      hideActiveChart();
      overlay.classList.remove('show');
      modalContent.innerHTML = "";
      const cb = modal.querySelector('.close-btn');
      if(cb) cb.remove();
      document.body.style.overflow='';
    }

    overlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeModal(e) });

    function doSearch(){
      const query = qEl.value.trim();
      lastQuery = query;
      goEl.classList.add('hidden');
      resultsEl.className = 'grid';
      if(!query){
        resultsEl.innerHTML = "";
        statsEl.style.display = "none";
        hero.classList.remove('compact');
        searchbarEl.style.display = 'flex';
        comparebarEl.style.display = 'flex';
        if(compareDividerEl) compareDividerEl.style.display = 'block';
        window.removeEventListener('scroll', handleScroll);
        currentResults = [];
        loadedCount = 0;
        return;
      }
      const ranked = DATA.main
        .filter(r => DATASET_MODE !== 'subset' || financialCIKs.has(String(r.CIK||"").trim()))
        .map(r => ({ rec:r, s:score(r, query) }))
        .filter(x => x.s>0)
        .sort((a,b)=> b.s - a.s)
        .map(x=>x.rec);
      renderResults(ranked);
      statsEl.textContent = `${ranked.length} result${ranked.length === 1 ? '' : 's'}`;
      statsEl.style.display = "inline-flex";
      hero.classList.add('compact');
      currentMode = 'search';
      searchbarEl.style.display = 'flex';
      comparebarEl.style.display = 'none';
      if(compareDividerEl) compareDividerEl.style.display = 'none';
    }

    // Show/hide search button depending on input content and last query, submitting on Enter
    qEl.addEventListener('input', ()=>{
      const value = qEl.value.trim();
      const has = value.length>0 && value !== lastQuery;
      goEl.classList.toggle('hidden', !has);
    });
    qEl.addEventListener('keydown', (e)=>{
      if(e.key === 'Enter'){
        e.preventDefault();
        doSearch();
      }
    });
    goEl.addEventListener('click', doSearch);

    // Focus cursor on page load
    window.addEventListener('load', ()=> qEl.focus());
  </script>
</body>
</html>