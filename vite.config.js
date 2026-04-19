import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite'; // Plugin oficial para v4

export default defineConfig({
    server: {
        host: '127.0.0.1', // Forzamos IP para evitar errores de conexión
        port: 5173,
        strictPort: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    plugins: [
        tailwindcss(), // Cargamos Tailwind v4 primero para que procese el CSS
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true, // Esto hace que el navegador se refresque solo al guardar
        }),
    ],
});
