<template>
    <div>
        <input type="text"
               :placeholder="placeholder"
               @input="updateContent"
               v-model="model"
               class="h-12 border border-border placeholder:text-sm/5 font-bold text-text-subtle placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-text-subtle focus:border-1 w-full border-border"/>
    </div>
</template>

<script>
import Permissions from "@/Mixins/Permissions.vue";

export default {
    name: "Input",
    mixins: [Permissions],
    components: {},
    props: ['modelValue', 'placeholder'],
    emits: ['update:modelValue'],
    data (){
        return {
           model: this.modelValue
        }
    },
    methods: {
        updateContent(){
            this.$emit('update:modelValue', this.model)
        }
    },
}
</script>

<style scoped>



</style>
