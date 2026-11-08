import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/css/modern.css',
                // js 
                'resources/js/app.js',
                'resources/js/modern.js',
                'resources/js/datatable.js',
                'resources/js/charts.js',
                'resources/js/address.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        chunkSizeWarningLimit: 2000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/pdfmake')) {
                        return 'vendor-pdfmake';
                    }

                    if (id.includes('node_modules/datatables.net')) {
                        return 'vendor-datatables';
                    }

                    if (id.includes('node_modules/jszip')) {
                        return 'vendor-jszip';
                    }

                    if (id.includes('node_modules/chart.js')) {
                        return 'vendor-charts';
                    }

                    if (
                        id.includes('node_modules/jquery') ||
                        id.includes('node_modules/bootstrap') ||
                        id.includes('node_modules/@popperjs')
                    ) {
                        return 'vendor-ui';
                    }
                },
            },
        },
    },
});
