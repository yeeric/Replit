<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db     = getDb();
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$isHtmx = !empty($_SERVER['HTTP_HX_REQUEST']);

// ── PUT /schedule/save ────────────────────────────────────────────────────────
if ($method === 'POST' && str_ends_with($path, '/save')) {
    $id = (int) ($_GET['id'] ?? 0);
    $sets = []; $params = [];
    foreach (['date' => 'date','starttime' => 'starttime','endtime' => 'endtime','roomlocation' => 'roomlocation'] as $f => $col) {
        if (isset($_POST[$f]) && $_POST[$f] !== '') { $sets[] = "{$col} = ?"; $params[] = $_POST[$f]; }
    }
    if ($sets) { $params[] = $id; $db->prepare("UPDATE session SET " . implode(', ', $sets) . " WHERE sessionid = ?")->execute($params); }
    $row = $db->prepare("SELECT sessionid, sessionname, date::text, starttime::text, endtime::text, roomlocation FROM session WHERE sessionid = ?");
    $row->execute([$id]);
    $s = $row->fetch();
    header('HX-Trigger: closeModal');
    echo renderSessionCard($s);
    exit;
}

// ── GET /schedule/edit?id=X ───────────────────────────────────────────────────
if ($isHtmx && str_ends_with($path, '/edit')) {
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT sessionid, sessionname, date::text, starttime::text, endtime::text, roomlocation FROM session WHERE sessionid = ?");
    $stmt->execute([$id]);
    $s = $stmt->fetch();
    if (!$s) { echo '<p class="text-sf-red p-4">Session not found.</p>'; exit; }

    $stime = substr($s['starttime'], 0, 5);
    $etime = substr($s['endtime'], 0, 5);
    $name  = htmlspecialchars($s['sessionname']);
    $room  = htmlspecialchars($s['roomlocation']);

    $inputCls = 'w-full border border-sf-border rounded px-3 py-2 text-sm text-sf-text focus:outline-none focus:ring-2 focus:ring-sf-blue focus:border-sf-blue';

    echo <<<HTML
<div class="px-5 py-5">
  <div class="flex items-center justify-between mb-1">
    <h3 class="text-base font-bold text-sf-text">Edit Session</h3>
    <button onclick="document.getElementById('edit-modal').close()"
      class="text-sf-muted hover:text-sf-text text-xl leading-none px-1">&times;</button>
  </div>
  <p class="text-sf-muted text-sm mb-5">{$name}</p>
  <form hx-post="/schedule/save?id={$s['sessionid']}"
        hx-target="#session-{$s['sessionid']}"
        hx-swap="outerHTML"
        class="space-y-4">
    <div>
      <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Date</label>
      <input type="date" name="date" value="{$s['date']}" class="{$inputCls}">
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Start Time</label>
        <input type="time" name="starttime" value="{$stime}" class="{$inputCls}">
      </div>
      <div>
        <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">End Time</label>
        <input type="time" name="endtime" value="{$etime}" class="{$inputCls}">
      </div>
    </div>
    <div>
      <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Room Location</label>
      <input type="text" name="roomlocation" value="{$room}" class="{$inputCls}">
    </div>
    <div class="flex justify-end gap-2 pt-1">
      <button type="button" onclick="document.getElementById('edit-modal').close()"
        class="px-4 py-2 text-sm border border-sf-border rounded text-sf-text hover:bg-sf-bg transition-colors">Cancel</button>
      <button type="submit"
        class="px-4 py-2 text-sm rounded font-semibold text-white transition-colors"
        style="background:#0176d3;" onmouseover="this.style.background='#014486'" onmouseout="this.style.background='#0176d3'">Save Changes</button>
    </div>
  </form>
</div>
HTML;
    exit;
}

// ── Session card helper ───────────────────────────────────────────────────────
function renderSessionCard(array $s): string {
    $start = substr($s['starttime'], 0, 5);
    $end   = substr($s['endtime'], 0, 5);
    $id    = $s['sessionid'];
    $name  = htmlspecialchars($s['sessionname']);
    $room  = htmlspecialchars($s['roomlocation']);
    return <<<HTML
<div id="session-{$id}" class="bg-white border border-sf-border rounded shadow-sm p-4">
  <div class="flex justify-between items-start gap-3">
    <div>
      <p class="font-semibold text-sf-text text-sm">{$name}</p>
      <p class="text-xs text-sf-muted mt-1">🕐 {$start} – {$end} &nbsp;·&nbsp; 📍 {$room}</p>
    </div>
    <button
      hx-get="/schedule/edit?id={$id}"
      hx-target="#modal-inner"
      hx-swap="innerHTML"
      hx-on::after-request="document.getElementById('edit-modal').showModal()"
      class="text-xs font-semibold border rounded px-3 py-1.5 shrink-0 transition-colors"
      style="color:#0176d3; border-color:#a8c8e8;"
      onmouseover="this.style.background='#d8edff'" onmouseout="this.style.background=''">
      Edit
    </button>
  </div>
</div>
HTML;
}

// ── HTMX partial: sessions for a date ────────────────────────────────────────
if ($isHtmx && isset($_GET['date'])) {
    $stmt = $db->prepare("SELECT sessionid, sessionname, date::text, starttime::text, endtime::text, roomlocation FROM session WHERE date = ? ORDER BY starttime");
    $stmt->execute([$_GET['date']]);
    foreach ($stmt->fetchAll() as $s) echo renderSessionCard($s);
    exit;
}

// ── Full page ─────────────────────────────────────────────────────────────────
$dates     = $db->query("SELECT DISTINCT date::text FROM session ORDER BY date")->fetchAll(PDO::FETCH_COLUMN);
$firstDate = $dates[0] ?? null;
$sessions  = [];
if ($firstDate) {
    $stmt = $db->prepare("SELECT sessionid, sessionname, date::text, starttime::text, endtime::text, roomlocation FROM session WHERE date = ? ORDER BY starttime");
    $stmt->execute([$firstDate]);
    $sessions = $stmt->fetchAll();
}

$tabs = '';
foreach ($dates as $i => $d) {
    $label = date('D, M j', strtotime($d));
    if ($i === 0) {
        $style = 'background:#0176d3; color:white; border-color:#0176d3;';
        $hov   = '';
    } else {
        $style = 'background:white; color:#3e3e3c; border-color:#dddbda;';
        $hov   = " onmouseover=\"this.style.background='#d8edff'\" onmouseout=\"this.style.background='white'\"";
    }
    $tabs .= "<button class=\"px-4 py-2 text-sm font-semibold rounded border transition-colors date-tab\"
        style=\"{$style}\"
        hx-get=\"/schedule?date={$d}\"
        hx-target=\"#sessions-grid\"
        hx-swap=\"innerHTML\"
        hx-on::before-request=\"document.querySelectorAll('.date-tab').forEach(b=>{b.style.background='white';b.style.color='#3e3e3c';b.style.borderColor='#dddbda'}); this.style.background='#0176d3'; this.style.color='white'; this.style.borderColor='#0176d3';\"
        {$hov}>{$label}</button>";
}

$cards = '';
foreach ($sessions as $s) $cards .= renderSessionCard($s);

$content = <<<HTML
<h2 class="text-xl font-bold text-sf-text mb-0.5">Conference Schedule</h2>
<p class="text-sf-muted text-sm mb-5">Select a day to view sessions. Click Edit to update time or location.</p>

<div class="flex flex-wrap gap-2 mb-5">{$tabs}</div>

<div id="sessions-grid" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
  {$cards}
</div>

<dialog id="edit-modal">
  <div id="modal-inner"></div>
</dialog>

<script>
  document.addEventListener('htmx:trigger', function(e) {
    if (e.detail.trigger === 'closeModal') document.getElementById('edit-modal').close();
  });
</script>
HTML;

renderLayout('Schedule', '/schedule', $content);
