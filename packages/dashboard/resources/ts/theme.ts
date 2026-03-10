/**
 * Beacon Theme Module
 *
 * Manages light/dark/system theme preference.
 * Stores user choice in localStorage under key 'beacon-theme'.
 * Applies [data-theme] on <html> element.
 */
import type { Theme } from './types'

const STORAGE_KEY = 'beacon-theme'
const ROOT = document.documentElement

function getSystemTheme(): 'light' | 'dark' {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function applyTheme(theme: Theme): void {
    const resolved = theme === 'system' ? getSystemTheme() : theme
    ROOT.setAttribute('data-theme', resolved)
}

export function initTheme(): void {
    const stored = (localStorage.getItem(STORAGE_KEY) as Theme | null) ?? 'system'
    applyTheme(stored)

    // Respond to OS-level theme changes when set to 'system'
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const current = (localStorage.getItem(STORAGE_KEY) as Theme | null) ?? 'system'
        if (current === 'system') applyTheme('system')
    })
}

export function toggleTheme(): void {
    const current = (localStorage.getItem(STORAGE_KEY) as Theme | null) ?? 'system'
    const resolved = current === 'system' ? getSystemTheme() : current
    const next: Theme = resolved === 'light' ? 'dark' : 'light'
    localStorage.setItem(STORAGE_KEY, next)
    applyTheme(next)
}

export function currentTheme(): Theme {
    return (localStorage.getItem(STORAGE_KEY) as Theme | null) ?? 'system'
}
