import { useEffect, useRef, useState } from 'react';
import { WhatsAppIcon } from '../ui/WhatsAppIcon';

export function FloatingWhatsAppButton({ whatsappUrl, label = 'Chat', accessibleLabel = 'Chat via WhatsApp', centerOnMobile = false }) {
  const linkRef = useRef(null);
  const [galleryInButtonPath, setGalleryInButtonPath] = useState(false);

  useEffect(() => {
    if (!centerOnMobile) return;
    const galleryParts = document.querySelectorAll('[data-route-gallery-edge-control]');
    if (!galleryParts.length) return;

    let observer;
    const observe = () => {
      observer?.disconnect();
      // Watch the centered 56px button slot plus space for its focus outline.
      const inset = Math.max(0, (window.innerHeight - 68) / 2);
      const left = Math.max(0, linkRef.current.getBoundingClientRect().left - 6);
      const intersections = new Set();
      observer = new IntersectionObserver(entries => {
        for (const entry of entries) {
          if (entry.isIntersecting) intersections.add(entry.target);
          else intersections.delete(entry.target);
        }
        setGalleryInButtonPath(intersections.size > 0);
      }, { rootMargin: `-${inset}px 0px -${inset}px -${left}px` });
      galleryParts.forEach(part => observer.observe(part));
    };
    observe();
    window.addEventListener('resize', observe);

    return () => {
      observer?.disconnect();
      window.removeEventListener('resize', observe);
    };
  }, [centerOnMobile]);

  const corner = 'bottom-[calc(1rem+env(safe-area-inset-bottom))] sm:h-12 sm:w-auto sm:gap-2.5 sm:px-4';
  const centered = 'max-lg:top-1/2 max-lg:-translate-y-1/2 lg:bottom-[calc(1rem+env(safe-area-inset-bottom))] lg:h-12 lg:w-auto lg:gap-2.5 lg:px-4';

  return (
    <a
      ref={linkRef}
      href={whatsappUrl}
      target="_blank"
      rel="noreferrer"
      aria-label={accessibleLabel}
      data-whatsapp-placement="floating"
      className={`floating-whatsapp fixed right-[calc(1rem+env(safe-area-inset-right))] z-40 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-primary shadow-lg shadow-black/15 transition-shadow hover:shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary ${centerOnMobile ? centered : corner} ${galleryInButtonPath ? 'max-lg:invisible' : ''}`}
    >
      <WhatsAppIcon className={`h-7 w-7 ${centerOnMobile ? 'lg:h-6 lg:w-6' : 'sm:h-6 sm:w-6'}`} />
      <span className={`hidden text-sm font-bold ${centerOnMobile ? 'lg:inline' : 'sm:inline'}`}>{label}</span>
    </a>
  );
}
