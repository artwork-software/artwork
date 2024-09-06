<script>
export default {
    name: "ColorHelper",
    methods: {
        backgroundColorWithOpacity(color, percent = 15) {
            if (!color) return `rgba(255, 255, 255, ${percent / 100})`;
            return `rgba(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, ${percent / 100})`;
        },
        calculateLuminance(color) {
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
        },
        isDarkColor(color) {
            return this.calculateLuminance(color) < 0.5;
        },
        TextColorWithDarken(color, percent = 75) {
            if (!color) return 'rgb(180, 180, 180)';
            return `rgb(${Math.max(0, parseInt(color.slice(-6, -4), 16) - percent)}, ${Math.max(0, parseInt(color.slice(-4, -2), 16) - percent)}, ${Math.max(0, parseInt(color.slice(-2), 16) - percent)})`;
        },
    }
}
</script>
