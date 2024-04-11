<template>
    <div>
        <div class="flex items-center gap-x-5">
            <h2 class="xWhiteBold">{{ $t('Project properties') }}</h2>
            <IconEdit class=" w-5 h-5 rounded-full " :class="inSidebar ? 'text-white' : 'text-artwork-buttons-context'"
                      @click="openProjectAttributeEditModal"
                      v-if="this.canEditComponent && projectMembersWriteAccess()"
            />
        </div>
        <div class="flex mt-3">
            <div>
                <SidebarTagComponent v-for="category in projectCategories"
                                     :displayed-text="category.name" :property="category"
                                     :hide-x="true"
                />
                <SidebarTagComponent v-for="genre in projectGenres"
                                     :displayed-text="genre.name" :property="genre"
                                     :hide-x="true"
                />
                <SidebarTagComponent v-for="sector in projectSectors"
                                     :displayed-text="sector.name" :property="sector"
                                     :hide-x="true"
                />
            </div>
        </div>
        <ProjectAttributeEditModal :show="showAttributeEditModal"
                                   :project="project"
                                   :projectCategoryIdArray="projectCategoryIds"
                                   :projectSectorIdArray="projectSectorIds"
                                   :projectGenreIdArray="projectGenreIds"
                                   :categories="categories"
                                   :sectors="sectors"
                                   :genres="genres"
                                   @closed="closeProjectAttributeEditModal"
        />
    </div>
</template>

<script>
import {defineComponent} from "vue";
import SidebarTagComponent from "@/Layouts/Components/SidebarTagComponent.vue";
import IconLib from "@/mixins/IconLib.vue";
import Permissions from "@/mixins/Permissions.vue";
import ProjectAttributeEditModal from "@/Layouts/Components/ProjectAttributeEditModal.vue";

export default defineComponent({
    mixins: [
        Permissions,
        IconLib
    ],
    components: {
        ProjectAttributeEditModal,
        SidebarTagComponent
    },
    props: [
        'project',
        'projectCategories',
        'projectGenres',
        'projectSectors',
        'categories',
        'sectors',
        'genres',
        'projectCategoryIds',
        'projectGenreIds',
        'projectSectorIds',
        'inSidebar',
        'canEditComponent'
    ],
    data() {
        return {
            showAttributeEditModal: false,
        };
    },
    methods: {
        openProjectAttributeEditModal() {
            this.showAttributeEditModal = true;
        },
        closeProjectAttributeEditModal() {
            this.showAttributeEditModal = false;
        },
        projectMembersWriteAccess() {
            if (this.$can('write projects')) {
                return true;
            }

            if (this.project.write_auth.length === 0) {
                return false;
            }

            let canWriteArray = [];
            this.project.write_auth.forEach(write => {
                    canWriteArray.push(write.id)
                }
            )
            return canWriteArray.includes(this.$page.props.user.id);
        },
    }
});
</script>
