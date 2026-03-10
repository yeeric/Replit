<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db = getDb();

if (!empty($_SERVER['HTTP_HX_REQUEST']) && isset($_GET['room'])) {
    $room = (int) $_GET['room'];
    $stmt = $db->prepare("
        SELECT a.attendeeid, a.firstname, a.lastname, a.email
        FROM student s
        INNER JOIN attendee a ON s.attendeeid = a.attendeeid
        WHERE s.roomnumberstaysin = ?
        ORDER BY a.lastname
    ");
    $stmt->execute([$room]);
    $students = $stmt->fetchAll();

    if (empty($students)) {
        echo '<p class="text-sf-muted text-sm py-2">No students assigned to this room.</p>';
    } else {
        echo '<table class="w-full text-sm">';
        echo '<thead><tr class="text-left border-b border-sf-bordli">';
        echo '<th class="pb-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">ID</th>';
        echo '<th class="pb-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Name</th>';
        echo '<th class="pb-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Email</th>';
        echo '</tr></thead><tbody class="divide-y divide-sf-bordli">';
        foreach ($students as $s) {
            echo "<tr>";
            echo "<td class=\"py-3 text-sf-muted\">{$s['attendeeid']}</td>";
            echo "<td class=\"py-3 font-medium text-sf-text\">{$s['firstname']} {$s['lastname']}</td>";
            echo "<td class=\"py-3 text-sf-muted\">{$s['email']}</td>";
            echo "</tr>";
        }
        echo '</tbody></table>';
    }
    exit;
}

$rooms = $db->query("SELECT roomnumber, numberofbeds FROM hotelroom ORDER BY roomnumber")->fetchAll();

$options = '';
foreach ($rooms as $r) {
    $b = $r['numberofbeds']; $bl = $b === 1 ? '1 bed' : "{$b} beds";
    $options .= "<option value=\"{$r['roomnumber']}\">Room {$r['roomnumber']} — {$bl}</option>";
}

$roomRows = '';
foreach ($rooms as $r) {
    $b = $r['numberofbeds']; $bl = $b === 1 ? '1 bed' : "{$b} beds";
    $roomRows .= "<tr class=\"border-b border-sf-bordli last:border-0\">
        <td class=\"px-5 py-3 font-semibold text-sf-text\">{$r['roomnumber']}</td>
        <td class=\"px-5 py-3 text-sf-muted\">{$bl}</td>
    </tr>";
}

$content = <<<HTML
<h2 class="text-xl font-bold text-sf-text mb-0.5">Hotel Rooms</h2>
<p class="text-sf-muted text-sm mb-5">View room assignments for student attendees</p>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

  <div class="bg-white rounded border border-sf-border shadow-sm">
    <div class="px-5 py-3 border-b border-sf-bordli bg-sf-bg">
      <span class="text-xs font-semibold text-sf-muted uppercase tracking-wide">All Rooms</span>
    </div>
    <table class="w-full text-sm">
      <thead><tr class="text-left border-b border-sf-bordli">
        <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Room #</th>
        <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Beds</th>
      </tr></thead>
      <tbody>{$roomRows}</tbody>
    </table>
  </div>

  <div class="bg-white rounded border border-sf-border shadow-sm">
    <div class="px-5 py-3 border-b border-sf-bordli bg-sf-bg">
      <span class="text-xs font-semibold text-sf-muted uppercase tracking-wide">Lookup Students by Room</span>
    </div>
    <div class="px-5 py-4">
      <select name="room"
        class="w-full border border-sf-border rounded px-3 py-2 text-sm text-sf-text bg-white focus:outline-none focus:ring-2 focus:ring-sf-blue focus:border-sf-blue"
        hx-get="/hotels"
        hx-target="#hotel-students"
        hx-trigger="change"
        hx-indicator="#hotel-loading">
        <option value="">— Select a room —</option>
        {$options}
      </select>
      <span id="hotel-loading" class="htmx-indicator text-sf-blue text-sm mt-2 block">Loading…</span>
    </div>
    <div id="hotel-students" class="px-5 pb-4 min-h-[48px]">
      <p class="text-sf-muted text-sm">Select a room to see students.</p>
    </div>
  </div>

</div>
HTML;

renderLayout('Hotel Rooms', '/hotels', $content);
