# Save the Med — landing page de validação

Página única de apresentação do projeto **Save the Med**: uma plataforma que conecta
farmácias com estoque próximo do vencimento a consumidores que procuram o mesmo produto
com desconto, na modalidade reserve-e-retire.

O conteúdo veio do documento do projeto e cobre problema, posicionamento, fluxo de uso,
painel da farmácia, índice Smart Expiry, entrada regulatória por fases, modelo de negócio,
cenário ilustrativo, escopo do MVP, plano de validação de quatro semanas, métricas,
arquitetura e avaliação da ideia — terminando no formulário de captação do piloto.

## Arquivos

- `index.html` — a página inteira: HTML, CSS e JavaScript num arquivo só, sem dependências
  de build. As fontes vêm do Google Fonts; sem rede, o navegador cai para as fontes do sistema.

## Como abrir

Basta abrir `save-the-med/index.html` no navegador, com dois cliques mesmo — a página
não depende de servidor. Para servir localmente em http://localhost:8081:

```
npm run serve:save-the-med
```

Sem Node instalado, o equivalente direto:

```
php -S localhost:8081 -t save-the-med
# ou, sem PHP:
python3 -m http.server 8081 --directory save-the-med
```

A página é independente do site da Treme Terra: não entra no build do Vite, não aparece no
sitemap e não compartilha CSS nem JS com as demais páginas do repositório.

## Para onde vão os leads

O formulário está pronto, mas o destino precisa ser configurado antes de publicar.
No fim de `index.html` existe o bloco:

```js
var CONFIG = {
  endpoint: "",  // URL que recebe POST em JSON (Formspree, API própria, Sheets...)
  whatsapp: "",  // ex.: "5561900000000" — usado se não houver endpoint
  email: ""      // ex.: "contato@savethemed.com.br" — último recurso
};
```

Ordem de prioridade: `endpoint` (POST em JSON), depois `whatsapp` (abre o WhatsApp Web com a
mensagem pronta), depois `email` (abre o cliente de e-mail). Sem nenhum dos três, o envio só
fica registrado no `localStorage` do próprio navegador, sob a chave `stm_leads` — útil para
testar, insuficiente para captar de verdade.

## Antes de publicar

- Definir o destino dos leads em `CONFIG`.
- Revisar com assessoria regulatória e jurídica os textos sobre categorias de produto,
  dispensação e validade. Medicamentos sujeitos a controle especial estão fora do MVP.
- Trocar o nome, se a pesquisa de marca e domínio apontar outra opção. "Save the Med" é
  provisório e a página diz isso no rodapé.
- Valores, planos, telas e indicadores são ilustrativos e estão marcados como tais.
