import { resolve } from 'node:path';

export default {
  build: {
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
        locacao: resolve(__dirname, 'locacao-equipamentos.html'),
        corporativos: resolve(__dirname, 'produtora-de-eventos-corporativos.html'),
        djEventos: resolve(__dirname, 'dj-para-eventos.html'),
        aluguelSom: resolve(__dirname, 'aluguel-som-profissional.html'),
        iluminacaoFestas: resolve(__dirname, 'iluminacao-para-festas.html'),
        painelLed: resolve(__dirname, 'painel-de-led.html'),
        locacaoPainelLed: resolve(__dirname, 'locacao-painel-led.html'),
      },
    },
  },
};
