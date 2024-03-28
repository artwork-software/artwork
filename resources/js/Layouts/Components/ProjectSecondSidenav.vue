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
        <div class="w-full flex items-center mb-4">
            <div class="xWhiteBold">{{ $t('Entry & registration') }}</div>
            <IconEdit class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                           @click="openEntranceModal"
                           v-if="projectMembersWriteAccess()"
            />
        </div>
        <div>
            <div class="text-secondary text-sm mb-1">
                {{ $t('Guests') }}: {{ project.num_of_guests ? project.num_of_guests : $t('Not defined') }}
            </div>
            <div class="text-secondary text-sm mb-1">
                {{ $t('Entrance') }}: {{ project.entry_fee ? project.entry_fee : $t('Not defined') }}
            </div>
            <div class="text-secondary text-sm mb-1">
                {{ $t('Registration required') }}: {{ project.registration_required ? $t('Yes') : $t('No') }}
            </div>
            <div v-if="project.registration_required">
                <div class="text-secondary text-sm mb-1">
                    {{ $t('Registration via') }}: {{ project.register_by ? project.register_by : $t('Not defined') }}
                </div>
                <div class="text-secondary text-sm mb-1">
                    {{ $t('Registration deadline') }}:
                    {{ project.registration_deadline ? project.registration_deadline : $t('No deadline') }}
                </div>
            </div>
            <div class="text-secondary text-sm">
                {{ $t('Closed society') }}: {{ project.closed_society ? $t('Yes') : $t('No') }}
            </div>
        </div>
    </div>
    <ProjectEntranceModal :show="show" :close-modal="closeEntranceModal" :project="project"/>
</template>

<script>
import ProjectEntranceModal from "@/Layouts/Components/ProjectEntranceModal.vue";
import {PencilAltIcon} from "@heroicons/vue/outline";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import SidebarTagComponent from "@/Layouts/Components/SidebarTagComponent.vue";
import ProjectAttributeEditModal from "@/Layouts/Components/ProjectAttributeEditModal.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import TeamTooltip from "@/Layouts/Components/TeamTooltip.vue";
import ProjectEditTeamModal from "@/Pages/Projects/Components/ProjectEditTeamModal.vue";
import Permissions from "@/mixins/Permissions.vue";
import IconLib from "@/mixins/IconLib.vue";
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
        ProjectEntranceModal,
        PencilAltIcon,
        TagComponent,
        ProjectAttributeEditModal,
        ProjectTeamComponent,
        ProjectAttributesComponent
    },
    data() {
        return {
            show: false,
            showTeamModal: false,
        }
    },
    methods: {
        openEntranceModal() {
            this.show = true;
        },
        closeEntranceModal() {
            this.show = false;
        },
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
