# Public Theme Tokens

Use these values when configuring Laravel Tailwind and public Blade layouts.

## Fonts

Load in the base public layout:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
```

## Tailwind Theme Values

```js
fontFamily: {
  sans: ['Plus Jakarta Sans', 'Inter', 'Arial', 'Helvetica', 'ui-sans-serif', 'system-ui', 'sans-serif'],
  display: ['Outfit', 'Plus Jakarta Sans', 'Inter', 'Arial', 'Helvetica', 'ui-sans-serif', 'system-ui', 'sans-serif'],
},
colors: {
  brandBlue: '#ef7d58',
  brandDark: '#201d1a',
  brandMuted: '#6f625a',
  brandLight: '#f7efe8',
  brandSoft: '#efe1d5',
  brandLine: '#dfd0c4',
},
boxShadow: {
  soft: '0 14px 34px rgba(32, 29, 26, 0.08)',
},
```

## Global CSS Defaults

```css
body {
  margin: 0;
  background: #f7efe8;
  color: #201d1a;
  font-family: 'Plus Jakarta Sans', Inter, Arial, Helvetica, ui-sans-serif, system-ui, sans-serif;
}
```

Keep WhatsApp-specific actions green instead of converting them to the terracotta accent.

