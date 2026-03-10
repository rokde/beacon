/**
 * Types shared between all Beacon TS modules.
 * These match the JSON shapes rendered by Blade components
 * into data-* attributes.
 */

export interface AggregatePoint {
    period_start: string // ISO datetime string
    value: number
    count: number
}

export interface ForecastPoint {
    date: string // ISO date string
    value: number
    lower?: number
    upper?: number
}

export interface ChartDataset {
    label: string
    data: AggregatePoint[]
    forecast?: ForecastPoint[]
    color?: string
}

export interface ChartOptions {
    type: 'line' | 'bar'
    datasets: ChartDataset[]
    granularity: 'minute' | 'hour' | 'day' | 'week' | 'month' | 'year'
    showForecast: boolean
    height?: number
    /** Show comparison period as second dataset */
    showComparison: boolean
}

export interface GaugeOptions {
    value: number // 0–100
    min?: number
    max?: number
    thresholds?: {
        warning: number // % at which color shifts to warning
        danger: number // % at which color shifts to danger
    }
}

export type Theme = 'light' | 'dark' | 'system'
