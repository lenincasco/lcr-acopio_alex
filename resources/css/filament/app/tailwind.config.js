import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './app/Filament/**/*.php',
        './resources/css/app.css',
    ],
    safelist: [
    'pointer-events-none',
    'appearance-none',
    'border-none',
    'bg-transparent'
  ],
}
