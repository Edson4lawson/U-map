
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      injectRegister: 'auto',
      manifest: {
        name: 'U-map Campus UAC',
        short_name: 'U-map',
        description: 'Navigation intelligente sur le campus de l\'UAC',
        theme_color: '#3B82F6',
        icons: [
          { src: 'pwa-192.png', sizes: '192x192', type: 'image/png' },
          { src: 'pwa-512.png', sizes: '512x512', type: 'image/png' },
          { src: 'pwa-192.png', sizes: '192x192', type: 'image/png', purpose: 'any maskable' },
          { src: 'pwa-512.png', sizes: '512x512', type: 'image/png', purpose: 'any maskable' }
        ]
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg}'],
        runtimeCaching: [
          {
            urlPattern: /^https:\/\/tile\.openstreetmap\.org\/.*/i,
            handler: 'CacheFirst',
            options: {
              cacheName: 'map-tiles',
              expiration: { maxEntries: 50, maxAgeSeconds: 60 * 60 * 24 * 30 }
            }
          },
          {
            urlPattern: /.*\/api\/places/i,
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'api-places-cache',
              expiration: { maxEntries: 5, maxAgeSeconds: 60 * 60 * 24 * 7 },
              cacheableResponse: { statuses: [0, 200] }
            }
          }
        ]
      }
    })
  ],
  build: {
    outDir: 'dist',
    sourcemap: false,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('vue') || id.includes('pinia')) return 'vendor'
            if (id.includes('leaflet')) return 'leaflet'
            if (id.includes('swiper') || id.includes('aos')) return 'ui'
          }
        }
      }
    },
    chunkSizeWarningLimit: 1000
  },
  server: {
    host: true,
    port: 5174
  }
})

