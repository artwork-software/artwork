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

    // Schrift auf Farbfläche: Weiß nur, wenn Weiß nach WCAG den höheren Kontrast hat als Schwarz.
    // Kontrast = (L_hell + 0,05) / (L_dunkel + 0,05); der Vergleich kippt bei relativer Luminanz
    // L ≈ 0,179 (sqrt(0,0525) − 0,05). Darunter gewinnt Weiß, darüber Schwarz. Dieselbe Regel wie
    // Spielplan-PDF und Tagesdienst-Bälle. Die frühere Helligkeitsschwelle 150 (0.299/0.587/0.114)
    // setzte auf mittleren Blau-/Türkis-/Pinktönen bei „Hoher Kontrast" noch weiße Schrift, obwohl
    // Schwarz dort den doppelten Kontrast hat (Befund Jannik 13.09.2026, Tagesansicht Dienstplan).
    // Versteht rgb()/rgba() und Hex (#rgb, #rrggbb, #rrggbbaa — Alpha wird gegen Weiß verrechnet).
    const DARK_LUMINANCE_THRESHOLD = 0.179;
    function isDarkColor(color) {
        const rgb = parseColorToRgb(color);
        if (!rgb) return false;
        return relativeLuminance(rgb) < DARK_LUMINANCE_THRESHOLD;
    }

    // WCAG 2.x relative Luminanz (sRGB-Gamma), 0 = Schwarz, 1 = Weiß
    function relativeLuminance([r, g, b]) {
        const [lr, lg, lb] = [r, g, b].map((v) => {
            const c = v / 255;
            return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * lr + 0.7152 * lg + 0.0722 * lb;
    }

    function parseColorToRgb(color) {
        if (typeof color !== 'string' || color === '') return null;
        const value = color.trim();
        if (value.startsWith('rgb')) {
            const parts = value.match(/[\d.]+/g)?.map(Number) ?? [];
            if (parts.length < 3) return null;
            const alpha = parts.length >= 4 ? Math.min(1, Math.max(0, parts[3])) : 1;
            return blendOverWhite(parts[0], parts[1], parts[2], alpha);
        }
        let hex = value.replace(/^#/, '');
        if (hex.length === 3) hex = hex.split('').map((c) => c + c).join('');
        if (!/^[0-9a-f]{6}([0-9a-f]{2})?$/i.test(hex)) return null;
        const r = parseInt(hex.slice(0, 2), 16);
        const g = parseInt(hex.slice(2, 4), 16);
        const b = parseInt(hex.slice(4, 6), 16);
        const alpha = hex.length === 8 ? parseInt(hex.slice(6, 8), 16) / 255 : 1;
        return blendOverWhite(r, g, b, alpha);
    }

    function blendOverWhite(r, g, b, alpha) {
        if (alpha >= 1) return [r, g, b];
        return [
            Math.round((1 - alpha) * 255 + alpha * r),
            Math.round((1 - alpha) * 255 + alpha * g),
            Math.round((1 - alpha) * 255 + alpha * b),
        ];
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
