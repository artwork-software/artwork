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

    return {
        backgroundColorWithOpacity,
        detectParentBackgroundColor,
        getTextColorBasedOnBackground,
        isDarkColor,
        parentBackgroundColor
    };
}
