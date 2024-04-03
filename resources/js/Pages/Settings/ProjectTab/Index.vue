<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import ProjectTabs from "@/Pages/Settings/Components/ProjectTabs.vue";
import {IconDragDrop} from "@tabler/icons-vue";
import {Link} from "@inertiajs/inertia-vue3";
import draggable from "vuedraggable";
import SingleTabComponent from "@/Pages/Settings/Components/SingleTabComponent.vue";
import DragComponentElement from "@/Pages/Settings/Components/DragComponentElement.vue";
import IconLib from "@/mixins/IconLib.vue";
import AddEditTabModal from "@/Pages/Settings/Components/AddEditTabModal.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import TabDropElement from "@/Pages/Settings/Components/TabDropElement.vue";

export default {
    name: "Index",
    components: {
        TabDropElement,
        PlusButton,
        AddEditTabModal,
        DragComponentElement, SingleTabComponent, draggable, Link, IconDragDrop, ProjectTabs, AppLayout},
    props: ['tabs', 'components'],
    mixins: [IconLib],
    data() {
        return {
            searchComponent: '',
            showAddEditModal: false,
        }
    },
    computed: {
        filteredComponents() {
            return Object.keys(this.components).reduce((acc, key) => {
                const filtered = this.components[key].filter(component => {
                    return component.name.toLowerCase().includes(this.searchComponent.toLowerCase());
                });

                if (filtered.length > 0) {
                    acc[key] = filtered;
                }

                return acc;
            }, {

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

                   <TabDropElement :order="1" />
                   <div v-for="tab in tabs" class="w-full">
                       <SingleTabComponent :tab="tab" />
                       <TabDropElement :order="tab.order + 1" />
                   </div>
               </div>

                <!-- Components List -->

                <div>
                    <div class="flex items-center justify-end w-full mb-3">
                        <div class="w-44 md:w-56 lg:w-72">
                            <div>
                                <div class="relative rounded-md shadow-sm">
                                    <input type="text" name="search" v-model="searchComponent" placeholder="Suche" id="account-number" class="block w-full rounded-md border-0 py-1.5 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                    <div class=" absolute inset-y-0 right-0 flex items-center pr-3">
                                        <IconSearch class="h-5 w-5 text-gray-400 pointer-events-none" aria-hidden="true" v-if="searchComponent.length === 0" />
                                        <IconCircleX class="h-5 w-5 text-gray-400 cursor-pointer hover:text-red-400" aria-hidden="true" v-else @click="searchComponent = ''" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-for="(componentsArray, index) in filteredComponents">
                        <h2 class="text-md font-bold mb-3">{{ $t(index) }}</h2>
                         <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-7 gap-3">
                             <DragComponentElement v-for="component in componentsArray" :component="component" />
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
