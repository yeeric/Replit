<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db = getDb();

// Handle HTMX partial: return only the members table
if (!empty($_SERVER['HTTP_HX_REQUEST']) && isset($_GET['id'])) {
    $id   = (int) $_GET['id'];
    $stmt = $db->prepare("
        SELECT cm.memberid, cm.firstname, cm.lastname
        FROM memberofcommittee moc
        INNER JOIN committeemember cm ON moc.memberid = cm.memberid
        WHERE moc.committeeid = ?
        ORDER BY cm.lastname
    ");
    $stmt->execute([$id]);
    $members = $stmt->fetchAll();

    if (empty($members)) {
        echo '<p class="text-gray-400 text-sm py-4">No members found for this committee.</p>';
    } else {
        echo '<table class="w-full text-sm">';
        echo '<thead><tr class="text-left text-gray-400 border-b border-gray-100 text-xs uppercase tracking-wide">';
        echo '<th class="pb-3 font-medium">#</th><th class="pb-3 font-medium">First Name</th><th class="pb-3 font-medium">Last Name</th>';
        echo '</tr></thead><tbody class="divide-y divide-gray-100">';
        foreach ($members as $m) {
            echo "<tr>";
            echo "<td class=\"py-3 text-gray-400\">{$m['memberid']}</td>";
            echo "<td class=\"py-3 font-medium\">{$m['firstname']}</td>";
            echo "<td class=\"py-3\">{$m['lastname']}</td>";
            echo "</tr>";
        }
        echo '</tbody></table>';
    }
    exit;
}

// Full page: load all committees for the dropdown
$committees = $db->query("SELECT committeeid, committeename FROM subcommittee ORDER BY committeeid")->fetchAll();

$options = '';
foreach ($committees as $c) {
    $options .= "<option value=\"{$c['committeeid']}\">{$c['committeename']}</option>";
}

$content = <<<HTML
<h2 class="text-2xl font-bold text-gray-800 mb-1">Committees</h2>
<p class="text-gray-500 text-sm mb-6">Select a sub-committee to view its members</p>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
  <div class="px-6 py-4 border-b border-gray-100">
    <label for="committee-select" class="block text-sm font-medium text-gray-700 mb-2">Sub-committee</label>
    <select id="committee-select" name="id"
      class="w-full sm:w-72 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      hx-get="/committees"
      hx-target="#members-container"
      hx-trigger="change"
      hx-indicator="#loading">
      <option value="">— Select a committee —</option>
      {$options}
    </select>
    <span id="loading" class="htmx-indicator ml-2 text-blue-500 text-sm">Loading…</span>
  </div>
  <div id="members-container" class="px-6 py-4 min-h-[80px]">
    <p class="text-gray-400 text-sm">Select a committee above to see its members.</p>
  </div>
</div>
HTML;

renderLayout('Committees', '/committees', $content);
