<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../layout.php';

$db   = getDb();
$reg  = (float) $db->query("SELECT COALESCE(SUM(fee),0)::numeric FROM attendee WHERE attendeetype IN ('Student','Professional')")->fetchColumn();
$spon = (float) $db->query("
    SELECT COALESCE(SUM(CASE sponsorlevel
        WHEN 'Platinum' THEN 10000 WHEN 'Gold' THEN 5000
        WHEN 'Silver' THEN 2500 WHEN 'Bronze' THEN 1000 ELSE 0 END),0)
    FROM sponsor")->fetchColumn();
$total = $reg + $spon;

/* SVG donut pie chart using SLDS colours */
function piePath(float $cx, float $cy, float $r, float $a1, float $a2, string $color): string {
    $x1 = $cx + $r * cos($a1); $y1 = $cy + $r * sin($a1);
    $x2 = $cx + $r * cos($a2); $y2 = $cy + $r * sin($a2);
    $lg = ($a2 - $a1 > M_PI) ? 1 : 0;
    return "<path d=\"M $cx $cy L $x1 $y1 A $r $r 0 $lg 1 $x2 $y2 Z\" fill=\"$color\"/>";
}
$cx = 90; $cy = 90; $r = 80;
$a0 = -M_PI / 2;
$regAng = ($total > 0) ? ($reg / $total) * 2 * M_PI : M_PI;
$svgChart = '<svg width="180" height="180" viewBox="0 0 180 180">';
if ($total > 0 && $reg > 0 && $spon > 0) {
    $svgChart .= piePath($cx,$cy,$r,$a0,$a0+$regAng,'#0176d3');
    $svgChart .= piePath($cx,$cy,$r,$a0+$regAng,$a0+2*M_PI,'#2e844a');
} elseif ($reg === 0.0) {
    $svgChart .= "<circle cx='$cx' cy='$cy' r='$r' fill='#2e844a'/>";
} else {
    $svgChart .= "<circle cx='$cx' cy='$cy' r='$r' fill='#0176d3'/>";
}
$svgChart .= "<circle cx='$cx' cy='$cy' r='44' fill='#f3f2f2'/>";
$svgChart .= '</svg>';

$fmt = fn(float $v) => '$' . number_format($v, 0);

$content = <<<HTML
<h2 class="text-xl font-bold text-sf-text mb-0.5">Finance Overview</h2>
<p class="text-sf-muted text-sm mb-5">Conference revenue summary</p>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
  <div class="bg-white rounded border border-sf-border p-5 shadow-sm">
    <p class="text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Total Revenue</p>
    <p class="text-3xl font-bold text-sf-text">{$fmt($total)}</p>
  </div>
  <div class="bg-white rounded border border-sf-border p-5 shadow-sm">
    <p class="text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Registration Fees</p>
    <p class="text-3xl font-bold" style="color:#0176d3;">{$fmt($reg)}</p>
  </div>
  <div class="bg-white rounded border border-sf-border p-5 shadow-sm">
    <p class="text-xs font-semibold text-sf-muted uppercase tracking-wide mb-1">Sponsorships</p>
    <p class="text-3xl font-bold" style="color:#2e844a;">{$fmt($spon)}</p>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
  <div class="bg-white rounded border border-sf-border p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-sf-text mb-4">Revenue Breakdown</h3>
    <div class="flex items-center gap-6">
      {$svgChart}
      <div class="space-y-3 flex-1">
        <div class="flex items-center gap-2">
          <span class="w-3 h-3 rounded-full inline-block shrink-0" style="background:#0176d3;"></span>
          <span class="text-sm text-sf-muted">Registration</span>
          <span class="ml-auto text-sm font-semibold text-sf-text">{$fmt($reg)}</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="w-3 h-3 rounded-full inline-block shrink-0" style="background:#2e844a;"></span>
          <span class="text-sm text-sf-muted">Sponsorship</span>
          <span class="ml-auto text-sm font-semibold text-sf-text">{$fmt($spon)}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="bg-white rounded border border-sf-border p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-sf-text mb-4">Fee Structure</h3>
    <table class="w-full text-sm">
      <thead><tr class="text-left border-b border-sf-bordli">
        <th class="pb-2 font-semibold text-sf-muted text-xs uppercase tracking-wide">Type</th>
        <th class="pb-2 font-semibold text-sf-muted text-xs uppercase tracking-wide text-right">Fee</th>
      </tr></thead>
      <tbody class="divide-y divide-sf-bordli">
        <tr><td class="py-2 text-sf-text">Student</td><td class="py-2 text-right font-semibold text-sf-text">$50</td></tr>
        <tr><td class="py-2 text-sf-text">Professional</td><td class="py-2 text-right font-semibold text-sf-text">$100</td></tr>
        <tr><td class="py-2 text-sf-muted">Sponsor</td><td class="py-2 text-right text-sf-muted">Comped</td></tr>
        <tr><td class="py-2 text-sf-muted">Platinum Sponsor</td><td class="py-2 text-right text-sf-muted">$10,000</td></tr>
        <tr><td class="py-2 text-sf-muted">Gold Sponsor</td><td class="py-2 text-right text-sf-muted">$5,000</td></tr>
        <tr><td class="py-2 text-sf-muted">Silver Sponsor</td><td class="py-2 text-right text-sf-muted">$2,500</td></tr>
        <tr><td class="py-2 text-sf-muted">Bronze Sponsor</td><td class="py-2 text-right text-sf-muted">$1,000</td></tr>
      </tbody>
    </table>
  </div>
</div>
HTML;

renderLayout('Finance Overview', '/', $content);
