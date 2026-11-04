// Official 2026 WhatsApp glyph; keep artwork and brand colors unchanged.
export function WhatsAppIcon({ className = 'h-5 w-5', variant = 'white' }) {
  return <img src={`/images/brand/whatsapp-glyph-${variant}.svg`} width="28" height="28" alt="" aria-hidden="true" className={`shrink-0 ${className}`} />;
}
