<svg width="64px" height="64px" xmlns="http://www.w3.org/2000/svg">
    <!-- Hintergrund -->
    <rect width="64" height="64" fill="{{ $bgColor }}" rx="10" ry="10"/>

    <!-- Text -->
    <text xmlns="http://www.w3.org/2000/svg" x="50%" y="50%" style="color: {{ $textColor }}; line-height: 1;font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;" alignment-baseline="middle" text-anchor="middle" font-size="28" font-weight="400" dy=".1em" dominant-baseline="middle" fill="{{ $textColor }}">
        {{ $letters }}
    </text>
</svg>