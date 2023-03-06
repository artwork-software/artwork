<template>
    <jet-dialog-modal :show="show" @close="closeModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Basisdaten bearbeiten
                </div>
                <XIcon @click="closeModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <input :placeholder="name"
                       id="title"
                       v-model="name"
                       class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                <ProjectAttributesMenu
                    :categories="traits.categories"
                    :genres="traits.genres"
                    :sectors="traits.sectors"
                    :projectCategories="projectCategories"
                    :projectGenres="projectGenres"
                    :projectSectors="projectSectors"
                    @update-project-category="updateProjectCategories"
                    @update-project-sector="updateProjectSectors"
                    @update-project-genre="updateProjectGenres"
                />

                <div class="mt-2 flex flex-wrap">
                    <BaseFilterTag v-for="category in projectCategories" :filter="category.name" @remove-filter="updateProjectCategories(category)" class="w-fit" />
                    <BaseFilterTag v-for="genre in projectGenres" :filter="genre.name" @remove-filter="updateProjectGenres(genre)" class="w-fit" />
                    <BaseFilterTag v-for="sector in projectSectors" :filter="sector.name" @remove-filter="updateProjectSectors(sector)" class="w-fit" />
                </div>

                <textarea placeholder="Kurzbeschreibung"
                          id="description"
                          v-model="description"
                          rows="4"
                          class="mt-4 border-gray-300 border-2 h-40 w-full text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                <div class="flex items-center my-3">
                    <input type="checkbox" v-model="showProjectGroup"
                           class="cursor-pointer h-4 w-4 text-success border-1 border-gray-400 bg-darkGrayBg focus:border-none"/>
                    <div class="text-md ml-2 text-primary">Gehört zu Projektgruppe</div>
                </div>
                <div v-if="showProjectGroup">
                    <input type="text"
                           id="projectGroup"
                           @focusin="showGroupSearchResults = true"
                           @change="this.project.group.name = this.groupName"
                           @focusout="this.groupName = '';"
                           v-model="this.project.group"
                           autocomplete="off"
                           placeholder="Projektgruppe suchen"
                           class="mt-2 p-4 border-2 text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                    <div
                        v-if="groupSearchResults.length > 0 && showGroupSearchResults"
                        class="absolute bg-primary truncate sm:text-sm w-10/12">
                        <div v-for="(group, index) in groupSearchResults"
                             :key="index"
                             @click="project.groupId = group.id; project.group = group; this.groupName = ''; showGroupSearchResults = false; this.groupSearchResults = [];"
                             class="p-4 text-white border-l-4 hover:border-l-success border-l-primary cursor-pointer">
                            {{ group.name }}
                        </div>
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

export default {
    name: "ProjectDataEditModal",
    props: {
        show: Boolean,
        closeModal: Function,
        project: Object,
        traits: Object
    },
    components: {
        BaseFilterTag,
        ProjectAttributesMenu,
        JetDialogModal,
        JetInputError,
        AddButton,
        XIcon,
        DownloadIcon,
        ChevronDownIcon
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
            group: this.project.group,
            showProjectGroup: this.project.group !== null,
            showGroupSearchResults: false,
            groupSearchResults: [],
            groupName: '',
            projectCategories: [],
            projectGenres: [],
            projectSectors: []
        }
    },
    methods: {
        updateProjectData() {
            this.$inertia.patch(`/projects/${this.project.id}`, {
                name: this.name,
                description: this.description,
                assignedSectorIds: this.createIdArray(this.projectSectors),
                assignedCategoryIds: this.createIdArray(this.projectCategories),
                assignedGenreIds: this.createIdArray(this.projectGenres),
                group: this.group
            }, {
                preserveState: true,
                preserveScroll: true
            })
        },
        updateProjectCategories(category) {
            this.projectCategories = this.updateTrait(category, this.projectCategories)
        },
        updateProjectGenres(genre) {
            this.projectGenres = this.updateTrait(genre, this.projectGenres)
        },
        updateProjectSectors(sector) {
           this.projectSectors = this.updateTrait(sector, this.projectSectors)
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
