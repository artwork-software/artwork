<script>
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import {componentIcon} from "@/Pages/Projects/Tab/projectTabComponentRules.js";

export default {
    name: "ComponentIcons",
    components: {PropertyIcon},
    props: ['type'],
    computed: {
        iconName() {
            return componentIcon(this.type);
        },
    },
}
</script>

<template>
    <PropertyIcon v-if="iconName" :name="iconName" class="w-6 h-6"/>
</template>
