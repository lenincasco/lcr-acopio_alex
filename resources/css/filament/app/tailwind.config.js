import preset from '../../../../vendor/filament/support/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
      './resources/css/app.css',
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './app/Filament/**/*.php',
    ],
}
