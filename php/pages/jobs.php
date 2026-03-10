<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db = getDb();

// HTMX partial: jobs for a company
if (!empty($_SERVER['HTTP_HX_REQUEST']) && isset($_GET['company'])) {
    $cid = (int) $_GET['company'];
    if ($cid === 0) {
        // Return all jobs
        $rows = $db->query("
            SELECT j.jobtitle, j.location, j.city, j.province, j.payrate::text, c.companyname
            FROM jobad j INNER JOIN company c ON j.postedbycompanyid = c.companyid
            ORDER BY j.payrate::numeric DESC
        ")->fetchAll();
    } else {
        $stmt = $db->prepare("
            SELECT j.jobtitle, j.location, j.city, j.province, j.payrate::text, c.companyname
            FROM jobad j INNER JOIN company c ON j.postedbycompanyid = c.companyid
            WHERE j.postedbycompanyid = ?
            ORDER BY j.payrate::numeric DESC
        ");
        $stmt->execute([$cid]);
        $rows = $stmt->fetchAll();
    }
    echo renderJobRows($rows);
    exit;
}

function renderJobRows(array $rows): string {
    if (empty($rows)) {
        return '<tr><td colspan="5" class="px-6 py-6 text-center text-gray-400 text-sm">No job ads found.</td></tr>';
    }
    $html = '';
    foreach ($rows as $j) {
        $pay = '$' . number_format((float)$j['payrate'], 2) . '/hr';
        $loc = htmlspecialchars("{$j['city']}, {$j['province']}");
        $html .= "<tr class=\"border-b border-gray-100 last:border-0\">
            <td class=\"px-6 py-3 font-medium\">" . htmlspecialchars($j['jobtitle']) . "</td>
            <td class=\"px-6 py-3 text-gray-500\">" . htmlspecialchars($j['companyname']) . "</td>
            <td class=\"px-6 py-3 text-gray-500\">{$loc}</td>
            <td class=\"px-6 py-3 text-gray-500\">" . htmlspecialchars($j['location']) . "</td>
            <td class=\"px-6 py-3 font-semibold text-emerald-700\">{$pay}</td>
        </tr>";
    }
    return $html;
}

// Full page
$jobs      = $db->query("
    SELECT j.jobtitle, j.location, j.city, j.province, j.payrate::text, c.companyname, c.companyid
    FROM jobad j INNER JOIN company c ON j.postedbycompanyid = c.companyid
    ORDER BY j.payrate::numeric DESC
")->fetchAll();

$companies = $db->query("SELECT companyid, companyname FROM company ORDER BY companyname")->fetchAll();

$options = '<option value="0">All Companies</option>';
foreach ($companies as $c) {
    $options .= "<option value=\"{$c['companyid']}\">" . htmlspecialchars($c['companyname']) . "</option>";
}

$jobRows = renderJobRows($jobs);

$content = <<<HTML
<h2 class="text-2xl font-bold text-gray-800 mb-1">Job Board</h2>
<p class="text-gray-500 text-sm mb-6">Job advertisements posted by sponsoring companies</p>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
  <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center gap-4">
    <label class="text-sm font-medium text-gray-700">Filter by Company:</label>
    <select name="company"
      class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      hx-get="/jobs"
      hx-target="#job-rows"
      hx-trigger="change"
      hx-swap="innerHTML">
      {$options}
    </select>
  </div>
  <table class="w-full text-sm">
    <thead><tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
      <th class="px-6 py-3 font-medium">Job Title</th>
      <th class="px-6 py-3 font-medium">Company</th>
      <th class="px-6 py-3 font-medium">City, Province</th>
      <th class="px-6 py-3 font-medium">Location Type</th>
      <th class="px-6 py-3 font-medium">Pay Rate</th>
    </tr></thead>
    <tbody id="job-rows">{$jobRows}</tbody>
  </table>
</div>
HTML;

renderLayout('Job Board', '/jobs', $content);
