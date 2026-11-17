/** @type {import('tailwindcss').Config} */
export default {
  content: ['./resources/views/**/*.blade.php', './resources/js/**/*.js'],
  theme: { extend: { colors: { fluxo: { 50: '#edf3ff', 500: '#2863ef', 600: '#1e50d8', 950: '#10182d' } } } },
  plugins: [],
};
