<template>
    <Disclosure as="div" class="my-2" v-slot="{ open }" >
        <DisclosureButton class="py-2 px-4 bg-border-subtle text-text flex justify-between items-center w-full" :class="{ 'rounded-t-lg': open, 'rounded-lg': !open }">
            <div class="flex items-center h-full gap-x-2 text-sm/5 font-semibold text-text font-bold">
                {{ component.component.data.label }}
                <InfoButtonComponent :component="component" />
            </div>
            <div class="flex items-center gap-2">
                <span
                    v-if="nonDefaultValueCount > 0"
                    class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-white/70 px-1 text-[10px] font-semibold tabular-nums text-accent-700 ring-1 ring-inset ring-text-inverse/30"
                    :aria-label="$t('Changed fields: {0}', [nonDefaultValueCount])"
                    :title="$t('Changed fields: {0}', [nonDefaultValueCount])"
                >
                    {{ nonDefaultValueCount }}
                </span>
                <component :is="IconChevronDown" class="size-3" :class="{ 'transform rotate-180': open }" />
            </div>
        </DisclosureButton>
        <DisclosurePanel class="px-4 py-2 bg-surface-sunken rounded-b-lg">
            <div v-for="(disclosureComponent, index) in component.disclosure_components" :key="disclosureComponent.id" class="">
                <Component
                    v-if="disclosureComponent?.id && canSeeComponent(disclosureComponent.component) && componentMapping[disclosureComponent.component?.type]"
                    :is="componentMapping[disclosureComponent.component?.type]"
                    :can-edit-component="canEditComponent(disclosureComponent.component)"
                    :project="headerObject.project"
                    :in-sidebar="false"
                    :loadedProjectInformation="loadedProjectInformation"
                    :header-object="headerObject"
                    :data="disclosureComponent.component"
                    :project-id="headerObject.project.id"
                    :projectCategories="headerObject.projectCategories"
                    :projectGenres="headerObject.projectGenres"
                    :projectSectors="headerObject.projectSectors"
                    :categories="headerObject.categories"
                    :sectors="headerObject.sectors"
                    :genres="headerObject.genres"
                    :projectCategoryIds="headerObject.projectCategoryIds"
                    :projectGenreIds="headerObject.projectGenreIds"
                    :projectSectorIds="headerObject.projectSectorIds"
                    :event-types="headerObject.eventTypes"
                    :opened_checklists="headerObject.project?.opened_checklists"
                    :checklist_templates="headerObject.project?.checklist_templates"
                    :projectManagerIds="headerObject.projectManagerIds"
                    :projectWriteIds="headerObject.projectWriteIds"
                    :tab_id="currentTab.id"
                    :first_project_tab_id="first_project_tab_id"
                    :first_project_calendar_tab_id="first_project_calendar_tab_id"
                    :first_project_budget_tab_id="first_project_budget_tab_id"
                    :rooms="headerObject.rooms"
                    :eventsInProject="headerObject.project.events"
                    :eventStatuses="headerObject.eventStatuses"
                    :event_properties="headerObject.event_properties"
                    :component="disclosureComponent"
                />
            </div>
        </DisclosurePanel>
    </Disclosure>
</template>

<script setup>

import {Disclosure, DisclosureButton, DisclosurePanel} from "@headlessui/vue";
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";
import {usePage} from "@inertiajs/vue3";
import {computed, inject, provide} from "vue";
import {usePermission} from "@/Composeables/Permission.js";
import { folderComponentMapping } from "@/Pages/Projects/Tab/projectTabComponents.js";
import {countFolderNonDefaultValues} from "@/Helper/ProjectComponentValueState.js";
import {IconChevronDown} from "@tabler/icons-vue";

const props = defineProps({
    data: {
        type: Object,
        required: true
    },
    projectId: {
        type: Number,
        required: true
    },
    inSidebar: {
        type: Boolean,
        required: false
    },
    canEditComponent: {
        type: Boolean,
        required: true
    },
    component: {
        type: Object,
        required: true
    }
})


const pageProps = usePage().props;
provide('pageProps', pageProps);

const headerObject = inject("headerObject");
const currentTab = inject("currentTab");
const first_project_tab_id = inject("first_project_tab_id");
const first_project_calendar_tab_id = inject("first_project_calendar_tab_id");
const first_project_budget_tab_id = inject("first_project_budget_tab_id");
const loadedProjectInformation = inject("loadedProjectInformation");

const { canSeeComponent, canEditComponent } = usePermission(usePage().props);

const componentMapping = folderComponentMapping();

const nonDefaultValueCount = computed(() =>
    countFolderNonDefaultValues(
        props.component.disclosure_components?.filter(
            (disclosureComponent) =>
                canSeeComponent(disclosureComponent.component)
                && Boolean(componentMapping[disclosureComponent.component?.type])
        )
    )
);


</script>

<style scoped>

</style>
