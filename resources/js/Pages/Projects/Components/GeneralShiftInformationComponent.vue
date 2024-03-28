<template>
    <div>
        <div class="flex justify-between">
            <div class="sLight">
                {{ $t('General shift information') }}
            </div>
            <div>
                <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
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
        'project'
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
