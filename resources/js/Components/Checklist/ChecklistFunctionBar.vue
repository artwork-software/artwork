<template>
    <div class="flex w-full items-center justify-between gap-x-4 mb-4">
        <h2 class=" leading-6 font-bold font-lexend text-text" :class="project ? 'font-lexend font-semibold text-[clamp(16px,2vw,18px)]/[21px] text-text' : 'font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text'">
            {{ $t(title) }}
        </h2>
        <div class="bg-border-subtle text-sm text-text-subtle leading-none border-2 border-border-subtle rounded-full inline-flex print:hidden">
            <button @click="updateChecklistStyle('kanban')" class="inline-flex items-center focus:outline-none focus:ring-0 hover:text-accent-500 focus:text-accent-500 rounded-l-full px-4 py-2" :class="$page.props.auth.user.checklist_style === 'kanban' ? 'bg-surface text-accent-500 rounded-full' : ''" id="grid">
                <IconLayoutKanban class="w-4 h-4 mr-2" />
                <span>{{ $t('Grid') }}</span>
            </button>
            <button @click="updateChecklistStyle('list')" class="inline-flex items-center focus:outline-none focus:ring-0 hover:text-accent-500 focus:text-accent-500 rounded-r-full px-4 py-2" :class="$page.props.auth.user.checklist_style === 'list' ? 'bg-surface text-accent-500 rounded-full' : ''" id="list">
                <IconLayoutList class="w-4 h-4 mr-2" />
                <span>{{ $t('List') }}</span>
            </button>
        </div>
        <slot name="buttons" />
        <div class="flex items-center justify-center gap-x-4 print:hidden">
            <slot name="search">

            </slot>
            <slot name="filter">

            </slot>
            <slot name="sort">

            </slot>
            <div class="ml-5" v-if="canEditComponent && (isAdmin || projectCanWriteIds?.includes($page.props.auth.user.id) || projectManagerIds.includes($page.props.auth.user.id)) || !project">
                <BaseUIButton label="New checklist" use-translation is-add-button @click="openAddChecklistModal = true" />
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

import {IconLayoutKanban, IconLayoutList} from "@tabler/icons-vue";
import {router, usePage} from "@inertiajs/vue3";
import {ref} from "vue";
import AddEditChecklistModal from "@/Components/Checklist/Modals/AddEditChecklistModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

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
        default: () => []
    },
    projectManagerIds: {
        type: Array,
        required: false,
        default: () => []
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
        default: () => []
    },
})

const openAddChecklistModal = ref(false);

const emit = defineEmits(['update:checklistStyle']);

const updateChecklistStyle = (type) => {
    // Update local page props immediately for reactive UI
    usePage().props.auth.user.checklist_style = type;
    emit('update:checklistStyle', type);

    // Persist to backend without reloading
    router.patch(route('user.checklist.style', {user: usePage().props.auth.user.id}), {
        checklist_style: type,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>
