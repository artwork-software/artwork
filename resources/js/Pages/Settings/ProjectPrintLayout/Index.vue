<template>
    <ProjectSettingsHeader :title="$t('Print Layout Settings')" :description="$t('Here you can manage the print layouts for your projects. You can add, edit and delete print layouts.')">
        <template #actions>
            <BaseUIButton variant="primary" hide-icon @click="showCreateOrUpdateModal = true">
                <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                {{ $t('Create print layout') }}
            </BaseUIButton>
        </template>
        <SettingsGuideBanner
            class="mb-6"
            storage-key="settings-guide.project.print-layout"
            title="How does this area work?"
            :paragraphs="[
                'Print layouts define how a project is put together as a PDF. All active layouts are offered for selection on the project detail page when generating a PDF.',
                'The builder chain: create building blocks in the component settings, build the project detail page in the tab settings, arrange the blocks for the PDF in the print layout, and turn them into columns of the project list in the overview builder. All four use the same component pool with different filters — that is why you do not see every component everywhere.',
            ]"
        />
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-10">
                <!-- Tab components -->
                <div class="w-full col-span-1">


                    <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
                        <SettingsGuideBanner
                            class="mb-4"
                            variant="inline"
                            storage-key="settings-guide.project.print-layout.editor"
                            title="Good to know when building layouts"
                            :paragraphs="[
                                'The number of columns in header, body and footer is fixed once the layout has been created.',
                                'The palette deliberately shows only printable components — that is why some building blocks from the tab settings are missing here.',
                            ]"
                        />
                        <div v-if="layouts.length > 0">
                            <div v-for="layout in layouts" class="mb-4">
                                <SingleProjectPrintLayout :layout="layout" :components="allComponents" />
                            </div>
                        </div>
                        <div v-else>
                            <BaseAlertComponent message="No print layouts found. Create a new print layout to get started." type="info" use-translation />
                        </div>
                    </div>
                </div>

                <!-- Components List -->

                <div class="col-span-1 rounded-lg bg-surface border border-border-subtle shadow-raised p-5">
                    <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
                        <div class="flex items-center justify-end w-full mb-3">
                            <div class="w-44 md:w-56 lg:w-72">
                                <div>
                                    <BaseInput id="search" type="text" name="search" v-model="searchComponent" :label="$t('Search')" />
                                </div>
                            </div>
                        </div>
                        <div v-for="componentsArray in filteredComponents">
                            <div>
                                <div class="flex items-center gap-x-4 cursor-pointer">
                                    <h2 class="text-md font-bold mb-2">{{ $t(componentsArray.name) }}</h2>
                                </div>
                                <div class="grid grid-cols-1 2xl:grid-cols-3 gap-2">
                                    <DragComponentElement v-for="component in componentsArray.components" :component="component" />
                                </div>
                            </div>
                        </div>
                        <div>
                            <h2 class="text-md font-bold mb-2">{{ $t('Special components') }}</h2>
                            <div class="grid grid-cols-1 2xl:grid-cols-3 gap-2">
                                <DragComponentElement v-for="component in filteredSpecialComponents" :component="component" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <CreateOrUpdateProjectPrintLayoutModal
            v-if="showCreateOrUpdateModal"
            @close="showCreateOrUpdateModal = false"
        />
    </ProjectSettingsHeader>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ProjectSettingsHeader from "@/Pages/Settings/Components/ProjectSettingsHeader.vue";
import DragComponentElement from "@/Pages/Settings/Components/DragComponentElement.vue";
import {IconCirclePlus} from "@tabler/icons-vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import {computed, ref} from "vue";
import CreateOrUpdateProjectPrintLayoutModal from "@/Pages/Settings/ProjectPrintLayout/Components/CreateOrUpdateProjectPrintLayoutModal.vue";
import SingleProjectPrintLayout from "@/Pages/Settings/ProjectPrintLayout/Components/SingleProjectPrintLayout.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";

const props = defineProps({
    layouts: {
        type: Object,
        required: true,
        default: [],
    },
    components: {
        type: Object,
        required: true,
        default: [],
    },
    componentsSpecial: {
        type: Object,
        required: true,
        default: [],
    },
    allComponents: {
        type: Object,
        required: true,
        default: [],
    }
})

const searchComponent = ref('');
const showCreateOrUpdateModal = ref(false);


const filteredComponents = computed(() => {
    return Object.keys(props.components).reduce((acc, key) => {
        const filtered = props.components[key].filter(component => {
            return component.name.toLowerCase().includes(searchComponent.value.toLowerCase());
        });

        if (filtered.length > 0) {
            acc[key] = {
                name: key,
                components: filtered,
                closed: false
            };
        } else {
            acc[key] = {
                name: key,
                components: filtered,
                closed: true
            };
        }

        return acc;
    }, {});
});

const filteredSpecialComponents = computed(() => {
    // filter special components with translation
    return props.componentsSpecial.filter(component => {
        return component.name.toLowerCase().includes(searchComponent.value.toLowerCase());
    });
})


</script>

<style scoped>

</style>
