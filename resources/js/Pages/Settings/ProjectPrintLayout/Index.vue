<template>
    <AppLayout >
        <div class="my-8 ml-14">
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

                            <div class="border border-gray-300 rounded-lg">
                                <div class="bg-gray-200 p-2 flex items-center justify-between" :class="layout.open ? 'rounded-t-lg' : 'rounded-lg'" >
                                    <div  @click="layout.open = !layout.open" class="cursor-pointer">
                                        <h3 class="headline3">{{ layout.name }}</h3>
                                        <p class="xsDark mt-1">{{ layout.description }}</p>
                                    </div>
                                    <div>
                                        <BaseMenu has-no-offset>
                                            <BaseMenuItem title="Edit"/>
                                        </BaseMenu>
                                    </div>
                                </div>
                                <div v-if="layout.open" class="rounded-b-lg border-gray-100">
                                    <div class="p-6">
                                        <h4 class="xsDark mb-2">{{ $t('Header') }}</h4>
                                        <div class="grid gap-4" :class="'grid-cols-' + layout['columns_header']">
                                            <template v-for="col in layout['columns_header']" :key="col">
                                                <div v-if="getComponent(layout, 'header', 1, col)" :key="getComponent(layout, 'header', 1, col).id">
                                                    <div class="relative p-5 rounded-lg border-2 h-full cursor-pointer flex items-center justify-center border-gray-300 hover:border-artwork-buttons-create duration-200 ease-in-out group">
                                                        <div class="absolute bg-artwork-buttons-create/40 inset-0 rounded-md hidden group-hover:block">
                                                            <div class="flex items-center justify-center gap-x-4 h-full">
                                                                <div class="rounded-full p-1 bg-red-500 shadow-md">
                                                                    <component is="IconX" class="size-5 text-white" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-col items-center justify-between">
                                                            <div>{{ $t(getComponent(layout, 'header', 1, col).component.name) }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else>
                                                    <div class="p-5 rounded-lg border-2 cursor-pointer border-dashed flex items-center justify-center hover:border-artwork-buttons-create duration-200 ease-in-out group" :class="[isDragging ? 'border-artwork-buttons-create' : 'border-gray-300']">
                                                        <div class="flex flex-col items-center justify-between">
                                                            <component is="IconCircleDashedPlus" class="h-8 w-8 group-hover:text-artwork-buttons-create duration-200 ease-in-out" :class="[isDragging ? 'text-artwork-buttons-create' : 'text-gray-300']" stroke-width="1.5" />
                                                            <p v-if="isDragging" class="xsDark mt-1">{{ $t('Drop here') }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <hr class="my-5">

                                        <h4 class="xsDark mb-2">{{ $t('Body') }}</h4>
                                        <div class="grid gap-4">
                                            <template v-for="row in getRowCount(layout, 'body')" :key="row">
                                                <div class="grid grid-cols-1 gap-4" :class="'grid-cols-' + layout['columns_body']">
                                                    <template v-for="col in layout['columns_body']" :key="col">
                                                        <div v-if="getComponent(layout, 'body', row, col)" :key="getComponent(layout, 'body', row, col).id">
                                                            <div class="relative p-5 rounded-lg border-2 h-full cursor-pointer flex items-center justify-center border-gray-300 hover:border-artwork-buttons-create duration-200 ease-in-out group">
                                                                <div class="absolute bg-artwork-buttons-create/40 inset-0 rounded-md hidden group-hover:block">
                                                                    <div class="flex items-center justify-center gap-x-4 h-full">
                                                                        <div class="rounded-full p-1 bg-red-500 shadow-md">
                                                                            <component is="IconX" class="size-5 text-white" />
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="flex flex-col items-center justify-between">
                                                                    <div>{{ $t(getComponent(layout, 'body', row, col).component.name) }} (Row: {{ row }}, Col: {{ col }})</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div v-else-if="row === getRowCount(layout, 'body')">
                                                            <div class="p-5 rounded-lg border-2 cursor-pointer border-dashed flex items-center justify-center hover:border-artwork-buttons-create duration-200 ease-in-out group" :class="[isDragging ? 'border-artwork-buttons-create' : 'border-gray-300']">
                                                                <div class="flex flex-col items-center justify-between">
                                                                    <component is="IconCircleDashedPlus" class="h-8 w-8 group-hover:text-artwork-buttons-create duration-200 ease-in-out" :class="[isDragging ? 'text-artwork-buttons-create' : 'text-gray-300']" stroke-width="1.5" />
                                                                    <p v-if="isDragging" class="xsDark mt-1">{{ $t('Drop here') }} (Row: {{ row }}, Col: {{ col }})</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div v-else>
                                                            <div class="p-5 rounded-lg border-2 cursor-pointer border-dashed flex items-center justify-center hover:border-artwork-buttons-create duration-200 ease-in-out group" :class="[isDragging ? 'border-artwork-buttons-create' : 'border-gray-300']">
                                                                <div class="flex flex-col items-center justify-between">
                                                                    <component is="IconCircleDashedPlus" class="h-8 w-8 group-hover:text-artwork-buttons-create duration-200 ease-in-out" :class="[isDragging ? 'text-artwork-buttons-create' : 'text-gray-300']" stroke-width="1.5" />
                                                                    <p v-if="isDragging" class="xsDark mt-1">{{ $t('Drop here') }} (Row: {{ row }}, Col: {{ col }})</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>

                                        <hr class="my-5">

                                        <h4 class="xsDark mb-2">{{ $t('Footer') }}</h4>
                                        <div class="grid gap-4" :class="'grid-cols-' + layout['columns_footer']">
                                            <template v-for="col in layout['columns_footer']" :key="col">
                                                <div v-if="getComponent(layout, 'footer', 1, col)" :key="getComponent(layout, 'footer', 1, col).id">
                                                    <div class="relative p-5 rounded-lg border-2 h-full cursor-pointer flex items-center justify-center border-gray-300 hover:border-artwork-buttons-create duration-200 ease-in-out group">
                                                        <div class="absolute bg-artwork-buttons-create/40 inset-0 rounded-md hidden group-hover:block">
                                                            <div class="flex items-center justify-center gap-x-4 h-full">
                                                                <div class="rounded-full p-1 bg-red-500 shadow-md">
                                                                    <component is="IconX" class="size-5 text-white" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-col items-center justify-between">
                                                            <div>{{ $t(getComponent(layout, 'footer', 1, col).component.name) }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else>
                                                    <div class="p-5 rounded-lg border-2 cursor-pointer border-dashed flex items-center justify-center hover:border-artwork-buttons-create duration-200 ease-in-out group" :class="[isDragging ? 'border-artwork-buttons-create' : 'border-gray-300']">
                                                        <div class="flex flex-col items-center justify-between">
                                                            <component is="IconCircleDashedPlus" class="h-8 w-8 group-hover:text-artwork-buttons-create duration-200 ease-in-out" :class="[isDragging ? 'text-artwork-buttons-create' : 'text-gray-300']" stroke-width="1.5" />
                                                            <p v-if="isDragging" class="xsDark mt-1">{{ $t('Drop here') }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
import {EventListenerForDragging} from "@/Composeables/EventListenerForDragging.js";

const { isDragging, addEventListenerForDraggingStart, removeEventListenerForDraggingStart } = EventListenerForDragging();
import AppLayout from "@/Layouts/AppLayout.vue";
import ProjectTabs from "@/Pages/Settings/Components/ProjectTabs.vue";
import DragComponentElement from "@/Pages/Settings/Components/DragComponentElement.vue";
import SingleTabComponent from "@/Pages/Settings/Components/SingleTabComponent.vue";
import {IconCircleX, IconSearch} from "@tabler/icons-vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import draggable from "vuedraggable";
import {computed, onMounted, onUnmounted, ref} from "vue";
import CreateOrUpdateProjectPrintLayoutModal
    from "@/Pages/Settings/ProjectPrintLayout/Components/CreateOrUpdateProjectPrintLayoutModal.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

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
})

const searchComponent = ref('');
const showCreateOrUpdateModal = ref(false);

const getComponent = (layout, section, row, col) => {
    return layout[section + '_components'].find(comp => comp.row === row && comp.position === col) || null;
};

const getRowCount = (layout, section) => {
    if (section !== 'body') return 1; // Header und Footer haben feste Zeilen

    const maxOccupiedRow = getMaxOccupiedRow(layout, section);
    const hasComponentInRow = layout[section + '_components'].some(comp => comp.row === maxOccupiedRow);

    return hasComponentInRow ? maxOccupiedRow + 1 : maxOccupiedRow;
};

const getMaxOccupiedRow = (layout, section) => {
    const components = layout[section + '_components'];
    return components.length ? Math.max(...components.map(c => c.row)) : 1;
};

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

const allComponents = computed(() => {
    return Object.values(filteredComponents.value).reduce((acc, value) => {
        return acc.concat(value.components);
    }, []);
});

const filteredSpecialComponents = computed(() => {
    // filter special components with translation
    return props.componentsSpecial.filter(component => {
        return component.name.toLowerCase().includes(searchComponent.value.toLowerCase());
    });
})

onMounted(() => {
    console.log('onMounted');
    const listeners = addEventListenerForDraggingStart();

    onUnmounted(() => {
        console.log('onUnmounted');
        removeEventListenerForDraggingStart(listeners);
    });
});
</script>

<style scoped>

</style>