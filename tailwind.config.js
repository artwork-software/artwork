/*
 * Unter Tailwind v4 ist resources/css/app.css (@theme) die einzige Farb- und
 * Token-Quelle. Diese Datei existiert nur noch für content-Erkennung und Plugins.
 */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{vue,js,ts,jsx,tsx}',
        "./index.html",
        "./node_modules/vue-tailwind-datepicker/**/*.js"
    ],
    plugins: [
        require('@tailwindcss/typography')
    ],
};
