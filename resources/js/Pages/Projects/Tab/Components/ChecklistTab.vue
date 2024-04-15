<template>
    <div class="mt-6 pb-6 bg-lightBackgroundGray">
        <div class="">
            <ChecklistComponent
                :project="project"
                :opened_checklists="opened_checklists"
                :project-can-write-ids="projectWriteIds"
                :project-manager-ids="projectManagerIds"
                :checklist_templates="checklist_templates"
            />
        </div>
    </div>
</template>

<script>
import JetInputError from "@/Jetstream/InputError.vue";
import {DocumentTextIcon, PencilAltIcon, XIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import ChecklistComponent from "@/Pages/Projects/Components/ChecklistComponent.vue";

export default{
    components: {
        ChecklistComponent,
        PencilAltIcon,
        XCircleIcon,
        DocumentTextIcon,
        SvgCollection,
        XIcon,
        JetInputError
    },
    props: [
        'project',
        'opened_checklists',
        'projectWriteIds',
        'projectManagerIds',
        'checklist_templates'
    ]
}
</script>
