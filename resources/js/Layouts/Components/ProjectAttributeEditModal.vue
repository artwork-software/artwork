<template>
    <BaseModal @closed="closeModal" v-if="show" modal-image="/Svgs/Overlays/illu_project_edit.svg">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Project properties') }}
                </div>
                <Menu class="relative">
                    <div>
                    <MenuButton @click="attributesOpened = true" class="w-full">
                        <div class="border-2 border-gray-300 w-full cursor-pointer truncate flex p-4 mt-4">
                            <div class="flex-grow xsLight text-left subpixel-antialiased">
                                {{ $t('Select project properties') }}
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
                            class="absolute right-0 mt-2 w-full origin-top-right divide-y divide-gray-200 rounded-sm bg-artwork-navigation-background ring-1 ring-black p-2 text-white opacity-100 z-50">
                            <div class="rounded-2xl max-h-56 overflow-y-auto bg-artwork-navigation-background border-none mt-2">
                                <BaseFilterDisclosure :title="$t('Category')">
                                    <div v-if="categories?.length > 0"
                                         v-for="category in categories"
                                         :key="category.id"
                                         class="flex w-full mb-2">
                                        <input type="checkbox"
                                               v-model="projectCategoryIds"
                                               :value="category.id"
                                               class="checkBoxOnDark"/>
                                        <p :class="[projectCategoryIds.includes(category.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                            {{ category.name }}
                                        </p>
                                    </div>
                                    <div v-else class="text-secondary">{{ $t('No project categories created yet') }}</div>
                                </BaseFilterDisclosure>
                                <hr class="border-gray-500 rounded-full mt-2 mb-2">
                                <BaseFilterDisclosure :title="$t('Genre')">
                                    <div v-if="genres?.length > 0"
                                         v-for="genre in genres"
                                         :key="genre.id"
                                         class="flex w-full mb-2">
                                        <input type="checkbox"
                                               v-model="projectGenreIds"
                                               :value="genre.id"
                                               class="checkBoxOnDark"/>
                                        <p :class="[projectGenreIds.includes(genre.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                            {{ genre.name }}
                                        </p>
                                    </div>
                                    <div v-else class="text-secondary">
                                        {{ $t('No genres created yet') }}
                                    </div>
                                </BaseFilterDisclosure>
                                <hr class="border-gray-500 rounded-full mt-2 mb-2">
                                <BaseFilterDisclosure :title="$t('Areas')">
                                    <div v-if="sectors?.length > 0"
                                         v-for="sector in sectors"
                                         :key="sector.id"
                                         class="flex w-full mb-2">
                                        <input type="checkbox"
                                               v-model="projectSectorIds"
                                               :value="sector.id"
                                               class="checkBoxOnDark"/>
                                        <p :class="[projectSectorIds.includes(sector.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                            {{ sector.name }}
                                        </p>
                                    </div>
                                    <div v-else class="text-secondary">
                                        {{ $t('No areas created yet') }}
                                    </div>
                                </BaseFilterDisclosure>
                            </div>
                        </MenuItems>
                    </transition>
                    </div>
                </Menu>
                <div class="mt-2 flex flex-wrap">
                    <div v-for="category in categories">
                        <BaseFilterTag v-if="projectCategoryIds.includes(category.id)"
                                       :filter="category"
                                       @remove-filter="deleteProjectCategory(category.id)"
                                       class="w-fit"
                        />
                    </div>
                    <div v-for="genre in genres">
                        <BaseFilterTag v-if="projectGenreIds.includes(genre.id)"
                                       :filter="genre"
                                       @remove-filter="deleteProjectGenre(genre.id)"
                                       class="w-fit"
                        />
                    </div>
                    <div v-for="sector in sectors">
                        <BaseFilterTag v-if="projectSectorIds.includes(sector.id)"
                                       :filter="sector"
                                       @remove-filter="deleteProjectSector(sector.id)"
                                       class="w-fit"
                        />
                    </div>
                </div>
            </div>
            <div class="justify-center flex w-full my-6 mt-32">
                <FormButton :text="$t('Save')"
                           @click="updateProjectData"/>
            </div>
    </BaseModal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInputError from '@/Jetstream/InputError.vue'
import {DownloadIcon, XIcon, ChevronDownIcon} from "@heroicons/vue/outline";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import BaseFilterDisclosure from "@/Layouts/Components/BaseFilterDisclosure.vue";
import {Menu, MenuButton, MenuItems} from "@headlessui/vue";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    mixins: [Permissions],
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
        BaseModal,
        FormButton,
        Menu,
        MenuItems,
        MenuButton,
        BaseFilterDisclosure,
        BaseFilterTag,
        JetDialogModal,
        JetInputError,
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
