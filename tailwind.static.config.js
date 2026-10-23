/**
 * Static Tailwind build for layouts/app (see resources/tailwind-static/input.css).
 * Same options the Play CDN used there: no prefix, preflight off.
 */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './public/js/**/*.js',
        './app/**/*.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    corePlugins: { preflight: false },
};
