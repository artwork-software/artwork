<template>
    <div>
        <div class="flex items-center gap-x-5">
            <div class="sLight">
                {{ $t('General shift information') }}
            </div>
            <div v-if="this.canEditComponent">
                <PencilAltIcon class=" w-5 h-5 rounded-full " :class="inSidebar ? 'text-white' : 'text-artwork-buttons-context'"
                               @click="openShiftInformationModal"/>
            </div>
        </div>
        <div class="mt-2">
            <div class="max-h-48 overflow-y-auto" v-if="project.shiftDescription">
                {{ project.shiftDescription }}
            </div>
            <div v-else>
                {{ $t('No shift information has been entered yet') }}
            </div>
        </div>
        <ShiftInformationModal
            :show="showShiftInformationModal"
            @close-modal="closeShiftInformationModal"
            :project="project"
        />
    </div>
</template>

<script>
import {defineComponent} from "vue";
import {PencilAltIcon} from "@heroicons/vue/outline";
import ShiftInformationModal from "@/Layouts/Components/ShiftInformationModal.vue";

export default defineComponent({
    components: {
        ShiftInformationModal,
        PencilAltIcon
    },
    props: [
        'project',
        'inSidebar',
        'canEditComponent'
    ],
    data() {
        return {
            showShiftInformationModal: false
        };
    },
    methods: {
        openShiftInformationModal() {
            this.showShiftInformationModal = true
        },
        closeShiftInformationModal() {
            this.showShiftInformationModal = false
        },
    }
});
</script>
