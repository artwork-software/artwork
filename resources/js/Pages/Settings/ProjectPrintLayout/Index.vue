<template>
    <ProjectSettingsHeader :title="$t('Print Layout Settings')" :description="$t('Here you can manage the print layouts for your projects. You can add, edit and delete print layouts. You can also set the default print layout for your projects.')">
        <template #actions>
            <button class="ui-button-add" @click="showCreateOrUpdateModal = true">
                <component :is="IconPlus" stroke-width="1" class="size-5" />
                {{ $t('Create print layout') }}
            </button>
        </template>
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-10">
                <!-- Tab components -->
                <div class="w-full col-span-1">


                    <div class="card white p-5">
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

                <div class="col-span-1 card glassy p-5">
                    <div class="card white p-5">
                        <div class="flex items-center justify-end w-full mb-3">
                            <div class="w-44 md:w-56 lg:w-72">
                                <div>
                                    <BaseInput id="search" type="text" name="search" v-model="searchComponent" label="Search" />
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
import ProjectSettingsHeader from "@/Pages/Settings/Components/ProjectSettingsHeader.vue";
import DragComponentElement from "@/Pages/Settings/Components/DragComponentElement.vue";
import {IconPlus} from "@tabler/icons-vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import {computed, ref} from "vue";
import CreateOrUpdateProjectPrintLayoutModal from "@/Pages/Settings/ProjectPrintLayout/Components/CreateOrUpdateProjectPrintLayoutModal.vue";
import SingleProjectPrintLayout from "@/Pages/Settings/ProjectPrintLayout/Components/SingleProjectPrintLayout.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

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
