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
      refresh: true
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
      'Access-Control-Allow-Headers': 'Content-Type, Authorization'
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
      // SOLUCIÓN RADICAL: Deshabilitar completamente el file watcher
      // En Docker con volúmenes montados, incluso el watcher nativo consume mucha CPU (136%+)
      // El watcher está deshabilitado por defecto - ignorar todos los archivos
      // Para habilitar polling con intervalo largo: VITE_USE_POLLING=true VITE_POLL_INTERVAL=60000
      usePolling: process.env.VITE_USE_POLLING === 'true',
      interval: process.env.VITE_USE_POLLING === 'true' 
        ? parseInt(process.env.VITE_POLL_INTERVAL || '60000') 
        : undefined,
      // Ignorar TODOS los archivos - el watch está deshabilitado por defecto
      // Esto elimina completamente el consumo de CPU del file watcher
      ignored: ['**/*']
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
