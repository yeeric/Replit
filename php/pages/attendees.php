<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db     = getDb();
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$isHtmx = !empty($_SERVER['HTTP_HX_REQUEST']);

function attendeeRow(array $a): string {
    $id    = (int)$a['attendeeid'];
    $name  = htmlspecialchars("{$a['firstname']} {$a['lastname']}");
    $email = htmlspecialchars($a['email']);
    $fee   = '$' . number_format((float)$a['fee'], 2);

    return <<<HTML
<tr id="attendee-row-{$id}" class="border-b border-sf-bordli last:border-0">
  <td class="px-5 py-3 text-sf-muted">{$id}</td>
  <td class="px-5 py-3 font-medium text-sf-text">{$name}</td>
  <td class="px-5 py-3 text-sf-muted">{$email}</td>
  <td class="px-5 py-3 font-semibold" style="color:#2e844a;">{$fee}</td>
  <td class="px-5 py-3 text-right">
    <button hx-delete="/attendees/delete?id={$id}"
      hx-target="#attendee-row-{$id}" hx-swap="outerHTML"
      hx-confirm="Delete {$name}?"
      class="text-xs font-semibold border rounded px-3 py-1.5 transition-colors"
      style="color:#ba0517; border-color:#f4b8b3;"
      onmouseover="this.style.background='#fcdbd9'" onmouseout="this.style.background=''">
      Delete
    </button>
  </td>
</tr>
HTML;
}

// ── POST /attendees/add ───────────────────────────────────────────────────────
if ($method === 'POST' && str_ends_with($path, '/add')) {
    $fn   = trim($_POST['firstname']    ?? '');
    $ln   = trim($_POST['lastname']     ?? '');
    $em   = trim($_POST['email']        ?? '');
    $type = trim($_POST['attendeetype'] ?? '');

    if (!$fn || !$ln || !$em || !$type) {
        http_response_code(400);
        echo '<tr><td colspan="4" class="px-5 py-3 text-sm" style="color:#ba0517;">All fields are required.</td></tr>';
        exit;
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO attendee (firstname, lastname, email, attendeetype) VALUES (?,?,?,?) RETURNING attendeeid, firstname, lastname, email, attendeetype, fee::text");
        $stmt->execute([$fn, $ln, $em, $type]);
        $a  = $stmt->fetch();
        $id = $a['attendeeid'];

        if ($type === 'Student') {
            $room = !empty($_POST['roomnumber']) ? (int)$_POST['roomnumber'] : null;
            $db->prepare("INSERT INTO student (attendeeid, roomnumberstaysin) VALUES (?,?)")->execute([$id, $room]);
        } elseif ($type === 'Professional') {
            $db->prepare("INSERT INTO professional (attendeeid) VALUES (?)")->execute([$id]);
        } elseif ($type === 'Sponsor') {
            $db->prepare("INSERT INTO sponsor (attendeeid, sponsorlevel, companyid) VALUES (?,?,?)")->execute([$id, $_POST['sponsorlevel'] ?? 'Bronze', (int)($_POST['companyid'] ?? 0)]);
        }
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo '<tr><td colspan="4" class="px-5 py-3 text-sm" style="color:#ba0517;">' . htmlspecialchars($e->getMessage()) . '</td></tr>';
        exit;
    }

    header('HX-Trigger: closeAttendeeModal');
    echo attendeeRow($a);
    exit;
}

// ── DELETE /attendees/delete?id=X ─────────────────────────────────────────────
if ($method === 'DELETE' && str_ends_with($path, '/delete')) {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo 'Invalid attendee id.';
        exit;
    }

    try {
        $stmt = $db->prepare("DELETE FROM attendee WHERE attendeeid = ? RETURNING attendeeid");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo 'Attendee not found.';
            exit;
        }
        echo '';
    } catch (Throwable $e) {
        http_response_code(500);
        echo 'Unable to delete attendee.';
    }
    exit;
}

// ── HTMX partial: tab switch ──────────────────────────────────────────────────
function renderAttendeeRows(array $rows): string {
    if (empty($rows)) {
        return '<tr><td colspan="5" class="px-5 py-6 text-center text-sf-muted text-sm">No attendees found.</td></tr>';
    }
    $html = '';
    foreach ($rows as $a) {
        $html .= attendeeRow($a);
    }
    return $html;
}

if ($isHtmx && isset($_GET['type'])) {
    $stmt = $db->prepare("SELECT attendeeid, firstname, lastname, email, fee::text FROM attendee WHERE attendeetype = ? ORDER BY attendeeid");
    $stmt->execute([$_GET['type']]);
    echo renderAttendeeRows($stmt->fetchAll());
    exit;
}

// ── Full page ─────────────────────────────────────────────────────────────────
$activeType = $_GET['type'] ?? 'Student';
$types      = ['Student', 'Professional', 'Sponsor'];

$stmt = $db->prepare("SELECT attendeeid, firstname, lastname, email, fee::text FROM attendee WHERE attendeetype = ? ORDER BY attendeeid");
$stmt->execute([$activeType]);
$attendees = $stmt->fetchAll();

$tabs = '';
foreach ($types as $t) {
    $isActive = $t === $activeType;
    if ($isActive) {
        $tabStyle = 'color:#0176d3; border-bottom:2px solid #0176d3; background:white;';
    } else {
        $tabStyle = 'color:#706e6b; border-bottom:2px solid transparent; background:white;';
    }
    $tabs .= "<button class=\"tab-btn px-5 py-3 text-sm font-semibold transition-colors\"
        style=\"{$tabStyle}\"
        hx-get=\"/attendees?type={$t}\"
        hx-target=\"#attendee-rows\"
        hx-swap=\"innerHTML\"
        hx-on::before-request=\"document.querySelectorAll('.tab-btn').forEach(b=>{b.style.color='#706e6b';b.style.borderBottomColor='transparent'}); this.style.color='#0176d3'; this.style.borderBottomColor='#0176d3';\"
        onmouseover=\"if(this.style.color!='rgb(1, 118, 211)') this.style.color='#3e3e3c'\"
        onmouseout=\"if(this.style.color!='rgb(1, 118, 211)') this.style.color='#706e6b'\">
      {$t}s
    </button>";
}

$companies = $db->query("SELECT companyid, companyname FROM company ORDER BY companyname")->fetchAll();
$companyOptions = '';
foreach ($companies as $c) {
    $companyOptions .= "<option value=\"{$c['companyid']}\">" . htmlspecialchars($c['companyname']) . "</option>";
}

$rows = renderAttendeeRows($attendees);

$inputCls = 'w-full border border-sf-border rounded px-3 py-2 text-sm text-sf-text focus:outline-none focus:ring-2 focus:ring-sf-blue focus:border-sf-blue';

$content = <<<HTML
<h2 class="text-xl font-bold text-sf-text mb-0.5">Attendees</h2>
<p class="text-sf-muted text-sm mb-5">Browse and register conference attendees</p>

<div class="bg-white rounded border border-sf-border shadow-sm overflow-hidden">
  <div class="flex items-center justify-between border-b border-sf-border" style="padding-left:4px; padding-right:16px;">
    <div class="flex">{$tabs}</div>
    <button onclick="document.getElementById('add-attendee-modal').showModal()"
      class="text-sm font-semibold text-white px-4 py-1.5 rounded transition-colors"
      style="background:#0176d3;" onmouseover="this.style.background='#014486'" onmouseout="this.style.background='#0176d3'">
      + Add Attendee
    </button>
  </div>
  <table class="w-full text-sm">
    <thead><tr class="text-left border-b border-sf-bordli bg-sf-bg">
      <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">ID</th>
      <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Name</th>
      <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Email</th>
      <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Fee</th>
      <th class="px-5 py-3"></th>
    </tr></thead>
    <tbody id="attendee-rows">{$rows}</tbody>
  </table>
</div>

<!-- Add Attendee Modal -->
<dialog id="add-attendee-modal">
  <div class="px-5 py-5">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-base font-bold text-sf-text">Register Attendee</h3>
      <button onclick="document.getElementById('add-attendee-modal').close()" class="text-sf-muted hover:text-sf-text text-xl px-1">&times;</button>
    </div>
    <form id="attendee-form"
          hx-post="/attendees/add"
          hx-target="#attendee-rows"
          hx-swap="beforeend"
          class="space-y-4">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">First Name</label>
          <input type="text" name="firstname" required class="{$inputCls}">
        </div>
        <div>
          <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Last Name</label>
          <input type="text" name="lastname" required class="{$inputCls}">
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Email</label>
        <input type="email" name="email" required class="{$inputCls}">
      </div>
      <div>
        <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Attendee Type</label>
        <select name="attendeetype" id="type-select" required
          class="{$inputCls}"
          onchange="toggleTypeFields(this.value)">
          <option value="Student">Student ($50)</option>
          <option value="Professional">Professional ($100)</option>
          <option value="Sponsor">Sponsor (Comped)</option>
        </select>
      </div>
      <div id="student-fields">
        <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Room Number (optional)</label>
        <input type="number" name="roomnumber" min="1" class="{$inputCls}">
      </div>
      <div id="sponsor-fields" class="hidden space-y-3">
        <div>
          <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Sponsor Level</label>
          <select name="sponsorlevel" class="{$inputCls}">
            <option value="Platinum">Platinum ($10,000)</option>
            <option value="Gold">Gold ($5,000)</option>
            <option value="Silver">Silver ($2,500)</option>
            <option value="Bronze">Bronze ($1,000)</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Company</label>
          <select name="companyid" class="{$inputCls}">{$companyOptions}</select>
        </div>
      </div>
      <div class="flex justify-end gap-2 pt-1">
        <button type="button" onclick="document.getElementById('add-attendee-modal').close()"
          class="px-4 py-2 text-sm border border-sf-border rounded text-sf-text hover:bg-sf-bg transition-colors">Cancel</button>
        <button type="submit"
          class="px-4 py-2 text-sm font-semibold text-white rounded transition-colors"
          style="background:#0176d3;" onmouseover="this.style.background='#014486'" onmouseout="this.style.background='#0176d3'">Register</button>
      </div>
    </form>
  </div>
</dialog>

<script>
function toggleTypeFields(type) {
  document.getElementById('student-fields').classList.toggle('hidden', type !== 'Student');
  document.getElementById('sponsor-fields').classList.toggle('hidden', type !== 'Sponsor');
}
document.addEventListener('htmx:trigger', function(e) {
  if (e.detail.trigger === 'closeAttendeeModal') {
    document.getElementById('add-attendee-modal').close();
    document.getElementById('attendee-form').reset();
    toggleTypeFields('Student');
  }
});
</script>
HTML;

renderLayout('Attendees', '/attendees', $content);
