<script>
export default {
    name: "Helper",
    methods: {
        decimalToCommonFraction(decimal) {
            const commonFractions = {
                0.125: '⅛',
                0.2: '⅕',
                0.25: '¼',
                0.3: '⅓',
                0.375: '⅜',
                0.4: '⅖',
                0.5: '½',
                0.6: '⅗',
                0.625: '⅝',
                0.666: '⅔',
                0.7: '⅗',
                0.75: '¾',
                0.8: '⅘',
                0.875: '⅞',
                0.333: '⅓',
                0.833: '⅚',
            };

            const wholePart = Math?.floor(decimal);
            const fractionPart = decimal - wholePart;

            // Finde den nächstgelegenen Bruch für den Bruchteil
            let closestFraction = Object?.keys(commonFractions)?.reduce((prev, curr) => {
                return (Math?.abs(curr - fractionPart) < Math?.abs(prev - fractionPart) ? curr : prev);
            });

            // Prüfen, ob der Bruchteil innerhalb der Toleranzgrenze liegt
            const tolerance = 0.01;
            let fractionSymbol = '';
            if (Math?.abs(closestFraction - fractionPart) <= tolerance) {
                fractionSymbol = commonFractions[closestFraction];
            }

            // Kombiniere die ganze Zahl und den Bruchteil
            return wholePart > 0 ? `${wholePart}${fractionSymbol}` : fractionSymbol || decimal.toString();
        }
    }
}
</script>

<template>

</template>

<style scoped>

</style>
