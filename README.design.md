# Portal EduX — Design System

Este documento define a identidade visual oficial do portal EduX e como implementá-la usando os artefatos fornecidos (design tokens, CSS base e componentes).

---

## 📁 Estrutura de arquivos

```
├── design-tokens.json          # Tokens de design (cores, tipografia, spacing)
├── resources/
│   └── css/
│       └── edux-base.css      # CSS base com variáveis e componentes
└── resources/views/
    └── components/            # Componentes Blade reutilizáveis
```

---

## 1. Design Tokens (`design-tokens.json`)

### Estrutura do arquivo

```json
{
  "colors": {
    "primary": "#1A73E8",
    "cta": "#FBC02D",
    "background": "#F5F5F5",
    "text": "#333333"
  },
  "typography": {
    "heading": {
      "family": "Poppins",
      "weight": "700",
      "size": "26-32px"
    },
    "body": {
      "family": "Inter",
      "weight": "400",
      "size": "18-20px"
    }
  },
  "buttons": {
    "default": {
      "background": "#FBC02D",
      "color": "#000000",
      "height": "50px",
      "borderRadius": "12px",
      "font": "Inter Bold 18px"
    }
  },
  "layout": {
    "maxColumns": 2,
    "breakpoint": "768px",
    "approach": "mobile-first"
  }
}
```

### Uso em pipelines

- **Figma Tokens:** Importar JSON diretamente
- **Style Dictionary:** Usar como source
- **CSS Custom Properties:** Já convertido em `edux-base.css`

---

## 2. CSS Base (`resources/css/edux-base.css`)

### Variáveis CSS disponíveis

```css
:root {
  /* Colors */
  --edux-primary: #1A73E8;
  --edux-cta: #FBC02D;
  --edux-background: #F5F5F5;
  --edux-text: #333333;
  
  /* Typography */
  --edux-font-heading: 'Poppins', sans-serif;
  --edux-font-body: 'Inter', sans-serif;
  
  /* Spacing */
  --edux-spacing-xs: 8px;
  --edux-spacing-sm: 16px;
  --edux-spacing-md: 24px;
  --edux-spacing-lg: 32px;
  --edux-spacing-xl: 48px;
  
  /* Shadows */
  --edux-shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
  --edux-shadow-md: 0 4px 8px rgba(0,0,0,0.12);
}
```

### Classes utilitárias

#### Layout
```css
.edux-container    /* Max-width container com padding responsivo */
.edux-grid         /* Grid responsivo (1 col mobile, max 2 desktop) */
.edux-section      /* Seção com espaçamento vertical adequado */
```

#### Componentes
```css
.edux-header       /* Header azul com logo e navegação */
.edux-card         /* Card branco com sombra leve */
.edux-btn          /* Botão amarelo padrão (50px altura) */
.edux-footer       /* Footer azul escuro */
.edux-steps        /* Lista de passos numerados */
```

### Importação no Laravel

```blade
{{-- Em resources/views/layouts/app.blade.php --}}
@vite('resources/css/edux-base.css')
```

Ou se não estiver usando Vite:

```blade
<link rel="stylesheet" href="{{ asset('css/edux-base.css') }}">
```

---

## 3. Princípios de Design (Regras fixas)

### Cores

| Elemento | Cor | Uso |
|----------|-----|-----|
| Fundo geral | `#F5F5F5` | Background de todas as páginas |
| Header/Footer | `#1A73E8` | Áreas institucionais |
| Botões CTA | `#FBC02D` | Ações principais |
| Texto principal | `#333333` | Corpo de texto |
| Texto secundário | `#666666` | Legendas, hints |

### Tipografia

```css
/* Títulos principais (h1, h2) */
font-family: 'Poppins', sans-serif;
font-weight: 700;
font-size: 26px-32px;
color: #1A73E8 ou #000000;

/* Corpo de texto */
font-family: 'Inter', sans-serif;
font-weight: 400;
font-size: 18px-20px;
line-height: 1.6;

/* Botões */
font-family: 'Inter', sans-serif;
font-weight: 700;
font-size: 18px;
```

### Botões

```html
<!-- Padrão -->
<button class="edux-btn">👉 Começar agora</button>

<!-- Variações -->
<button class="edux-btn edux-btn--secondary">Ver mais</button>
<button class="edux-btn edux-btn--outline">Saiba mais</button>
```

**Especificações:**
- Altura mínima: `50px` (mobile-friendly)
- Border radius: `12px`
- Padding horizontal: `24px`
- Font: Inter Bold 18px
- Ícones recomendados: ▶ 👉 ✓ 🎯

### Layout responsivo

```css
/* Mobile first (default) */
.edux-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
}

/* Desktop (≥768px) */
@media (min-width: 768px) {
  .edux-grid {
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    max-columns: 2; /* NUNCA mais que 2 colunas */
  }
}
```

### Ilustrações e imagens

- ✅ Ilustrações com linhas grossas, cores sólidas (azul + amarelo)
- ✅ Personagens sorrindo, poses amigáveis
- ✅ Estilo flat/semi-flat
- ❌ Fotos realistas
- ❌ Imagens complexas ou muito detalhadas
- ❌ Gradientes complexos

---

## 4. Componentes principais

### Header

```blade
<header class="edux-header">
  <div class="edux-container">
    <div class="edux-header__wrapper">
      <span class="edux-header__logo">📚 EduX</span>
      <nav class="edux-header__nav">
        <a href="{{ route('dashboard') }}">Início</a>
        <a href="{{ route('courses.index') }}">Cursos</a>
        <a href="{{ route('logout') }}" class="edux-btn edux-btn--small">Sair</a>
      </nav>
    </div>
  </div>
</header>
```

### Card de curso

```blade
<article class="edux-card">
  <div class="edux-card__illustration">
    {{-- Ilustração SVG ou imagem --}}
  </div>
  <h3 class="edux-card__title">{{ $course->title }}</h3>
  <p class="edux-card__description">{{ $course->summary }}</p>
  <a href="{{ route('courses.show', $course) }}" class="edux-btn">
    👉 Acessar curso
  </a>
</article>
```

### Seção "3 passos"

```blade
<section class="edux-section">
  <h2>Como funciona?</h2>
  <div class="edux-steps">
    <div class="edux-step">
      <span class="edux-step__number">1</span>
      <h3>Escolha seu curso</h3>
      <p>Navegue pelos cursos disponíveis</p>
    </div>
    <div class="edux-step">
      <span class="edux-step__number">2</span>
      <h3>Assista às aulas</h3>
      <p>Aprenda no seu ritmo</p>
    </div>
    <div class="edux-step">
      <span class="edux-step__number">3</span>
      <h3>Receba o certificado</h3>
      <p>Comprove seu conhecimento</p>
    </div>
  </div>
</section>
```

### Footer

```blade
<footer class="edux-footer">
  <div class="edux-container">
    <p>&copy; {{ date('Y') }} EduX. Todos os direitos reservados.</p>
    <nav class="edux-footer__nav">
      <a href="#">Sobre</a>
      <a href="#">Contato</a>
      <a href="#">Termos</a>
    </nav>
  </div>
</footer>
```

---

## 5. Fluxo de desenvolvimento

### Para criar uma nova tela

1. **Importar CSS base**
   ```blade
   @vite('resources/css/edux-base.css')
   ```

2. **Usar estrutura base**
   ```blade
   @extends('layouts.app')
   
   @section('content')
     <div class="edux-container">
       <section class="edux-section">
         {{-- Seu conteúdo --}}
       </section>
     </div>
   @endsection
   ```

3. **Reutilizar componentes**
   - Header: `.edux-header`
   - Cards: `.edux-card`
   - Botões: `.edux-btn`
   - Grid: `.edux-grid`

4. **Validar responsividade**
   - Testar em 320px (mobile pequeno)
   - Testar em 768px (tablet)
   - Testar em 1024px+ (desktop)

5. **Seguir checklist de conformidade**

---

## 6. Checklist de conformidade

Antes de fazer merge/deploy, verifique:

### Visual
- [ ] Fundo da página é `#F5F5F5`
- [ ] Títulos usam Poppins Bold + cor `#1A73E8` ou preto
- [ ] Botões CTA usam classe `.edux-btn` (fundo amarelo)
- [ ] Textos usam Inter 18-20px, parágrafos curtos
- [ ] Espaçamento generoso entre seções (mín. 48px)

### Layout
- [ ] Abordagem mobile-first implementada
- [ ] Máximo 2 colunas no desktop (≥768px)
- [ ] Cards/elementos têm espaçamento adequado
- [ ] Header e footer mantêm cores institucionais

### Acessibilidade
- [ ] Botões têm altura mínima de 50px
- [ ] Contraste de cores adequado (WCAG AA)
- [ ] Textos não justificados
- [ ] Links e botões facilmente clicáveis

### Performance
- [ ] Imagens otimizadas (WebP quando possível)
- [ ] CSS importado uma única vez
- [ ] Sem inline styles desnecessários

---

## 7. Troubleshooting

### Problema: Botões não aparecem amarelos

**Solução:**
```blade
{{-- Verificar se o CSS foi importado --}}
@vite('resources/css/edux-base.css')

{{-- Usar a classe correta --}}
<button class="edux-btn">Texto</button>
```

### Problema: Layout não responsivo

**Solução:**
```html
<!-- Adicionar viewport meta tag -->
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Usar classes de grid responsivas -->
<div class="edux-grid">...</div>
```

### Problema: Fontes não carregam

**Solução:**
```html
<!-- Adicionar no <head> -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Poppins:wght@700&display=swap" rel="stylesheet">
```

---

## 8. Recursos adicionais

- **Design Tokens:** `design-tokens.json`
- **CSS completo:** `resources/css/edux-base.css`
- **Figma (se aplicável):** [link do projeto]
- **Dúvidas:** Consultar time de design ou abrir issue

---

## 9. Versionamento

Este design system segue versionamento semântico:

- **MAJOR:** Mudanças que quebram compatibilidade (ex: trocar cores principais)
- **MINOR:** Novos componentes ou variações
- **PATCH:** Correções de bugs ou ajustes pequenos

**Versão atual:** 1.0.0

---

## Contribuindo

Ao propor mudanças no design system:

1. Abra uma issue descrevendo a necessidade
2. Aguarde aprovação do time de design
3. Atualize `design-tokens.json` E `edux-base.css`
4. Documente mudanças neste README
5. Atualize número de versão

**Regra de ouro:** Mantenha consistência. Se algo não está no design system, não crie "do seu jeito" — proponha adição oficial primeiro.



# Dashboard do Aluno — Diretrizes de UI

> Versão 1.0 — focado em mobile, público classe C e D, app estilo Duolingo.

---

## 1. Objetivo

* Ser **extremamente simples**.
* Facilitar o acesso rápido a:

  * Meus cursos
  * Certificados
  * +Cursos
  * Notificações
  * Duxes (moeda virtual)

Nada de telas cheias. Poucas opções, muito claras.

---

## 2. Público

* Classe C e D
* Muitos com baixa escolaridade
* Alguns têm dificuldade de leitura
* Usam quase sempre **celular**, muitas vezes de tela pequena

**Regra:**
Sempre pense: “Uma pessoa cansada, no ônibus, entende isso em 2 segundos?”

---

## 3. Princípios de design

1. **Mobile primeiro**

   * Projetar tudo pensando em **tela de celular**.
   * Só depois adaptar para desktop, se necessário.

2. **Poucas opções por vez**

   * Tela inicial com **no máximo 5 cards principais**.
   * Navegação inferior com **4 ícones**.

3. **Ícone + texto**

   * Nunca só texto.
   * Sempre ícone grande + texto curto.

4. **Textos curtos**

   * Frases simples.
   * Sem termos difíceis.
   * Evitar parágrafos grandes.

5. **Foco na ação**

   * Cada tela precisa deixar claro:

     * “O que eu faço agora?”
     * Ex.: “Continuar curso”, “Ver aulas”, “Comprar curso”.

---

## 4. Cores e tipografia (herdado do EduX)

Usar sempre as cores oficiais:

* Azul primário: `#1A73E8`
* Amarelo CTA: `#FBC02D`
* Fundo: `#F5F5F5`
* Texto principal: `#333333`
* Texto secundário: `#666666`
* Preto: `#000000`

Fontes:

* Títulos: **Poppins**, bold
* Textos: **Inter**, normal
* Botões: **Inter**, bold

**Regras:**

* Fundo da app: sempre **cinza claro** (`#F5F5F5`).
* Cards e blocos: fundo branco, com sombra leve.
* Ação principal: botão **amarelo** com texto **preto**.

---

## 5. Estrutura da navegação

### 5.1. Barra inferior (fixa)

Sempre presente no app (exceto em tela de vídeo full screen):

1. 🏠 **Home**
2. 🎓 **Meus cursos**
3. 🛒 **+Cursos**
4. ☰ **Mais** (perfil, notificações, ajuda, sair)

**Regras:**

* Só **ícones** na barra inferior.
* Ícone **ativo** em azul (`#1A73E8`).
* Ícones inativos em cinza escuro.
* Tamanho mínimo para toque: **48x48px**.

---

## 6. Tela inicial (Home do aluno)

### 6.1. Layout

* Título simples no topo: “Olá, [Nome]”
* Subtexto curto: “Continue seus estudos”
* Abaixo, **cards grandes**, 2 por linha:

Cards padrão:

1. 🎓 **Meus cursos**
2. 📜 **Certificados**
3. 🛒 **+Cursos**
4. 🔔 **Notificações**
5. 💰 **Meus duxes**

### 6.2. Padrão de cards

**Formato:**

* Forma: **quadrado** ou levemente retangular
* Lado: ~120–140px no mobile
* Fundo: branco
* Bordas: arredondadas (10–16px)
* Sombra leve

**Conteúdo do card:**

* Ícone grande no topo (emoji ou SVG simples)
* Título claro embaixo (máx. 2 palavras)

Exemplos de texto:

* “Meus cursos”
* “Certificados”
* “+Cursos”
* “Alertas”
* “Meus duxes”

---

## 7. Padrão de botões

### 7.1. Estilo principal

* Cor de fundo: **amarelo CTA** `#FBC02D`
* Texto: **preto**
* Fonte: Inter, bold, 16–18px
* Altura mínima: **50px** (ideal 56px)
* Bordas: arredondadas (10–14px)
* Ícone opcional no início (👉, ▶, ✓)

Exemplos de texto:

* “Continuar curso”
* “Ver aulas”
* “Baixar certificado”
* “Comprar com duxes”
* “Ver mais cursos”

### 7.2. Variantes

* Secundário: borda azul, texto azul, fundo branco.
* Perigo (casos raros, ex: sair da conta): fundo vermelho claro, texto branco.

---

## 8. Ícones padrão (sem frescura)

Usar sempre os mesmos ícones para manter hábito visual:

* Home: 🏠
* Meus cursos: 🎓
* Certificados: 📜
* +Cursos: 🛒
* Notificações: 🔔
* Perfil: 👤
* Ajuda / suporte: ❓
* Configurações: ⚙️
* Duxes (moeda): 💰

Se depois criar um ícone gráfico próprio para “Duxes”, manter o mesmo conceito: **moeda simples, fácil de reconhecer**.

---

## 9. Textos padrão (microcopy)

### 9.1. Títulos

* “Meus cursos”
* “Certificados”
* “+Cursos”
* “Notificações”
* “Meus duxes”

### 9.2. Ações

* “Continuar”
* “Ver curso”
* “Ver aulas”
* “Recomeçar”
* “Baixar certificado”
* “Comprar curso”
* “Usar duxes”

### 9.3. Estados vazios

* Sem cursos:

  * “Você ainda não tem cursos.”
  * Botão: “Ver +cursos”

* Sem certificados:

  * “Você ainda não concluiu nenhum curso.”
  * Botão: “Ver seus cursos”

* Sem notificações:

  * “Você não tem notificações.”

---

## 10. Estados importantes

### 10.1. Carregando

* Usar skeleton simples (blocos cinza) ou spinner bem leve.
* Texto curto: “Carregando…”

### 10.2. Erro

* Mensagem simples:

  * “Algo deu errado.”
  * “Tente novamente em alguns segundos.”
* Botão: “Tentar de novo”

### 10.3. Sem internet

* Ícone simples (ex: 📶 com X).
* Texto:

  * “Sem conexão”
  * “Verifique sua internet”

---

## 11. Acessibilidade mínima

* Tamanho mínimo do texto: **14px**, ideal **16px+**.
* Tocar em qualquer botão deve ser fácil (área grande).
* Não usar textos muito claros em fundos claros.
* Evitar parágrafos longos.

---

## 12. Checklist para qualquer tela do aluno

Antes de aprovar uma tela da área do aluno, verificar:

1. [ ] Fundo cinza claro (`#F5F5F5`)
2. [ ] No máximo 2 colunas
3. [ ] Ícones grandes e claros
4. [ ] Textos curtos e simples
5. [ ] Botões amarelos para ações principais
6. [ ] Navegação inferior com 4 ícones
7. [ ] Espaço em branco suficiente (nada apertado)
8. [ ] Pensado primeiro para celular

---

**Regra final:**

> Se uma pessoa que não gosta de estudar e não entende de tecnologia abrir o app e, **em 3 segundos**, conseguir achar “Meus cursos” e “Continuar”, a interface está certa.
