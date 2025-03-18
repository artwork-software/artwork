<script>
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import {IconDotsVertical, IconDragDrop, IconTrash} from "@tabler/icons-vue";
import {Disclosure, DisclosureButton, DisclosurePanel, Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import DropComponentInDisclosureComponentElement
    from "@/Pages/Settings/Components/DropComponentInDisclosureComponentElement.vue";

export default {
    name: "SingleComponent",
    components: {
        DisclosurePanel,
        DisclosureButton,
        Disclosure,
        DropComponentInDisclosureComponentElement,
        TextareaComponent,
        BaseMenu, IconTrash, IconDragDrop, IconDotsVertical, ComponentIcons,
        Menu,
        MenuButton,
        MenuItems,
        MenuItem,},
    data(){
        return {
            dragging: false,
        }
    },
    props: ['element', 'tab'],
    methods: {
        removeComponentFromTab() {
            this.$inertia.delete(route('tab.remove.component', {projectTab: this.tab.id}),
                {
                    data: {
                        component_id: this.element.id
                    }
                }
            );
        },
        updateNote() {
            this.$inertia.patch(route('tab.update.component.note', {componentInTab: this.element.id}), {
                note: this.element.note
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    this.element.openNoteInput = false
                }
            })
        },
        requestDeleteComponentInDisclosure(componentId) {
            this.$inertia.delete(route('tab.remove.component.in.disclosure'), {
                data: {
                    id: componentId,
                },
                preserveScroll: true,
                preserveState: true,
            })
        }
    }
}
</script>

<template>
    <div class="flex group w-full items-center">
        <div class="flex items-center bg-artwork-project-background py-5 px-4 my-1 rounded-lg flex-wrap w-full" :key="element.id" :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
            <div class="flex justify-between w-full items-center">
                <div class="w-full">
                    <div class="flex">
                        <div class="col-span-6 flex items-center gap-x-3">
                            <ComponentIcons :type="element.component.type" />
                            <div class="">
                                <div class="flex items-center gap-4">
                                    {{element.component.name }}
                                    <div class="text-[10px] text-gray-500 font-light" v-if="element.component.data.height">
                                        {{ element.component.data.height }} Pixel <span v-if="element.component.data.showLine === true">| {{ $t('Show a separator line')}}</span>
                                    </div>
                                    <div class="text-[10px] text-gray-500 font-light truncate" v-if="element.component.data.title_size">
                                        {{ element.component.data.title_size }} Pixel
                                    </div>
                                </div>
                                <div class="col-span-2 text-xs flex items-center">
                                    {{ $t(element.component.type)}}
                                </div>
                                <div class="my-2">
                                    <div class="xxsDarkBold">
                                        Tooltip Text (optional):
                                    </div>
                                    <div class="cursor-pointer my-1.5" @click="element.openNoteInput = !element.openNoteInput" v-if="!element.openNoteInput" :class="element.note ? 'xxsDark' : 'xxsLight'">
                                        {{ element.note ?? $t('Click here to add text') }}
                                    </div>
                                    <div v-else class="my-1">
                                        <TextareaComponent v-model="element.note" id="note" :label="$t('Enter text here')" :show-label="false" @focusout="updateNote" />
                                    </div>
                                </div>

                                <div v-if="element.component.type === 'DisclosureComponent'">
                                    <Disclosure as="div" class="" v-slot="{ open }">
                                        <DisclosureButton class=" xsDark">
                                            <div class="flex items-center gap-2">
                                                {{ $t('Components in Disclosure') }}
                                                <div>
                                                    <component is="IconChevronDown" class="size-3" :class="{ 'transform rotate-180': open }" />
                                                </div>
                                            </div>
                                            <DropComponentInDisclosureComponentElement v-if="!open" :element="element" :index="1" />
                                        </DisclosureButton>
                                        <DisclosurePanel>
                                            <DropComponentInDisclosureComponentElement  :element="element" :index="1" />
                                            <div v-for="(component, index) in element.disclosure_components" :key="component.id" class="">
                                                <div class="flex items-center gap-x-5 justify-between w-full group/component">
                                                    <div>
                                                        <div class="flex items-center gap-x-1 xsDark">
                                                            <component is="IconRadiusBottomLeft" class="size-3 -mt-2" />
                                                            <ComponentIcons :type="component.component.type" />
                                                            {{ $t(component.component.name) }}
                                                        </div>
                                                        <div class="xxsLight">
                                                            {{ $t(component.component.type) }}
                                                        </div>
                                                    </div>
                                                    <div class="invisible group-hover/component:visible">
                                                        <component is="IconTrash" class="size-5 hover:text-red-500 cursor-pointer" @click="requestDeleteComponentInDisclosure(component.id)" />
                                                    </div>
                                                </div>
                                                <DropComponentInDisclosureComponentElement :element="element" :index="component.order + 1" />
                                            </div>
                                        </DisclosurePanel>
                                    </Disclosure>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <IconDragDrop class="xsDark h-5 w-5 hidden group-hover:block cursor-pointer relative z-100"/>
                <div  class="invisible group-hover:visible">
                    <BaseMenu has-no-offset>
                        <MenuItem v-slot="{ active }">
                            <a href="#" @click="removeComponentFromTab(element.id)"
                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                <IconTrash stroke-width="1.5"
                                           class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                           aria-hidden="true"/>
                                {{ $t('Delete') }}
                            </a>
                        </MenuItem>
                    </BaseMenu>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
