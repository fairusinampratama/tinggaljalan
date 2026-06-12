# Tinggal Jalan Indonesia Tours Frontend

A responsive travel package catalog and booking-request frontend for **Tinggal Jalan Indonesia Tours**. The project is built with Vite, React, Tailwind CSS v4, and lucide-react.

This is a frontend-only implementation before backend integration. It demonstrates the customer journey, route details, pricing logic, booking states, SEO metadata, WhatsApp handoff, and regional behavior without backend booking persistence, real payment capture, CMS, database, email delivery, or real form submission.

## Features

- Brand-aligned Tinggal Jalan frontend with current logo, favicon, and app title
- Responsive layout for mobile, tablet, and desktop
- Region-based language selector for Indonesia, English, and Mandarin
- Viator-inspired route catalog cards with ratings, review counts, duration, free-cancellation label, and strong price hierarchy
- Route catalog with localized duration, pickup, private-trip labels, IDR/USD pricing, and route-specific add-ons
- Route detail page with gallery, package option, itinerary, pickup/drop-off, inclusions, exclusions, good-to-know notes, policies, add-ons, and traveler proof
- Step-by-step booking flow: trip setup, customer details, review, and confirmation
- Booking request flow with voucher discount, pickup point, pax, date, traveler type, separate currency selection, add-ons, customer email, and notes
- Currency-based payment label: IDR uses Midtrans and USD uses Stripe after team confirmation
- Blocked-booking and limited-seat state examples for availability review
- WhatsApp CTA with prefilled booking summary
- Email field and confirmation state for booking-request follow-up
- Runtime SEO metadata, canonical URLs, Open Graph/Twitter tags, route JSON-LD, `robots.txt`, and `sitemap.xml`
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
│   │   ├── seo/
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

- Booking, voucher, block-booking, add-on, payment-label, and email follow-up states are frontend-only until backend integration.
- Routes are frontend-only React routes; configure a production host fallback to `index.html` before deploying.
- WhatsApp links use the Tinggal Jalan contact number from `src/data/brand.js`.
- Booking state is kept in memory for frontend review and resets on browser refresh.
- Static prototype data lives in `src/data`.
- Shared helper logic lives in `src/utils`.
- Route/page screens live in `src/pages`.
- Traveler type and currency are separate booking fields. Currency controls price display, voucher eligibility, and payment label.
- The temporary SEO base URL is `https://tinggaljalan.com` in `src/utils/seo.js`; update it when the final deployment domain changes.
- Booking and checkout pages are marked `noindex`; home, routes, and route detail pages are indexable.

## Verification

Verify with:

```bash
npm run build
```

Recommended frontend QA before backend:

```bash
rg -n "prototype|mock|dummy|DUMMY" index.html src public/robots.txt public/sitemap.xml
rg -n "adventure-|terrain-sweep|route-line|Outfit|fonts.googleapis|font-black" src index.html
npm run preview
```
