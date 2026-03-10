<?php
/**
 * Shared HTML layout — Salesforce Lightning Design System colour scheme.
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
            /* Salesforce Lightning palette */
            sf: {
              navy:    '#032d60',  /* sidebar background */
              navymd: '#0a2e5c',  /* sidebar hover / active */
              navyli: '#0b3d91',  /* sidebar divider tint */
              blue:   '#0176d3',  /* primary action blue */
              bluehv: '#014486',  /* primary hover */
              blueli: '#d8edff',  /* light blue background */
              bluebr: '#a8c8e8',  /* blue border */
              bg:     '#f3f2f2',  /* app background */
              border: '#dddbda',  /* card / input borders */
              bordli: '#e5e4e2',  /* light divider */
              text:   '#3e3e3c',  /* primary text */
              muted:  '#706e6b',  /* secondary / placeholder text */
              navtxt: '#c9dff5',  /* sidebar inactive text */
              green:  '#2e844a',  /* success dark */
              greenli:'#4bca81',  /* success light */
              red:    '#ba0517',  /* destructive */
              redli:  '#fcdbd9',  /* destructive bg */
              redbr:  '#f4b8b3',  /* destructive border */
            }
          }
        }
      }
    }
  </script>
  <style>
    dialog::backdrop { background: rgba(3,45,96,0.55); }
    dialog { border-radius: 0.5rem; border: 1px solid #dddbda; box-shadow: 0 8px 32px rgba(3,45,96,0.18); padding: 0; width: min(520px, 95vw); }
    .htmx-indicator { opacity: 0; transition: opacity 200ms; }
    .htmx-request .htmx-indicator { opacity: 1; }
  </style>
</head>
<body class="bg-sf-bg text-sf-text flex h-screen overflow-hidden">
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

    $html  = '<aside class="w-64 flex-shrink-0 flex flex-col h-screen overflow-y-auto" style="background:#032d60;">';

    /* Branding header */
    $html .= '<div class="px-5 py-4" style="border-bottom:1px solid #0a2e5c;">';
    $html .= '<p class="text-xs font-semibold uppercase tracking-widest" style="color:#7ba5c9;">Conference</p>';
    $html .= '<h1 class="text-base font-bold text-white mt-0.5 leading-tight">CISC 332</h1>';
    $html .= '</div>';

    $html .= '<nav class="flex-1 px-2 py-3 space-y-0.5">';

    foreach ($links as $href => $info) {
        $isActive = ($href === $active);
        if ($isActive) {
            $cls  = 'flex items-center gap-3 px-3 py-2.5 rounded text-white font-semibold text-sm';
            $style = 'background:#0a2e5c; border-left:3px solid #0176d3;';
        } else {
            $cls  = 'flex items-center gap-3 px-3 py-2.5 rounded text-sm transition-colors';
            $style = 'color:#c9dff5; border-left:3px solid transparent;';
        }
        $html .= "<a href=\"{$href}\" class=\"{$cls}\" style=\"{$style}\"";
        if (!$isActive) {
            $html .= " onmouseover=\"this.style.background='#0a2e5c';this.style.color='#ffffff'\"";
            $html .= " onmouseout=\"this.style.background='';this.style.color='#c9dff5'\"";
        }
        $html .= ">";
        $html .= "<span class=\"text-base\">{$info['icon']}</span><span>{$info['label']}</span></a>";
    }

    $html .= '</nav>';
    $html .= '<div class="px-5 py-3 text-xs" style="border-top:1px solid #0a2e5c; color:#7ba5c9;">PHP · HTMX · Tailwind</div>';
    $html .= '</aside>';
    return $html;
}

function renderLayout(string $title, string $active, string $content): void {
    echo pageHead($title);
    echo sidebar($active);
    echo '<main class="flex-1 overflow-y-auto bg-sf-bg">';
    echo '<div class="max-w-5xl mx-auto px-6 py-7">';
    echo $content;
    echo '</div></main>';
    echo '</body></html>';
}
