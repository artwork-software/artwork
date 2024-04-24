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
            {{ this.toCurrencyString(sageData.buchungsbetrag) }}
        </div>
        <div class="w-40 text-right">
            {{ this.formatBookingDataDate(sageData.buchungsdatum) }}
        </div>
        <div v-if="type === 'global'" class="w-28 text-right">
            {{ sageData.kst_traeger}}
        </div>
        <div class=" ml-2 flex">
            <IconTrash class="w-5 h-5 cursor-pointer hover:text-red-600" @click="this.$emit('removeSageNotAssignedData', sageData)"/>
        </div>
        <div class="w-5 ml-2">
            <IconDragDrop class="w-5 h-5 cursor-grab ml-2" />
        </div>
    </div>
</template>

<script>
import {TrashIcon} from "@heroicons/vue/solid";
import IconLib from "@/Mixins/IconLib.vue";
import CurrencyFloatToStringFormatter from "@/Mixins/CurrencyFloatToStringFormatter.vue";

export default {
    name: "SageDataDragElement",
    components: {
        TrashIcon
    },
    mixins: [IconLib, CurrencyFloatToStringFormatter],
    props: ['sageData','type'],
    emits: ['removeSageNotAssignedData'],
    methods: {
        onDragStart(event) {
            this.sageData.type = 'globaleMove';
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
