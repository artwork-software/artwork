<template>
    <div>
        <div class="flex items-center gap-x-5">
            <BasePageTitle title="Project team" :white-text="inSidebar" />
            <IconEdit class=" w-5 h-5 rounded-full " :class="inSidebar ? 'text-white' : 'text-artwork-buttons-context'"
                      @click="showTeamModal = true"
                      v-if="this.canEditComponent && (projectMembersWriteAccess() || hasAdminRole())"
            />
        </div>
        <div v-if="loadError" class="text-xs text-rose-600 mt-2">
            {{ loadError }}
        </div>
        <div v-else-if="loadingTeam" class="text-xs text-secondary mt-2">
            {{ $t('Loading data...') }}
        </div>
        <div class="flex w-full mt-2 flex-wrap mb-3">
            <span
                class="flex font-black w-full xxsLightSidebar subpixel-antialiased tracking-widest uppercase">
                {{ $t('Project management') }}
            </span>
            <div class="flex flex-wrap mt-2 -mr-3" v-for="user in (teamProject.project_managers || [])" :key="user.id">
                <UserPopoverTooltip :user="user" width="11" height="11" classes="border-2 border-white rounded-full" />
            </div>
        </div>
        <div class="mb-3">
            <div v-for="role in (teamProject.projectRoles || [])" :key="role.id" class="mb-3">
                 <div v-if="checkRoleHasUser(role)">
                     <span class="flex font-black w-full xxsLightSidebar subpixel-antialiased tracking-widest uppercase">
                        {{ role.name }}
                     </span>
                     <div class="flex">
                         <div class="flex mt-2" v-for="(user, index) in (teamProject.usersArray || [])" :key="`${role.id}-${user.id}`">
                             <div v-if="user?.pivot_roles?.includes(role.id)" :class="index !== 0 ? '' : '-mr-3'">
                                 <UserPopoverTooltip :user="user" width="11" height="11" classes="border-2 border-white rounded-full" />
                             </div>
                         </div>
                     </div>
                 </div>
            </div>
        </div>
        <div class="flex w-full mt-2 flex-wrap mb-4">
            <span class="flex font-black xxsLightSidebar w-full subpixel-antialiased tracking-widest uppercase">
                {{ $t('Project team') }}
            </span>
            <div class="flex w-full">
                <div class="flex" v-if="teamProject.departments?.length > 0">
                    <div class="flex mt-2 -mr-3" v-for="department in teamProject.departments" :key="department.id">
                        <TeamIconCollection :data-tooltip-target="department.name"
                                            :iconName="department.svg_name"
                                            :alt="department.name"
                                            class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                        <TeamTooltip :team="department"/>
                    </div>
                </div>
                <div class="flex -mr-3 mt-2" v-for="user in (teamProject.usersArray || [])" :key="user.id">
                    <UserPopoverTooltip :user="user" width="11" height="11" classes="border-2 border-white rounded-full" />
                </div>
            </div>
        </div>
        <!-- Modal -->
        <ProjectEditTeamModal :show="this.showTeamModal"
                              :assigned-users="teamProject?.usersArray ? teamProject.usersArray : []"
                              :assigned-departments="teamProject.departments ? teamProject.departments : []"
                              :project-id="currentProjectId()"
                              :userIsProjectManager="this.userIsProjectManager()"
                              @closed="this.showTeamModal = false"
                              :projectRoles="teamProject.projectRoles || []"
        />
    </div>

</template>

<script>
import {defineComponent} from 'vue';
import axios from 'axios';
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import TeamTooltip from "@/Layouts/Components/TeamTooltip.vue";
import IconLib from "@/Mixins/IconLib.vue";
import Permissions from "@/Mixins/Permissions.vue";
import ProjectEditTeamModal from "@/Pages/Projects/Components/ProjectEditTeamModal.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ToolTipDefault from "@/Components/ToolTips/ToolTipDefault.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";

export default defineComponent({
    mixins: [
        Permissions,
        IconLib
    ],
    components: {
        BasePageTitle,
        ToolTipDefault,
        UserPopoverTooltip,
        ProjectEditTeamModal,
        TeamTooltip,
        UserTooltip,
        TeamIconCollection
    },
    props: {
        project: {
            type: Object,
            default: null
        },
        projectId: {
            type: [Number, String],
            default: null
        },
        inSidebar: {
            type: Boolean,
            default: false
        },
        canEditComponent: {
            type: Boolean,
            default: false
        }
    },
    data() {
        const hasInitialTeam =
            this.project &&
            Array.isArray(this.project.usersArray) &&
            this.project.usersArray.length > 0;
        return {
            showTeamModal: false,
            loadingTeam: false,
            loadError: null,
            localProject: hasInitialTeam ? this.project : null
        };
    },
    computed: {
        teamProject() {
            return this.localProject ?? this.project ?? {};
        },
        onlyTeamMember() {
            if (!this.teamProject?.usersArray) {
                return [];
            }
            const managerIds = (this.teamProject?.project_managers ?? []).map(manager => manager.id);
            return this.teamProject.usersArray.filter(user => !managerIds.includes(user.id));
        }
    },
    watch: {
        project: {
            deep: true,
            immediate: true,
            handler(newProject) {
                if (this.projectHasTeamData(newProject)) {
                    this.localProject = newProject;
                }
            }
        }
    },
    mounted() {
        this.ensureTeamData();
    },
    methods: {
        async ensureTeamData() {
            if (this.projectHasTeamData(this.teamProject)) {
                return;
            }

            const id = this.currentProjectId();
            if (!id) {
                return;
            }

            this.loadingTeam = true;
            this.loadError = null;

            try {
                const response = await axios.get(route('projects.tabs.team', {project: id}));
                this.localProject = response.data.project ?? null;
            } catch (e) {
                this.loadError = this.$t
                    ? this.$t('Teamdaten konnten nicht geladen werden.')
                    : 'Failed to load team data.';
            } finally {
                this.loadingTeam = false;
            }
        },
        projectHasTeamData(project) {
            return project?.usersArray && project.usersArray.length > 0;
        },
        currentProjectId() {
            return this.project?.id ?? this.projectId ?? this.teamProject?.id ?? null;
        },
        projectMembersWriteAccess() {
            if (this.$can('write projects')) {
                return true;
            }

            if (!this.teamProject?.write_auth || this.teamProject.write_auth.length === 0) {
                return false;
            }

            let canWriteArray = [];
            this.teamProject.write_auth.forEach(write => {
                    canWriteArray.push(write.id)
                }
            )
            return canWriteArray.includes(this.$page.props.auth.user.id);
        },
        userIsProjectManager() {
            let managerIdArray = [];
            (this.teamProject?.project_managers ?? []).forEach(manager => {
                    managerIdArray.push(manager.id)
                }
            )
            return managerIdArray.includes(this.$page.props.auth.user.id);
        },
        checkRoleHasUser(role) {
            return (this.teamProject?.usersArray ?? []).some(user => user.pivot_roles.includes(role.id));
        }
    }
});
</script>
