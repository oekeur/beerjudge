@props(['title', 'vite' => ['resources/css/app.css'], 'themeInit' => true])
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#b45309">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<title>{{ $title }}</title>
@vite($vite)
@if($themeInit)
    <x-theme-init />
@endif
{{ $slot }}
