<template>
    <PlaceholderInputContainer>
        <input :id="this.id"
               :value="this.modelValue"
               @input="this.$emit('update:modelValue', $event.target.value)"
               type="text"
               class="px-1.5 py-2 peer h-full w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b border-gray-300 text-primary placeholder-secondary placeholder-transparent"
               placeholder="placeholder"/>
        <PlaceholderInputLabel :for="this.id" :label="this.label"/>
    </PlaceholderInputContainer>
</template>

<script>
import {defineComponent} from "vue";
import InputLabel from "@/Components/Inputs/Labels/InputLabel.vue";
import PlaceholderInputLabel from "@/Components/Inputs/Labels/PlaceholderInputLabel.vue";
import PlaceholderInputContainer from "@/Components/Inputs/Container/PlaceholderInputContainer.vue";

export default defineComponent({
    components: {
        PlaceholderInputContainer,
        PlaceholderInputLabel,
        InputLabel
    },
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
