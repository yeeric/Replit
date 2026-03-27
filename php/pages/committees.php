<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db = getDb();
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$isHtmx = !empty($_SERVER['HTTP_HX_REQUEST']);

// ── POST /committees/subcommittee ──────────────────────────────────────────────
if ($method === 'POST' && str_ends_with($path, '/subcommittee')) {
    $name = trim($_POST['committeename'] ?? '');
    $chairFirst = trim($_POST['chair_firstname'] ?? '');
    $chairLast = trim($_POST['chair_lastname'] ?? '');
    if ($name === '' || $chairFirst === '' || $chairLast === '') {
        http_response_code(400);
        echo '<p class="text-sm" style="color:#ba0517;">Subcommittee name and chair first/last name are required.</p>';
        exit;
    }

    try {
        $db->beginTransaction();

        $memberStmt = $db->prepare("
            INSERT INTO committeemember (memberid, firstname, lastname)
            SELECT COALESCE(MAX(memberid), 0) + 1, ?, ?
            FROM committeemember
            RETURNING memberid
        ");
        $memberStmt->execute([$chairFirst, $chairLast]);
        $chairMemberId = (int)$memberStmt->fetchColumn();

        $committeeStmt = $db->prepare("
            INSERT INTO subcommittee (committeeid, committeename, chairmemberid)
            SELECT COALESCE(MAX(committeeid), 0) + 1, ?, ?
            FROM subcommittee
            RETURNING committeeid, committeename
        ");
        $committeeStmt->execute([$name, $chairMemberId]);
        $created = $committeeStmt->fetch();
        $id = (int)$created['committeeid'];
        $safeName = htmlspecialchars($created['committeename']);

        $linkStmt = $db->prepare("INSERT INTO memberofcommittee (committeeid, memberid) VALUES (?, ?)");
        $linkStmt->execute([$id, $chairMemberId]);

        $db->commit();

        // Append to both committee selectors.
        echo "<option value=\"{$id}\">{$safeName}</option>";
        echo "<option value=\"{$id}\" hx-swap-oob=\"beforeend:#member-committeeid\">{$safeName}</option>";
        echo "<div id=\"committee-create-result\" hx-swap-oob=\"innerHTML\"><p class=\"text-sm\" style=\"color:#2e844a;\">Subcommittee created.</p></div>";
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo '<p class="text-sm" style="color:#ba0517;">Unable to create subcommittee.</p>';
    }
    exit;
}

// ── POST /committees/member ───────────────────────────────────────────────────
if ($method === 'POST' && str_ends_with($path, '/member')) {
    $first = trim($_POST['firstname'] ?? '');
    $last = trim($_POST['lastname'] ?? '');
    $committeeId = (int)($_POST['committeeid'] ?? 0);

    if ($first === '' || $last === '' || $committeeId <= 0) {
        http_response_code(400);
        echo '<p class="text-sm" style="color:#ba0517;">First name, last name, and committee are required.</p>';
        exit;
    }

    $committeeExists = $db->prepare("SELECT 1 FROM subcommittee WHERE committeeid = ?");
    $committeeExists->execute([$committeeId]);
    if (!$committeeExists->fetchColumn()) {
        http_response_code(400);
        echo '<p class="text-sm" style="color:#ba0517;">Selected committee does not exist.</p>';
        exit;
    }

    try {
        $db->beginTransaction();

        $memberStmt = $db->prepare("
            INSERT INTO committeemember (memberid, firstname, lastname)
            SELECT COALESCE(MAX(memberid), 0) + 1, ?, ?
            FROM committeemember
            RETURNING memberid
        ");
        $memberStmt->execute([$first, $last]);
        $memberId = (int)$memberStmt->fetchColumn();

        $linkStmt = $db->prepare("INSERT INTO memberofcommittee (committeeid, memberid) VALUES (?, ?)");
        $linkStmt->execute([$committeeId, $memberId]);

        $db->commit();
        header('HX-Trigger: committeeMemberAdded');
        echo '<p class="text-sm" style="color:#2e844a;">Committee member added.</p>';
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo '<p class="text-sm" style="color:#ba0517;">Unable to add committee member.</p>';
    }
    exit;
}

// ── HTMX partial: member table by committee ──────────────────────────────────
if ($isHtmx && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
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
            echo '<tr>';
            echo '<td class="py-3 text-sf-muted">' . (int)$m['memberid'] . '</td>';
            echo '<td class="py-3 font-medium text-sf-text">' . htmlspecialchars($m['firstname']) . '</td>';
            echo '<td class="py-3 text-sf-text">' . htmlspecialchars($m['lastname']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    exit;
}

$committees = $db->query("SELECT committeeid, committeename FROM subcommittee ORDER BY committeeid")->fetchAll();

$options = '';
foreach ($committees as $c) {
    $options .= "<option value=\"{$c['committeeid']}\">" . htmlspecialchars($c['committeename']) . '</option>';
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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">
  <div class="bg-white rounded border border-sf-border shadow-sm p-5">
    <h3 class="text-sm font-semibold text-sf-text uppercase tracking-wide mb-3">Add Subcommittee</h3>
    <form hx-post="/committees/subcommittee"
          hx-target="#committee-select"
          hx-swap="beforeend"
          hx-on::after-request="if(event.detail.successful) this.reset()"
          class="space-y-3">
      <div>
        <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Name</label>
        <input type="text" name="committeename" required
          class="w-full border border-sf-border rounded px-3 py-2 text-sm text-sf-text focus:outline-none focus:ring-2 focus:ring-sf-blue focus:border-sf-blue"
          placeholder="e.g. Accessibility Committee">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Chair First Name</label>
          <input type="text" name="chair_firstname" required
            class="w-full border border-sf-border rounded px-3 py-2 text-sm text-sf-text focus:outline-none focus:ring-2 focus:ring-sf-blue focus:border-sf-blue">
        </div>
        <div>
          <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Chair Last Name</label>
          <input type="text" name="chair_lastname" required
            class="w-full border border-sf-border rounded px-3 py-2 text-sm text-sf-text focus:outline-none focus:ring-2 focus:ring-sf-blue focus:border-sf-blue">
        </div>
      </div>
      <div class="flex items-center justify-between gap-3">
        <button type="submit"
          class="text-sm font-semibold text-white px-4 py-2 rounded transition-colors"
          style="background:#0176d3;" onmouseover="this.style.background='#014486'" onmouseout="this.style.background='#0176d3'">
          Create Subcommittee
        </button>
        <div id="committee-create-result" class="text-sm text-sf-muted"></div>
      </div>
    </form>
  </div>

  <div class="bg-white rounded border border-sf-border shadow-sm p-5">
    <h3 class="text-sm font-semibold text-sf-text uppercase tracking-wide mb-3">Add Committee Member</h3>
    <form hx-post="/committees/member"
          hx-target="#member-create-result"
          hx-swap="innerHTML"
          hx-on::after-request="if(event.detail.successful) this.reset()"
          class="space-y-3">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">First Name</label>
          <input type="text" name="firstname" required
            class="w-full border border-sf-border rounded px-3 py-2 text-sm text-sf-text focus:outline-none focus:ring-2 focus:ring-sf-blue focus:border-sf-blue">
        </div>
        <div>
          <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Last Name</label>
          <input type="text" name="lastname" required
            class="w-full border border-sf-border rounded px-3 py-2 text-sm text-sf-text focus:outline-none focus:ring-2 focus:ring-sf-blue focus:border-sf-blue">
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Sub-committee</label>
        <select id="member-committeeid" name="committeeid" required
          class="w-full border border-sf-border rounded px-3 py-2 text-sm text-sf-text bg-white focus:outline-none focus:ring-2 focus:ring-sf-blue focus:border-sf-blue">
          <option value="">— Select a committee —</option>
          {$options}
        </select>
      </div>
      <div class="flex items-center justify-between gap-3">
        <button type="submit"
          class="text-sm font-semibold text-white px-4 py-2 rounded transition-colors"
          style="background:#0176d3;" onmouseover="this.style.background='#014486'" onmouseout="this.style.background='#0176d3'">
          Add Member
        </button>
        <div id="member-create-result" class="text-sm text-sf-muted"></div>
      </div>
    </form>
  </div>
</div>

<script>
document.body.addEventListener('committeeMemberAdded', function() {
  var select = document.getElementById('committee-select');
  if (select && select.value) {
    htmx.ajax('GET', '/committees?id=' + encodeURIComponent(select.value), '#members-container');
  }
});
</script>
HTML;

renderLayout('Committees', '/committees', $content);
