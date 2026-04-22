<title>{{ $seo->title }}</title>
<meta name="description" content="{{ $seo->description }}">
<link rel="canonical" href="{{ $seo->url }}">
@if($seo->favicon)
    <link rel="icon" href="{{ $seo->favicon }}">
    <link rel="shortcut icon" href="{{ $seo->favicon }}">
    <link rel="apple-touch-icon" href="{{ $seo->favicon }}">
@endif
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:site_name" content="{{ config('app.name', 'Vendly') }}">
<meta property="og:locale" content="es_CO">
<meta property="og:title" content="{{ $seo->title }}">
<meta property="og:description" content="{{ $seo->description }}">
<meta property="og:url" content="{{ $seo->url }}">
@if($seo->image)
    <meta property="og:image" content="{{ $seo->image }}">
    <meta property="og:image:secure_url" content="{{ $seo->image }}">
    @if($seo->imageAlt)
        <meta property="og:image:alt" content="{{ $seo->imageAlt }}">
    @endif
@endif
<meta name="twitter:card" content="{{ $seo->image ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo->title }}">
<meta name="twitter:description" content="{{ $seo->description }}">
@if($seo->image)
    <meta name="twitter:image" content="{{ $seo->image }}">
    @if($seo->imageAlt)
        <meta name="twitter:image:alt" content="{{ $seo->imageAlt }}">
    @endif
@endif
