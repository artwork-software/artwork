
<template>
    <div class="drag-item p-2 my-2 bg-gray-50/10 text-white text-xs rounded-lg flex items-center gap-2" draggable="true" @dragstart="onDragStart">
        <div>
            <img :src="item.profile_photo_url" alt="" class="h-6 w-6 rounded-full object-cover">
        </div>
        <div>
            {{ item.first_name }} {{ item.last_name }}
        </div>
    </div>
</template>
<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: "DragElement",
    props: ['item'],
    methods: {
        onDragStart(event) {
            event.dataTransfer.setData('application/json', JSON.stringify([{id: this.item.id, master: this.item.can_master }])); // only pass the id
        }
    }
})
</script>
<style scoped>

</style>
