<template>
    <div class="flex w-full items-center justify-between gap-x-4 mb-4">
        <h2 class=" leading-6 font-bold font-lexend text-primary" :class="project ? 'headline3' : 'headline1'">
            {{ $t(title) }}
        </h2>
        <div class="bg-gray-200 text-sm text-gray-500 leading-none border-2 border-gray-200 rounded-full inline-flex print:hidden">
            <button @click="updateChecklistStyle('kanban')" class="inline-flex items-center focus:outline-none focus:ring-0 hover:text-blue-400 focus:text-blue-400 rounded-l-full px-4 py-2" :class="$page.props.auth.user.checklist_style === 'kanban' ? 'bg-white text-blue-400 rounded-full' : ''" id="grid">
                <IconLayoutKanban class="w-4 h-4 mr-2" />
                <span>{{ $t('Grid') }}</span>
            </button>
            <button @click="updateChecklistStyle('list')" class="inline-flex items-center focus:outline-none focus:ring-0 hover:text-blue-400 focus:text-blue-400 rounded-r-full px-4 py-2" :class="$page.props.auth.user.checklist_style === 'list' ? 'bg-white text-blue-400 rounded-full' : ''" id="list">
                <IconLayoutList class="w-4 h-4 mr-2" />
                <span>{{ $t('List') }}</span>
            </button>
        </div>
        <slot name="buttons" class="print:hidden">

        </slot>
        <div class="flex items-center justify-center gap-x-3 print:hidden">
            <slot name="search">

            </slot>
            <slot name="filter">

            </slot>
            <slot name="sort">

            </slot>
            <div class="flex items-center w-max" v-if="canEditComponent && (isAdmin || projectCanWriteIds?.includes($page.props.auth.user.id) || projectManagerIds.includes($page.props.auth.user.id)) || can('can use checklists') && isInOwnTaskManagement">
                <GlassyIconButton text="New checklist" :icon="IconPlus" @click="openAddChecklistModal = true" />
            </div>
        </div>
    </div>


    <AddEditChecklistModal
        :project="project"
        v-if="openAddChecklistModal"
        @closed="openAddChecklistModal = false"
        :tab_id="tab_id"
        :checklist_templates="checklist_templates"
        :create-own-checklist="isInOwnTaskManagement"
    />

</template>

<script setup>

import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import {IconLayoutKanban, IconLayoutList, IconPlus} from "@tabler/icons-vue";
import {router, usePage} from "@inertiajs/vue3";
import {ref} from "vue";
import AddEditChecklistModal from "@/Components/Checklist/Modals/AddEditChecklistModal.vue";

import {usePermission} from "@/Composeables/Permission.js";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
const { can, canAny } = usePermission(usePage().props)

const props = defineProps({
    title: {
        type: String,
        required: false,
        default: 'Checklists'
    },
    isAdmin: {
        type: Boolean,
        required: false,
        default: false
    },
    canEditComponent: {
        type: Boolean,
        required: false,
        default: false
    },
    projectCanWriteIds: {
        type: Array,
        required: false,
        default: []
    },
    projectManagerIds: {
        type: Array,
        required: false,
        default: []
    },
    project: {
        type: Object,
        required: false,
        default: null
    },
    tab_id: {
        type: Number,
        required: false,
        default: null
    },
    checklist_templates: {
        type: Object,
        required: false,
        default: null
    },
    isInOwnTaskManagement: {
        type: Boolean,
        required: false,
        default: false
    },
    filters: {
        type: Array,
        required: false,
        default: []
    },
})

const openAddChecklistModal = ref(false);

const selectedFilter = ref(props.filters.find(filter => filter.type ===  Number(usePage().props.urlParameters.filter)) || props.filters[0]);
const updateChecklistStyle = (type) => {
    router.patch(route('user.checklist.style', {user: usePage().props.auth.user.id}), {
        checklist_style: type,
    }, {
        preserveScroll: true,
    });
}

const updateTasksFilter = (type) => {
    router.reload({
        data: {
            filter: type
        },
        only: ['checklists', 'money_source_task', 'private_checklists']
    });
}
</script>

<style scoped>

</style>
