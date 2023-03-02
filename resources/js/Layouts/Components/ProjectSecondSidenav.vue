<template>
    <div class="w-full mt-24">
        <div class="w-full flex items-center mb-4">
            <div class="text-secondary text-md">Eintritt & Anmeldung</div>
            <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                            @click="openEntranceModal"/>
        </div>
        <div>
            <div class="text-secondary text-sm mb-1">Gäste: {{ project.num_of_guests ? project.num_of_guests : 'Nicht definiert' }}</div>
            <div class="text-secondary text-sm mb-1">Eintritt: {{ project.entry_fee ? project.entry_fee : 'Nicht definiert' }}</div>
            <div class="text-secondary text-sm mb-1">Anmeldung erforderlich: {{ project.registration_required ? 'Ja' : 'Nein' }}</div>
            <div v-if="project.registration_required">
                <div class="text-secondary text-sm mb-1">Anmeldung über: {{ project.register_by ? project.register_by : 'Nicht definiert' }}</div>
                <div class="text-secondary text-sm mb-1">Anmeldefrist: {{ project.registration_deadline ? project.registration_deadline : 'Keine Frist' }}</div>
            </div>
            <div class="text-secondary text-sm">Geschlossene Gesellschaft: {{ project.closed_society ? 'Ja' : 'Nein' }}</div>
        </div>
    </div>
    <ProjectEntranceModal :show="show" :close-modal="closeEntranceModal" :project="props.project" />
</template>

<script setup>
import ProjectEntranceModal from "@/Layouts/Components/ProjectEntranceModal.vue";
import {PencilAltIcon} from "@heroicons/vue/outline";
import {ref} from "vue";

const props = defineProps({
    project: Object
})

const show = ref(false)

const openEntranceModal = () => {
    show.value = true
}

const closeEntranceModal = () => {
    show.value = false
}

</script>

<style scoped>

</style>
