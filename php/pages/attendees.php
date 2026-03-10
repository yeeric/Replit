<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db     = getDb();
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$isHtmx = !empty($_SERVER['HTTP_HX_REQUEST']);

// ── POST /attendees/add — create attendee ─────────────────────────────────────
if ($method === 'POST' && str_ends_with($path, '/add')) {
    $fn   = trim($_POST['firstname']    ?? '');
    $ln   = trim($_POST['lastname']     ?? '');
    $em   = trim($_POST['email']        ?? '');
    $type = trim($_POST['attendeetype'] ?? '');

    if (!$fn || !$ln || !$em || !$type) {
        http_response_code(400);
        echo '<tr><td colspan="4" class="px-6 py-3 text-red-500 text-sm">All fields are required.</td></tr>';
        exit;
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO attendee (firstname, lastname, email, attendeetype) VALUES (?,?,?,?) RETURNING attendeeid, firstname, lastname, email, attendeetype, fee::text");
        $stmt->execute([$fn, $ln, $em, $type]);
        $a  = $stmt->fetch();
        $id = $a['attendeeid'];

        if ($type === 'Student') {
            $room = !empty($_POST['roomnumber']) ? (int) $_POST['roomnumber'] : null;
            $db->prepare("INSERT INTO student (attendeeid, roomnumberstaysin) VALUES (?,?)")->execute([$id, $room]);
        } elseif ($type === 'Professional') {
            $db->prepare("INSERT INTO professional (attendeeid) VALUES (?)")->execute([$id]);
        } elseif ($type === 'Sponsor') {
            $lvl = $_POST['sponsorlevel'] ?? 'Bronze';
            $cid = (int) ($_POST['companyid'] ?? 0);
            $db->prepare("INSERT INTO sponsor (attendeeid, sponsorlevel, companyid) VALUES (?,?,?)")->execute([$id, $lvl, $cid]);
        }
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo '<tr><td colspan="4" class="px-6 py-3 text-red-500 text-sm">' . htmlspecialchars($e->getMessage()) . '</td></tr>';
        exit;
    }

    $fee   = '$' . number_format((float) $a['fee'], 2);
    $name  = htmlspecialchars("{$a['firstname']} {$a['lastname']}");
    $email = htmlspecialchars($a['email']);
    // Send headers before any body output
    header('HX-Trigger: closeAttendeeModal');
    echo <<<HTML
<tr class="border-b border-gray-100 last:border-0">
  <td class="px-6 py-3 text-gray-400">{$a['attendeeid']}</td>
  <td class="px-6 py-3 font-medium">{$name}</td>
  <td class="px-6 py-3 text-gray-500">{$email}</td>
  <td class="px-6 py-3 text-gray-600">{$fee}</td>
</tr>
HTML;
    exit;
}

// ── HTMX partial: show attendee rows for a tab ────────────────────────────────
if ($isHtmx && isset($_GET['type'])) {
    $type = $_GET['type'];
    $stmt = $db->prepare("SELECT attendeeid, firstname, lastname, email, fee::text FROM attendee WHERE attendeetype = ? ORDER BY attendeeid");
    $stmt->execute([$type]);
    $rows = $stmt->fetchAll();
    echo renderAttendeeRows($rows);
    exit;
}

function renderAttendeeRows(array $rows): string {
    if (empty($rows)) {
        return '<tr><td colspan="4" class="px-6 py-6 text-center text-gray-400 text-sm">No attendees found.</td></tr>';
    }
    $html = '';
    foreach ($rows as $a) {
        $name = htmlspecialchars("{$a['firstname']} {$a['lastname']}");
        $email = htmlspecialchars($a['email']);
        $fee   = '$' . number_format((float) $a['fee'], 2);
        $html .= "<tr class=\"border-b border-gray-100 last:border-0\">
            <td class=\"px-6 py-3 text-gray-400\">{$a['attendeeid']}</td>
            <td class=\"px-6 py-3 font-medium\">{$name}</td>
            <td class=\"px-6 py-3 text-gray-500\">{$email}</td>
            <td class=\"px-6 py-3 text-gray-600\">{$fee}</td>
        </tr>";
    }
    return $html;
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
    $cls = $isActive
        ? 'px-5 py-2.5 text-sm font-semibold text-blue-600 border-b-2 border-blue-600 bg-white'
        : 'px-5 py-2.5 text-sm text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 bg-white transition-colors';
    $tabs .= "<button class=\"{$cls}\"
        hx-get=\"/attendees?type={$t}\"
        hx-target=\"#attendee-rows\"
        hx-swap=\"innerHTML\"
        hx-on::before-request=\"document.querySelectorAll('.tab-btn').forEach(b=>{b.classList.remove('text-blue-600','border-blue-600'); b.classList.add('text-gray-500','border-transparent')}); this.classList.add('text-blue-600','border-blue-600'); this.classList.remove('text-gray-500','border-transparent')\"
    >{$t}s</button>";
}

$companies = $db->query("SELECT companyid, companyname FROM company ORDER BY companyname")->fetchAll();
$companyOptions = '';
foreach ($companies as $c) {
    $companyOptions .= "<option value=\"{$c['companyid']}\">" . htmlspecialchars($c['companyname']) . "</option>";
}

$rows = renderAttendeeRows($attendees);

$content = <<<HTML
<h2 class="text-2xl font-bold text-gray-800 mb-1">Attendees</h2>
<p class="text-gray-500 text-sm mb-6">Browse and register conference attendees</p>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
  <div class="flex items-center justify-between border-b border-gray-200 px-2">
    <div class="flex">{$tabs}</div>
    <button onclick="document.getElementById('add-attendee-modal').showModal()"
      class="mr-4 text-sm bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 font-medium">
      + Add Attendee
    </button>
  </div>

  <table class="w-full text-sm">
    <thead><tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
      <th class="px-6 py-3 font-medium">ID</th>
      <th class="px-6 py-3 font-medium">Name</th>
      <th class="px-6 py-3 font-medium">Email</th>
      <th class="px-6 py-3 font-medium">Fee</th>
    </tr></thead>
    <tbody id="attendee-rows">{$rows}</tbody>
  </table>
</div>

<!-- Add Attendee Modal -->
<dialog id="add-attendee-modal" class="min-w-[420px]">
  <div class="px-6 py-5">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold text-gray-800">Register Attendee</h3>
      <button onclick="document.getElementById('add-attendee-modal').close()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
    </div>
    <form id="attendee-form"
          hx-post="/attendees/add"
          hx-target="#attendee-rows"
          hx-swap="beforeend"
          class="space-y-4">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">First Name</label>
          <input type="text" name="firstname" required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Last Name</label>
          <input type="text" name="lastname" required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
        <input type="email" name="email" required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Attendee Type</label>
        <select name="attendeetype" id="type-select" required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          onchange="toggleTypeFields(this.value)">
          <option value="Student">Student ($50)</option>
          <option value="Professional">Professional ($100)</option>
          <option value="Sponsor">Sponsor (Comped)</option>
        </select>
      </div>

      <div id="student-fields">
        <label class="block text-xs font-medium text-gray-600 mb-1">Room Number (optional)</label>
        <input type="number" name="roomnumber" min="1"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div id="sponsor-fields" class="hidden space-y-3">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Sponsor Level</label>
          <select name="sponsorlevel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="Platinum">Platinum ($10,000)</option>
            <option value="Gold">Gold ($5,000)</option>
            <option value="Silver">Silver ($2,500)</option>
            <option value="Bronze">Bronze ($1,000)</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Company</label>
          <select name="companyid" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            {$companyOptions}
          </select>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="document.getElementById('add-attendee-modal').close()"
          class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
        <button type="submit"
          class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Register</button>
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
