# Tinggal Jalan Landing Page Prototype

A modern responsive travel agency landing page prototype for **Tinggal Jalan**. The project is built with Vite, React, Tailwind CSS, and lucide-react.

This is a visual prototype only. It does not include backend booking logic, payment, CMS, routing, or real form submission.

## Features

- Premium travel agency landing page
- Responsive layout for mobile, tablet, and desktop
- Navbar with desktop dropdowns and mobile menu
- Hero section with WhatsApp-first CTA
- Custom styled booking/search dropdowns
- Popular destination cards for Bromo, Jogja, Tumpak Sewu, and Medan / Lake Toba
- Package highlight cards with duration, route, price, includes, and CTA
- Trust proof section
- About, activities, testimonials, gallery, booking form, CTA footer, and contact footer
- Floating WhatsApp CTA
- Local image assets and favicon

## Tech Stack

- Vite
- React
- Tailwind CSS
- lucide-react

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
│       └── *.jpg
├── src/
│   ├── App.jsx
│   ├── index.css
│   └── main.jsx
├── tailwind.config.js
├── postcss.config.js
└── vite.config.js
```

## Assets

The travel images are stored locally in `public/images`. Image source URLs are documented in `public/images/credits.md`.

## Notes

- Booking and search forms are visual-only.
- WhatsApp links use the prototype contact number from the provided materials.
- The code is intentionally simple so it can be converted later into Laravel Blade.

## Verification

Last verified with:

```bash
npm run build
```
