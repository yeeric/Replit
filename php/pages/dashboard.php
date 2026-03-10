<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db  = getDb();
$reg = (float) $db->query("SELECT COALESCE(SUM(fee),0)::numeric FROM attendee WHERE attendeetype IN ('Student','Professional')")->fetchColumn();
$spon = (float) $db->query("
    SELECT COALESCE(SUM(CASE sponsorlevel
        WHEN 'Platinum' THEN 10000 WHEN 'Gold' THEN 5000
        WHEN 'Silver'   THEN 2500  WHEN 'Bronze' THEN 1000 ELSE 0 END),0)
    FROM sponsor")->fetchColumn();
$total = $reg + $spon;

// Build SVG pie chart
function piePath(float $cx, float $cy, float $r, float $startAngle, float $endAngle, string $color): string {
    $x1 = $cx + $r * cos($startAngle);
    $y1 = $cy + $r * sin($startAngle);
    $x2 = $cx + $r * cos($endAngle);
    $y2 = $cy + $r * sin($endAngle);
    $large = ($endAngle - $startAngle > M_PI) ? 1 : 0;
    return "<path d=\"M $cx $cy L $x1 $y1 A $r $r 0 $large 1 $x2 $y2 Z\" fill=\"$color\"/>";
}

$cx = 90; $cy = 90; $r = 80;
$startReg = -M_PI / 2;
$regAngle = ($total > 0) ? ($reg / $total) * 2 * M_PI : M_PI;
$endReg   = $startReg + $regAngle;
$svgChart = '<svg width="180" height="180" viewBox="0 0 180 180">';
if ($total > 0 && $reg > 0 && $spon > 0) {
    $svgChart .= piePath($cx, $cy, $r, $startReg, $endReg, '#2563eb');
    $svgChart .= piePath($cx, $cy, $r, $endReg, $startReg + 2 * M_PI, '#10b981');
} elseif ($reg === 0.0) {
    $svgChart .= "<circle cx='$cx' cy='$cy' r='$r' fill='#10b981'/>";
} else {
    $svgChart .= "<circle cx='$cx' cy='$cy' r='$r' fill='#2563eb'/>";
}
// Donut hole
$svgChart .= "<circle cx='$cx' cy='$cy' r='45' fill='#f3f4f6'/>";
$svgChart .= '</svg>';

$fmtMoney = fn(float $v) => '$' . number_format($v, 0);

$content = <<<HTML
<h2 class="text-2xl font-bold text-gray-800 mb-1">Finance Overview</h2>
<p class="text-gray-500 text-sm mb-6">Conference revenue summary</p>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
  <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Total Revenue</p>
    <p class="text-3xl font-bold text-gray-900">{$fmtMoney($total)}</p>
  </div>
  <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Registration Fees</p>
    <p class="text-3xl font-bold text-blue-600">{$fmtMoney($reg)}</p>
  </div>
  <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Sponsorships</p>
    <p class="text-3xl font-bold text-emerald-600">{$fmtMoney($spon)}</p>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
  <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
    <h3 class="font-semibold text-gray-700 mb-4">Revenue Breakdown</h3>
    <div class="flex items-center gap-6">
      {$svgChart}
      <div class="space-y-3">
        <div class="flex items-center gap-2">
          <span class="w-3 h-3 rounded-full bg-blue-600 inline-block"></span>
          <span class="text-sm text-gray-600">Registration</span>
          <span class="ml-auto text-sm font-semibold">{$fmtMoney($reg)}</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
          <span class="text-sm text-gray-600">Sponsorship</span>
          <span class="ml-auto text-sm font-semibold">{$fmtMoney($spon)}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
    <h3 class="font-semibold text-gray-700 mb-4">Fee Structure</h3>
    <table class="w-full text-sm">
      <thead><tr class="text-left text-gray-400 border-b border-gray-100">
        <th class="pb-2 font-medium">Type</th><th class="pb-2 font-medium text-right">Fee</th>
      </tr></thead>
      <tbody class="divide-y divide-gray-100">
        <tr><td class="py-2 text-gray-700">Student</td><td class="py-2 text-right font-semibold">$50</td></tr>
        <tr><td class="py-2 text-gray-700">Professional</td><td class="py-2 text-right font-semibold">$100</td></tr>
        <tr><td class="py-2 text-gray-700">Sponsor</td><td class="py-2 text-right font-semibold text-gray-400">Comped</td></tr>
        <tr class="text-gray-400"><td class="py-2">Platinum Sponsor</td><td class="py-2 text-right">$10,000</td></tr>
        <tr class="text-gray-400"><td class="py-2">Gold Sponsor</td><td class="py-2 text-right">$5,000</td></tr>
        <tr class="text-gray-400"><td class="py-2">Silver Sponsor</td><td class="py-2 text-right">$2,500</td></tr>
        <tr class="text-gray-400"><td class="py-2">Bronze Sponsor</td><td class="py-2 text-right">$1,000</td></tr>
      </tbody>
    </table>
  </div>
</div>
HTML;

renderLayout('Finance Overview', '/', $content);
