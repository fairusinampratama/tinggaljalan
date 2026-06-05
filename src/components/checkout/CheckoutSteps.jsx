import { useBooking } from '../../context/BookingContext';

const stepHoverClass = 'transition duration-300 hover:border-brandBlue/20';

export function CheckoutSteps({ current }) {
  const { t } = useBooking();
  const steps = [
    { label: t.stepTrip, path: '/booking' },
    { label: t.stepContact, path: '/checkout/review' },
    { label: t.stepConfirm, path: '/checkout/confirmation' },
  ];

  return (
    <div className="mb-6 grid gap-2 sm:grid-cols-3">
      {steps.map((step, index) => (
        <div
          key={step.path}
          className={`rounded-xl border px-4 py-3 text-sm font-black ${stepHoverClass} ${
            index <= current ? 'border-brandBlue bg-brandBlue text-white' : 'border-brandLine bg-white text-brandMuted'
          }`}
        >
          {index + 1}. {step.label}
        </div>
      ))}
    </div>
  );
}
