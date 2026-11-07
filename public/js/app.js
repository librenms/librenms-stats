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
const palette = ['#c9192c', '#414141', '#416fa1', '#8d5a35', '#627742', '#929292'];

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
    const details = element('details', 'chart-table');
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

    details.append(element('summary', '', 'View data table'), table);
    return details;
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

function renderBars(container, points, format, percent) {
    const list = element('ul', 'bars');
    const top = points.slice(0, 8);
    const max = percent ? 100 : Math.max(1, ...top.map(point => point.value));

    for (const { label, value } of top) {
        const item = element('li');
        const name = element('span', 'series-label', label);
        const track = element('span', 'bar-track');
        track.setAttribute('aria-hidden', 'true');
        const fill = element('span', 'bar-fill');
        fill.style.width = `${(value ?? 0) / max * 100}%`;
        track.append(fill);
        item.append(name, element('span', 'value', format(value)), track);
        list.append(item);
    }

    container.append(list);
    if (points.length > top.length) {
        container.append(element('p', 'chart-caption', `Top ${top.length} of ${points.length} categories`));
    }
}

function renderDonut(container, points, total, share, unit) {
    const top = points.slice(0, 5);
    if (points.length > top.length) {
        top.push({ label: 'Other categories', value: points.slice(top.length).reduce((sum, point) => sum + point.value, 0) });
    }

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
        item.append(swatch, element('span', 'series-label', label), element('span', 'value', share(value)));
        legend.append(item);
    }

    ring.style.background = total ? `conic-gradient(${stops.join(', ')})` : 'var(--border)';
    const layout = element('div', 'donut-layout');
    layout.append(ring, legend);
    container.append(layout);
}

function renderChart(chart, rows) {
    const { type, unit } = chart.dataset;
    const line = type === 'line';
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
    const total = type === 'donut' ? points.reduce((sum, point) => sum + point.value, 0) : 0;
    const share = value => total > 0 && value !== null ? percentShare.format(value / total) : '—';

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
                ? `Avg ${latest.installs > 0 ? number.format(latest.value / latest.installs) : '—'} ${unit.toLowerCase()} / install`
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
    } else if (type === 'donut') {
        renderDonut(chart, points, total, share, unit);
    } else {
        renderBars(chart, adoption ? entries : points, format, percent);
    }

    if (entries.length > 0) {
        const columns = [
            [line ? 'Date' : 'Category', point => point.label],
            [percent ? 'Percent' : unit, point => format(point.value)],
        ];
        if (type === 'donut') {
            columns.push(['Share', point => share(point.value)]);
        }
        if (perInstall) {
            columns.push(
                ['Installations', point => number.format(point.installs)],
                ['Avg / install', point => point.installs > 0 ? number.format(point.value / point.installs) : '—'],
            );
        }
        if (adoption) {
            columns.push(
                ['Using feature', point => number.format(point.active)],
                ['Reporting feature', point => number.format(point.reported)],
            );
        }
        chart.append(dataTable(entries, title, columns));
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
            throw new Error(`Chart request returned ${response.status}`);
        }

        const data = await response.json();
        for (const chart of charts) {
            renderChart(chart, data[chart.dataset.chart]);
        }
        status.textContent = 'Charts loaded.';
    } catch (failure) {
        console.error(failure);
        status.textContent = 'Chart data could not load.';
        error.hidden = false;
        for (const chart of charts) {
            chart.replaceChildren(element('p', 'chart-message', 'Chart data unavailable.'));
        }
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
