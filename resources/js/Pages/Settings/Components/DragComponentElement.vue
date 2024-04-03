<script>
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";

export default {
    name: "DragComponentElement",
    components: {ComponentIcons},
    props: ['component'],
    methods: {
        onDragStart(event) {
            event.dataTransfer.setData(
                'application/json',
                JSON.stringify( {
                    id: this.component.id,
                    type: this.component.type,
                    name: this.component.name,
                    drop_type: 'component'
                })
            );
        }
    }
}
</script>

<template>
    <div class="p-3 rounded-lg border mb-3 hover:cursor-grab" draggable="true" @dragstart="onDragStart">
        <div class="flex items-center justify-center mb-2">
            <ComponentIcons :type="component.type" />
        </div>
        <div class="text-center text-sm font-bold">
            {{ component.name }}
        </div>
    </div>
</template>

<style scoped>

</style>
