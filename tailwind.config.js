/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        'start-dark': '#020617',
        'neon-cyan': '#22D3EE',
        'neon-purple': '#A855F7',
        'neon-rose': '#FB7185',
      },
      animation: {
        'glow-pulse': 'glow 2s infinite alternate',
        'float': 'float 3s ease-in-out infinite',
      },
      keyframes: {
        glow: {
          '0%': { filter: 'drop-shadow(0 0 5px #22D3EE)' },
          '100%': { filter: 'drop-shadow(0 0 20px #A855F7)' },
        },
        float: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-15px)' },
        }
      }
    },
  },
  plugins: [],
}
