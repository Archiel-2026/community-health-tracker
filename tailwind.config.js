/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.php",
    "./index-admin-staff.php",
    "./admin/**/*.php",
    "./staff/**/*.php",
    "./user/**/*.php",
    "./auth/**/*.php",
    "./includes/**/*.php",
    "./resident-header/**/*.php",
    "./api/**/*.php",
  ],
  theme: {
    extend: {
      colors: {
        'warm-blue': '#3a7bd5',
        'warm-blue-light': '#4a90e2',
        'warm-blue-dark': '#2a6bc5',
        'off-white': '#f8fafc',
      },
      fontFamily: {
        poppins: ['Poppins', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
