import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { AppLayout } from './components/layout/AppLayout';
import { BookingProvider } from './context/BookingContext';

const pages = import.meta.glob('./pages/**/*.jsx');

createInertiaApp({
  title: (title) => (title ? `${title} | Tinggal Jalan` : 'Tinggal Jalan'),
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
    createRoot(el).render(<App {...props} />);
  },
  progress: {
    color: '#B99A5E',
  },
});
