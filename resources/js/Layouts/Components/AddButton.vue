<template>
    <!-- TODO: Translation -->
    <button v-if="text !== 'Zu Teams zuweisen' && text !== 'Areal hinzufügen' && text !== 'Raum hinzufügen' && mode !== 'modal' && type !== 'secondary'" type="button" :disabled="disabled" :class="disabled ? 'bg-text-subtle' : 'bg-accent-600 hover:bg-accent-600 hover:shadow-artwork-buttons-create'"
            class="flex py-2 px-12 mt-1 ml-6 items-center border border-transparent rounded-full shadow-sm text-white  focus:outline-none">
        <PropertyIcon name="IconCirclePlus" stroke-width="1.5" v-if="mode !== 'modal' && text !== 'Speichern'" class="h-4 w-4 mr-2" aria-hidden="true"/>
        <p class="text-sm capitalize">{{ text }}</p>
    </button>
    <button v-else-if="type === 'secondary'" type="button" :disabled="disabled"
            class="flex py-2 px-8 mt-1 ml-6 items-center border-2 border-accent-600 rounded-full shadow-sm text-accent-600 bg-secondaryHover hover:bg-accent-700 hover:text-white focus:outline-none">
        <p class="text-sm capitalize">{{ text }}</p>
    </button>
    <button v-else-if="type === 'delete'" type="button" :disabled="disabled"
            class="flex py-2 px-8 mt-1 ml-6 items-center border-2 border-danger rounded-full shadow-sm text-white bg-danger hover:bg-danger hover:text-white focus:outline-none">
        <p class="text-sm capitalize">{{ text }}</p>
    </button>
    <button v-else-if="type === 'save'" type="button" :disabled="disabled"
            class="flex py-2 px-8 mt-1 ml-6 items-center border-2 border-accent-600 rounded-full shadow-sm text-white bg-accent-600 hover:bg-accent-700 hover:text-white focus:outline-none">
        <p class="text-sm capitalize">{{ text }}</p>
    </button>
    <button v-else type="button" :disabled="disabled" :class="disabled ? 'bg-text-subtle' : 'bg-accent-600 hover:bg-accent-700'"
            class="flex px-16 py-4 mt-1 items-center border border-transparent rounded-full shadow-sm text-white hover:shadow-artwork-buttons-create  focus:outline-none">
        <PropertyIcon name="IconCirclePlus" stroke-width="1.5" v-if="mode !== 'modal'" class="h-4 w-4 mr-2" aria-hidden="true"/>
        <p class="text-sm capitalize">{{ text }}</p>
    </button>
</template>

<script>
import {IconCirclePlus} from "@tabler/icons-vue";
import Button from "@/Jetstream/Button.vue";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";


export default {
    name: "AddButton",
    mixins: [Permissions, IconLib],
    components: {PropertyIcon, Button, IconCirclePlus},
    props: {
        text: String,
        mode: String,
        type: String,
        disabled: Boolean
    }
}
</script>

<style scoped>

</style>
