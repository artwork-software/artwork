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
                <Menu class="relative">
                    <div>
                    <MenuButton @click="attributesOpened = true" class="w-full">
                        <div class="border-2 border-gray-300 w-full cursor-pointer truncate flex p-4 mt-4">
                            <div class="flex-grow xsLight text-left subpixel-antialiased">
                                Projekteigenschaften wählen
                            </div>
                            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                        </div>
                    </MenuButton>

                    <transition
                        enter-active-class="transition duration-50 ease-out"
                        enter-from-class="transform scale-100 opacity-100"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <MenuItems
                            class="absolute right-0 mt-2 w-full origin-top-right divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                            <div class="rounded-2xl max-h-56 overflow-y-auto bg-primary border-none mt-2">

                                <!-- Project Categories Section -->
                                <BaseFilterDisclosure title="Kategorie">

                                    <div v-if="categories?.length > 0"
                                         v-for="category in categories"
                                         :key="category.id"
                                         class="flex w-full mb-2">
                                        <input type="checkbox"
                                               v-model="projectCategoryIds"
                                               :value="category.id"
                                               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                        <p :class="[projectCategoryIds.includes(category.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                            {{ category.name }}
                                        </p>
                                    </div>
                                    <div v-else class="text-secondary">Noch keine Projektkategorien angelegt</div>
                                </BaseFilterDisclosure>

                                <hr class="border-gray-500 rounded-full mt-2 mb-2">

                                <!-- Project Genres Section -->
                                <BaseFilterDisclosure title="Genre">

                                    <div v-if="genres?.length > 0"
                                         v-for="genre in genres"
                                         :key="genre.id"
                                         class="flex w-full mb-2">
                                        <input type="checkbox"
                                               v-model="projectGenreIds"
                                               :value="genre.id"
                                               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                        <p :class="[projectGenreIds.includes(genre.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                            {{ genre.name }}
                                        </p>
                                    </div>
                                    <div v-else class="text-secondary">Noch keine Genres angelegt</div>
                                </BaseFilterDisclosure>

                                <hr class="border-gray-500 rounded-full mt-2 mb-2">

                                <!-- Project sectors Section -->
                                <BaseFilterDisclosure title="Bereiche">

                                    <div v-if="sectors?.length > 0"
                                         v-for="sector in sectors"
                                         :key="sector.id"
                                         class="flex w-full mb-2">
                                        <input type="checkbox"
                                               v-model="projectSectorIds"
                                               :value="sector.id"
                                               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                        <p :class="[projectSectorIds.includes(sector.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                            {{ sector.name }}
                                        </p>
                                    </div>
                                    <div v-else class="text-secondary">Noch keine Bereiche angelegt</div>
                                </BaseFilterDisclosure>
                            </div>
                        </MenuItems>
                    </transition>
                    </div>
                </Menu>
                <div class="mt-2 flex flex-wrap">
                    <div v-for="category in categories">
                        <BaseFilterTag v-if="projectCategoryIds.includes(category.id)"  :filter="category.name" @remove-filter="deleteProjectCategory(category.id)" class="w-fit" />
                    </div>
                    <div v-for="genre in genres">
                        <BaseFilterTag v-if="projectGenreIds.includes(genre.id)"  :filter="genre.name" @remove-filter="deleteProjectGenre(genre.id)" class="w-fit" />
                    </div>
                    <div v-for="sector in sectors">
                        <BaseFilterTag v-if="projectSectorIds.includes(sector.id)"  :filter="sector.name" @remove-filter="deleteProjectSector(sector.id)" class="w-fit" />
                    </div>
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
import JetInputError from '@/Jetstream/InputError.vue'
import AddButton from "@/Layouts/Components/AddButton";
import {DownloadIcon, XIcon, ChevronDownIcon} from "@heroicons/vue/outline";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag";
import BaseFilterDisclosure from "@/Layouts/Components/BaseFilterDisclosure.vue";
import {Menu, MenuButton, MenuItems} from "@headlessui/vue";

export default {
    name: "ProjectDataEditModal",
    props: {
        show: Boolean,
        project: Object,
        categories: Array,
        sectors: Array,
        genres: Array,
        projectCategoryIdArray: Array,
        projectGenreIdArray: Array,
        projectSectorIdArray: Array,
    },
    components: {
        Menu,
        MenuItems,
        MenuButton,
        BaseFilterDisclosure,
        BaseFilterTag,
        JetDialogModal,
        JetInputError,
        AddButton,
        XIcon,
        DownloadIcon,
        ChevronDownIcon
    },
    data() {
        return {
            projectCategoryIds: this.projectCategoryIdArray ? this.projectCategoryIdArray : [],
            projectGenreIds: this.projectGenreIdArray ? this.projectGenreIdArray : [],
            projectSectorIds: this.projectSectorIdArray ? this.projectSectorIdArray : [],
            attributesOpened: false,
        }
    },
    methods: {
        updateProjectData() {
            this.$inertia.patch(route('projects.update_attributes',{project: this.project.id}), {
                assignedSectorIds: this.projectSectorIds,
                assignedCategoryIds: this.projectCategoryIds,
                assignedGenreIds: this.projectGenreIds,
            }, {
                preserveState: true,
                preserveScroll: true
            })
            this.closeModal(true);
        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        deleteProjectCategory(categoryId) {
            this.projectCategoryIds.splice(this.projectCategoryIds.indexOf(categoryId),1);
        },
        deleteProjectGenre(genreId) {
            this.projectGenreIds.splice(this.projectGenreIds.indexOf(genreId),1);
        },
        deleteProjectSector(sectorId) {
           this.projectSectorIds.splice(this.projectSectorIds.indexOf(sectorId),1);
        },
    }
}
</script>

<style scoped>

</style>
