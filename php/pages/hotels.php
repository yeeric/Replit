<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db = getDb();

// HTMX partial: students in a room
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
        echo '<p class="text-gray-400 text-sm py-2">No students assigned to this room.</p>';
    } else {
        echo '<table class="w-full text-sm">';
        echo '<thead><tr class="text-left text-gray-400 border-b border-gray-100 text-xs uppercase tracking-wide">';
        echo '<th class="pb-3 font-medium">ID</th><th class="pb-3 font-medium">Name</th><th class="pb-3 font-medium">Email</th>';
        echo '</tr></thead><tbody class="divide-y divide-gray-100">';
        foreach ($students as $s) {
            echo "<tr>";
            echo "<td class=\"py-3 text-gray-400\">{$s['attendeeid']}</td>";
            echo "<td class=\"py-3 font-medium\">{$s['firstname']} {$s['lastname']}</td>";
            echo "<td class=\"py-3 text-gray-500\">{$s['email']}</td>";
            echo "</tr>";
        }
        echo '</tbody></table>';
    }
    exit;
}

// Full page
$rooms = $db->query("SELECT roomnumber, numberofbeds FROM hotelroom ORDER BY roomnumber")->fetchAll();

$options = '';
foreach ($rooms as $r) {
    $beds     = $r['numberofbeds'];
    $bedLabel = $beds === 1 ? '1 bed' : "{$beds} beds";
    $options .= "<option value=\"{$r['roomnumber']}\">Room {$r['roomnumber']} — {$bedLabel}</option>";
}

$roomRows = '';
foreach ($rooms as $r) {
    $beds      = $r['numberofbeds'];
    $bedLabel  = $beds === 1 ? '1 bed' : "{$beds} beds";
    $roomRows .= "<tr class=\"border-b border-gray-100\">
        <td class=\"px-6 py-3 font-semibold\">{$r['roomnumber']}</td>
        <td class=\"px-6 py-3 text-gray-500\">{$bedLabel}</td>
    </tr>";
}

$content = <<<HTML
<h2 class="text-2xl font-bold text-gray-800 mb-1">Hotel Rooms</h2>
<p class="text-gray-500 text-sm mb-6">View room assignments for student attendees</p>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

  <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-700">All Rooms</div>
    <table class="w-full text-sm">
      <thead><tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
        <th class="px-6 py-3 font-medium">Room #</th><th class="px-6 py-3 font-medium">Beds</th>
      </tr></thead>
      <tbody>{$roomRows}</tbody>
    </table>
  </div>

  <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="px-6 py-4 border-b border-gray-100">
      <p class="text-sm font-medium text-gray-700 mb-2">Lookup Students by Room</p>
      <select name="room"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        hx-get="/hotels"
        hx-target="#hotel-students"
        hx-trigger="change"
        hx-indicator="#hotel-loading">
        <option value="">— Select a room —</option>
        {$options}
      </select>
      <span id="hotel-loading" class="htmx-indicator text-blue-500 text-sm mt-1 block">Loading…</span>
    </div>
    <div id="hotel-students" class="px-6 py-4 min-h-[60px]">
      <p class="text-gray-400 text-sm">Select a room to see students.</p>
    </div>
  </div>

</div>
HTML;

renderLayout('Hotel Rooms', '/hotels', $content);
