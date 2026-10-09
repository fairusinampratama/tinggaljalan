import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { useLayoutEffect } from 'react';
import { AppLayout } from './components/layout/AppLayout';
import { BookingProvider } from './context/BookingContext';

const pages = import.meta.glob('./pages/**/*.jsx');

function InitialApp({ App, props, anchor }) {
  useLayoutEffect(() => {
    if (!anchor) return;
    const target = document.getElementById(anchor.id);
    if (target) {
      // Keep the native fragment's visible position through DOM replacement,
      // before paint. Do not animate a second trip down the page.
      window.scrollBy({ top: target.getBoundingClientRect().top - anchor.top, behavior: 'instant' });
      requestAnimationFrame(() => requestAnimationFrame(() => {
        document.documentElement.removeAttribute('data-initial-fragment');
      }));
    }
  }, [anchor]);
  return <App {...props} />;
}

createInertiaApp({
  title: (title) => {
    const pageTitle = title?.trim();

    if (!pageTitle) return 'Tinggal Jalan';

    return /^(?:tinggal jalan)(?:\s*\||$)|\|\s*tinggal jalan$/i.test(pageTitle)
      ? pageTitle
      : `${pageTitle} | Tinggal Jalan`;
  },
  resolve: async (name) => {
    const loadPage = pages[`./pages/${name}.jsx`];

    if (!loadPage) {
      throw new Error(`Inertia page not found: ${name}`);
    }

    const page = await loadPage();

    const componentName = name.split('/').pop();
    const component = page.default ?? page[componentName];

    if (!component) {
      throw new Error(`Inertia page export not found: ${name}`);
    }

    component.layout =
      component.layout ??
      ((pageContent) => (
        <BookingProvider>
          <AppLayout>{pageContent}</AppLayout>
        </BookingProvider>
      ));

    return component;
  },
  setup({ el, App, props }) {
    if (!props.initialPage.props.translations?.searchTitle) {
      throw new Error('Initial translations unavailable; keeping server content.');
    }
    let target;
    try { target = document.getElementById(decodeURIComponent(window.location.hash.slice(1))); } catch { /* malformed fragment */ }
    const anchor = target ? { id: target.id, top: target.getBoundingClientRect().top } : null;
    createRoot(el).render(<InitialApp App={App} props={props} anchor={anchor} />);
  },
  progress: {
    color: '#B99A5E',
  },
}).catch((error) => {
  // Page/chunk failures must leave the readable server document in place.
  console.error('TinggalJalan could not start; server content remains available.', error);
});
