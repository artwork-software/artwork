<script>
import { Menu, MenuButton, MenuItem, MenuItems,Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions
} from '@headlessui/vue'
import IconLib from "@/Mixins/IconLib.vue";
import {useProjectDataListener} from "@/Composeables/Listener/useProjectDataListener.js";
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";
export default {
    name: "DropDown",
    mixins: [IconLib],
    components: {
        InfoButtonComponent,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions
    },
    props: {
        data: {
            type: Object,
            required: true
        },
        projectId: {
            type: Number,
            required: true
        },
        inSidebar: {
            type: Boolean,
            required: false
        },
        canEditComponent: {
            type: Boolean,
            required: true
        },
        component: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            projectData: this.data,
            selected: this.data.project_value ? this.data.project_value.data.selected : this.data.data.selected
        }
    },
    mounted() {
        useProjectDataListener(this.projectData, this.projectId).init();
    },
    watch: {
        // if the data changes, update the text
        projectData: {
            handler: function (newVal, oldVal) {
                this.selected = newVal.project_value ? newVal.project_value.data.selected : newVal.data.selected
            },
            deep: true
        }
    },
    methods: {
        updateTextData(value) {
            this.$inertia.patch(route('project.tab.component.update', {project: this.projectId, component: this.data.id}), {
                data: {
                    selected: value
                },
            }, {
                preserveScroll: true,
                preserveState: true
            }, {
                preserveScroll: true,
                preserveState: true
            })
        }
    }
}
</script>

<template>
    <div class="flex items-center justify-between">
        <Listbox as="div" class="w-96" v-model="selected" :disabled="!this.canEditComponent">
            <ListboxLabel class="block text-sm font-medium leading-6"  :class="inSidebar ? 'text-white' : 'text-gray-900'">
                {{ data.data.label }}
            </ListboxLabel>
            <div class="relative mt-2">
                <ListboxButton class="menu-button" :class="inSidebar ? 'bg-primary text-white border  border-gray-300' : ''">
                    <div class="block truncate">{{ selected }}</div>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                    <IconChevronDown class="h-5 w-5 text-gray-400" aria-hidden="true" />
                </span>
                </ListboxButton>

                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto  py-1 text-base ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
                                    :class="inSidebar ? 'bg-primary text-white border border-gray-300' : 'bg-white'">
                        <ListboxOption as="template" v-for="item in data.data.options" :key="item.value" :value="item.value" v-slot="{ active, selected }">
                            <li @click="updateTextData(item.value)" :class="[active ? 'bg-indigo-600 text-white' : inSidebar ? 'text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ item.value }}</span>
                                <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                <IconCircleCheck class="h-5 w-5" aria-hidden="true" />
                              </span>
                            </li>
                        </ListboxOption>
                    </ListboxOptions>
                </transition>
            </div>
        </Listbox>
        <InfoButtonComponent :component="component" />
    </div>

</template>

<style scoped>

</style>
