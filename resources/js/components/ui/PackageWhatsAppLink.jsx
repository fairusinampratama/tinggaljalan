import { WhatsAppIcon } from './WhatsAppIcon';

// Receive the URL already resolved by BookingContext. Presentation and placement
// are independent; no button constructs its own recipient or message.
export function PackageWhatsAppLink({ whatsappUrl, label, accessibleLabel, placement, className }) {
  return (
    <a
      href={whatsappUrl}
      target="_blank"
      rel="noreferrer"
      data-whatsapp-placement={placement}
      aria-label={accessibleLabel ?? `${label} — WhatsApp`}
      className={className}
    >
      <WhatsAppIcon className="h-5 w-5 shrink-0" />
      <span>{label}</span>
    </a>
  );
}
