<script>
import { ColorPicker } from "vue3-colorpicker";
import "vue3-colorpicker/style.css";
export default {
    name: "ColorPickerComponent",
    components: {
        ColorPicker
    },
    emits: ['updateColor'],
    props: {
        color: String
    },
    methods: {
        updateColor(color) {
            // return the color value to the parent component
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

<style>
.vc-color-wrap {
    height: 3rem !important;
    width: 3rem !important;
    min-width: 3rem !important;
    max-width: 3rem !important;
    box-shadow: none !important;

}
</style>
