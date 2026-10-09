// CMS descriptions are plain text with occasional **emphasis**. Render only
// that notation; all text remains escaped by React (no HTML/Markdown parser).
export function InlineStrong({ text = '' }) {
  return String(text).split(/(\*\*[^*\n]+\*\*)/g).map((part, index) =>
    /^\*\*[^*\n]+\*\*$/.test(part) ? <strong key={index}>{part.slice(2, -2)}</strong> : part,
  );
}
