import { resolve } from 'node:path';

export default {
  build: {
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
        locacao: resolve(__dirname, 'locacao-equipamentos.html'),
      },
    },
  },
};
