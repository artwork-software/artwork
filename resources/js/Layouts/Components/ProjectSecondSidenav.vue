<template>
    <div class="w-full mt-24">
        <div class="w-full pb-10 mb-5 border-b-2 border-gray-700">
            <ProjectTeamComponent :project="this.project"/>
        </div>
        <div class="w-full pb-10 mb-5 border-b-2 border-gray-700">
            <ProjectAttributesComponent :project="this.project"
                                        :project-categories="this.projectCategories"
                                        :project-genres="this.projectGenres"
                                        :project-sectors="this.projectSectors"
                                        :categories="this.categories"
                                        :genres="this.genres"
                                        :sectors="this.sectors"
                                        :project-category-ids="this.projectCategoryIds"
                                        :project-genre-ids="this.projectGenreIds"
                                        :project-sector-ids="this.projectSectorIds"
            />
        </div>
    </div>
</template>

<script>
import {PencilAltIcon} from "@heroicons/vue/outline";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import SidebarTagComponent from "@/Layouts/Components/SidebarTagComponent.vue";
import ProjectAttributeEditModal from "@/Layouts/Components/ProjectAttributeEditModal.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import TeamTooltip from "@/Layouts/Components/TeamTooltip.vue";
import ProjectEditTeamModal from "@/Pages/Projects/Components/ProjectEditTeamModal.vue";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";
import ProjectTeamComponent from "@/Pages/Projects/Components/ProjectTeamComponent.vue";
import ProjectAttributesComponent from "@/Pages/Projects/Components/ProjectAttributesComponent.vue";

export default {
    mixins: [Permissions, IconLib],
    props: [
        'project',
        'projectMembers',
        'projectCategories',
        'projectGenres',
        'projectSectors',
        'categories',
        'sectors',
        'genres',
        'projectCategoryIds',
        'projectGenreIds',
        'projectSectorIds'
    ],
    components: {
        ProjectEditTeamModal,
        TeamTooltip,
        TeamIconCollection,
        UserTooltip,
        SidebarTagComponent,
        PencilAltIcon,
        TagComponent,
        ProjectAttributeEditModal,
        ProjectTeamComponent,
        ProjectAttributesComponent
    },
    data() {
        return {
            showTeamModal: false,
        }
    },
    methods: {
        projectMembersWriteAccess: function () {
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
}
</script>
