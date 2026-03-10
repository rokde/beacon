/**
 * Beacon Polling Module
 *
 * Periodically refreshes dashboard tiles by fetching the partial HTML
 * from the server and replacing the tile's inner content.
 * Chart.js instances are properly destroyed before re-init to avoid leaks.
 */
import { destroyChart } from './chart'

interface PollingOptions {
    /** URL to fetch the refreshed dashboard HTML from */
    url: string
    /** Interval in seconds */
    interval: number
    /** CSS selector for the container to replace */
    containerSelector: string
    /** Callback fired after each successful refresh */
    onRefresh?: () => void
}

export function startPolling(options: PollingOptions): () => void {
    let handle: ReturnType<typeof setInterval> | null = null
    let controller: AbortController | null = null

    const indicator = document.querySelector<HTMLElement>('.beacon-refresh-indicator')

    async function refresh(): Promise<void> {
        if (indicator) indicator.classList.add('beacon-refresh-indicator--spinning')

        controller?.abort()
        controller = new AbortController()

        try {
            const response = await fetch(options.url, {
                signal: controller.signal,
                headers: { 'X-Beacon-Refresh': '1' },
            })

            if (!response.ok) return

            const html = await response.text()
            const parser = new DOMParser()
            const doc = parser.parseFromString(html, 'text/html')
            const newContainer = doc.querySelector(options.containerSelector)
            const currentContainer = document.querySelector(options.containerSelector)

            if (!newContainer || !currentContainer) return

            // Destroy any existing Chart.js instances to avoid canvas re-use errors
            currentContainer
                .querySelectorAll<HTMLCanvasElement>('canvas[data-beacon-chart]')
                .forEach((canvas) => destroyChart(canvas))

            // Replace content
            currentContainer.innerHTML = newContainer.innerHTML

            // Re-initialise charts in the replaced content
            initChartsInContainer(currentContainer as HTMLElement)

            options.onRefresh?.()
        } catch (err) {
            if (err instanceof Error && err.name === 'AbortError') return
            console.warn('[Beacon] Polling refresh failed:', err)
        } finally {
            if (indicator) indicator.classList.remove('beacon-refresh-indicator--spinning')
        }
    }

    handle = setInterval(() => {
        void refresh()
    }, options.interval * 1000)

    // Return a stop function
    return (): void => {
        if (handle !== null) clearInterval(handle)
        controller?.abort()
    }
}

/**
 * Scan a container for [data-beacon-chart] canvases and initialise them.
 * Called after polling replaces HTML.
 */
function initChartsInContainer(container: HTMLElement): void {
    container.querySelectorAll<HTMLCanvasElement>('canvas[data-beacon-chart]').forEach((canvas) => {
        const raw = canvas.dataset['beaconChart']
        if (!raw) return
        try {
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            const options = JSON.parse(raw) as any
            // Dynamically import to avoid circular deps — chart module is loaded once
            void import('./chart').then(({ initChart }) => {
                initChart(canvas, options)
            })
        } catch {
            console.warn('[Beacon] Failed to parse chart options on canvas', canvas)
        }
    })
}
