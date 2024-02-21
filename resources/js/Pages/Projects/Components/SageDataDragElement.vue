<script>
export default {
    name: "SageDataDragElement",
    props: ['sageData'],
    methods: {
        currencyFormattedValue(value) {
            return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(value);
        },
        onDragStart(event) {
            event.dataTransfer.setData('text/plain', JSON.stringify(this.sageData));
        }
    }
}
</script>

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
           {{ currencyFormattedValue(sageData.buchungsbetrag) }}
       </div>
       <div class="w-40 text-right">
           {{ sageData.buchungsdatum }}
       </div>
   </div>
</template>
