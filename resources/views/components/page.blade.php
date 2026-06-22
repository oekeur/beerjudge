@props(['title', 'vite' => ['resources/css/app.css'], 'themeInit' => true])
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <x-app-head :title="$title" :vite="$vite" :themeInit="$themeInit">
        {{ $head ?? '' }}
    </x-app-head>
</head>
{{ $slot }}
</html>
