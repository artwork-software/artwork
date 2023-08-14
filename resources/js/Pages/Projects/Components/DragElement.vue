
<template>
    <div class="drag-item w-full p-2 my-2 bg-gray-50/10 text-white text-xs rounded-lg flex items-center gap-2" draggable="true" @dragstart="onDragStart">
        <div>
            <img :src="item.profile_photo_url" alt="" class="h-6 w-6 rounded-full object-cover">
        </div>
        <div class="text-left">
            <div v-if="type === 0">
                {{ item.first_name }} {{ item.last_name }} (Intern)
                <div class="text-xs w-full"> {{plannedHours.toFixed(1)}} | 168</div>
            </div>
            <div v-else-if="type === 1">
                {{ item.first_name }} {{ item.last_name }} (Extern)
                <div class="text-xs w-full">0 | 0</div>
            </div>
            <div v-else>
                {{ item.provider_name }} (Dienstleister)
                <div class="text-xs w-full">0 | 0</div>
            </div>
        </div>
    </div>
</template>
<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: "DragElement",
    props: ['item', 'type','plannedHours'],
    methods: {
        onDragStart(event) {
            event.dataTransfer.setData('application/json', JSON.stringify([{id: this.item.id, master: this.item.can_master, type: this.type }])); // only pass the id
        }
    }
})
</script>
<style scoped>

</style>
