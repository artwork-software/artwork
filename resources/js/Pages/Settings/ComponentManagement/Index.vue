<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import ProjectTabs from "@/Pages/Settings/Components/ProjectTabs.vue";
import {IconCircleX, IconSearch} from "@tabler/icons-vue";
import DragComponentElement from "@/Pages/Settings/Components/DragComponentElement.vue";
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import IconLib from "@/mixins/IconLib.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import AddNewComponentModal from "@/Pages/Settings/ComponentManagement/Components/AddNewComponentModal.vue";
import SingleComponent from "@/Pages/Settings/ComponentManagement/Components/SingleComponent.vue";

export default {
    name: "Index",
    components: {
        SingleComponent,
        AddNewComponentModal,
        PlusButton, ComponentIcons, IconCircleX, DragComponentElement, IconSearch, ProjectTabs, AppLayout},
    props: ['components', 'componentsSpecial', 'tabComponentTypes'],
    mixins: [IconLib],
    data() {
        return {
            searchComponent: '',
            showAddNewComponentModal: false
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

        },
        filteredSpecialComponents() {
            // filter special components with translation
            return this.componentsSpecial?.filter(component => {
                return this.$t(component.name).toLowerCase().includes(this.searchComponent.toLowerCase());
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


            <div>
                <div class="flex items-center justify-end w-full mb-3">
                    <div class="flex items-center gap-x-5">
                        <div>
                            <PlusButton @click="showAddNewComponentModal = true" />
                        </div>
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
                </div>
                <div v-for="(componentsArray, index) in filteredComponents">
                    <h2 class="text-md font-bold mb-3">{{ $t(index) }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-12 gap-3">
                        <div class="p-3 rounded-lg border mb-3 flex flex-col h-28 justify-center items-center group relative" v-for="component in componentsArray">
                            <SingleComponent :component="component" />
                        </div>
                    </div>
                </div>
                <div>
                    <h2 class="text-md font-bold mb-3">{{ $t('Special components') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-12 gap-3">
                        <div class="p-3 rounded-lg border mb-3 flex flex-col h-28 justify-center items-center group relative" v-for="component in filteredSpecialComponents">
                            <div class="flex items-center justify-center mb-2">
                                <ComponentIcons :type="component.type" />
                            </div>
                            <div class="text-center text-sm font-bold">
                                {{ $t(component.name) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <AddNewComponentModal v-if="showAddNewComponentModal" @close="showAddNewComponentModal = false" :tab-component-types="tabComponentTypes" />
    </AppLayout>
</template>

<style scoped>

</style>
