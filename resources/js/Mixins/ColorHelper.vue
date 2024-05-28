<script>
export default {
    name: "ColorHelper",
    methods: {
        backgroundColorWithOpacity(color, percent = 15){
            if (!color) return `rgb(255, 255, 255, ${percent}%)`;
            return `rgb(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, ${percent}%)`;
        },
        TextColorWithDarken(color, percent = 75){
            if (!color) return 'rgb(180, 180, 180)';
            return `rgb(${parseInt(color.slice(-6, -4), 16) - percent}, ${parseInt(color.slice(-4, -2), 16) - percent}, ${parseInt(color.slice(-2), 16) - percent})`;
        },
    }
}
</script>
