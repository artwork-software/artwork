<script>
import { createInstanceFormatter } from "@/Helper/instanceFormat.js";

export default {
    methods: {
        // Betrag ohne Währungssymbol im Zahlenformat der Instanz (vorher fest de-DE)
        toCurrencyString(value) {
            return createInstanceFormatter(this.$page.props.instanceFormat).formatNumber(value ?? 0);
        },
    }
}
</script>
