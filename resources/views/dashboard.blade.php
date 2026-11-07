<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Explore aggregate statistics shared by LibreNMS installations.">
    <title>Usage Statistics · LibreNMS</title>
    <link rel="icon" href="{{ asset('img/favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ config('stats.asset_version') }}">
    <script src="{{ asset('js/app.js') }}?v={{ config('stats.asset_version') }}" defer></script>
</head>
<body>
    <header class="site-header">
        <div class="shell site-header-inner">
            <a class="brand" href="https://www.librenms.org/" aria-label="LibreNMS home">
                <img src="{{ asset('img/librenms.svg') }}" alt="LibreNMS" width="133" height="26">
            </a>
            <h1>Usage Statistics</h1>
            <a class="privacy-link" href="https://docs.librenms.org/General/Callback-Stats-and-Privacy/">Privacy</a>
        </div>
    </header>

    <main class="shell">
        <p class="reporting-count"><strong>{{ number_format($submitters) }}</strong> {{ $submitters === 1 ? 'installation' : 'installations' }} reporting in the last 24 hours</p>

        <nav class="category-nav" aria-label="Chart categories">
            @foreach ($chartGroups->keys() as $category)
                <a href="#category-{{ Illuminate\Support\Str::slug($category) }}">{{ $category }}</a>
            @endforeach
        </nav>

        <div id="dashboard" data-source="{{ route('charts', absolute: false) }}">
            <p id="chart-status" class="sr-only" role="status"></p>
            <div id="chart-error" class="notice" hidden>
                <span>Chart data couldn’t load.</span>
                <button type="button" id="chart-retry">Try again</button>
            </div>
            <noscript><p class="notice">Enable JavaScript to view the charts.</p></noscript>

            @foreach ($chartGroups as $category => $charts)
                <section class="chart-group" id="category-{{ Illuminate\Support\Str::slug($category) }}" aria-labelledby="group-{{ $loop->index }}">
                    <h2 class="group-title" id="group-{{ $loop->index }}">{{ $category }}</h2>
                    <div class="chart-grid">
                        @foreach ($charts as $id => $chart)
                            @php($anchor = substr($id, strlen('draw-')))
                            @php($displayType = $chart['display_type'] ?? $chart['type'])
                            <article class="chart-card" id="{{ $anchor }}" aria-labelledby="{{ $anchor }}-title">
                                <header class="card-heading">
                                    <h3 id="{{ $anchor }}-title">{{ $chart['title'] }}</h3>
                                    <span class="chart-period">{{ $chart['type'] === 'line' ? 'Last 3 months' : 'Last 24 hours' }}</span>
                                </header>
                                @if($chart['adoption'] ?? false)
                                    <p class="chart-note">Among installations that reported each feature. Distributed polling means two or more reported pollers.</p>
                                @endif
                                <div class="chart" data-chart="{{ $id }}" data-type="{{ $chart['type'] }}" data-display-type="{{ $displayType }}" data-visible-limit="{{ config('stats.visible_categories')[$displayType] ?? '' }}" data-unit="{{ $chart['unit'] ?? 'Total' }}" @if($chart['percent'] ?? false) data-percent @endif @if($chart['adoption'] ?? false) data-adoption @endif @if($chart['per_install'] ?? false) data-per-install @endif>
                                    <p class="chart-message">Loading chart…</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </main>
</body>
</html>
