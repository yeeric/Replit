<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db     = getDb();
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$isHtmx = !empty($_SERVER['HTTP_HX_REQUEST']);

// ── Handle PUT /schedule/save (update session) ───────────────────────────────
if ($method === 'POST' && str_ends_with($path, '/save')) {
    $id       = (int) ($_GET['id'] ?? 0);
    $body     = $_POST;
    $sets     = []; $params = [];
    $map      = ['date' => 'date', 'starttime' => 'starttime', 'endtime' => 'endtime', 'roomlocation' => 'roomlocation'];
    foreach ($map as $form => $col) {
        if (isset($body[$form]) && $body[$form] !== '') {
            $sets[]   = "{$col} = ?";
            $params[] = $body[$form];
        }
    }
    if ($sets) {
        $params[] = $id;
        $db->prepare("UPDATE session SET " . implode(', ', $sets) . " WHERE sessionid = ?")->execute($params);
    }
    // Return updated session row + close modal instruction
    $row = $db->prepare("SELECT sessionid, sessionname, date::text, starttime::text, endtime::text, roomlocation FROM session WHERE sessionid = ?");
    $row->execute([$id]);
    $s = $row->fetch();

    // Tell HTMX to close the dialog
    header('HX-Trigger: closeModal');
    // Return the updated session card HTML
    echo renderSessionCard($s);
    exit;
}

// ── Handle GET /schedule/edit?id=X  (edit modal fragment) ────────────────────
if ($isHtmx && str_ends_with($path, '/edit')) {
    $id   = (int) ($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT sessionid, sessionname, date::text, starttime::text, endtime::text, roomlocation FROM session WHERE sessionid = ?");
    $stmt->execute([$id]);
    $s = $stmt->fetch();
    if (!$s) { echo '<p class="text-red-500">Session not found.</p>'; exit; }

    $stime = substr($s['starttime'], 0, 5);
    $etime = substr($s['endtime'], 0, 5);
    echo <<<HTML
<div class="px-6 py-5">
  <div class="flex items-center justify-between mb-4">
    <h3 class="text-lg font-semibold text-gray-800">Edit Session</h3>
    <button onclick="document.getElementById('edit-modal').close()"
      class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
  </div>
  <p class="text-sm text-gray-500 mb-5">{$s['sessionname']}</p>
  <form hx-post="/schedule/save?id={$s['sessionid']}"
        hx-target="#session-{$s['sessionid']}"
        hx-swap="outerHTML"
        class="space-y-4">
    <div>
      <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
      <input type="date" name="date" value="{$s['date']}"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Start Time</label>
        <input type="time" name="starttime" value="{$stime}"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">End Time</label>
        <input type="time" name="endtime" value="{$etime}"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
    </div>
    <div>
      <label class="block text-xs font-medium text-gray-600 mb-1">Room Location</label>
      <input type="text" name="roomlocation" value="{$s['roomlocation']}"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="document.getElementById('edit-modal').close()"
        class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
      <button type="submit"
        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Save Changes</button>
    </div>
  </form>
</div>
HTML;
    exit;
}

// ── Helper: render one session card ──────────────────────────────────────────
function renderSessionCard(array $s): string {
    $start = substr($s['starttime'], 0, 5);
    $end   = substr($s['endtime'], 0, 5);
    $id    = $s['sessionid'];
    return <<<HTML
<div id="session-{$id}" class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
  <div class="flex justify-between items-start">
    <div>
      <p class="font-semibold text-gray-800">{$s['sessionname']}</p>
      <p class="text-sm text-gray-500 mt-1">🕐 {$start} – {$end} &nbsp;|&nbsp; 📍 {$s['roomlocation']}</p>
    </div>
    <button
      hx-get="/schedule/edit?id={$id}"
      hx-target="#modal-inner"
      hx-swap="innerHTML"
      hx-on::after-request="document.getElementById('edit-modal').showModal()"
      class="text-xs text-blue-600 hover:text-blue-800 border border-blue-200 rounded-lg px-3 py-1.5 hover:bg-blue-50 transition-colors">
      Edit
    </button>
  </div>
</div>
HTML;
}

// ── Handle HTMX partial: sessions for a given date ───────────────────────────
if ($isHtmx && isset($_GET['date'])) {
    $date = $_GET['date'];
    $stmt = $db->prepare("SELECT sessionid, sessionname, date::text, starttime::text, endtime::text, roomlocation FROM session WHERE date = ? ORDER BY starttime");
    $stmt->execute([$date]);
    $sessions = $stmt->fetchAll();
    foreach ($sessions as $s) echo renderSessionCard($s);
    exit;
}

// ── Full page ─────────────────────────────────────────────────────────────────
$dates = $db->query("SELECT DISTINCT date::text FROM session ORDER BY date")->fetchAll(PDO::FETCH_COLUMN);

// Load first date by default
$firstDate = $dates[0] ?? null;
$sessions  = [];
if ($firstDate) {
    $stmt = $db->prepare("SELECT sessionid, sessionname, date::text, starttime::text, endtime::text, roomlocation FROM session WHERE date = ? ORDER BY starttime");
    $stmt->execute([$firstDate]);
    $sessions = $stmt->fetchAll();
}

$tabs = '';
foreach ($dates as $i => $d) {
    $label  = date('D, M j', strtotime($d));
    $active = $i === 0 ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100';
    $tabs  .= "<button class=\"px-4 py-2 text-sm font-medium rounded-lg {$active} transition-colors\"
        hx-get=\"/schedule?date={$d}\"
        hx-target=\"#sessions-grid\"
        hx-swap=\"innerHTML\"
        hx-on::before-request=\"document.querySelectorAll('.date-tab').forEach(b => b.className = b.className.replace('bg-blue-600 text-white','bg-white text-gray-600 hover:bg-gray-100')); this.className = this.className.replace('bg-white text-gray-600 hover:bg-gray-100','bg-blue-600 text-white')\"
    >{$label}</button>";
}

$sessionCards = '';
foreach ($sessions as $s) {
    $sessionCards .= renderSessionCard($s);
}

$content = <<<HTML
<h2 class="text-2xl font-bold text-gray-800 mb-1">Conference Schedule</h2>
<p class="text-gray-500 text-sm mb-6">Select a day to view sessions. Click Edit to update time or location.</p>

<div class="flex flex-wrap gap-2 mb-6">{$tabs}</div>

<div id="sessions-grid" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
  {$sessionCards}
</div>

<dialog id="edit-modal" class="min-w-[400px]"
  hx-on:close="this.innerHTML='<div id=\'modal-inner\'></div>'"
  hx-on:htmx:trigger:closeModal="document.getElementById('edit-modal').close()">
  <div id="modal-inner"></div>
</dialog>

<script>
  document.addEventListener('htmx:trigger', function(e) {
    if (e.detail.trigger === 'closeModal') {
      document.getElementById('edit-modal').close();
    }
  });
</script>
HTML;

renderLayout('Schedule', '/schedule', $content);
