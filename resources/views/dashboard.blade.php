<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Explore aggregate statistics shared by LibreNMS installations.">
    <title>Usage Statistics · LibreNMS</title>
    <link rel="icon" href="{{ asset('img/favicon.png') }}" type="image/png">
    <script src="{{ asset('js/theme.js') }}?v={{ config('stats.asset_version') }}"></script>
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
            <div class="header-actions">
                <fieldset class="theme-control" hidden>
                    <legend class="sr-only">Color theme</legend>
                    <label class="theme-option" title="Follow system appearance">
                        <input type="radio" name="theme" value="auto" aria-label="Auto (follow system appearance)" checked>
                        <span>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                        </span>
                    </label>
                    <label class="theme-option" title="Use light appearance">
                        <input type="radio" name="theme" value="light" aria-label="Light">
                        <span>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/></svg>
                        </span>
                    </label>
                    <label class="theme-option" title="Use dark appearance">
                        <input type="radio" name="theme" value="dark" aria-label="Dark">
                        <span>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 13.3A9 9 0 0 1 10.7 3.5a9 9 0 1 0 9.8 9.8Z"/></svg>
                        </span>
                    </label>
                </fieldset>
                <a class="privacy-link" href="https://docs.librenms.org/General/Callback-Stats-and-Privacy/">Privacy</a>
            </div>
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
