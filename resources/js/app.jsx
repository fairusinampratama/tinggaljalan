import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { useLayoutEffect } from 'react';
import { AppLayout } from './components/layout/AppLayout';
import { BookingProvider } from './context/BookingContext';
import { prepareInitialHandoff, revealInitialApp } from './utils/initialHandoff';

const pages = import.meta.glob('./pages/**/*.jsx');

function InitialApp({ App, props, handoff, onFailure }) {
  useLayoutEffect(() => {
    if (handoff) return revealInitialApp(handoff, onFailure);
  }, [handoff, onFailure]);
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
    const handoff = prepareInitialHandoff(el);
    const root = createRoot(handoff?.stage ?? el, { onUncaughtError: () => onFailure() });
    const onFailure = () => {
      // The original document remains readable on media failure or timeout.
      root.unmount();
      handoff?.stage.remove();
    };
    root.render(<InitialApp App={App} props={props} handoff={handoff} onFailure={onFailure} />);
  },
  progress: {
    color: '#B99A5E',
  },
}).catch((error) => {
  // Page/chunk failures must leave the readable server document in place.
  console.error('TinggalJalan could not start; server content remains available.', error);
});
