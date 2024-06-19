<template>
    <BaseModal @closed="closeModal(false)" v-if="show" modal-image="/Svgs/Overlays/illu_project_edit.svg">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Edit basic data') }}
                </div>
                <div class="group">
                    <div
                        class=" flex col-span-2 w-full justify-center border-2 bg-stone-50 border-gray-300 cursor-pointer border-dashed rounded-md p-2"
                        @dragover.prevent
                        @drop.stop.prevent="uploadDraggedKeyVisual($event)"
                        @click="selectNewKeyVisual"
                        v-if="this.project.key_visual_path === null">
                        <div class="space-y-1 text-center">
                            <div class="xsLight flex my-auto h-40 items-center"
                                 v-if="this.project.key_visual_path === null">
                                <span v-html="$t('Drag your key visual here')"></span>
                                <input id="keyVisual-upload" ref="keyVisual"
                                       name="file-upload" type="file" class="sr-only"
                                       @change="updateKeyVisual"/>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex items-center justify-center relative w-full">
                        <div
                            class="absolute !gap-4 w-full text-center flex items-center justify-center hidden group-hover:block">
                            <button @click="downloadKeyVisual" type="button"
                                    class="mr-3 inline-flex rounded-full bg-artwork-buttons-create p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                <IconDownload class="h-5 w-5" aria-hidden="true"/>
                            </button>
                            <button @click="selectNewKeyVisual" type="button"
                                    class="mr-3 inline-flex rounded-full bg-artwork-buttons-create p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                <IconEdit
                                    class="h-5 w-5 text-primaryText group-hover:text-white"
                                    aria-hidden="true"/>
                            </button>
                            <button @click="deleteKeyVisual" type="button"
                                    class="inline-flex rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                <IconX class="h-5 w-5 text-primaryText group-hover:text-white"
                                       aria-hidden="true"/>
                            </button>
                        </div>
                        <div class="text-center">
                            <div class="cursor-pointer">
                                <img src="">
                                <img :src="'/storage/keyVisual/' + this.project.key_visual_path"
                                     alt="Aktuelles Key-Visual"
                                     class="rounded-md w-full h-48">
                                <input id="keyVisual-upload" ref="keyVisual"
                                       name="file-upload" type="file" class="sr-only"
                                       @change="updateKeyVisual"/>
                            </div>
                        </div>
                    </div>
                    <jet-input-error :message="this.uploadKeyVisualFeedback"/>
                </div>
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
                                <span v-else  class="items-center font-medium px-2 py-1.5 inline-flex border rounded-full"
                                      :style="{
                                            backgroundColor: backgroundColorWithOpacity(states.find(state => state.id === selectedState)?.color),
                                            color: TextColorWithDarken(states.find(state => state.id === selectedState)?.color),
                                            borderColor: TextColorWithDarken(states.find(state => state.id === selectedState)?.color)
                                        }">
                                    {{ this.states.find(state => state.id === selectedState)?.name}}
                                </span>
                                <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                            </button>
                        </ListboxButton>
                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                            <ListboxOptions
                                class="absolute w-[88%] z-10 mt-12 bg-white shadow-lg max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                <ListboxOption as="template" class=""
                                               v-for="state in states"
                                               :key="state.id"
                                               :value="state.id" v-slot="{ active, selected }">
                                    <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-1 pl-3 text-sm subpixel-antialiased']"
                                        @click="updateProjectState(state)">
                                        <div class="flex">
                                             <span class=" items-center font-medium px-2 py-1.5 inline-flex border rounded-full" :style="{backgroundColor: backgroundColorWithOpacity(state.color), color: TextColorWithDarken(state.color), borderColor: TextColorWithDarken(state.color)}">
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
    </BaseModal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInputError from '@/Jetstream/InputError.vue'
import {
    DownloadIcon,
    XIcon,
    ChevronDownIcon
} from "@heroicons/vue/outline";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions
} from "@headlessui/vue";
import {CheckIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {useForm} from "@inertiajs/vue3";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import Input from "@/Jetstream/Input.vue";
import ColorHelper from "@/Mixins/ColorHelper.vue";

export default {
    mixins: [
        Permissions,
        IconLib,
        ColorHelper
    ],
    name: "ProjectDataEditModal",
    props: {
        show: Boolean,
        project: Object,
        groupProjects: Array,
        currentGroup: Object|String,
        states: Array
    },
    components: {
        BaseModal,
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
            budgetDeadline: this.project.budget_deadline,
            keyVisualForm: useForm({
                keyVisual: null,
            }),
            uploadKeyVisualFeedback: "",
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
        },
        close() {
            this.$emit('closed');
        },
        selectNewKeyVisual() {
            this.$refs.keyVisual.click();
        },
        updateKeyVisual() {
            this.validateTypeAndUploadKeyVisual(this.$refs.keyVisual.files[0]);
        },
        uploadDraggedKeyVisual(event) {
            this.validateTypeAndUploadKeyVisual(event.dataTransfer.files[0]);
        },
        validateTypeAndUploadKeyVisual(file) {
            this.uploadKeyVisualFeedback = "";
            const allowedTypes = [
                "image/jpeg",
                "image/svg+xml",
                "image/png",
                "image/gif"
            ]

            if (allowedTypes.includes(file.type)) {
                this.keyVisualForm.keyVisual = file;
                this.keyVisualForm.post(
                    route('projects_key_visual.update', {project: this.project.id}),
                    {
                        onError: error => {
                            this.uploadKeyVisualFeedback = error.keyVisual;
                        }
                    }
                );
            } else {
                this.uploadKeyVisualFeedback = this.$t(
                    'Only logos and illustrations of the type .jpeg, .svg, .png and .gif are accepted.'
                );
            }
        },
        downloadKeyVisual() {
            let link = document.createElement('a');
            link.href = route('project.download.keyVisual', this.project.id);
            link.target = '_blank';
            link.click();
        },
        deleteKeyVisual() {
            this.$inertia.delete(route('project.delete.keyVisual', this.project.id))
        },
    }
}
</script>
