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
                    drop_type: 'component',
                    sidebar_enabled: this.component.sidebar_enabled
                })
            );
        }
    }
}
</script>

<template>
    <div class="p-3 rounded-lg border mb-3 hover:cursor-grab flex flex-col h-28 justify-center items-center" draggable="true" @dragstart="onDragStart">
        <div class="flex items-center justify-center mb-2">
            <ComponentIcons :type="component.type" />
        </div>
        <div class="text-center text-sm font-bold">
            <span v-if="component.special">
                {{ $t(component.name) }}
            </span>
            <div v-else>
                {{ component.name }}
                <div class="text-[10px] text-gray-500 font-light" v-if="component.data.height">
                    {{ component.data.height }} Pixel <span v-if="component.data.showLine === true">| {{ $t('Show a separator line')}}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
