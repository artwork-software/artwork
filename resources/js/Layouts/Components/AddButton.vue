<template>
    <button v-if="text !== 'Zu Teams zuweisen' && text !== 'Areal hinzufügen' && text !== 'Raum hinzufügen' && mode !== 'modal'" type="button"
            class="flex py-2 px-4 mt-1 ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-buttonBlue hover:shadow-blueButton hover:bg-buttonHover focus:outline-none">
        <PlusCircleIcon v-if="mode !== 'modal' && text !== 'Speichern'" class="h-4 w-4 mr-2" aria-hidden="true"/>
        <p class="text-sm">{{ text }}</p>
    </button>
    <button v-else type="button"
            class="flex p-2 mt-1 items-center border border-transparent rounded-full shadow-sm text-white bg-buttonBlue hover:shadow-blueButton hover:bg-buttonHover focus:outline-none">
        <PlusCircleIcon v-if="mode !== 'modal'" class="h-4 w-4 mr-2" aria-hidden="true"/>
        <p class="text-sm">{{ text }}</p>
    </button>
</template>

<script>
import Button from "@/Jetstream/Button";
import { PlusCircleIcon } from '@heroicons/vue/outline'


export default {
    name: "AddButton",
    components: {Button, PlusCircleIcon},
    props: {
        text: String,
        mode: String
    }
}
</script>

<style scoped>

</style>
