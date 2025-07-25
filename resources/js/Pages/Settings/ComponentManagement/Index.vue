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
            <div>
                <div class="flex items-center justify-end w-full mb-3">
                    <div class="flex items-center gap-x-5">
                        <div>
                            <GlassyIconButton icon="IconPlus" @click="showAddNewComponentModal = true" :text="$t('Create a new component')"/>
                        </div>
                        <div class="w-44 md:w-56 lg:w-72">
                            <div>
                                <BaseInput id="search" type="text" name="search" v-model="searchComponent" label="Search" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card white p-5">
                    <div v-for="(componentsArray, index) in filteredComponents">
                        <h2 class="text-md font-bold mb-3">{{ $t(index) }}</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 3xl:grid-cols-12 gap-3 w-full">
                            <DropComponentsToolTip  v-for="component in componentsArray" :top="true" :tooltip-text="component.special ? $t(component.name) : component.name">
                                <div class="p-3 rounded-lg border border-gray-200 mb-3 flex flex-col h-28 min-w-28 justify-center items-center group relative truncate break-all">
                                    <SingleComponent :component="component" />
                                </div>
                            </DropComponentsToolTip>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-md font-bold mb-3">{{ $t('Special components') }}</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-8 gap-3">
                            <DropComponentsToolTip  v-for="component in filteredSpecialComponents" :top="true" :tooltip-text="component.special ? $t(component.name) : component.name">
                                <div class="p-3 rounded-lg border border-gray-200 mb-3 flex flex-col h-28 justify-center items-center group relative truncate break-all">
                                    <SingleComponent :component="component" />
                                </div>
                            </DropComponentsToolTip>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ComponentModal v-if="showAddNewComponentModal"
                        :show="showAddNewComponentModal"
                        mode="create"
                        :tab-component-types="tabComponentTypes"
                        @close="showAddNewComponentModal = false"
        />
    </AppLayout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import ProjectTabs from "@/Pages/Settings/Components/ProjectTabs.vue";
import DragComponentElement from "@/Pages/Settings/Components/DragComponentElement.vue";
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import IconLib from "@/Mixins/IconLib.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import SingleComponent from "@/Pages/Settings/ComponentManagement/Components/SingleComponent.vue";
import ComponentModal from "@/Pages/Settings/ComponentManagement/Components/ComponentModal.vue";
import DropComponentsToolTip from "@/Components/ToolTips/DropComponentsToolTip.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default {
    name: "Index",
    components: {
        BaseInput,
        GlassyIconButton,
        DropComponentsToolTip,
        SingleComponent,
        PlusButton,
        ComponentIcons,
        DragComponentElement,
        ProjectTabs,
        AppLayout,
        ComponentModal
    },
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
