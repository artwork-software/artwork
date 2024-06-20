<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import ProjectTabs from "@/Pages/Settings/Components/ProjectTabs.vue";
import {IconDragDrop} from "@tabler/icons-vue";
import {Link} from "@inertiajs/vue3";
import draggable from "vuedraggable";
import SingleTabComponent from "@/Pages/Settings/Components/SingleTabComponent.vue";
import DragComponentElement from "@/Pages/Settings/Components/DragComponentElement.vue";
import IconLib from "@/Mixins/IconLib.vue";
import AddEditTabModal from "@/Pages/Settings/Components/AddEditTabModal.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";

export default {
    name: "Index",
    components: {
        PlusButton,
        AddEditTabModal,
        DragComponentElement, SingleTabComponent, draggable, Link, IconDragDrop, ProjectTabs, AppLayout
    },
    props: ['tabs', 'components', 'componentsSpecial'],
    mixins: [IconLib],
    data() {
        return {
            searchComponent: '',
            showAddEditModal: false,
            dragging: false,
        }
    },
    computed: {
        filteredComponents() {
            return Object.keys(this.components).reduce((acc, key) => {
                const filtered = this.components[key].filter(component => {
                    return component.name.toLowerCase().includes(this.searchComponent.toLowerCase());
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

        },
        filteredSpecialComponents() {
            // filter special components with translation
            return this.componentsSpecial.filter(component => {
                return this.$t(component.name).toLowerCase().includes(this.searchComponent.toLowerCase());
            });
        },
    },
    methods:{
        updateComponentOrder(components) {
            components.map((component, index) => {
                component.order = index + 1
            })

            this.$inertia.post(route('tab.reorder'), {
                components: components,
            }, {
                preserveScroll: true
            });
        }
    }

}
</script>

<template>
    <AppLayout>
        <div class="my-8 ml-14 mr-40">
            <div class="">
                <h2 class="headline1 my-6">{{$t('Tab Settings')}}</h2>
                <div class="xsLight">
                    {{$t('Define global settings for projects.')}}
                </div>
            </div>

            <ProjectTabs />


            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Tab components -->
               <div class="w-full">
                   <div class="flex justify-end mb-5">
                       <PlusButton @click="showAddEditModal = true" />
                   </div>


                   <draggable ghost-class="opacity-50" key="draggableKey" item-key="id" :list="tabs" @start="dragging=true" @end="dragging=false" @change="updateComponentOrder(tabs)">
                       <template #item="{element}" :key="element.id">
                           <div class="mb-2">
                               <div class="" :key="element.id" :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                   <SingleTabComponent :all-tabs="tabs" :tab="element" />
                               </div>
                           </div>
                        </template>
                    </draggable>
               </div>

                <!-- Components List -->

                <div>
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
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-7 gap-2">
                                <DragComponentElement v-for="component in componentsArray.components" :component="component" />
                            </div>
                        </div>
                   </div>
                   <div>
                       <h2 class="text-md font-bold mb-2">{{ $t('Special components') }}</h2>
                       <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-7 gap-2">
                           <DragComponentElement v-for="component in filteredSpecialComponents" :component="component" />
                       </div>
                   </div>
                </div>
            </div>
        </div>

        <AddEditTabModal v-if="showAddEditModal" @close="showAddEditModal = false" />
    </AppLayout>
</template>

<style scoped>

</style>
