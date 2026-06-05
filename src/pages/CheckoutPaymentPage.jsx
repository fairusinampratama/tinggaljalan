import { Navigate } from 'react-router-dom';

export function CheckoutPaymentPage() {
  return <Navigate to="/checkout/confirmation" replace />;
}
