<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import ProjectTabs from "@/Pages/Settings/Components/ProjectTabs.vue";
import {IconDragDrop} from "@tabler/icons-vue";
import {Link} from "@inertiajs/inertia-vue3";
import draggable from "vuedraggable";
import SingleTabComponent from "@/Pages/Settings/Components/SingleTabComponent.vue";
import DragComponentElement from "@/Pages/Settings/Components/DragComponentElement.vue";
import IconLib from "@/mixins/IconLib.vue";

export default {
    name: "Index",
    components: {DragComponentElement, SingleTabComponent, draggable, Link, IconDragDrop, ProjectTabs, AppLayout},
    props: ['tabs', 'components'],
    mixins: [IconLib],
    data() {
        return {
            searchComponent: '',
        }
    },
    computed: {
        filteredComponents() {
            return this.components.filter(component => {
                return component.name.toLowerCase().includes(this.searchComponent.toLowerCase())
            })
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
                   <div v-for="tab in tabs" class="mb-3 w-full">
                       <SingleTabComponent :tab="tab" />
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
                   <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                       <div v-for="component in filteredComponents">
                           <DragComponentElement :component="component" />
                       </div>
                   </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
