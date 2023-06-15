
<template>
    <div class="drag-item w-full p-2 my-2 bg-gray-50/10 text-white text-xs rounded-lg flex items-center gap-2" draggable="true" @dragstart="onDragStart">
        <div>
            <img :src="item.profile_photo_url" v-if="type === 0" alt="" class="h-6 w-6 rounded-full object-cover">
            <img :src="item.profile_image" v-else alt="" class="h-6 w-6 rounded-full object-cover">
        </div>
        <div v-if="type === 0">
            {{ item.first_name }} {{ item.last_name }} (Intern)
        </div>
        <div v-else-if="type === 1">
            {{ item.first_name }} {{ item.last_name }} (Extern)
        </div>
        <div v-else>
            {{ item.provider_name }} (Dienstleister)
        </div>
    </div>
</template>
<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: "DragElement",
    props: ['item', 'type'],
    methods: {
        onDragStart(event) {
            event.dataTransfer.setData('application/json', JSON.stringify([{id: this.item.id, master: this.item.can_master, type: this.type }])); // only pass the id
        }
    }
})
</script>
<style scoped>

</style>
