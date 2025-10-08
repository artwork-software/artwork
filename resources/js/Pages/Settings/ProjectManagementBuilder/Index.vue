<template>
    <ProjectSettingsHeader :title="$t('Project overview builder')" :description="$t('Define global settings for projects.')">

            <div>
                <BasePageTitle
                    :title="$t('Project overview builder')"
                    :description="$t('Set up the project overview for your artwork. To do this, drag and drop the components from the lower area into the project overview. You can also adjust the order of the components using drag & drop.')"
                />
            </div>

            <div class="card white p-5 mt-5">
                <div class="overflow-x-auto">
                    <draggable
                        ghost-class="opacity-50"
                        key="draggableKey"
                        class="flex  min-w-max"
                        item-key="id"
                        :list="componentsInGrid"
                        @start="dragging = true"
                        @end="dragging = false"
                        @change="updateProjectManagementOrder(componentsInGrid)"
                    >
                        <template #item="{ element }" :key="element.id">
                            <div class="flex items-center w-fit">
                                <SingleComponentInGrid :element="element" />
                                <DropComponentInGrid :order="element.order"/>
                            </div>
                        </template>
                    </draggable>
                </div>
            </div>

            <div class="card white p-5 mt-5">
                <div class="flex items-center justify-end">
                    <div class="w-44 md:w-56 lg:w-72">
                        <div>
                            <div class="relative rounded-md shadow-sm">
                                <input type="text" name="search" v-model="searchComponent" :placeholder="$t('Search')" class="block w-full rounded-md border-0 py-1.5 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <IconSearch class="h-5 w-5 text-gray-400 pointer-events-none" aria-hidden="true" v-if="searchComponent.length === 0" />
                                    <IconCircleX class="h-5 w-5 text-gray-400 cursor-pointer hover:text-red-400" aria-hidden="true" v-else @click="searchComponent = ''" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <BasePageTitle :title="$t('Components')" :description="$t('Available components')" />

                    <div v-if="availableComponents.length" class="flex flex-wrap gap-4 mt-4">
                        <div v-for="availableComponent in computedAvailableComponents" :key="availableComponent.id">
                            <DragComponent :component="availableComponent" />
                        </div>
                    </div>
                </div>
            </div>
    </ProjectSettingsHeader>
</template>

<script setup>

import ProjectSettingsHeader from "@/Pages/Settings/Components/ProjectSettingsHeader.vue";
import SearchInput from "@/Components/Form/SearchInput.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import {computed, ref} from "vue";
import DragComponent from "@/Pages/Settings/ProjectManagementBuilder/Components/DragComponent.vue";
import draggable from "vuedraggable";
import {router} from "@inertiajs/vue3";
import DropComponentInGrid from "@/Pages/Settings/ProjectManagementBuilder/Components/DropComponentInGrid.vue";
import {IconCircleX, IconSearch} from "@tabler/icons-vue";
import { useI18n } from 'vue-i18n';
import SingleComponentInGrid from "@/Pages/Settings/ProjectManagementBuilder/Components/SingleComponentInGrid.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
const props = defineProps({
    componentsInGrid: {
        type: Object,
        required: true,
    },
    availableComponents: {
        type: Object,
        required: true,
    },
})

const dragging = ref(false);
const searchComponent = ref('');

const { t } = useI18n(); // Zugriff auf die Lokalisierungsfunktion

const computedAvailableComponents = computed(() => {
    return props.availableComponents.filter((component) => {
        const translatedName = t(component.name); // Übersetzter Name
        const searchText = searchComponent.value.toLowerCase();
        return (
            component.sidebar_enabled &&
            component.type !== 'SeparatorComponent' &&
            component.type !== 'Title' &&
            props.componentsInGrid.every((componentInGrid) => componentInGrid.component_id !== component.id) &&
            translatedName.toLowerCase().includes(searchText)
        );
    });
});

const updateProjectManagementOrder = (componentsInGrid) => {
    componentsInGrid.map((component, index) => {
        component.order = index + 1
    })

    router.patch(route('project-management-builder.update.order'), {
        components: componentsInGrid
    })
}

</script>

<style scoped>

</style>
