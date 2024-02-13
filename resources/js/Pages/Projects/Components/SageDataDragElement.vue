<script>
export default {
    name: "SageDataDragElement",
    props: ['sageData'],
    methods: {
        currencyFormattedValue(value) {
            return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(value);
        },
        onDragStart(event) {
            //event.preventDefault();
            event.dataTransfer.setData('text/plain', JSON.stringify(this.sageData));
            //console.log('drag start', this.sageData);
        }
    }
}
</script>

<template>
   <div class="flex items-center my-1.5 cursor-grab" draggable="true" @dragstart="onDragStart">
       <div class="w-28">
           {{ sageData.kto }}
       </div>
       <div class="w-28">
           {{ sageData.kst }}
       </div>
       <div class="w-64 truncate">
           {{ sageData.description }}
       </div>
       <div class="w-52 text-right">
           {{ currencyFormattedValue(sageData.amount) }}
       </div>
   </div>
</template>

<style scoped>

</style>
