{{-- POV logo mark: the "O" lens (orange disc, blue tick ring, dark pupil, cream highlights).
     Usage: <x-pov-mark class="w-9 h-9" /> --}}
<svg {{ $attributes->merge(['class' => 'flex-shrink-0']) }} viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="POV logo">
    <!-- outer tick marks -->
    <circle cx="50" cy="50" r="47" fill="none" stroke="#3399DD" stroke-width="4" stroke-dasharray="2.2 6" />
    <!-- orange disc -->
    <circle cx="50" cy="50" r="45" fill="#FF5A36" />
    <!-- lens: blue ring + dark pupil -->
    <circle cx="50" cy="50" r="28" fill="#0B132B" stroke="#3399DD" stroke-width="3.5" />
    <!-- highlights -->
    <circle cx="41" cy="40" r="8.5" fill="#F6F2EA" />
    <circle cx="61" cy="61" r="4" fill="#F6F2EA" />
</svg>
