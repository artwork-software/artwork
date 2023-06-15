<template>
    <div class="mx-5 mt-6 p-5  bg-lightBackgroundGray">
        <div class="grid grid-cols-3 ml-10 mt-6 p-5 max-w-screen-2xl bg-lightBackgroundGray ">
            <ChecklistComponent
                :project="project"
                :opened_checklists="opened_checklists"
                :project-can-write-ids="projectCanWriteIds"
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
    components: {ChecklistComponent, PencilAltIcon, XCircleIcon, DocumentTextIcon, SvgCollection, XIcon, JetInputError},
    props: [
        'project',
        'opened_checklists',
        'projectCanWriteIds',
        'projectManagerIds',
        'checklist_templates'
    ],
    data() {

    },
    methods:{

    }
}
</script>

<style scoped>

</style>
