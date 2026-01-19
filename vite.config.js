/// <reference types="vitest" />
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import path from 'path'
import { visualizer } from 'rollup-plugin-visualizer'

export default defineConfig({
  plugins: [
    vue(),
    laravel({
      input: [
        'resources/assets/js/app.ts',
        'resources/assets/js/remote/app.ts'
      ],
      // Solo refrescar cuando cambien vistas o rutas (reduce CPU)
      refresh: [
        'resources/views/**',
        'routes/**',
        'app/Http/**'
      ]
    }),
    // Deshabilitar visualizer en desarrollo para reducir carga
    ...(process.env.NODE_ENV === 'production' ? [visualizer({
      filename: 'stats.html'
    })] : [])
  ],
  server: {
    host: '0.0.0.0',
    port: 5173,
    // Habilitar CORS para permitir solicitudes desde el navegador
    cors: true,
    // Configurar headers CORS explícitamente
    headers: {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type, Authorization',
      // Deshabilitar el CSP automático de Vite para usar el middleware de Laravel
      'Content-Security-Policy': ''
    },
    strictPort: false,
    hmr: {
      // En Docker, el HMR debe usar localhost desde la perspectiva del navegador
      // El servidor escucha en 0.0.0.0 pero el cliente se conecta a localhost
      host: process.env.VITE_HMR_HOST || 'localhost',
      clientPort: process.env.VITE_HMR_PORT ? parseInt(process.env.VITE_HMR_PORT) : 5173,
      // Protocolo para HMR - usar ws para desarrollo local
      protocol: process.env.VITE_HMR_PROTOCOL || 'ws',
      // Timeout más largo para evitar desconexiones en Docker
      timeout: 60000
    },
    watch: {
      // Usar polling en Docker (el watcher nativo no funciona bien con volúmenes montados)
      // Intervalos optimizados: 1000ms balancea respuesta y consumo de CPU
      // Basado en mejores prácticas de la comunidad para Docker + Vite
      usePolling: true,
      interval: parseInt(process.env.VITE_POLL_INTERVAL || '1000'),
      binaryInterval: parseInt(process.env.VITE_POLL_BINARY_INTERVAL || '1500'),
      // Ignorar directorios grandes que no necesitan ser monitoreados
      // Esto reduce significativamente el número de archivos a verificar
      ignored: [
        '**/node_modules/**',
        '**/storage/**',
        '**/vendor/**',
        '**/dist/**',
        '**/.git/**',
        '**/database/**',
        '**/tests/**',
        '**/.phpunit.cache/**',
        '**/bootstrap/cache/**'
      ],
      // Esperar a que las escrituras de archivos se completen antes de reaccionar
      // Evita reacciones a escrituras temporales o incompletas
      awaitWriteFinish: {
        stabilityThreshold: 200,
        pollInterval: 100
      }
    },
    // Optimizaciones para desarrollo
    fs: {
      strict: false
    },
    // Timeout más largo para requests
    hmrTimeout: 60000
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/assets/js'),
      '@modules': path.resolve(__dirname, './node_modules')
    }
  },
  test: {
    environment: 'jsdom',
    setupFiles: path.resolve(__dirname, './resources/assets/js/__tests__/setup.ts')
  }
})
