<template>
    <ColorPicker
        format="hex"
        shape="circle"
        picker-type="chrome"
        use-type="pure"
        round-history
        lang="En"
        theme="white"
        v-on:update:pureColor="updateColor"
        v-on:pureColorChange="updateColor"
        v-on:update:gradientColor="updateColor"
        v-on:gradientColorChange="updateColor"
        v-on:update:activeKey="updateColor"
        v-on:activeKeyChange="updateColor"
        :pure-color="selectedColor"
        disable-alpha
        class="w-full"
    />
</template>

<script>
import { defineAsyncComponent } from "vue";

export default {
    name: "ColorPickerComponent",
    components: {
        ColorPicker: defineAsyncComponent(() => import("vue3-colorpicker"))
    },
    emits: ['updateColor'],
    props: {
        color: String
    },
    methods: {
        updateColor(color) {
            this.$emit("updateColor", color);
        },
    },
    computed: {
        selectedColor() {
            return this.color ?? "#ffffff";
        },
    }
}
</script>

<style>
.vc-color-wrap {
    height: 3rem !important;
    width: 3rem !important;
    min-width: 3rem !important;
    max-width: 3rem !important;
    box-shadow: none !important;

}
</style>
