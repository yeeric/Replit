<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db = getDb();

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
        echo '<p class="text-sf-muted text-sm py-4">No members found for this committee.</p>';
    } else {
        echo '<table class="w-full text-sm">';
        echo '<thead><tr class="text-left border-b border-sf-bordli">';
        echo '<th class="pb-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">#</th>';
        echo '<th class="pb-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">First Name</th>';
        echo '<th class="pb-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Last Name</th>';
        echo '</tr></thead><tbody class="divide-y divide-sf-bordli">';
        foreach ($members as $m) {
            echo "<tr>";
            echo "<td class=\"py-3 text-sf-muted\">{$m['memberid']}</td>";
            echo "<td class=\"py-3 font-medium text-sf-text\">{$m['firstname']}</td>";
            echo "<td class=\"py-3 text-sf-text\">{$m['lastname']}</td>";
            echo "</tr>";
        }
        echo '</tbody></table>';
    }
    exit;
}

$committees = $db->query("SELECT committeeid, committeename FROM subcommittee ORDER BY committeeid")->fetchAll();

$options = '';
foreach ($committees as $c) {
    $options .= "<option value=\"{$c['committeeid']}\">" . htmlspecialchars($c['committeename']) . "</option>";
}

$content = <<<HTML
<h2 class="text-xl font-bold text-sf-text mb-0.5">Committees</h2>
<p class="text-sf-muted text-sm mb-5">Select a sub-committee to view its members</p>

<div class="bg-white rounded border border-sf-border shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-sf-bordli bg-sf-bg">
    <label for="committee-select" class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-2">Sub-committee</label>
    <div class="flex items-center gap-3">
      <select id="committee-select" name="id"
        class="w-72 border border-sf-border rounded px-3 py-2 text-sm text-sf-text bg-white focus:outline-none focus:ring-2 focus:ring-sf-blue focus:border-sf-blue"
        hx-get="/committees"
        hx-target="#members-container"
        hx-trigger="change"
        hx-indicator="#loading">
        <option value="">— Select a committee —</option>
        {$options}
      </select>
      <span id="loading" class="htmx-indicator text-sf-blue text-sm">Loading…</span>
    </div>
  </div>
  <div id="members-container" class="px-5 py-4 min-h-[80px]">
    <p class="text-sf-muted text-sm">Select a committee above to see its members.</p>
  </div>
</div>
HTML;

renderLayout('Committees', '/committees', $content);
