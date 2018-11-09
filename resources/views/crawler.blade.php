<!doctype html>
<html lang="ge">
	<head>
        <meta charset="utf-8">
        <meta property="og:url"                content="{{ $meta['url'] }}" />
        <meta property="og:title"              content="{{ $meta['title'] }}" />
        <meta property="og:description"        content="{{ $meta['description'] }}" />
        <meta property="og:image"              content="{{ $meta['image'] }}" />
		<link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
		<title>Pollitic - {{ $meta['title'] }}</title>
	</head>
	<body>
		<noscript>
			<h3>აუცილებელია გქონდეთ ჩართული Javascript, რომ იხილოთ ეს საიტი.</h3>
		</noscript>
		<div id="root"></div>
		</body>
</html>