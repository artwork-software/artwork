<template>
    <jet-dialog-modal :show="show" @close="closeModal(false)">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Basisdaten bearbeiten
                </div>
                <XIcon @click="closeModal(false)"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <input :placeholder="name"
                       id="title"
                       v-model="name"
                       class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                <div class="mt-4">
                    <div class="flex items-center mb-2" v-if="!project.is_group">
                        <input id="hasGroup" type="checkbox" v-model="this.hasGroup"
                               @change="removeSelectedGroup"
                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                        <label for="hasGroup" :class="this.hasGroup ? 'xsDark' : 'xsLight subpixel-antialiased'"
                               class="ml-2">
                            Gehört zu Projektgruppe
                        </label>
                    </div>
                    <div v-if="this.hasGroup" class="mb-2">
                        <Listbox as="div" v-model="this.selectedGroup" id="room">
                            <ListboxButton class="inputMain w-full h-10 cursor-pointer truncate flex p-2">
                                <div class="flex-grow flex text-left xsDark">
                                    {{
                                        this.selectedGroup?.name ? this.selectedGroup.name : 'Projektgruppe suchen'
                                    }}
                                </div>
                                <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                            </ListboxButton>
                            <ListboxOptions class="w-5/6 bg-primary max-h-32 overflow-y-auto text-sm absolute">
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

            </div>
            <div class="justify-center flex w-full my-6">
                <AddButton text="Speichern" mode="modal" class="px-6 py-3" :disabled="name.length < 1"
                           @click="updateProjectData"/>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInputError from "@/Jetstream/DialogModal";
import AddButton from "@/Layouts/Components/AddButton";
import {DownloadIcon, XIcon, ChevronDownIcon} from "@heroicons/vue/outline";
import ProjectAttributesMenu from "@/Layouts/Components/ProjectAttributesMenu";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {CheckIcon} from "@heroicons/vue/solid";

export default {
    name: "ProjectDataEditModal",
    props: {
        show: Boolean,
        project: Object,
        groupProjects: Array,
        currentGroup: Object
    },
    components: {
        ListboxOption,
        ListboxOptions,
        ListboxButton,
        Listbox,
        BaseFilterTag,
        ProjectAttributesMenu,
        JetDialogModal,
        JetInputError,
        AddButton,
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
            name: this.project.name,
            description: this.project.description,
            hasGroup: !!this.currentGroup,
            selectedGroup: this.currentGroup,
        }
    },
    methods: {
        updateProjectData() {
            this.$inertia.patch(`/projects/${this.project.id}`, {
                name: this.name,
                selectedGroup: this.selectedGroup
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
        updateTrait(trait, array) {
            if (typeof trait == "string") {
                trait = array.find(sect => sect.name === trait)
            }
            if(!array.includes(trait)) {
                trait.included = true
                array.push(trait)
            }
            else {
                trait.included = false
                array = array.filter(item => item.id !== trait.id)
            }
            return array
        },
        createIdArray(array) {
            let idArray = []
            array.forEach(item => idArray.push(item.id))
            return idArray
        }
    }
}
</script>

<style scoped>

</style>
