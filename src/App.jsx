import { useEffect } from 'react';
import { BrowserRouter, Route, Routes, useLocation } from 'react-router-dom';
import { AppLayout } from './components/layout/AppLayout';
import { BookingPage } from './pages/BookingPage';
import { CheckoutConfirmationPage } from './pages/CheckoutConfirmationPage';
import { CheckoutPaymentPage } from './pages/CheckoutPaymentPage';
import { CheckoutReviewPage } from './pages/CheckoutReviewPage';
import { HomePage } from './pages/HomePage';
import { NewsDetailPage } from './pages/NewsDetailPage';
import { NewsPage } from './pages/NewsPage';
import { RouteDetailPage } from './pages/RouteDetailPage';
import { RoutesPage } from './pages/RoutesPage';

function ScrollRestoration() {
  const { hash, pathname } = useLocation();

  useEffect(() => {
    if (hash) {
      window.requestAnimationFrame(() => {
        document.querySelector(hash)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
      return;
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
  }, [hash, pathname]);

  return null;
}

function App() {
  return (
    <BrowserRouter>
      <ScrollRestoration />
      <Routes>
        <Route element={<AppLayout />}>
          <Route index element={<HomePage />} />
          <Route path="routes" element={<RoutesPage />} />
          <Route path="routes/:routeId" element={<RouteDetailPage />} />
          <Route path="news" element={<NewsPage />} />
          <Route path="news/:articleSlug" element={<NewsDetailPage />} />
          <Route path="booking" element={<BookingPage />} />
          <Route path="checkout/review" element={<CheckoutReviewPage />} />
          <Route path="checkout/payment" element={<CheckoutPaymentPage />} />
          <Route path="checkout/confirmation" element={<CheckoutConfirmationPage />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}

export default App;
