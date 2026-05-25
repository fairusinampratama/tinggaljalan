/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        display: ['Playfair Display', 'Georgia', 'serif'],
      },
      colors: {
        sand: '#f8f1e7',
        clay: '#b9633f',
        sunset: '#e59a47',
        forest: '#193b33',
        ocean: '#1f6f7a',
      },
      boxShadow: {
        soft: '0 20px 60px rgba(25, 59, 51, 0.12)',
      },
    },
  },
  plugins: [],
};
