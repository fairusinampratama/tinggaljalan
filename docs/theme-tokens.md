# Public Theme Tokens

Use these values for the public browser experience. Filament and branded email surfaces retain their dedicated themes.

## Fonts

Fonts are bundled through `@fontsource` packages in the Vite entries. Do not add remote Google Fonts links.

```css
font-family: Manrope, Inter, Arial, Helvetica, ui-sans-serif, system-ui, sans-serif;
font-family: 'DM Serif Display', Georgia, 'Times New Roman', serif;
```

Use DM Serif Display only for public destination, editorial, and section headings. Use Manrope for body copy, prices, navigation, forms, buttons, and the entire admin panel.

## Tailwind Theme Values

```css
--font-sans: Manrope, Inter, Arial, Helvetica, ui-sans-serif, system-ui, sans-serif;
--font-display: 'DM Serif Display', Georgia, 'Times New Roman', serif;

--color-primary: #102a36;
--color-secondary: #2f6f6d;
--color-accent: #b99a5e;
--color-canvas: #ffffff;
--color-surface: #ffffff;
--color-ink: #172126;
--color-muted: #667277;
--color-line: #e3e8e6;
--color-subtle: #f5f7f6;
--shadow-soft: 0 10px 30px rgba(16, 42, 54, 0.07);
```

## Usage

- Navy is the primary action and high-emphasis surface color.
- Teal is reserved for links, active navigation, and secondary emphasis.
- Gold is a restrained accent for eyebrows, ratings, dividers, and premium details.
- Pure white forms the page and primary card surfaces; subtle gray-green is reserved for controls and occasional grouping.
- Keep WhatsApp actions green and preserve semantic success, warning, danger, and validation colors.
- Transactional emails use Arial/Helvetica fallbacks while sharing the luxury palette.
