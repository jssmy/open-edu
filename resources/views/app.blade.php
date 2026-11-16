<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/jpeg" href="/images/workshops/logo.jpeg">
    @vite('resources/js/app.ts')
    @inertiaHead
</head>
<body class="bg-surface font-sans text-text antialiased">
    @inertia
</body>
</html>
