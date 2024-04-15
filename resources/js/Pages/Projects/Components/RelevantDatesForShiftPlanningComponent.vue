<template>
    <div>
        <div class="flex items-center gap-x-5">
            <div class="sLight">
                {{ $t('Relevant dates for shift planning') }}
            </div>
            <div v-if="this.canEditComponent">
                <PencilAltIcon class=" w-5 h-5 rounded-full" :class="inSidebar ? 'text-white' : 'text-artwork-buttons-context'"
                               @click="openShiftRelevantEventTypeModal"/>
            </div>
        </div>
        <div class="flex py-2 w-72 flex-wrap">
            <div class="flex" v-for="eventType in this.project.shift_relevant_event_types">
                <TagComponent type="gray" :displayed-text="eventType.name" hideX="true"></TagComponent>
            </div>
        </div>
        <ShiftRelevantEventTypeModal
            :show="showShiftRelevantEventTypeModal"
            @close-modal="closeShiftRelevantEventTypeModal"
            :project="project"
            :event-types="eventTypes"
        />
    </div>
</template>

<script>
import {defineComponent} from "vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import {PencilAltIcon} from "@heroicons/vue/outline";
import ShiftRelevantEventTypeModal from "@/Layouts/Components/ShiftRelevantEventTypeModal.vue";

export default defineComponent({
    components: {
        ShiftRelevantEventTypeModal,
        PencilAltIcon,
        TagComponent
    },
    props: [
        'project',
        'eventTypes',
        'inSidebar',
        'canEditComponent'
    ],
    data() {
        return {
            showShiftRelevantEventTypeModal: false
        };
    },
    methods: {
        openShiftRelevantEventTypeModal(){
            this.showShiftRelevantEventTypeModal = true;
        },
        closeShiftRelevantEventTypeModal(){
            this.showShiftRelevantEventTypeModal = false;
        }
    }
});
</script>
