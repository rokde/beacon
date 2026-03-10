import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import { resolve } from 'path'

export default defineConfig({
  plugins: [
    tailwindcss(),
  ],

  build: {
    // Output goes into dist/ — committed to git so host apps
    // can use vendor:publish without a Node build step.
    outDir: 'dist',
    emptyOutDir: true,

    // Named entry points: one CSS bundle, one JS bundle
    rollupOptions: {
      input: {
        beacon: resolve(__dirname, 'resources/ts/beacon.ts'),
      },
      output: {
        // Deterministic filenames — no hashes in dist/
        // (versioning is handled by the Composer package version)
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.names?.some(n => n.endsWith('.css'))) {
            return 'css/[name][extname]'
          }
          return 'assets/[name][extname]'
        },
      },
    },

    // Tree-shake aggressively — Chart.js supports this via named imports
    minify: 'esbuild',

    sourcemap: process.env.NODE_ENV !== 'production',
  },

  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/ts'),
    },
  },
})
