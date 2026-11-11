const dashboard = document.querySelector('#dashboard');
const charts = [...dashboard.querySelectorAll('[data-chart]')];
const categoryNav = document.querySelector('.category-nav');
const categoryLinks = [...categoryNav.querySelectorAll('a')];
const categoryGroups = [...dashboard.querySelectorAll('.chart-group')];
const status = document.querySelector('#chart-status');
const error = document.querySelector('#chart-error');
const number = new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 });
const compact = new Intl.NumberFormat(undefined, { notation: 'compact', maximumFractionDigits: 1 });
const percentShare = new Intl.NumberFormat(undefined, { style: 'percent', maximumFractionDigits: 1 });
const date = new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', timeZone: 'UTC' });
const palette = Array.from({ length: 6 }, (_, index) => `var(--series-${index + 1})`);

function element(tag, className = '', value = '') {
    const node = document.createElement(tag);
    node.className = className;
    node.textContent = value;
    return node;
}

function svgElement(tag, attributes = {}, value = '') {
    const node = document.createElementNS('http://www.w3.org/2000/svg', tag);
    for (const [key, attribute] of Object.entries(attributes)) {
        node.setAttribute(key, attribute);
    }
    node.textContent = value;
    return node;
}

function dataTable(points, title, columns) {
    const table = element('table');
    const caption = element('caption', 'sr-only', title);
    table.append(caption);
    const header = table.createTHead().insertRow();
    for (const [label] of columns) {
        const cell = element('th', '', label);
        cell.scope = 'col';
        header.append(cell);
    }

    const body = table.createTBody();
    for (const point of points) {
        const row = body.insertRow();
        for (const [, format] of columns) {
            row.insertCell().textContent = format(point);
        }
    }

    return table;
}

function chartDetails(label, content) {
    const details = element('details', 'chart-table');
    details.append(element('summary', '', label), content);
    return details;
}

function formatAverage(point) {
    return point.installs > 0 ? number.format(Math.round(point.value / point.installs)) : '—';
}

function renderLine(container, points, title, percent, format) {
    const width = Math.max(240, Math.floor(container.clientWidth));
    const height = 164;
    const left = 44;
    const right = width - 14;
    const top = 13;
    const bottom = height - 27;
    const max = Math.max(1, ...points.map(point => point.value));
    const magnitude = 10 ** Math.floor(Math.log10(max / 4));
    const step = Math.ceil(max / 4 / magnitude) * magnitude;

    const svg = svgElement('svg', { viewBox: `0 0 ${width} ${height}`, role: 'img', 'aria-label': title });
    const dates = points.map(point => Date.parse(`${point.label}T00:00:00Z`));
    const span = dates.at(-1) - dates[0];
    const x = index => span ? left + (dates[index] - dates[0]) / span * (right - left) : (left + right) / 2;
    const y = value => bottom - value / (step * 4) * (bottom - top);

    for (let tick = 0; tick <= 4; tick++) {
        const value = tick * step;
        const position = y(value);
        svg.append(
            svgElement('line', { x1: left, y1: position, x2: right, y2: position, class: 'gridline' }),
            svgElement('text', { x: left - 7, y: position + 4, 'text-anchor': 'end' }, percent ? format(value) : compact.format(value)),
        );
    }

    const coordinates = points.map(({ value }, index) => `${x(index)},${y(value)}`).join(' ');
    svg.append(
        svgElement('polygon', { points: `${x(0)},${bottom} ${coordinates} ${x(points.length - 1)},${bottom}`, class: 'area' }),
        svgElement('polyline', { points: coordinates, class: 'line' }),
    );

    const ticks = span ? Math.min(points.length, width >= 410 ? 4 : 3) : 1;
    for (let tick = 0; tick < ticks; tick++) {
        const fraction = ticks === 1 ? .5 : tick / (ticks - 1);
        const label = date.format(dates[0] + span * fraction);
        const anchor = ticks === 1 ? 'middle' : tick === 0 ? 'start' : tick === ticks - 1 ? 'end' : 'middle';
        svg.append(svgElement('text', { x: left + fraction * (right - left), y: height - 5, 'text-anchor': anchor }, label));
    }

    for (const [index, point] of points.entries()) {
        const marker = svgElement('circle', { cx: x(index), cy: y(point.value), r: points.length > 40 ? 2 : 3, class: 'point' });
        marker.append(svgElement('title', {}, `${point.label} · ${format(point.value)}`));
        svg.append(marker);
    }

    container.replaceChildren(svg);
}

function barList(points, max, format) {
    const list = element('ul', 'bars');

    for (const point of points) {
        const item = element('li');
        const name = element('span', 'series-label', point.label);
        const track = element('span', 'bar-track');
        track.setAttribute('aria-hidden', 'true');
        const fill = element('span', 'bar-fill');
        fill.style.width = `${(point.value ?? 0) / max * 100}%`;
        track.append(fill);
        item.append(name, element('span', 'value', format(point)), track);
        list.append(item);
    }

    return list;
}

function renderDonut(container, points, title, unit, visibleLimit) {
    const top = points.slice(0, visibleLimit);
    const other = points.slice(top.length);
    if (other.length > 0) {
        top.push({ label: 'Other', value: other.reduce((sum, point) => sum + point.value, 0) });
    }

    const total = points.reduce((sum, point) => sum + point.value, 0);
    const share = value => total > 0 ? percentShare.format(value / total) : '—';
    const ring = element('div', 'donut');
    const center = element('div', 'donut-total');
    const count = element('strong', '', compact.format(total));
    count.title = number.format(total);
    center.append(count, element('span', '', unit));
    ring.append(center);
    const legend = element('ul', 'legend');
    const stops = [];
    let start = 0;

    for (const [index, { label, value }] of top.entries()) {
        const color = palette[index];
        const end = start + (total ? value / total * 100 : 0);
        stops.push(`${color} ${start}% ${end}%`);
        start = end;

        const item = element('li');
        const swatch = element('span', 'swatch');
        swatch.style.backgroundColor = color;
        item.append(swatch, element('span', 'series-label', label), element('span', 'value', `${number.format(value)} · ${share(value)}`));
        legend.append(item);
    }

    ring.style.background = total ? `conic-gradient(${stops.join(', ')})` : 'var(--border)';
    const layout = element('div', 'donut-layout');
    layout.append(ring, legend);
    container.append(layout);

    if (other.length > 0) {
        const table = dataTable(other, `${title} · Other`, [
            ['Category', point => point.label],
            [unit, point => number.format(point.value)],
            ['Share', point => share(point.value)],
        ]);
        container.append(chartDetails(`Show other (${number.format(other.length)})`, table));
    }
}

function renderChart(chart, rows) {
    const { type, displayType = type, unit } = chart.dataset;
    const visibleLimit = Number(chart.dataset.visibleLimit) || (displayType === 'donut' ? 5 : 8);
    const line = displayType === 'line';
    const percent = chart.hasAttribute('data-percent');
    const adoption = chart.hasAttribute('data-adoption');
    const perInstall = chart.hasAttribute('data-per-install');
    const entries = (rows ?? []).map(row => {
        const rawValue = type === 'donut' ? row.value : row.a;
        const value = Number(rawValue);
        const label = type === 'donut' ? row.label : row.y;

        return { ...row, label: label || 'Unknown', value: rawValue == null || !Number.isFinite(value) ? null : value };
    });
    entries.sort(line
        ? (a, b) => a.label.localeCompare(b.label)
        : (a, b) => (b.value ?? -Infinity) - (a.value ?? -Infinity));
    const points = entries.filter(point => point.value !== null && (!line || /^\d{4}-\d{2}-\d{2}$/.test(point.label)));

    chart.replaceChildren();
    const title = chart.closest('article').querySelector('h3').textContent;
    const format = value => value === null ? '—' : percent ? `${number.format(value)}%` : number.format(value);
    if (points.length === 0 && !(adoption && entries.length > 0)) {
        chart.append(element('p', 'chart-message', 'No data for this period.'));
    } else if (line) {
        const latest = points.at(-1);
        const latestValue = element('p', 'chart-latest');
        latestValue.append(
            element('strong', '', format(latest.value)),
            element('span', '', perInstall
                ? `Avg ${formatAverage(latest)} ${unit.toLowerCase()} / install`
                : 'of reported devices'),
        );
        const plot = element('div');
        chart.append(latestValue, plot);
        renderLine(plot, points, title, percent, format);

        let width = plot.clientWidth;
        new ResizeObserver(() => {
            if (width !== plot.clientWidth) {
                width = plot.clientWidth;
                renderLine(plot, points, title, percent, format);
            }
        }).observe(plot);
    } else if (displayType === 'donut') {
        renderDonut(chart, points, title, unit, visibleLimit);
    } else {
        const bars = adoption ? entries : points;
        const max = percent ? 100 : bars.reduce((largest, point) => Math.max(largest, point.value), 1);
        const barValue = adoption
            ? point => point.reported > 0
                ? `${format(point.value)} (${number.format(point.active)} of ${number.format(point.reported)})`
                : '— (0 reported)'
            : point => format(point.value);
        chart.append(barList(bars.slice(0, visibleLimit), max, barValue));
        const extra = bars.slice(visibleLimit);
        if (extra.length > 0) {
            chart.append(chartDetails(`Show ${number.format(extra.length)} more`, barList(extra, max, barValue)));
        }
    }

    if (line && entries.length > 0) {
        const columns = [
            ['Date', point => point.label],
            [percent ? 'Percent' : unit, point => format(point.value)],
        ];
        if (perInstall) {
            columns.push(
                ['Installations', point => number.format(point.installs)],
                ['Avg / install', formatAverage],
            );
        }
        chart.append(chartDetails('View daily values', dataTable(entries, title, columns)));
    }
}

function showChartError(failure) {
    console.error(failure);
    status.textContent = 'Chart data could not load.';
    error.hidden = false;
    for (const chart of charts) {
        chart.replaceChildren(element('p', 'chart-message', 'Chart data unavailable.'));
    }
}

async function loadCharts() {
    error.hidden = true;
    status.textContent = 'Loading chart data.';
    for (const chart of charts) {
        chart.replaceChildren(element('p', 'chart-message', 'Loading chart…'));
    }

    try {
        const response = await fetch(dashboard.dataset.source, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            showChartError(new Error(`Chart request returned ${response.status}`));
            return;
        }

        const data = await response.json();
        for (const chart of charts) {
            renderChart(chart, data[chart.dataset.chart]);
        }
        status.textContent = 'Charts loaded.';
    } catch (failure) {
        showChartError(failure);
    }
}

function showCategoryFromLocation() {
    const target = document.getElementById(window.location.hash.slice(1));
    const targetGroup = target?.closest('.chart-group');
    const group = targetGroup ?? categoryGroups[0];

    for (const section of categoryGroups) {
        section.hidden = section !== group;
    }
    for (const link of categoryLinks) {
        if (link.hash === `#${group.id}`) {
            link.setAttribute('aria-current', 'location');
        } else {
            link.removeAttribute('aria-current');
        }
    }

    if (targetGroup) {
        target.scrollIntoView({ block: 'start' });
    }
}

function measureCategoryNav() {
    document.documentElement.style.setProperty('--category-nav-height', `${categoryNav.offsetHeight}px`);
}

measureCategoryNav();
new ResizeObserver(measureCategoryNav).observe(categoryNav);
window.addEventListener('hashchange', showCategoryFromLocation);
showCategoryFromLocation();
document.querySelector('#chart-retry').addEventListener('click', loadCharts);
loadCharts();
