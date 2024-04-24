<template>
    <div class="w-full mt-24">
        <RelevantDatesForShiftPlanningComponent :project="this.project"
                                                :event-types="this.eventTypes"
        />
        <hr class="my-10 border-darkGray">
        <ShiftContactPersonsComponent :project="this.project"/>
        <hr class="my-10 border-darkGray">
        <GeneralShiftInformationComponent :project="this.project"/>
    </div>
</template>

<script>
import {PencilAltIcon} from '@heroicons/vue/outline';
import Permissions from "@/Mixins/Permissions.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import ShiftInformationModal from "@/Layouts/Components/ShiftInformationModal.vue";
import ProjectCopyrightModal from "@/Layouts/Components/ProjectCopyrightModal.vue";
import ShiftContactModal from "@/Layouts/Components/ShiftContactModal.vue";
import ShiftRelevantEventTypeModal from "@/Layouts/Components/ShiftRelevantEventTypeModal.vue";
import RelevantDatesForShiftPlanningComponent
    from "@/Pages/Projects/Components/RelevantDatesForShiftPlanningComponent.vue";
import ShiftContactPersonsComponent from "@/Pages/Projects/Components/ShiftContactPersonsComponent.vue";
import GeneralShiftInformationComponent from "@/Pages/Projects/Components/GeneralShiftInformationComponent.vue";

export default {
    mixins: [Permissions],
    name: "ProjectShiftSidenav",
    components: {
        ShiftRelevantEventTypeModal,
        ShiftContactModal,
        ProjectCopyrightModal,
        ShiftInformationModal,
        TagComponent,
        PencilAltIcon,
        RelevantDatesForShiftPlanningComponent,
        ShiftContactPersonsComponent,
        GeneralShiftInformationComponent
    },
    props: {
        project: Object,
        eventTypes:Array,
    }
}
</script>
