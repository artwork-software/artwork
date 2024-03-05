<template>
    <jet-dialog-modal :show="show" @close="closeModal(false)">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Edit basic data') }}
                </div>
                <XIcon @click="closeModal(false)"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <input :placeholder="name"
                       id="title"
                       v-model="name"
                       class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"/>
                <div class="flex mt-2 w-full">
                    <Listbox as="div" class="flex w-full" v-model="selectedState">
                        <ListboxButton class="w-full text-left">
                            <button class="w-full h-12 flex justify-between xsDark items-center text-left border border-2 border-gray-300 bg-white px-4 py-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                    @click="openColor = !openColor">
                                <span class="w-full" v-if="!selectedState">
                                    {{ $t('Select project status') }}
                                </span>
                                <span v-else>
                                    {{ this.states.find(state => state.id === selectedState)?.name}}
                                </span>
                                <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                            </button>
                        </ListboxButton>
                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                            <ListboxOptions
                                class="absolute w-[88%] z-10 mt-12 bg-primary shadow-lg max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                <ListboxOption as="template" class=""
                                               v-for="state in states"
                                               :key="state.id"
                                               :value="state.id" v-slot="{ active, selected }">
                                    <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-1 text-sm subpixel-antialiased']"
                                        @click="updateProjectState(state)">
                                        <div class="flex">
                                            <span class="rounded-full items-center font-medium px-2 mt-2 text-sm ml-2 mr-1 mb-1 inline-flex">
                                                {{ state.name }}
                                            </span>
                                        </div>
                                        <span
                                            :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                            <CheckIcon v-if="selected"
                                                       class="h-5 w-5 flex text-success"
                                                       aria-hidden="true"/>
                                        </span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </Listbox>
                </div>
                <div class="mt-4">
                    <div class="flex items-center mb-2" v-if="!project.is_group">
                        <input id="hasGroup" type="checkbox" v-model="this.hasGroup"
                               @change="removeSelectedGroup"
                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                        <label for="hasGroup" :class="this.hasGroup ? 'xsDark' : 'xsLight subpixel-antialiased'"
                               class="ml-2">
                            {{ $t('Belongs to project group') }}
                        </label>
                    </div>
                    <div v-if="this.hasGroup" class="mb-2">
                        <Listbox as="div" v-model="this.selectedGroup" id="room">
                            <ListboxButton class="inputMain w-full h-10 cursor-pointer truncate flex p-2">
                                <div class="flex-grow flex text-left xsDark">
                                    {{
                                        this.selectedGroup?.name ? this.selectedGroup.name : $t('Search project group')
                                    }}
                                </div>
                                <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                            </ListboxButton>
                            <ListboxOptions class="w-[88%] bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                <ListboxOption v-if="this.groupProjects.length === 0"
                                               class="w-full text-secondary cursor-pointer p-2 flex justify-between"
                                               :value="null">
                                    {{ $t('No project group has been created yet') }}
                                </ListboxOption>
                                <ListboxOption v-for="projectGroup in groupProjects"
                                               class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                               :key="projectGroup.id"
                                               :value="projectGroup"
                                               v-slot="{ active, selected }">
                                    <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                        {{ projectGroup.name }}
                                    </div>
                                    <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                </ListboxOption>
                            </ListboxOptions>
                        </Listbox>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="xsDark">
                        <span>{{ $t('Budget deadline') }}</span>
                    </div>
                    <div class="flex mt-1">
                        <input v-model="this.budgetDeadline"
                               id="budgetDeadline"
                               type="date"
                               required
                               class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
                    </div>
                </div>
            </div>
            <div class="justify-center flex w-full my-6">
                <FormButton :text="$t('Save')" :disabled="name.length < 1"
                            @click="updateProjectData"/>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInputError from '@/Jetstream/InputError.vue'
import {
    DownloadIcon,
    XIcon,
    ChevronDownIcon
} from "@heroicons/vue/outline";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions
} from "@headlessui/vue";
import {CheckIcon} from "@heroicons/vue/solid";
import Permissions from "@/mixins/Permissions.vue";
import Input from "@/Jetstream/Input.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default {
    mixins: [Permissions],
    name: "ProjectDataEditModal",
    props: {
        show: Boolean,
        project: Object,
        groupProjects: Array,
        currentGroup: Object,
        states: Array
    },
    components: {
        FormButton,
        Input,
        ListboxOption,
        ListboxOptions,
        ListboxButton,
        Listbox,
        BaseFilterTag,
        JetDialogModal,
        JetInputError,
        XIcon,
        DownloadIcon,
        ChevronDownIcon,
        CheckIcon
    },
    watch: {
        groupName: {
            deep: true,
            handler() {
                if (!this.groupName) {
                    this.groupSearchResults = [];
                    return;
                }
                axios.get('/projects/search', {params: {query: this.groupName}})
                    .then(response => this.groupSearchResults = response.data)
            },
        },
    },
    data() {
        return {
            name: this.project?.name,
            description: this.project?.description,
            hasGroup: !!this.currentGroup,
            selectedGroup: this.currentGroup,
            selectedState: this.project?.state?.id,
            openColor: false,
            budgetDeadline: this.project.budget_deadline
        }
    },
    methods: {
        updateProjectData() {
            this.$inertia.patch(route('projects.update', {project: this.project.id}), {
                name: this.name,
                selectedGroup: this.selectedGroup,
                budget_deadline: this.budgetDeadline
            }, {
                preserveState: true,
                preserveScroll: true
            })
            this.closeModal(true);
        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        removeSelectedGroup() {
            if (!this.hasGroup) {
                this.selectedGroup = null;
            }
        },
        updateProjectState(state) {
            this.$inertia.patch(route('update.project.state', this.project.id), {
                state_id: state.id
            }, {
                preserveState: true,
                preserveScroll: true
            })
        }
    }
}
</script>
