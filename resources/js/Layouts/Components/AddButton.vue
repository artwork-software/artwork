<template>
    <!-- TODO: Translation -->
    <button v-if="text !== 'Zu Teams zuweisen' && text !== 'Areal hinzufügen' && text !== 'Raum hinzufügen' && mode !== 'modal' && type !== 'secondary'" type="button" :disabled="disabled" :class="disabled ? 'bg-secondary' : 'bg-artwork-buttons-create hover:bg-artwork-buttons-create hover:shadow-artwork-buttons-create'"
            class="flex py-2 px-12 mt-1 ml-6 items-center border border-transparent rounded-full shadow-sm text-white  focus:outline-none">
        <IconCirclePlus stroke-width="1.5" v-if="mode !== 'modal' && text !== 'Speichern'" class="h-4 w-4 mr-2" aria-hidden="true"/>
        <p class="text-sm capitalize">{{ text }}</p>
    </button>
    <button v-else-if="type === 'secondary'" type="button" :disabled="disabled"
            class="flex py-2 px-8 mt-1 ml-6 items-center border-2 border-artwork-buttons-create rounded-full shadow-sm text-artwork-buttons-create bg-secondaryHover hover:bg-artwork-buttons-hover hover:text-white focus:outline-none">
        <p class="text-sm capitalize">{{ text }}</p>
    </button>
    <button v-else-if="type === 'delete'" type="button" :disabled="disabled"
            class="flex py-2 px-8 mt-1 ml-6 items-center border-2 border-red-600 rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700 hover:text-white focus:outline-none">
        <p class="text-sm capitalize">{{ text }}</p>
    </button>
    <button v-else-if="type === 'save'" type="button" :disabled="disabled"
            class="flex py-2 px-8 mt-1 ml-6 items-center border-2 border-artwork-buttons-create rounded-full shadow-sm text-white bg-artwork-buttons-create hover:bg-artwork-buttons-hover hover:text-white focus:outline-none">
        <p class="text-sm capitalize">{{ text }}</p>
    </button>
    <button v-else type="button" :disabled="disabled" :class="disabled ? 'bg-secondary' : 'bg-artwork-buttons-create hover:bg-artwork-buttons-hover'"
            class="flex px-16 py-4 mt-1 items-center border border-transparent rounded-full shadow-sm text-white hover:shadow-artwork-buttons-create  focus:outline-none">
        <IconCirclePlus stroke-width="1.5" v-if="mode !== 'modal'" class="h-4 w-4 mr-2" aria-hidden="true"/>
        <p class="text-sm capitalize">{{ text }}</p>
    </button>
</template>

<script>
import Button from "@/Jetstream/Button.vue";
import { PlusCircleIcon } from '@heroicons/vue/outline'
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";


export default {
    name: "AddButton",
    mixins: [Permissions, IconLib],
    components: {Button, PlusCircleIcon},
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
