<script>
export default {
    name: "ColorHelper",
    methods: {
        backgroundColorWithOpacity(color){
            if (!color) return 'rgb(255, 255, 255, 15%)';
            return `rgb(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, 15%)`;
        },
        TextColorWithDarken(color){
            if (!color) return 'rgb(180, 180, 180)';
            return `rgb(${parseInt(color.slice(-6, -4), 16) - 75}, ${parseInt(color.slice(-4, -2), 16) - 75}, ${parseInt(color.slice(-2), 16) - 75})`;
        },
    }
}
</script>
