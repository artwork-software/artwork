<template>
    <button v-if="text !== 'Zu Teams zuweisen' && text !== 'Areal hinzufügen' && text !== 'Raum hinzufügen'" type="button"
            class="flex p-2 mt-1 ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-buttonBlue hover:shadow-blueButton hover:bg-buttonHover focus:outline-none">
        <PlusCircleIcon class="h-4 w-4" aria-hidden="true"/>
        <p class="ml-2 text-sm">{{ text }}</p>
    </button>
    <button v-else type="button"
            class="flex p-2 mt-1 items-center border border-transparent rounded-full shadow-sm text-white bg-buttonBlue hover:shadow-blueButton hover:bg-buttonHover focus:outline-none">
        <PlusCircleIcon class="h-4 w-4" aria-hidden="true"/>
        <p class="ml-2 text-sm">{{ text }}</p>
    </button>
</template>

<script>
import Button from "@/Jetstream/Button";
import { PlusCircleIcon } from '@heroicons/vue/outline'


export default {
    name: "AddButton",
    components: {Button, PlusCircleIcon},
    props: {
        text: String
    }
}
</script>

<style scoped>

</style>
