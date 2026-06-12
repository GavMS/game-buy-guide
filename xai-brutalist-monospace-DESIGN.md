# Design System: xAI Brutalist Monospace

## 1. Definição do Estilo

- **Nome:** xAI Brutalist Monospace
- **Tipo:** Dark Brutalist, Monospace Display, Zero Shadows, Dim-on-Hover, Sharp Corners
- **Keywords:** xAI, brutalist, monospace, dark, zero shadows, dim-on-hover, sharp corners, GeistMono, universalSans, terminal luxury
- **Era:** 2024-2026 Terminal Brutalism
- **Light/Dark:** ✗ Not Recommended / ✓ Full

## 2. Paleta de Cores

- **Primárias:** Escuro #1f2228, Branco #ffffff, Branco 50% rgba(255,255,255,0.5), Branco 10% rgba(255,255,255,0.1)
- **Secundárias:** Branco 20% rgba(255,255,255,0.2), Branco 5% rgba(255,255,255,0.05), Branco 3% rgba(255,255,255,0.03), Ring Blue rgb(59,130,246)

## 3. Efeitos Visuais

Canvas dark near-black (#1f2228) com subtom azul quente. Monospace para display headlines em escala extrema (até 320px) weight 300. Botões uppercase monospace com letter-spacing 1.4px. Zero sombras em qualquer lugar. Cantos afiados (0px radius). Hover que DIMINUI opacidade para 0.5 (inverso da convenção). Profundidade via bordas de opacidade (rgba branco 0.1 padrão, 0.2 ativo). Hierarquia de texto via opacidade de branco (100%, 70%, 50%, 30%).

## 4. AI Prompt Keywords

Design an xAI-inspired dark brutalist landing page. Near-black background (#1f2228) with warm blue undertone. Monospace font for display headlines at extreme scale (72px+) weight 300. Uppercase monospace buttons with 1.4px letter-spacing. Zero shadows anywhere. Sharp corners (0px radius). Hover DIMS to rgba(255,255,255,0.5) instead of brightening. Depth through opacity-based borders (white at 0.1 default, 0.2 active). Text hierarchy via white opacity (100%, 70%, 50%, 30%). Sans-serif for body at 16px/1.5.

## 5. CSS Technical

```css
background: #1f2228; color: #ffffff; border: 1px solid rgba(255,255,255,0.1); border-radius: 0px; box-shadow: none; font-family: monospace for display/buttons, sans-serif for body; font-weight: 300 display, 400 body; text-transform: uppercase buttons; letter-spacing: 1.4px buttons
```

## 6. Design System Variables

```css
--bg: #1f2228; --text: #ffffff; --text-secondary: rgba(255,255,255,0.7); --text-muted: rgba(255,255,255,0.5); --text-disabled: rgba(255,255,255,0.3); --border: rgba(255,255,255,0.1); --border-strong: rgba(255,255,255,0.2); --surface: rgba(255,255,255,0.03); --radius: 0px
```

## 7. Checklist de Implementação

- ☐ Fundo dark #1f2228
- ☐ Monospace display weight 300
- ☐ Botões uppercase monospace
- ☐ Zero sombras
- ☐ Radius 0px
- ☐ Hover diminui opacidade
- ☐ Bordas por opacidade
- ☐ Responsivo

## 8. Visual Theme & Atmosphere

Estilo xAI Brutalist Monospace com brutalismo dark, monospace em escala extrema e zero sombras. Ideal para labs de IA, pesquisa e plataformas técnicas. Inspirado no design da xAI, que usa monospace como luxo e brutalismo como sofisticação — a ausência de design É o design.

- Density: 8/10 — Dense
- Variance: 4/10 — Moderate
- Motion: 4/10 — Subtle

## 9. Color Palette & Roles

- **Escuro** (#1f2228) — Dark surface, primary background
- **Branco** (#ffffff) — Light surface, card backgrounds
- **** (rgba(255,255,255,0.5)) — Supporting palette color
- **** (rgba(255,255,255,0.1)) — Supporting palette color
- **** (rgba(255,255,255,0.2)) — Extended palette, decorative use
- **** (rgba(255,255,255,0.05)) — Extended palette, decorative use
- **** (rgba(255,255,255,0.03)) — Extended palette, decorative use
- **Ring Blue** (rgb(59,130,246)) — Secondary accent

## 10. Typography Rules

- **Display / Hero:** monospace for display/buttons — Weight 700, tight tracking, used for headline impact
- **Body:** monospace for display/buttons — Weight 400, 16px/1.6 line-height, max 72ch per line
- **UI Labels / Captions:** monospace for display/buttons — 0.875rem, weight 500, slight letter-spacing
- **Monospace:** monospace for display/buttons — Used for code, metadata, and technical values

Scale:
- Hero: clamp(2.5rem, 5vw, 4rem)
- H1: 2.25rem
- H2: 1.5rem
- Body: 1rem / 1.6
- Small: 0.875rem

## 11. Component Stylings

- **Primary Button:** Sharp edges (0px) shape. Accent color fill. Hover: 8% darken + subtle lift shadow. Active: -1px translate tactile press. Font weight 600. No outer glows.
- **Secondary / Ghost Button:** Outline variant. 1.5px border in muted color. Text in primary color. Hover: subtle background fill.
- **Cards:** Sharp edges (0px) corners. Surface background. Subtle shadow (0 2px 12px rgba(0,0,0,0.06)). 1px border stroke.
- **Inputs:** Label above input. 1px border stroke. Focus ring: 2px accent color offset 2px. Error text below in semantic red. No floating labels.
- **Navigation:** Primary surface background. Active item: accent color indicator. Font weight 500 when active.
- **Skeletons:** Shimmer animation matching component dimensions. No circular spinners.
- **Empty States:** Icon-based composition with descriptive text and action button.

## 12. Layout Principles

- **Grid:** CSS Grid primary. Max-width containment: 1280px centered with 1.5rem side padding.
- **Spacing rhythm:** Balanced. Base unit: 0.5rem (8px).
- **Section vertical gaps:** clamp(4rem, 8vw, 8rem).
- **Hero layout:** Split-screen (text left, visual right).
- **Feature sections:** Zig-zag alternating text+image rows. No 3-equal-columns.
- **Mobile collapse:** All multi-column layouts collapse below 768px. No horizontal overflow.
- **z-index contract:** base (0) / sticky-nav (100) / overlay (200) / modal (300) / toast (500).

## 13. Motion & Interaction

- **Physics:** Ease-out curves, 200-300ms duration. Smooth and predictable.
- **Entry animations:** Fade + translate-Y (16px → 0) over 420ms ease-out. Staggered cascades for lists: 80ms between items.
- **Hover states:** Subtle color shift + shadow adjustment over 200ms.
- **Page transitions:** Fade only (200ms).
- **Performance:** Only transform and opacity animated. No layout-triggering properties.

## 14. Anti-Patterns (Banned)

- No emojis in UI — use icon system only (Lucide, Heroicons)
- No rounded corners — sharp edges only
- No subtle shadows — use hard borders instead
- No pure white (#FFFFFF) backgrounds — use off-white or dark surfaces
- No oversaturated accent colors (saturation cap: 80%)
- No 3-column equal-width feature layouts — use zig-zag or asymmetric grid
- No `h-screen` — use `min-h-[100dvh]`
- No AI copywriting clichés: "Elevate", "Seamless", "Unleash", "Next-Gen"
- No broken external image links — use picsum.photos or inline SVG
- No generic lorem ipsum in demos

## Contexto Histórico

Inspirado no design da xAI, que usa monospace como luxo e brutalismo como sofisticação — a ausência de design É o design.

## Caso de Uso

Labs de IA, Pesquisa, Plataformas técnicas, Infraestrutura de modelos
