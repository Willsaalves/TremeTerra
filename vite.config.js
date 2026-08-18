import { resolve } from 'node:path';

export default {
  build: {
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
        locacao: resolve(__dirname, 'locacao-equipamentos.html'),
        corporativos: resolve(__dirname, 'produtora-de-eventos-corporativos.html'),
        djEventos: resolve(__dirname, 'dj-para-eventos.html'),
      },
    },
  },
};
