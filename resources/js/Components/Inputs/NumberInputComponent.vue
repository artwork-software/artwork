<template>
    <PlaceholderInputLabelContainer>
        <input :id="this.id"
               :value="this.modelValue"
               @input="this.$emit('update:modelValue', $event.target.value)"
               type="number"
               class="p-1.5 w-full text-sm peer border-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent focus:border-primary focus:ring-0"
               :class="isSmall ? '' : 'h-12'"
               placeholder="placeholder"/>
        <PlaceholderLabel :for="this.id" :label="this.label" :is-small="isSmall"/>
    </PlaceholderInputLabelContainer>
</template>

<script>
import {defineComponent} from "vue";
import PlaceholderInputLabelContainer from "@/Components/Inputs/Container/PlaceholderInputLabelContainer.vue";
import PlaceholderLabel from "@/Components/Inputs/Labels/PlaceholderLabel.vue";

export default defineComponent({
    components: {PlaceholderLabel, PlaceholderInputLabelContainer},
    props: {
        id: {
            type: String,
            required: true
        },
        label: {
            type: String,
            required: true
        },
        modelValue: {
            type: String,
            required: true
        },
        isSmall: {
            type: Boolean,
            default: false
        }
    },
    emits: [
        'update:modelValue'
    ]
});
</script>
