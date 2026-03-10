<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db = getDb();

function renderJobRows(array $rows): string {
    if (empty($rows)) {
        return '<tr><td colspan="5" class="px-5 py-6 text-center text-sf-muted text-sm">No job ads found.</td></tr>';
    }
    $html = '';
    foreach ($rows as $j) {
        $pay = '$' . number_format((float)$j['payrate'], 2) . '/hr';
        $loc = htmlspecialchars("{$j['city']}, {$j['province']}");
        $html .= "<tr class=\"border-b border-sf-bordli last:border-0\">
            <td class=\"px-5 py-3 font-medium text-sf-text\">" . htmlspecialchars($j['jobtitle']) . "</td>
            <td class=\"px-5 py-3 text-sf-muted\">" . htmlspecialchars($j['companyname']) . "</td>
            <td class=\"px-5 py-3 text-sf-muted\">{$loc}</td>
            <td class=\"px-5 py-3 text-sf-muted\">" . htmlspecialchars($j['location']) . "</td>
            <td class=\"px-5 py-3 font-semibold\" style=\"color:#2e844a;\">{$pay}</td>
        </tr>";
    }
    return $html;
}

if (!empty($_SERVER['HTTP_HX_REQUEST']) && isset($_GET['company'])) {
    $cid = (int) $_GET['company'];
    if ($cid === 0) {
        $rows = $db->query("SELECT j.jobtitle, j.location, j.city, j.province, j.payrate::text, c.companyname FROM jobad j INNER JOIN company c ON j.postedbycompanyid = c.companyid ORDER BY j.payrate::numeric DESC")->fetchAll();
    } else {
        $stmt = $db->prepare("SELECT j.jobtitle, j.location, j.city, j.province, j.payrate::text, c.companyname FROM jobad j INNER JOIN company c ON j.postedbycompanyid = c.companyid WHERE j.postedbycompanyid = ? ORDER BY j.payrate::numeric DESC");
        $stmt->execute([$cid]);
        $rows = $stmt->fetchAll();
    }
    echo renderJobRows($rows);
    exit;
}

$jobs      = $db->query("SELECT j.jobtitle, j.location, j.city, j.province, j.payrate::text, c.companyname FROM jobad j INNER JOIN company c ON j.postedbycompanyid = c.companyid ORDER BY j.payrate::numeric DESC")->fetchAll();
$companies = $db->query("SELECT companyid, companyname FROM company ORDER BY companyname")->fetchAll();

$options = '<option value="0">All Companies</option>';
foreach ($companies as $c) {
    $options .= "<option value=\"{$c['companyid']}\">" . htmlspecialchars($c['companyname']) . "</option>";
}

$jobRows = renderJobRows($jobs);

$content = <<<HTML
<h2 class="text-xl font-bold text-sf-text mb-0.5">Job Board</h2>
<p class="text-sf-muted text-sm mb-5">Job advertisements posted by sponsoring companies</p>

<div class="bg-white rounded border border-sf-border shadow-sm">
  <div class="px-5 py-3 border-b border-sf-bordli bg-sf-bg flex flex-wrap items-center gap-3">
    <label class="text-xs font-semibold text-sf-muted uppercase tracking-wide">Filter by Company</label>
    <select name="company"
      class="border border-sf-border rounded px-3 py-2 text-sm text-sf-text bg-white focus:outline-none focus:ring-2 focus:ring-sf-blue focus:border-sf-blue"
      hx-get="/jobs"
      hx-target="#job-rows"
      hx-trigger="change"
      hx-swap="innerHTML">
      {$options}
    </select>
  </div>
  <table class="w-full text-sm">
    <thead><tr class="text-left border-b border-sf-bordli">
      <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Job Title</th>
      <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Company</th>
      <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">City, Province</th>
      <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Location Type</th>
      <th class="px-5 py-3 font-semibold text-sf-muted text-xs uppercase tracking-wide">Pay Rate</th>
    </tr></thead>
    <tbody id="job-rows">{$jobRows}</tbody>
  </table>
</div>
HTML;

renderLayout('Job Board', '/jobs', $content);
