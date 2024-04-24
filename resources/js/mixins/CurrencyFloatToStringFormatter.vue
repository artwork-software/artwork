<script>
export default {
    methods: {
        toCurrencyString(value) {
            if (value === null || typeof value === 'undefined') {
                value = "0";
            }
            //cast value to String, replace commas by dots. Parse Number and format it to 1.234,56
            return Number(
                String(value).replace(',', '.')
            ).toLocaleString(
                'de-DE',
                {
                    minimumFractionDigits: 2
                }
            );
        },
    }
}
</script>
