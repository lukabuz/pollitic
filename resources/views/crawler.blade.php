<!doctype html>
<html lang="ge">
	<head>
        <meta charset="utf-8">
        <meta property="og:url"                content="{{ $meta['url'] }}" />
        <meta property="og:title"              content="{{ $meta['title'] }}" />
        <meta property="og:description"        content="{{ $meta['description'] }}" />
        @if(isset($meta['image']))
            <meta property="og:image" content="{{ $meta['image']}}" />
        @else
            <meta property="og:image" content="https://www.pollitic.ge/static/media/default.663af676.png" />
        @endif
        <meta property="og:type"              content="{{ $meta['type'] }}" />
		<link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
		<title>Pollitic - {{ $meta['title'] }}</title>
	</head>
	<body>
    </body>
</html>