/**
 * Beacon Dashboard — Main Entry Point
 *
 * Convention: all configuration flows from Blade → data-* attributes.
 * No global JS variables, no inline scripts in Blade templates.
 *
 * Activation attributes:
 *   [data-beacon-chart]       → ChartOptions JSON  → initChart()
 *   [data-beacon-gauge]       → GaugeOptions JSON  → initGauge()
 *   [data-beacon-poll]        → PollingOptions JSON → startPolling()
 *   [data-beacon-theme-toggle]                     → toggleTheme()
 */

import '../css/beacon.css'
import { initTheme, toggleTheme } from './theme'
import { initChart } from './chart'
import { initGauge } from './gauge'
import { startPolling } from './polling'
import type { ChartOptions, GaugeOptions } from './types'

// ── 1. Theme — must run before paint to avoid FOUC ──────────────────────────
initTheme()

// ── 2. Bootstrap after DOM is ready ─────────────────────────────────────────
function bootstrap(): void {
    // Charts
    document.querySelectorAll<HTMLCanvasElement>('canvas[data-beacon-chart]').forEach((canvas) => {
        const raw = canvas.dataset['beaconChart']
        if (!raw) return
        try {
            const options = JSON.parse(raw) as ChartOptions
            initChart(canvas, options)
        } catch (err) {
            console.warn('[Beacon] Invalid chart options:', err, canvas)
        }
    })

    // Gauges
    document.querySelectorAll<HTMLElement>('[data-beacon-gauge]').forEach((container) => {
        const raw = container.dataset['beaconGauge']
        if (!raw) return
        try {
            const options = JSON.parse(raw) as GaugeOptions
            initGauge(container, options)
        } catch (err) {
            console.warn('[Beacon] Invalid gauge options:', err, container)
        }
    })

    // Polling
    document.querySelectorAll<HTMLElement>('[data-beacon-poll]').forEach((el) => {
        const interval = parseInt(el.dataset['beaconPoll'] ?? '300', 10)
        const url = el.dataset['beaconPollUrl'] ?? window.location.href
        const selector = el.dataset['beaconPollTarget'] ?? '[data-beacon-poll]'

        startPolling({ url, interval, containerSelector: selector })
    })

    // Theme toggle buttons
    document.querySelectorAll<HTMLButtonElement>('[data-beacon-theme-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            toggleTheme()
            // Update button icon
            updateThemeToggleIcon(btn)
        })
        updateThemeToggleIcon(btn)
    })
}

function updateThemeToggleIcon(btn: HTMLButtonElement): void {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark'
    btn.textContent = isDark ? '☀' : '☾'
    btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode')
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap)
} else {
    bootstrap()
}
