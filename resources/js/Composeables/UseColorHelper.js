import { ref } from 'vue';

export function useColorHelper() {
    const parentBackgroundColor = ref(null);
    function backgroundColorWithOpacity(color, percent = 100) {
        if (!color) return `rgba(255, 255, 255, ${percent / 100})`;
        const r = parseInt(color.slice(-6, -4), 16);
        const g = parseInt(color.slice(-4, -2), 16);
        const b = parseInt(color.slice(-2), 16);
        const parentRgba = parentBackgroundColor.value || 'rgba(255, 255, 255, 1)';
        const parentRgbMatch = parentRgba.match(/\d+/g).map(Number);
        const [parentR, parentG, parentB] = parentRgbMatch;
        const blendedR = Math.round((1 - percent / 100) * parentR + (percent / 100) * r);
        const blendedG = Math.round((1 - percent / 100) * parentG + (percent / 100) * g);
        const blendedB = Math.round((1 - percent / 100) * parentB + (percent / 100) * b);
        return `rgb(${blendedR}, ${blendedG}, ${blendedB})`;
    }

    function detectParentBackgroundColor(element) {
        if (!element || !element.parentElement) {
            parentBackgroundColor.value = 'rgba(255, 255, 255, 1)';
            return;
        }

        const parentElement = element.parentElement;
        const computedStyle = window.getComputedStyle(parentElement);
        const bgColor = computedStyle.backgroundColor;

        if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
            parentBackgroundColor.value = bgColor;
        } else {
            detectParentBackgroundColor(parentElement);
        }
    }

    function backgroundColorWithOpacityOld(color, percent = 15) {
        if (!color) return `rgba(255, 255, 255, ${percent / 100})`;
        return `rgba(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, ${percent / 100})`;
    }

    function getHighContrastPercent(settings) {
        return settings?.high_contrast ? 75 : 15;
    }

    function getTextColorBasedOnBackground(color) {
        const isDark = isDarkColor(color);
        return isDark ? '#FFFFFF' : '#000000';
    }

    function isDarkColor(color) {
        if (color.startsWith('rgb')) {
            const rgb = color.match(/\d+/g).map(Number);
            const [r, g, b] = rgb;
            const luminance = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
            return luminance < 0.5;
        }
        return false;
    }

    // former ColorHelper mixin: WCAG-gamma luminance (handles hex and rgb strings)
    function calculateLuminance(color) {
        let rgb;
        if (color.startsWith('rgb')) {
            // Convert "rgb(r, g, b)" or "rgba(r, g, b, a)" to hex format
            rgb = color.match(/\d+/g).slice(0, 3).map(Number);
        } else {
            rgb = [
                parseInt(color.slice(1, 3), 16),
                parseInt(color.slice(3, 5), 16),
                parseInt(color.slice(5, 7), 16),
            ];
        }
        const [r, g, b] = rgb.map(v => v / 255);
        const a = [r, g, b].map(v => v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4));
        return 0.2126 * a[0] + 0.7152 * a[1] + 0.0722 * a[2];
    }

    // former ColorHelper mixin isDarkColor (luminance-based, differs from isDarkColor above)
    function isDarkColorByLuminance(color) {
        return calculateLuminance(color) < 0.5;
    }

    // former ColorHelper mixin TextColorWithDarken
    function TextColorWithDarken(color, percent = 75) {
        if (!color) return 'rgb(180, 180, 180)';
        return `rgb(${Math.max(0, parseInt(color.slice(-6, -4), 16) - percent)}, ${Math.max(0, parseInt(color.slice(-4, -2), 16) - percent)}, ${Math.max(0, parseInt(color.slice(-2), 16) - percent)})`;
    }

    return {
        backgroundColorWithOpacity,
        detectParentBackgroundColor,
        getHighContrastPercent,
        getTextColorBasedOnBackground,
        isDarkColor,
        parentBackgroundColor,
        backgroundColorWithOpacityOld,
        calculateLuminance,
        isDarkColorByLuminance,
        TextColorWithDarken
    };
}
