<template>
    <div>
        <div class="flex items-center gap-x-5">
            <h2 class=" xWhiteBold">{{ $t('Project team') }}</h2>
            <IconEdit class=" w-5 h-5 rounded-full " :class="inSidebar ? 'text-white' : 'text-artwork-buttons-context'"
                      @click="showTeamModal = true"
                      v-if="this.canEditComponent && (projectMembersWriteAccess() || hasAdminRole())"
            />
        </div>
        <div class="flex w-full mt-2 flex-wrap mb-3">
            <span
                class="flex font-black w-full xxsLightSidebar subpixel-antialiased tracking-widest uppercase">
                {{ $t('Project management') }}
            </span>
            <div class="flex flex-wrap mt-2 -mr-3" v-for="user in project.project_managers">
                <UserPopoverTooltip :user="user" width="11" height="11" classes="border-2 border-white rounded-full" />
            </div>
        </div>
        <div class="mb-3">
            <div v-for="role in project.projectRoles" class="mb-3">
                 <div v-if="checkRoleHasUser(role)">
                     <span class="flex font-black w-full xxsLightSidebar subpixel-antialiased tracking-widest uppercase">
                        {{ role.name }}
                     </span>
                     <div class="flex">
                         <div class="flex mt-2" v-for="(user, index) in project.usersArray">
                             <div class="" v-if="user?.pivot_roles?.includes(role.id)" :class="index !== 0 ? '' : '-mr-3'">
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
                <div class="flex" v-if="this.project.departments.length > 0">
                    <div class="flex mt-2 -mr-3" v-for="department in this.project.departments">
                        <TeamIconCollection :data-tooltip-target="department.name"
                                            :iconName="department.svg_name"
                                            :alt="department.name"
                                            class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                        <TeamTooltip :team="department"/>
                    </div>
                </div>
                <div class="flex -mr-3 mt-2" v-for="user in project.usersArray">
                    <UserPopoverTooltip :user="user" width="11" height="11" classes="border-2 border-white rounded-full" />
                </div>
            </div>
        </div>
        <!-- Modal -->
        <ProjectEditTeamModal :show="this.showTeamModal"
                              :assigned-users="this.project.usersArray"
                              :assigned-departments="this.project.departments"
                              :project-id="this.project.id"
                              :userIsProjectManager="this.userIsProjectManager"
                              @closed="this.showTeamModal = false"
                              :projectRoles="this.project.projectRoles"
        />
    </div>

</template>

<script>
import {defineComponent} from 'vue';
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import TeamTooltip from "@/Layouts/Components/TeamTooltip.vue";
import IconLib from "@/Mixins/IconLib.vue";
import Permissions from "@/Mixins/Permissions.vue";
import ProjectEditTeamModal from "@/Pages/Projects/Components/ProjectEditTeamModal.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ToolTipDefault from "@/Components/ToolTips/ToolTipDefault.vue";

export default defineComponent({
    mixins: [
        Permissions,
        IconLib
    ],
    components: {
        ToolTipDefault,
        UserPopoverTooltip,
        ProjectEditTeamModal,
        TeamTooltip,
        UserTooltip,
        TeamIconCollection
    },
    props: [
        'project',
        'inSidebar',
        'canEditComponent'
    ],
    data() {
        return {
            showTeamModal: false
        };
    },
    computed: {
        onlyTeamMember() {
            // return all users that are not project managers
            return this.project.usersArray.filter(user => {
                let managerIdArray = [];
                this.project.project_managers.forEach(manager => {
                        managerIdArray.push(manager.id)
                    }
                )
                return !managerIdArray.includes(user.id);
            });
        }
    },
    methods: {
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
        userIsProjectManager() {
            let managerIdArray = [];
            this.project.project_managers.forEach(manager => {
                    managerIdArray.push(manager.id)
                }
            )
            return managerIdArray.includes(this.$page.props.user.id);
        },
        checkRoleHasUser(role) {
            return this.project.usersArray.some(user => user.pivot_roles.includes(role.id));
        }
    }
});
</script>
