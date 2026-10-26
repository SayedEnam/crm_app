import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: { alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) } },
  server: { port: 5173 },
  build: { rollupOptions: { output: { manualChunks(id) {
    if (id.includes('/node_modules/recharts/') || id.includes('/node_modules/d3-')) return 'charts'
    if (id.includes('/node_modules/@tanstack/')) return 'query'
    if (id.includes('/node_modules/react/') || id.includes('/node_modules/react-dom/') || id.includes('/node_modules/scheduler/')) return 'react'
    if (id.includes('/node_modules/zod/') || id.includes('/node_modules/react-hook-form/') || id.includes('/node_modules/@hookform/')) return 'forms'
  } } } },
})
