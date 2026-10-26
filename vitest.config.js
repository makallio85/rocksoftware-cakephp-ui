import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

// The components' unit tests, in a happy-dom page.
export default defineConfig({
    plugins: [vue()],
    test: {
        environment: 'happy-dom',
        include: ['resources/js/**/*.test.js'],
        // The formats are compared with the PHP helper's in Helsinki time.
        env: { TZ: 'Europe/Helsinki' },
    },
});
