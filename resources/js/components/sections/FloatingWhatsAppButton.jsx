import { WhatsAppIcon } from '../ui/WhatsAppIcon';

export function FloatingWhatsAppButton({ whatsappUrl, label = 'Chat', accessibleLabel = 'Chat via WhatsApp', hasMobileContactBar = false }) {
  return (
    <a
      href={whatsappUrl}
      target="_blank"
      rel="noreferrer"
      aria-label={accessibleLabel}
      data-whatsapp-placement="floating"
      className={`floating-whatsapp fixed bottom-[calc(1rem+env(safe-area-inset-bottom))] right-[calc(1rem+env(safe-area-inset-right))] z-40 h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-primary shadow-lg shadow-black/15 transition-shadow hover:shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary sm:h-12 sm:w-auto sm:gap-2.5 sm:px-4 ${hasMobileContactBar ? 'hidden lg:flex' : 'flex'}`}
    >
      <WhatsAppIcon className="h-7 w-7 sm:h-6 sm:w-6" />
      <span className="hidden text-sm font-bold sm:inline">{label}</span>
    </a>
  );
}
