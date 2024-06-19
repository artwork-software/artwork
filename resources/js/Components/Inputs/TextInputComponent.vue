<template>
    <PlaceholderInputLabelContainer>
        <input :id="this.id"
               :value="this.modelValue"
               @input="this.$emit('update:modelValue', $event.target.value)"
               type="text"
               class="p-1.5 w-full h-12 text-sm peer border-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent focus:border-primary focus:ring-0"
               placeholder="placeholder"/>
        <PlaceholderLabel :for="this.id" :label="this.label"/>
    </PlaceholderInputLabelContainer>
</template>

<script>
import {defineComponent} from "vue";
import PlaceholderInputLabelContainer from "@/Components/Inputs/Container/PlaceholderInputLabelContainer.vue";
import PlaceholderLabel from "@/Components/Inputs/Labels/PlaceholderLabel.vue";

export default defineComponent({
    components: {PlaceholderLabel, PlaceholderInputLabelContainer},
    props: [
        'id',
        'label',
        'modelValue'
    ],
    emits: [
        'update:modelValue'
    ]
});
</script>
