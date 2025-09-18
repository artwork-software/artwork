<script>
import IconLib from "@/Mixins/IconLib.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {IconCirclePlus} from "@tabler/icons-vue";

export default {
    name: "PlusButton",
    methods: {IconCirclePlus},
    components: {ToolTipComponent},
    mixins: [IconLib],
    props: {
        buttonText: {
            type: String,
            default: 'Neuer Termin'
        }
    }
}
</script>

<template>
    <button type="button" class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover rounded-full p-1 flex items-center justify-center transition-all duration-150 ease-in-out">
        <ToolTipComponent
            direction="left"
            :tooltip-text="buttonText"
            :icon="IconCirclePlus"
            icon-size="h-8 w-8 text-white"
            stroke="2.5"
        />
    </button>
</template>

<style scoped>

</style>
