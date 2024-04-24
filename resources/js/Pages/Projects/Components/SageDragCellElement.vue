<script>
import IconLib from "@/mixins/IconLib.vue";

export default {
    name: "SageDragCellElement",
    props: ['cell'],
    mixins: [IconLib],
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
