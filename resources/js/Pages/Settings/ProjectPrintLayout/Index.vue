<template>
    <AppLayout >
        <div class="artwork-container">
            <div class="">
                <h2 class="headline1 my-6">{{ $t('Print Layout Settings') }}</h2>
                <div class="xsLight">
                    {{ $t('Here you can manage the print layouts for your projects. You can add, edit and delete print layouts. You can also set the default print layout for your projects.') }}
                </div>
            </div>

            <ProjectTabs />



            <div class="grid grid-cols-1 xl:grid-cols-2 gap-10">
                <!-- Tab components -->
                <div class="w-full col-span-1">
                    <div class="flex justify-end mb-5">
                        <PlusButton @click="showCreateOrUpdateModal = true" :button-text="$t('Create tab')" />
                    </div>


                    <div>
                        <div v-for="layout in layouts" class="mb-4">
                            <SingleProjectPrintLayout :layout="layout" :components="allComponents" />
                        </div>
                    </div>
                </div>

                <!-- Components List -->

                <div class="col-span-1">
                    <div class="flex items-center justify-end w-full mb-3">
                        <div class="w-44 md:w-56 lg:w-72">
                            <div>
                                <div class="relative rounded-md shadow-sm">
                                    <input type="text" name="search" v-model="searchComponent" :placeholder="$t('Search')" id="account-number" class="block w-full rounded-md border-0 py-1.5 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                    <div class=" absolute inset-y-0 right-0 flex items-center pr-3">
                                        <IconSearch class="h-5 w-5 text-gray-400 pointer-events-none" aria-hidden="true" v-if="searchComponent.length === 0" />
                                        <IconCircleX class="h-5 w-5 text-gray-400 cursor-pointer hover:text-red-400" aria-hidden="true" v-else @click="searchComponent = ''" />
                                    </div>
                                </div>
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

    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import ProjectTabs from "@/Pages/Settings/Components/ProjectTabs.vue";
import DragComponentElement from "@/Pages/Settings/Components/DragComponentElement.vue";
import {IconCircleX, IconSearch} from "@tabler/icons-vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import {computed, ref} from "vue";
import CreateOrUpdateProjectPrintLayoutModal from "@/Pages/Settings/ProjectPrintLayout/Components/CreateOrUpdateProjectPrintLayoutModal.vue";
import SingleProjectPrintLayout from "@/Pages/Settings/ProjectPrintLayout/Components/SingleProjectPrintLayout.vue";

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