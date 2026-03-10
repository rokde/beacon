/**
 * Beacon Chart Module
 *
 * Uses named Chart.js imports for tree-shaking — only registers
 * the components actually needed.
 */
import {
    Chart,
    CategoryScale,
    LinearScale,
    TimeScale,
    PointElement,
    LineElement,
    BarElement,
    Filler,
    Tooltip,
    Legend,
    type ChartConfiguration,
    type ChartDataset as CJSDataset,
} from 'chart.js'

import type { ChartOptions, AggregatePoint, ForecastPoint } from './types'

// Register only what we need — keeps the bundle small
Chart.register(
    CategoryScale,
    LinearScale,
    TimeScale,
    PointElement,
    LineElement,
    BarElement,
    Filler,
    Tooltip,
    Legend,
)

// ─── CSS variable helpers ────────────────────────────────────────────────────

function cssVar(name: string): string {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim()
}

// ─── Formatters ─────────────────────────────────────────────────────────────

function formatLabel(periodStart: string, granularity: ChartOptions['granularity']): string {
    const d = new Date(periodStart)
    switch (granularity) {
        case 'minute':
        case 'hour':
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        case 'day':
            return d.toLocaleDateString([], { month: 'short', day: 'numeric' })
        case 'week':
            return `W${getISOWeek(d)} ${d.getFullYear()}`
        case 'month':
            return d.toLocaleDateString([], { month: 'short', year: '2-digit' })
        case 'year':
            return String(d.getFullYear())
    }
}

function getISOWeek(d: Date): number {
    const date = new Date(d.getTime())
    date.setHours(0, 0, 0, 0)
    date.setDate(date.getDate() + 3 - ((date.getDay() + 6) % 7))
    const week1 = new Date(date.getFullYear(), 0, 4)
    return (
        1 +
        Math.round(
            ((date.getTime() - week1.getTime()) / 86400000 - 3 + ((week1.getDay() + 6) % 7)) / 7,
        )
    )
}

// ─── Dataset builders ────────────────────────────────────────────────────────

function buildHistoricalDataset(
    label: string,
    data: AggregatePoint[],
    color: string,
    type: ChartOptions['type'],
): CJSDataset<'line' | 'bar'> {
    const base = {
        label,
        data: data.map((p) => p.value),
        borderColor: color,
        backgroundColor: type === 'bar' ? color + '99' : 'transparent',
        borderWidth: 2,
        pointRadius: data.length > 60 ? 0 : 3,
        pointHoverRadius: 5,
        tension: 0.3,
    }

    if (type === 'line') {
        return {
            ...base,
            type: 'line',
        } as CJSDataset<'line'>
    }

    return {
        ...base,
        type: 'bar',
        borderRadius: 3,
    } as CJSDataset<'bar'>
}

function buildForecastDatasets(points: ForecastPoint[], _mainColor: string): CJSDataset<'line'>[] {
    const forecastColor = cssVar('--beacon-chart-forecast')
    const forecastFill = cssVar('--beacon-chart-forecast-fill')

    const datasets: CJSDataset<'line'>[] = []

    // Confidence band (upper) — filled area between upper and lower
    if (points.some((p) => p.upper !== undefined)) {
        datasets.push({
            label: 'Forecast (upper)',
            data: points.map((p) => p.upper ?? p.value),
            borderColor: 'transparent',
            backgroundColor: forecastFill,
            fill: '+1',
            pointRadius: 0,
            tension: 0.3,
        } as CJSDataset<'line'>)

        datasets.push({
            label: 'Forecast (lower)',
            data: points.map((p) => p.lower ?? p.value),
            borderColor: 'transparent',
            backgroundColor: forecastFill,
            fill: false,
            pointRadius: 0,
            tension: 0.3,
        } as CJSDataset<'line'>)
    }

    // Main forecast line
    datasets.push({
        label: 'Forecast',
        data: points.map((p) => p.value),
        borderColor: forecastColor,
        backgroundColor: 'transparent',
        borderDash: [4, 4],
        pointRadius: 0,
        tension: 0.3,
    } as CJSDataset<'line'>)

    return datasets
}

// ─── Main factory ────────────────────────────────────────────────────────────

export function initChart(canvas: HTMLCanvasElement, options: ChartOptions): Chart {
    const palette = [
        cssVar('--beacon-chart-1'),
        cssVar('--beacon-chart-2'),
        cssVar('--beacon-chart-3'),
        cssVar('--beacon-chart-4'),
    ]

    // Build labels from the first dataset
    const firstDataset = options.datasets[0]
    const labels = (firstDataset?.data ?? []).map((p) =>
        formatLabel(p.period_start, options.granularity),
    )

    // Extend labels for forecast if present
    const forecastPoints = firstDataset?.forecast ?? []
    const forecastLabels = forecastPoints.map((p) => formatLabel(p.date, options.granularity))
    const allLabels = [...labels, ...forecastLabels]

    // Build datasets
    const datasets: CJSDataset<'line' | 'bar'>[] = []

    options.datasets.forEach((ds, i) => {
        const color = ds.color ?? palette[i % palette.length] ?? palette[0]!
        datasets.push(buildHistoricalDataset(ds.label, ds.data, color, options.type))

        if (options.showForecast && ds.forecast && ds.forecast.length > 0) {
            // Pad historical dataset with nulls to align with forecast x-axis
            const historicalDs = datasets.at(-1)!
            ;(historicalDs.data as (number | null)[]).push(
                ...Array<null>(forecastPoints.length).fill(null),
            )

            // Forecast datasets aligned to forecast labels segment
            buildForecastDatasets(ds.forecast, color).forEach((fds) => {
                ;(fds.data as (number | null)[]) = [
                    ...Array<null>(ds.data.length).fill(null),
                    ...(fds.data as number[]),
                ]
                datasets.push(fds as CJSDataset<'line' | 'bar'>)
            })
        }
    })

    const config: ChartConfiguration<'line' | 'bar'> = {
        type: options.type,
        data: {
            labels: allLabels,
            datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: options.datasets.length > 1 || options.showForecast,
                    labels: {
                        color: cssVar('--beacon-text-secondary'),
                        font: { size: 11 },
                        boxWidth: 12,
                        padding: 12,
                        // Hide internal forecast band datasets from legend
                        filter: (item) =>
                            !item.text.includes('(upper)') && !item.text.includes('(lower)'),
                    },
                },
                tooltip: {
                    backgroundColor: cssVar('--beacon-surface'),
                    borderColor: cssVar('--beacon-border'),
                    borderWidth: 1,
                    titleColor: cssVar('--beacon-text-primary'),
                    bodyColor: cssVar('--beacon-text-secondary'),
                    padding: 10,
                    callbacks: {
                        // Skip internal forecast band rows in tooltip
                        label: (ctx) => {
                            if (
                                ctx.dataset.label?.includes('(upper)') ||
                                ctx.dataset.label?.includes('(lower)')
                            )
                                return undefined
                            return ` ${ctx.dataset.label}: ${ctx.formattedValue}`
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        color: cssVar('--beacon-border-subtle'),
                    },
                    ticks: {
                        color: cssVar('--beacon-text-muted'),
                        font: { size: 11 },
                        maxTicksLimit: 8,
                    },
                },
                y: {
                    grid: {
                        color: cssVar('--beacon-border-subtle'),
                    },
                    ticks: {
                        color: cssVar('--beacon-text-muted'),
                        font: { size: 11 },
                    },
                    beginAtZero: false,
                },
            },
        },
    }

    return new Chart(canvas, config)
}

/**
 * Destroy chart if one already exists on the canvas.
 * Safe to call before re-initialisation (e.g. polling refresh).
 */
export function destroyChart(canvas: HTMLCanvasElement): void {
    const existing = Chart.getChart(canvas)
    existing?.destroy()
}
