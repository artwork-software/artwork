<template>
    <InputContainer>
        <InputLabel :for="this.id" :label="this.label"/>
        <input :id="this.id"
               :value="this.modelValue"
               @input="this.$emit('update:modelValue', $event.target.value)"
               type="date"
               class="px-1.5 py-2 peer h-full w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b border-gray-300 text-primary"/>
    </InputContainer>
</template>

<script>
import {defineComponent} from "vue";
import InputContainer from "@/Components/Inputs/InputContainer.vue";
import InputLabel from "@/Components/Inputs/Labels/InputLabel.vue";

export default defineComponent({
    components: {InputLabel, InputContainer},
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
