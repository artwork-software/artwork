<template>
    <div class="w-full mt-24">
        <div class="flex justify-between">
            <div class="sLight">
                Für Schichtplanung relevante Termine
            </div>
            <div>
                <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                               @click="openCopyrightModal"/>
            </div>
        </div>
        <div>
            hier tags
        </div>
        <hr class="my-10 border-darkGray">
        <div class="flex justify-between">
            <div class="sLight">
                Ansprechpartner*innen
            </div>
            <div>
                <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                               @click="openCopyrightModal"/>
            </div>
        </div>
        <div>
            Hier die Ansprechpartner anzeigen
        </div>
        <hr class="my-10 border-darkGray">
        <div class="flex justify-between">
            <div class="sLight">
                Generelle Schichtinformationen
            </div>
            <div>
                <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                               @click="openCopyrightModal"/>
            </div>
        </div>
        <div>
            Hier Schichtinformationen
        </div>
    </div>

</template>

<script>
import {
    PencilAltIcon,
} from '@heroicons/vue/outline';
import Permissions from "@/mixins/Permissions.vue";
import TagComponent from "@/Layouts/Components/NotificationEventInfoRow.vue";

export default {
    mixins: [Permissions],
    name: "ProjectShiftSidenav",
    components: {
        TagComponent,
        PencilAltIcon,
    },
    props: {
        project: Object,
    },
    data() {
        return {
            showFileDeleteModal: false,
            showFileUploadModal: false,
            showContractUploadModal: false,
            showContractDeleteModal: false,
            showProjectFiles: false,
            showContracts: false,
            showMoneySources: false,
            showFileEditModal: false,
            showContractEditModal: false,
            showCopyrightModal: false,
            projectFileToEdit: null,
            projectFileToDelete: null

        }
    },
    methods: {}
}
</script>

<style scoped>

</style>
