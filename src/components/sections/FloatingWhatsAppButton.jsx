import { MessageCircle } from 'lucide-react';

export function FloatingWhatsAppButton({ whatsappUrl, label = 'Chat', avoidMobileBottomBar = false }) {
  const bottomClass = avoidMobileBottomBar
    ? 'bottom-[calc(5.75rem+env(safe-area-inset-bottom))] lg:bottom-4'
    : 'bottom-4';

  return (
    <a href={whatsappUrl} target="_blank" rel="noreferrer" className={`fixed right-4 z-50 grid h-11 w-11 place-items-center rounded-full bg-[#25D366] text-white shadow-lg shadow-black/15 transition duration-200 hover:-translate-y-1 hover:bg-[#1fb457] hover:shadow-xl hover:shadow-[#25D366]/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#25D366] sm:flex sm:w-auto sm:gap-2 sm:px-4 ${bottomClass}`}>
      <MessageCircle className="h-4 w-4" />
      <span className="hidden text-sm font-bold sm:inline">{label}</span>
    </a>
  );
}
