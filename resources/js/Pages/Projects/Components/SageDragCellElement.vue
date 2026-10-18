<script>
import {IconDragDrop} from "@tabler/icons-vue";

export default {
    name: "SageDragCellElement",
    components: {IconDragDrop},
    props: ['cell'],
    emits: ['isDragged'],
    methods: {
        onDragStart(event) {
            this.cell.type = 'rowMove';
            event.dataTransfer.setData('text/plain', JSON.stringify(this.cell));
        },
    }
}
</script>

<template>
    <div class="w-5 h-5 cursor-grab ml-2" draggable="true" @dragstart="onDragStart">
        <IconDragDrop class="w-5 h-5 cursor-grab " />
    </div>
</template>

<style scoped>

</style>
