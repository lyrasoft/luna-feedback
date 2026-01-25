import { resolve } from 'node:path';
import dts from 'unplugin-dts/vite';
import { defineConfig } from 'vite';

export default defineConfig(({ mode }) => {

  return {
    base: './',
    resolve: {
      alias: {
        '~feedback': resolve(__dirname, 'src'),
      },
    },
    build: {
      lib: {
        entry: 'src/index.ts',
        name: 'Feedback',
        formats: ['es'],
      },
      rollupOptions: {
        output: {
          format: 'es',
          entryFileNames: '[name].js',
          chunkFileNames: 'chunks/[name].js',
          assetFileNames: (info) => {
            // if (info.originalFileNames[0] === 'style.css') {
            //   return 'flower.css';
            // }

            return 'assets/[name][extname]';
          },
        },
        external: [
          '@windwalker-io/unicorn-next',
          '@lyrasoft/ts-toolkit',
          /^@lyrasoft\/ts-toolkit/,
          'node:crypto',
          '@unicorn/*',
          'bootstrap',
          'sortablejs',
          '@asika32764/vue-animate',
          'bootstrap',
          'vue',
          'vue-draggable-plus',
          'vue-multi-uploader'
        ]
      },
      outDir: 'dist',
      emptyOutDir: true,
      sourcemap: true,
      minify: false,
    },
    plugins: [
      dts({
        tsconfigPath: resolve('./tsconfig.json'),
        bundleTypes: true,
      })
    ]
  };
});


