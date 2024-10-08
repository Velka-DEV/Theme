<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>%theme_name% theme</title>
    <link rel="icon" href="{{ Vite::asset('themes/%theme_name%/assets/images/favicon.png') }}" type="image/png"/>
    @vite([
        'themes/%theme_name%/assets/styles/app.scss',
    ])
</head>

<body>

@include('partials.header')

<div>{{ $slot }}</div>

@include('partials.footer')
@vite([
    'themes/%theme_name%/assets/scripts/app.ts',
])
</body>
</html>
