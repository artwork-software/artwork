<template>
    <div class="flex items-center my-1.5 cursor-grab" draggable="true" @dragstart="onDragStart">

        <div class="w-28">
            {{ sageData.sa_kto }}
        </div>
        <div class="w-28">
            {{ sageData.kst_stelle }}
        </div>
        <div class="w-64 truncate">
            {{ sageData.buchungstext }}
        </div>
        <div class="w-52 text-right">
            {{ this.currencyFormattedValue(sageData.buchungsbetrag) }}
        </div>
        <div class="w-40 text-right">
            {{ this.formatBookingDataDate(sageData.buchungsdatum) }}
        </div>
        <div v-if="type === 'global'" class="w-28 text-right">
            {{ sageData.kst_traeger}}
        </div>
        <div class="w-5 ml-2">
            <TrashIcon class="w-5 h-5 cursor-pointer hover:text-red-600"
                       @click="this.$emit('removeSageNotAssignedData', sageData)"
            />
        </div>
    </div>
</template>

<script>
import {TrashIcon} from "@heroicons/vue/solid";

export default {
    name: "SageDataDragElement",
    components: {
        TrashIcon
    },
    props: ['sageData','type'],
    emits: ['removeSageNotAssignedData'],
    methods: {
        currencyFormattedValue(value) {
            return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(value);
        },
        onDragStart(event) {
            event.dataTransfer.setData('text/plain', JSON.stringify(this.sageData));
        },
        formatBookingDataDate(dateString) {
            let parts = dateString.split('T');
            parts = parts[0].split('-');

            return parts[2] + '.' + parts[1] + '.' + parts[0];
        },
    }
}
</script>
