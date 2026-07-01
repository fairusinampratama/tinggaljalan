import { Navigate } from 'react-router-dom';
import { Seo } from '../components/seo/Seo';

export function CheckoutPaymentPage() {
  return (
    <>
      <Seo
        title="Checkout Payment | Tinggal Jalan"
        description="Tinggal Jalan checkout payment handoff."
        path="/checkout/payment"
        noindex
      />
      <Navigate to="/checkout/confirmation" replace />
    </>
  );
}
