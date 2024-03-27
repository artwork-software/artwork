<template>
    <div class="w-full mt-24">
        <div class="w-full pb-10 mb-5 border-b-2 border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="mb-3 xWhiteBold">{{ $t('Project team') }}</h2>
                <IconEdit class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                               @click="showTeamModal = true"
                               v-if="projectMembersWriteAccess()"
                />
            </div>
            <div class="flex w-full mt-2 flex-wrap">
                <span
                    class="flex font-black w-full xxsLightSidebar subpixel-antialiased tracking-widest uppercase">
                    {{ $t('Project management') }}
                </span>
                <div class="flex flex-wrap mt-2 -mr-3" v-for="user in this.project.project_managers">
                    <img :data-tooltip-target="user?.id" :src="user?.profile_photo_url" :alt="user?.name"
                         class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                    <UserTooltip :user="user"/>
                </div>
            </div>
            <div class="flex w-full mt-2 flex-wrap">
                <span class="flex font-black xxsLightSidebar w-full subpixel-antialiased tracking-widest uppercase">
                    {{ $t('Project team') }}
                </span>
                <div class="flex w-full">
                    <div class="flex" v-if="this.project.departments !== []">
                        <div class="flex mt-2 -mr-3" v-for="department in this.project.departments">
                            <TeamIconCollection :data-tooltip-target="department.name"
                                                :iconName="department.svg_name"
                                                :alt="department.name"
                                                class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                            <TeamTooltip :team="department"/>
                        </div>
                    </div>
                    <div class="flex -mr-3 mt-2" v-for="user in this.onlyTeamMember">
                        <img :data-tooltip-target="user?.id" :src="user?.profile_photo_url" :alt="user?.name"
                             class="rounded-full ring-white ring-2 h-11 w-11 object-cover"/>
                        <UserTooltip :user="user"/>
                    </div>
                </div>
            </div>
        </div>
        <div class="w-full pb-10 mb-5 border-b-2 border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="mb-3 xWhiteBold">{{ $t('Project properties') }}</h2>
                <IconEdit class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                               @click="openProjectAttributeEditModal"
                               v-if="projectMembersWriteAccess()"
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

    <ProjectEditTeamModal :show="this.showTeamModal"
                          :assigned-users="this.project.users"
                          :assigned-departments="this.project.departments"
                          :project-id="this.project.id"
                          :userIsProjectManager="this.userIsProjectManager"
                          @closed="this.showTeamModal = false"
    />
    <ProjectEntranceModal :show="show" :close-modal="closeEntranceModal" :project="project"/>

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
        ProjectAttributeEditModal
    },
    data() {
        return {
            show: false,
            showAttributeEditModal: false,
            showTeamModal: false,
        }
    },
    computed: {
        onlyTeamMember() {
            return this.project.users.filter(user => user.pivot_is_manager === false);
        }
    },
    methods: {
        openEntranceModal() {
            this.show = true;
        },
        openProjectAttributeEditModal() {
            this.showAttributeEditModal = true;
        },
        closeProjectAttributeEditModal() {
            this.showAttributeEditModal = false;
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
        userIsProjectManager: function () {
            let managerIdArray = [];
            this.project.project_managers.forEach(manager => {
                    managerIdArray.push(manager.id)
                }
            )
            return managerIdArray.includes(this.$page.props.user.id);
        }
    }
}
</script>
