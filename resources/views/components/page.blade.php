@props(['title', 'vite' => ['resources/css/app.css'], 'head' => ''])
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <x-app-head :title="$title" :vite="$vite">
        {{ $head }}
    </x-app-head>
</head>
{{ $slot }}
</html>
