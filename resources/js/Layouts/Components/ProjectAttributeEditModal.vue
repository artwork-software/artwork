<template>
    <jet-dialog-modal :show="show" @close="closeModal(false)">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Projekteigenschaften
                </div>
                <XIcon @click="closeModal(false)"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <ProjectAttributesMenu
                    :categories="categories"
                    :genres="genres"
                    :sectors="sectors"
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
            </div>
            <div class="justify-center flex w-full my-6 mt-32">
                <AddButton text="Speichern" mode="modal" class="px-6 py-3"
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
        project: Object,
        categories: Array,
        sectors: Array,
        genres: Array,
        projectCategories: Array,
        projectGenres: Array,
        projectSectors: Array,
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
    data() {
        return {
            projectCategories: this.projectCategories ? this.projectCategories : [],
            projectGenres: this.projectGenres ? this.projectGenres : [],
            projectSectors: this.projectSectors ? this.projectSectors : []
        }
    },
    methods: {
        updateProjectData() {
            this.$inertia.patch(route('projects.update_attributes',{project: this.project.id}), {
                assignedSectorIds: this.createIdArray(this.projectSectors),
                assignedCategoryIds: this.createIdArray(this.projectCategories),
                assignedGenreIds: this.createIdArray(this.projectGenres),
            }, {
                preserveState: true,
                preserveScroll: true
            })
            this.closeModal(true);
        },
        closeModal(bool) {
            this.$emit('closed', bool);
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
