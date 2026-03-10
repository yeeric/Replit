<?php
/**
 * Shared HTML layout helpers for the CISC 332 Conference app.
 * Every page uses renderLayout() to wrap its content in the sidebar shell.
 */

function pageHead(string $title): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title} | CISC 332</title>
  <script src="https://unpkg.com/htmx.org@2.0.4" defer></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: { DEFAULT: '#2563eb', dark: '#1d4ed8', light: '#dbeafe' }
          }
        }
      }
    }
  </script>
  <style>
    [x-cloak] { display: none !important; }
    dialog::backdrop { background: rgba(0,0,0,0.5); }
    dialog { border-radius: 0.75rem; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 0; width: min(540px, 95vw); }
    .htmx-indicator { opacity: 0; transition: opacity 200ms; }
    .htmx-request .htmx-indicator { opacity: 1; }
  </style>
</head>
<body class="bg-gray-100 text-gray-900 flex h-screen overflow-hidden">
HTML;
}

function sidebar(string $active): string {
    $links = [
        '/'           => ['icon' => '💰', 'label' => 'Finance Overview'],
        '/committees' => ['icon' => '👥', 'label' => 'Committees'],
        '/hotels'     => ['icon' => '🏨', 'label' => 'Hotel Rooms'],
        '/schedule'   => ['icon' => '📅', 'label' => 'Schedule'],
        '/sponsors'   => ['icon' => '🏢', 'label' => 'Sponsors & Companies'],
        '/jobs'       => ['icon' => '💼', 'label' => 'Job Board'],
        '/attendees'  => ['icon' => '🎫', 'label' => 'Attendees'],
    ];

    $html  = '<aside class="w-64 flex-shrink-0 bg-slate-900 text-white flex flex-col h-screen overflow-y-auto">';
    $html .= '<div class="px-6 py-5 border-b border-slate-700">';
    $html .= '<p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Conference</p>';
    $html .= '<h1 class="text-lg font-bold text-white mt-0.5">CISC 332</h1>';
    $html .= '</div>';
    $html .= '<nav class="flex-1 px-3 py-4 space-y-1">';

    foreach ($links as $href => $info) {
        $isActive = ($href === $active) || ($active === '/' && $href === '/');
        $cls = $isActive
            ? 'flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary text-white font-medium text-sm'
            : 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white text-sm transition-colors';
        $html .= "<a href=\"{$href}\" class=\"{$cls}\">";
        $html .= "<span>{$info['icon']}</span><span>{$info['label']}</span></a>";
    }

    $html .= '</nav>';
    $html .= '<div class="px-6 py-4 border-t border-slate-700 text-xs text-slate-500">PHP + HTMX + Tailwind</div>';
    $html .= '</aside>';
    return $html;
}

function renderLayout(string $title, string $active, string $content): void {
    echo pageHead($title);
    echo sidebar($active);
    echo '<main class="flex-1 overflow-y-auto">';
    echo '<div class="max-w-5xl mx-auto px-6 py-8">';
    echo $content;
    echo '</div></main>';
    echo '</body></html>';
}
