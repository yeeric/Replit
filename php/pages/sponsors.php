<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db     = getDb();
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// ── POST /sponsors/company ────────────────────────────────────────────────────
if ($method === 'POST' && str_ends_with($path, '/company')) {
    $name = trim($_POST['companyname'] ?? '');
    if ($name === '') { http_response_code(400); echo 'Company name is required.'; exit; }
    $stmt = $db->prepare("INSERT INTO company (companyname) VALUES (?) RETURNING companyid, companyname");
    $stmt->execute([$name]);
    $c = $stmt->fetch();
    $id = $c['companyid']; $cname = htmlspecialchars($c['companyname']);
    echo companyRow($id, $cname);
    exit;
}

// ── DELETE /sponsors/company?id=X ─────────────────────────────────────────────
if ($method === 'DELETE' && str_ends_with($path, '/company')) {
    $db->prepare("DELETE FROM company WHERE companyid = ?")->execute([(int)($_GET['id'] ?? 0)]);
    echo '';
    exit;
}

function companyRow(int $id, string $cname): string {
    return <<<HTML
<tr id="company-row-{$id}" class="border-b border-sf-bordli last:border-0">
  <td class="px-5 py-3 text-sf-text font-medium">{$cname}</td>
  <td class="px-5 py-3 text-right">
    <button hx-delete="/sponsors/company?id={$id}"
      hx-target="#company-row-{$id}" hx-swap="outerHTML"
      hx-confirm="Delete {$cname}?"
      class="text-xs font-semibold border rounded px-3 py-1.5 transition-colors"
      style="color:#ba0517; border-color:#f4b8b3;"
      onmouseover="this.style.background='#fcdbd9'" onmouseout="this.style.background=''">
      Delete
    </button>
  </td>
</tr>
HTML;
}

function tierBadge(string $lvl): string {
    $styles = [
        'Platinum' => 'background:#e8e8e8; color:#444;',
        'Gold'     => 'background:#fff3cd; color:#856404;',
        'Silver'   => 'background:#f3f2f2; color:#706e6b;',
        'Bronze'   => 'background:#fde8d8; color:#875500;',
    ];
    $s = $styles[$lvl] ?? 'background:#f3f2f2; color:#706e6b;';
    return "<span class=\"inline-block px-2 py-0.5 text-xs font-semibold rounded\" style=\"{$s}\">{$lvl}</span>";
}

$sponsors  = $db->query("
    SELECT a.attendeeid, a.firstname, a.lastname, c.companyname, s.sponsorlevel
    FROM sponsor s
    INNER JOIN attendee a ON s.attendeeid = a.attendeeid
    INNER JOIN company  c ON s.companyid  = c.companyid
    ORDER BY CASE s.sponsorlevel WHEN 'Platinum' THEN 1 WHEN 'Gold' THEN 2 WHEN 'Silver' THEN 3 ELSE 4 END, a.lastname
")->fetchAll();

$companies = $db->query("SELECT companyid, companyname FROM company ORDER BY companyname")->fetchAll();

$sponsorRows = '';
foreach ($sponsors as $s) {
    $id   = (int)$s['attendeeid'];
    $name = htmlspecialchars("{$s['firstname']} {$s['lastname']}");
    $comp = htmlspecialchars($s['companyname']);
    $sponsorRows .= "<tr id=\"sponsor-attendee-row-{$id}\" class=\"border-b border-sf-bordli last:border-0\">
        <td class=\"px-5 py-3 font-medium text-sf-text\">{$name}</td>
        <td class=\"px-5 py-3 text-sf-muted\">{$comp}</td>
        <td class=\"px-5 py-3\">" . tierBadge($s['sponsorlevel']) . "</td>
        <td class=\"px-5 py-3 text-right\">
          <button hx-delete=\"/attendees/delete?id={$id}\"
            hx-target=\"#sponsor-attendee-row-{$id}\" hx-swap=\"outerHTML\"
            hx-confirm=\"Delete {$name}?\"
            class=\"text-xs font-semibold border rounded px-3 py-1.5 transition-colors\"
            style=\"color:#ba0517; border-color:#f4b8b3;\"
            onmouseover=\"this.style.background='#fcdbd9'\" onmouseout=\"this.style.background=''\">
            Delete
          </button>
        </td>
    </tr>";
}

$companyRows = '';
foreach ($companies as $c) {
    $companyRows .= companyRow($c['companyid'], htmlspecialchars($c['companyname']));
}

$btnPrimary = 'text-sm font-semibold text-white px-4 py-1.5 rounded transition-colors';

$content = <<<HTML
<h2 class="text-xl font-bold text-sf-text mb-0.5">Sponsors &amp; Companies</h2>
<p class="text-sf-muted text-sm mb-5">Manage sponsoring companies and view sponsor attendees</p>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  <div class="bg-white rounded border border-sf-border shadow-sm">
    <div class="px-5 py-3 border-b border-sf-bordli bg-sf-bg">
      <span class="text-xs font-semibold text-sf-muted uppercase tracking-wide">Sponsor Attendees</span>
    </div>
    <table class="w-full text-sm">
      <thead><tr class="text-left border-b border-sf-bordli">
        <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Name</th>
        <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Company</th>
        <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Level</th>
        <th class="px-5 py-3"></th>
      </tr></thead>
      <tbody>{$sponsorRows}</tbody>
    </table>
  </div>

  <div class="bg-white rounded border border-sf-border shadow-sm">
    <div class="px-5 py-3 border-b border-sf-bordli bg-sf-bg flex justify-between items-center">
      <span class="text-xs font-semibold text-sf-muted uppercase tracking-wide">Registered Companies</span>
      <button onclick="document.getElementById('add-company-modal').showModal()"
        class="{$btnPrimary}"
        style="background:#0176d3;" onmouseover="this.style.background='#014486'" onmouseout="this.style.background='#0176d3'">
        + Add Company
      </button>
    </div>
    <table class="w-full text-sm">
      <thead><tr class="text-left border-b border-sf-bordli">
        <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Company Name</th>
        <th class="px-5 py-3"></th>
      </tr></thead>
      <tbody id="companies-list">{$companyRows}</tbody>
    </table>
  </div>

</div>

<!-- Add Company Modal -->
<dialog id="add-company-modal">
  <div class="px-5 py-5">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-base font-bold text-sf-text">Add Company</h3>
      <button onclick="document.getElementById('add-company-modal').close()" class="text-sf-muted hover:text-sf-text text-xl px-1">&times;</button>
    </div>
    <form hx-post="/sponsors/company"
          hx-target="#companies-list"
          hx-swap="beforeend"
          hx-on::after-request="this.reset(); document.getElementById('add-company-modal').close()">
      <label class="block text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Company Name</label>
      <input type="text" name="companyname" required autocomplete="off"
        placeholder="e.g. Acme Corp"
        class="w-full border border-sf-border rounded px-3 py-2 text-sm text-sf-text mb-5 focus:outline-none focus:ring-2 focus:ring-sf-blue focus:border-sf-blue">
      <div class="flex justify-end gap-2">
        <button type="button" onclick="document.getElementById('add-company-modal').close()"
          class="px-4 py-2 text-sm border border-sf-border rounded text-sf-text hover:bg-sf-bg transition-colors">Cancel</button>
        <button type="submit"
          class="{$btnPrimary} px-4 py-2"
          style="background:#0176d3;" onmouseover="this.style.background='#014486'" onmouseout="this.style.background='#0176d3'">Create</button>
      </div>
    </form>
  </div>
</dialog>
HTML;

renderLayout('Sponsors & Companies', '/sponsors', $content);
