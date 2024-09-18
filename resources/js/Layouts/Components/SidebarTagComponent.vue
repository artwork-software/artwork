<template>
    <span ref="sidebarTagComponent"
          class="rounded-full items-center font-medium border px-3 text-sm mr-1 mb-1 h-8 inline-flex"
          :style="{ backgroundColor: this.backgroundColorWithOpacity(this.item?.color), color: this.getTextColorBasedOnParent(), borderColor: this.backgroundColorWithOpacity(this.item?.color, 30) }">
        {{ item?.name }}
        <button v-if="!hideX" type="button" @click="this.method(property)">
            <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
        </button>
    </span>
</template>

<script>
import Button from "@/Jetstream/Button.vue";
import {XIcon} from "@heroicons/vue/outline";
import Permissions from "@/Mixins/Permissions.vue";
import ColorHelper from "@/Mixins/ColorHelper.vue";

export default {
    name: "SidebarTagComponent",
    components: { Button, XIcon },
    mixins: [Permissions, ColorHelper],
    props: ['item', 'icon', 'hideX', 'method', 'property'],
    data() {
        return {
            parentBackgroundColor: null,
        };
    },
    mounted() {
        this.detectParentBackgroundColor();
    },
    methods: {
        detectParentBackgroundColor(element = this.$refs.sidebarTagComponent) {
            if (!element || !element.parentElement) {
                this.parentBackgroundColor = 'rgba(255, 255, 255, 1)';
                return;
            }

            const parentElement = element.parentElement;
            const computedStyle = window.getComputedStyle(parentElement);
            const bgColor = computedStyle.backgroundColor;

            if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
                this.parentBackgroundColor = bgColor;
            } else {
                this.detectParentBackgroundColor(parentElement);
            }
        },
        checkExplicitBackgroundColor(color) {
            const rgbToHex = (r, g, b) => {
                return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1).toUpperCase();
            };

            const rgbMatch = color.match(/\d+/g);
            if (rgbMatch) {
                const [r, g, b] = rgbMatch.map(Number);
                const hexColor = rgbToHex(r, g, b);

                if (hexColor === "#000000") return "#FFFFFF";
                if (hexColor === "#FFFFFF") return "#000000";
            }
            return null;
        },
        getTextColorBasedOnParent() {
            const explicitTextColor = this.checkExplicitBackgroundColor(this.backgroundColorWithOpacity(this.item?.color));
            if (explicitTextColor) {
                return explicitTextColor;
            }
            if (this.parentBackgroundColor) {
                const isDark = this.isDarkColor(this.parentBackgroundColor);
                return isDark ? '#FFFFFF' : '#000000';
            }
            return '#000000';
        }
    }
}
</script>
