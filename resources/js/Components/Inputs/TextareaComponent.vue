<template>
    <InputContainer>
        <InputLabel :for="this.id" :label="this.label"/>
        <textarea :id="this.id"
                  class="p-2 peer h-full w-full focus:border-primary focus:ring-0 border-b border-gray-300 text-primary"
                  @input="this.$emit('update:modelValue', $event.target.value)"
                  :rows="this.rows"
                  :cols="this.cols"
                  :value="this.modelValue"/>
    </InputContainer>
</template>

<script>
import {defineComponent} from "vue";
import PlaceholderInputLabel from "@/Components/Inputs/Labels/PlaceholderInputLabel.vue";
import PlaceholderInputContainer from "@/Components/Inputs/Container/PlaceholderInputContainer.vue";
import InputContainer from "@/Components/Inputs/Container/InputContainer.vue";
import InputLabel from "@/Components/Inputs/Labels/InputLabel.vue";

export default defineComponent({
    components: {InputLabel, InputContainer, PlaceholderInputContainer, PlaceholderInputLabel},
    props: [
        'id',
        'label',
        'modelValue',
        'rows',
        'cols'
    ],
    emits: [
        'update:modelValue'
    ]
});
</script>
