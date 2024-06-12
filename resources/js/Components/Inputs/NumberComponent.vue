<template>
    <PlaceholderInputContainer>
        <input :id="this.id"
               :value="this.modelValue"
               @input="this.$emit('update:modelValue', $event.target.value)"
               type="number"
               class="px-1.5 py-2 peer h-full w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b border-gray-300 text-primary placeholder-secondary placeholder-transparent"
               placeholder="placeholder"/>
        <PlaceholderInputLabel :for="this.id" :label="this.label"/>
    </PlaceholderInputContainer>
</template>

<script>
import {defineComponent} from "vue";
import PlaceholderInputContainer from "@/Components/Inputs/PlaceholderInputContainer.vue";
import PlaceholderInputLabel from "@/Components/Inputs/Labels/PlaceholderInputLabel.vue";

export default defineComponent({
    components: {PlaceholderInputLabel, PlaceholderInputContainer},
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
