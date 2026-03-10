/**
 * Beacon Gauge Module
 *
 * Renders an SVG arc gauge for Ratio KPIs (0–100%).
 * The static initial value comes from Blade (server-rendered SVG),
 * this module handles animated updates on polling refresh.
 */
import type { GaugeOptions } from './types'

function cssVar(name: string): string {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim()
}

/**
 * Describes an SVG arc path for a given percentage.
 * Uses a fixed viewBox of 100x60 with radius 40, center (50, 50).
 */
function describeArc(pct: number): string {
    const r = 40
    const cx = 50
    const cy = 50
    // Arc spans from -180° to 0° (left to right, bottom half only → semi-circle)
    const startAngle = -Math.PI
    const endAngle = 0
    const angle = startAngle + (endAngle - startAngle) * Math.min(1, Math.max(0, pct / 100))

    const x = cx + r * Math.cos(angle)
    const y = cy + r * Math.sin(angle)
    const largeArc = angle - startAngle > Math.PI ? 1 : 0

    const startX = cx + r * Math.cos(startAngle)
    const startY = cy + r * Math.sin(startAngle)

    return `M ${startX} ${startY} A ${r} ${r} 0 ${largeArc} 1 ${x} ${y}`
}

function gaugeColor(value: number, options: GaugeOptions): string {
    const { warning = 60, danger = 80 } = options.thresholds ?? {}
    if (value >= danger) return cssVar('--beacon-danger')
    if (value >= warning) return cssVar('--beacon-warning')
    return cssVar('--beacon-success')
}

export function initGauge(container: HTMLElement, options: GaugeOptions): void {
    const pct =
        ((options.value - (options.min ?? 0)) / ((options.max ?? 100) - (options.min ?? 0))) * 100

    const ns = 'http://www.w3.org/2000/svg'
    const svg = document.createElementNS(ns, 'svg')
    svg.setAttribute('viewBox', '0 0 100 60')
    svg.setAttribute('role', 'img')
    svg.setAttribute('aria-label', `Gauge: ${options.value}%`)
    svg.classList.add('beacon-gauge__arc')

    // Background track
    const track = document.createElementNS(ns, 'path')
    track.setAttribute('d', describeArc(100))
    track.setAttribute('fill', 'none')
    track.setAttribute('stroke', cssVar('--beacon-border'))
    track.setAttribute('stroke-width', '8')
    track.setAttribute('stroke-linecap', 'round')
    svg.appendChild(track)

    // Value arc
    const arc = document.createElementNS(ns, 'path')
    arc.setAttribute('d', describeArc(pct))
    arc.setAttribute('fill', 'none')
    arc.setAttribute('stroke', gaugeColor(options.value, options))
    arc.setAttribute('stroke-width', '8')
    arc.setAttribute('stroke-linecap', 'round')
    svg.appendChild(arc)

    // Center value text
    const text = document.createElementNS(ns, 'text')
    text.setAttribute('x', '50')
    text.setAttribute('y', '48')
    text.setAttribute('text-anchor', 'middle')
    text.setAttribute('dominant-baseline', 'middle')
    text.setAttribute('font-size', '14')
    text.setAttribute('font-weight', '700')
    text.setAttribute('fill', cssVar('--beacon-text-primary'))
    text.textContent = `${Math.round(options.value)}%`
    svg.appendChild(text)

    container.innerHTML = ''
    container.appendChild(svg)
}
