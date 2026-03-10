<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db     = getDb();
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$isHtmx = !empty($_SERVER['HTTP_HX_REQUEST']);

// ── POST /sponsors/company — add company ──────────────────────────────────────
if ($method === 'POST' && str_ends_with($path, '/company')) {
    $name = trim($_POST['companyname'] ?? '');
    if ($name === '') { http_response_code(400); echo 'Company name is required.'; exit; }
    $stmt = $db->prepare("INSERT INTO company (companyname) VALUES (?) RETURNING companyid, companyname");
    $stmt->execute([$name]);
    $c = $stmt->fetch();
    $id = $c['companyid']; $cname = htmlspecialchars($c['companyname']);
    echo <<<HTML
<tr id="company-row-{$id}" class="border-b border-gray-100 last:border-0">
  <td class="px-6 py-3 font-medium">{$cname}</td>
  <td class="px-6 py-3 text-right">
    <button hx-delete="/sponsors/company?id={$id}"
      hx-target="#company-row-{$id}" hx-swap="outerHTML"
      hx-confirm="Delete {$cname}?"
      class="text-xs text-red-500 hover:text-red-700 border border-red-200 px-3 py-1 rounded-lg hover:bg-red-50">
      Delete
    </button>
  </td>
</tr>
HTML;
    exit;
}

// ── DELETE /sponsors/company?id=X ─────────────────────────────────────────────
if ($method === 'DELETE' && str_ends_with($path, '/company')) {
    $id = (int) ($_GET['id'] ?? 0);
    $db->prepare("DELETE FROM company WHERE companyid = ?")->execute([$id]);
    echo ''; // Remove the row
    exit;
}

// ── Full page ─────────────────────────────────────────────────────────────────
$sponsors   = $db->query("
    SELECT a.attendeeid, a.firstname, a.lastname, c.companyname, s.sponsorlevel
    FROM sponsor s
    INNER JOIN attendee a ON s.attendeeid = a.attendeeid
    INNER JOIN company  c ON s.companyid  = c.companyid
    ORDER BY CASE s.sponsorlevel WHEN 'Platinum' THEN 1 WHEN 'Gold' THEN 2 WHEN 'Silver' THEN 3 ELSE 4 END, a.lastname
")->fetchAll();

$companies  = $db->query("SELECT companyid, companyname FROM company ORDER BY companyname")->fetchAll();

$tierBadge = function(string $lvl): string {
    $map = [
        'Platinum' => 'bg-slate-100 text-slate-700',
        'Gold'     => 'bg-yellow-100 text-yellow-800',
        'Silver'   => 'bg-gray-100 text-gray-600',
        'Bronze'   => 'bg-orange-100 text-orange-700',
    ];
    $cls = $map[$lvl] ?? 'bg-gray-100 text-gray-500';
    return "<span class=\"inline-block px-2 py-0.5 text-xs font-semibold rounded-full {$cls}\">{$lvl}</span>";
};

$sponsorRows = '';
foreach ($sponsors as $s) {
    $name = htmlspecialchars("{$s['firstname']} {$s['lastname']}");
    $company = htmlspecialchars($s['companyname']);
    $sponsorRows .= "<tr class=\"border-b border-gray-100 last:border-0\">
        <td class=\"px-6 py-3 font-medium\">{$name}</td>
        <td class=\"px-6 py-3 text-gray-500\">{$company}</td>
        <td class=\"px-6 py-3\">{$tierBadge($s['sponsorlevel'])}</td>
    </tr>";
}

$companyRows = '';
foreach ($companies as $c) {
    $id = $c['companyid']; $cname = htmlspecialchars($c['companyname']);
    $companyRows .= <<<HTML
<tr id="company-row-{$id}" class="border-b border-gray-100 last:border-0">
  <td class="px-6 py-3 font-medium">{$cname}</td>
  <td class="px-6 py-3 text-right">
    <button hx-delete="/sponsors/company?id={$id}"
      hx-target="#company-row-{$id}" hx-swap="outerHTML"
      hx-confirm="Delete {$cname}? This will also remove related job ads and sponsor records."
      class="text-xs text-red-500 hover:text-red-700 border border-red-200 px-3 py-1 rounded-lg hover:bg-red-50">
      Delete
    </button>
  </td>
</tr>
HTML;
}

$content = <<<HTML
<h2 class="text-2xl font-bold text-gray-800 mb-1">Sponsors &amp; Companies</h2>
<p class="text-gray-500 text-sm mb-6">Manage sponsoring companies and view sponsor attendees</p>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

  <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-700">Sponsor Attendees</div>
    <table class="w-full text-sm">
      <thead><tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
        <th class="px-6 py-3 font-medium">Name</th>
        <th class="px-6 py-3 font-medium">Company</th>
        <th class="px-6 py-3 font-medium">Level</th>
      </tr></thead>
      <tbody>{$sponsorRows}</tbody>
    </table>
  </div>

  <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
      <span class="font-semibold text-gray-700">Registered Companies</span>
      <button onclick="document.getElementById('add-company-modal').showModal()"
        class="text-sm bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 font-medium">
        + Add Company
      </button>
    </div>
    <table class="w-full text-sm">
      <thead><tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
        <th class="px-6 py-3 font-medium">Company Name</th>
        <th class="px-6 py-3"></th>
      </tr></thead>
      <tbody id="companies-list">{$companyRows}</tbody>
    </table>
  </div>

</div>

<!-- Add Company Modal -->
<dialog id="add-company-modal" class="min-w-[360px]">
  <div class="px-6 py-5">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold text-gray-800">Add Company</h3>
      <button onclick="document.getElementById('add-company-modal').close()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
    </div>
    <form hx-post="/sponsors/company"
          hx-target="#companies-list"
          hx-swap="beforeend"
          hx-on::after-request="this.reset(); document.getElementById('add-company-modal').close()">
      <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
      <input type="text" name="companyname" required autocomplete="off"
        placeholder="e.g. Acme Corp"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
      <div class="flex justify-end gap-2">
        <button type="button" onclick="document.getElementById('add-company-modal').close()"
          class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
        <button type="submit"
          class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Create</button>
      </div>
    </form>
  </div>
</dialog>
HTML;

renderLayout('Sponsors & Companies', '/sponsors', $content);
