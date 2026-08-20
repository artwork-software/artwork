{{-- Buchstaben-Avatar. Wird als Bild-URL ausgeliefert (route generate-avatar-image),
     damit dieselbe Grafik nicht als Data-URI in jedem Payload-Eintrag landet.
     Geometrie identisch zum frueheren Inline-SVG aus HasProfilePhotoCustom. --}}
<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64" role="img" aria-label="Avatar {{ $letters }}">
    <rect x="0" y="0" width="64" height="64" rx="12" ry="12" fill="{{ $bgColor }}"/>
    <text x="32" y="32"
          text-anchor="middle"
          dominant-baseline="central"
          font-family="-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Inter,Arial,sans-serif"
          font-size="26"
          font-weight="500"
          letter-spacing="0.5"
          fill="{{ $textColor }}">{{ $letters }}</text>
</svg>
