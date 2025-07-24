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
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default {
    name: "Index",
    components: {
        BaseInput,
        GlassyIconButton,
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
            // Update local order
            components.map((component, index) => {
                component.order = index + 1
            })

            // Create a minimal payload with only necessary data (id and order)
            const minimalComponents = components.map(component => ({
                id: component.id,
                order: component.order
            }));

            this.$inertia.post(route('tab.reorder'), {
                components: minimalComponents,
            }, {
                preserveScroll: true
            });
        }
    }

}
</script>

<template>
    <AppLayout>
        <div class="artwork-container">
            <div class="">
                <h2 class="headline1 my-6">{{$t('Tab Settings')}}</h2>
                <div class="xsLight">
                    {{$t('Define global settings for projects.')}}
                </div>
            </div>

            <ProjectTabs />


            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <!-- Tab components -->
               <div class="w-full col-span-1">
                   <div class="flex justify-end mb-5">
                       <GlassyIconButton icon="IconPlus" @click="showAddEditModal = true" :text="$t('Create tab')" />
                   </div>


                   <div class="card white p-5">
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
        </div>

        <AddEditTabModal v-if="showAddEditModal" @close="showAddEditModal = false" />
    </AppLayout>
</template>

<style scoped>

</style>
