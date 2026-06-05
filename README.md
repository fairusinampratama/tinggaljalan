# Tinggal Jalan Indonesia Tours Prototype

A responsive travel package and booking-flow prototype for **Tinggal Jalan Indonesia Tours**. The project is built with Vite, React, Tailwind CSS v4, and lucide-react.

This is a frontend prototype only. It demonstrates the customer journey, route details, pricing logic, booking states, WhatsApp handoff, and regional behavior without backend booking logic, real payment capture, CMS, database, email delivery, or real form submission.

## Features

- Brand-aligned Tinggal Jalan mockup with current logo, favicon, and app title
- Responsive layout for mobile, tablet, and desktop
- Region-based language selector for Indonesia, English, and Mandarin
- Route catalog with localized duration, pickup, private-trip labels, and region-based IDR/USD pricing
- Klook-style route detail page with gallery, package option, itinerary, pickup/drop-off, inclusions, exclusions, good-to-know notes, policies, and traveler proof preview
- Step-by-step booking flow: trip setup, customer details, review, and confirmation
- Booking mockup with voucher discount, pickup point, pax, date, customer email, and notes
- Mock Midtrans / Stripe payment gateway selection based on traveler region
- Mock blocked-booking state for Bromo closure demo
- WhatsApp CTA with prefilled booking summary
- Email field and confirmation state for prototype order/payment follow-up
- Basic SEO and Open Graph metadata
- Local route image assets plus current-logo favicon

## Tech Stack

- Vite 8
- React 19
- React Router
- Tailwind CSS 4 via `@tailwindcss/vite`
- lucide-react 1

## Getting Started

Install dependencies:

```bash
npm install
```

Start the development server:

```bash
npm run dev
```

Build for production:

```bash
npm run build
```

Preview a production build:

```bash
npm run preview
```

## Project Structure

```text
.
├── index.html
├── public/
│   ├── favicon.svg
│   └── images/
│       ├── credits.md
│       ├── destinations/
│       └── routes/
├── src/
│   ├── components/
│   │   ├── checkout/
│   │   ├── layout/
│   │   ├── sections/
│   │   └── ui/
│   ├── context/
│   ├── data/
│   ├── pages/
│   ├── utils/
│   ├── App.jsx
│   ├── index.css
│   └── main.jsx
└── vite.config.js
```

## Assets

The travel images are stored locally in `public/images`. Image source URLs are documented in `public/images/credits.md`.

## Notes

- Booking, voucher, block-booking, payment gateway, and email follow-up states are mock-only.
- Routes are frontend-only React routes; configure a production host fallback to `index.html` before deploying.
- WhatsApp links use the Tinggal Jalan prototype contact number.
- Booking state is kept in memory for prototype review and resets on browser refresh.
- Static prototype data lives in `src/data`.
- Shared helper logic lives in `src/utils`.
- Route/page screens live in `src/pages`.
- Local/Indonesia region uses IDR and Midtrans in the prototype. English and Mandarin regions use USD and Stripe.

## Verification

Verify with:

```bash
npm run build
```
