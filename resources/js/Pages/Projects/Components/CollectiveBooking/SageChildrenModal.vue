<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        @click.self="$emit('close')"
    >
        <div class="bg-white rounded-lg p-6 w-11/12 max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">Sammelbuchungen</h3>
                <XIcon class="w-6 h-6 cursor-pointer" @click="$emit('close')" />
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto border-collapse">
                    <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 text-left">KTO</th>
                        <th class="px-4 py-2 text-left">KST</th>
                        <th class="px-4 py-2 text-left">Buchungstext</th>
                        <th class="px-4 py-2 text-right">Betrag</th>
                        <th class="px-4 py-2 text-right">Datum</th>
                        <th class="px-4 py-2 text-left">Kostenträger</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="child in children" :key="child.id" class="border-t">
                        <td class="px-4 py-2">{{ child.sa_kto !== '' ? child.sa_kto : child.kto_soll }}</td>
                        <td class="px-4 py-2">{{ child.kst_stelle }}</td>
                        <td class="px-4 py-2 truncate max-w-xs">{{ child.buchungstext }}</td>
                        <td class="px-4 py-2 text-right">{{ toCurrencyString(child.buchungsbetrag) }}</td>
                        <td class="px-4 py-2 text-right">{{ formatBookingDataDate(child.buchungsdatum) }}</td>
                        <td class="px-4 py-2">{{ child.kst_traeger }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import { XIcon } from "@heroicons/vue/solid";
import CurrencyFloatToStringFormatter from "@/Mixins/CurrencyFloatToStringFormatter.vue";

export default {
    name: "SageChildrenModal",
    components: { XIcon },
    mixins: [CurrencyFloatToStringFormatter],
    props: {
        children: {
            type: Array,
            default: () => []
        }
    },
    methods: {
        formatBookingDataDate(dateString) {
            const [date] = dateString.split('T');
            const [year, month, day] = date.split('-');
            return `${day}.${month}.${year}`;
        }
    }
};
</script>
