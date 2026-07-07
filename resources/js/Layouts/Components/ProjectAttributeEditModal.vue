<template>
    <ArtworkBaseModal @close="closeModal"
                      v-if="show"
                      :title="$t('Project properties')"
                      :description="$t('Assign categories, genres and areas to this project.')">
        <div class="space-y-6 max-h-[55vh] overflow-y-auto pr-1">
            <!-- Kategorien -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ $t('Category') }}</h3>
                <div v-if="categories?.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2">
                    <label v-for="category in categories"
                           :key="category.id"
                           class="flex items-center gap-3 cursor-pointer">
                        <div class="group grid size-4 shrink-0 grid-cols-1">
                            <input type="checkbox"
                                   v-model="projectCategoryIds"
                                   :value="category.id"
                                   class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 forced-colors:appearance-auto"/>
                            <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white" viewBox="0 0 14 14" fill="none">
                                <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <span class="flex items-center gap-2 text-sm text-gray-900 truncate">
                            <span v-if="category.color"
                                  class="size-2.5 rounded-full border border-gray-200 shrink-0"
                                  :style="{ backgroundColor: category.color }"/>
                            {{ category.name }}
                        </span>
                    </label>
                </div>
                <p v-else class="text-xs text-gray-500">{{ $t('No project categories created yet') }}</p>
            </div>

            <!-- Genres -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ $t('Genre') }}</h3>
                <div v-if="genres?.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2">
                    <label v-for="genre in genres"
                           :key="genre.id"
                           class="flex items-center gap-3 cursor-pointer">
                        <div class="group grid size-4 shrink-0 grid-cols-1">
                            <input type="checkbox"
                                   v-model="projectGenreIds"
                                   :value="genre.id"
                                   class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 forced-colors:appearance-auto"/>
                            <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white" viewBox="0 0 14 14" fill="none">
                                <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <span class="flex items-center gap-2 text-sm text-gray-900 truncate">
                            <span v-if="genre.color"
                                  class="size-2.5 rounded-full border border-gray-200 shrink-0"
                                  :style="{ backgroundColor: genre.color }"/>
                            {{ genre.name }}
                        </span>
                    </label>
                </div>
                <p v-else class="text-xs text-gray-500">{{ $t('No genres created yet') }}</p>
            </div>

            <!-- Bereiche -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ $t('Areas') }}</h3>
                <div v-if="sectors?.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2">
                    <label v-for="sector in sectors"
                           :key="sector.id"
                           class="flex items-center gap-3 cursor-pointer">
                        <div class="group grid size-4 shrink-0 grid-cols-1">
                            <input type="checkbox"
                                   v-model="projectSectorIds"
                                   :value="sector.id"
                                   class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 forced-colors:appearance-auto"/>
                            <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white" viewBox="0 0 14 14" fill="none">
                                <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <span class="flex items-center gap-2 text-sm text-gray-900 truncate">
                            <span v-if="sector.color"
                                  class="size-2.5 rounded-full border border-gray-200 shrink-0"
                                  :style="{ backgroundColor: sector.color }"/>
                            {{ sector.name }}
                        </span>
                    </label>
                </div>
                <p v-else class="text-xs text-gray-500">{{ $t('No areas created yet') }}</p>
            </div>
        </div>
        <div class="justify-end flex w-full mt-6">
            <BaseUIButton :label="$t('Save')" is-add-button
                          @click="updateProjectData"/>
        </div>
    </ArtworkBaseModal>
</template>

<script>
import Permissions from "@/Mixins/Permissions.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

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
        BaseUIButton,
        ArtworkBaseModal,
    },
    data() {
        return {
            projectCategoryIds: this.projectCategoryIdArray ? this.projectCategoryIdArray : [],
            projectGenreIds: this.projectGenreIdArray ? this.projectGenreIdArray : [],
            projectSectorIds: this.projectSectorIdArray ? this.projectSectorIdArray : [],
        }
    },
    methods: {
        updateProjectData() {
            this.$inertia.patch(route('projects.update_attributes', {project: this.project.id}), {
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
    }
}
</script>
