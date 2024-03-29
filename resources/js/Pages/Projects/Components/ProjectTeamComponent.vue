<template>
    <div>
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
                <img :data-tooltip-target="user.id"
                     :src="user.profile_photo_url"
                     :alt="user.name"
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
                    <img :data-tooltip-target="user.id"
                         :src="user.profile_photo_url"
                         :alt="user.name"
                         class="rounded-full ring-white ring-2 h-11 w-11 object-cover"/>
                    <UserTooltip :user="user"/>
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
    </div>
</template>

<script>
import {defineComponent} from 'vue';
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import TeamTooltip from "@/Layouts/Components/TeamTooltip.vue";
import IconLib from "@/mixins/IconLib.vue";
import Permissions from "@/mixins/Permissions.vue";
import ProjectEditTeamModal from "@/Pages/Projects/Components/ProjectEditTeamModal.vue";

export default defineComponent({
    mixins: [
        Permissions,
        IconLib
    ],
    components: {
        ProjectEditTeamModal,
        TeamTooltip,
        UserTooltip,
        TeamIconCollection
    },
    props: [
        'project'
    ],
    data() {
        return {
            showTeamModal: false
        };
    },
    computed: {
        onlyTeamMember() {
            return this.project.users.filter(user => user.pivot_is_manager === false);
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
        }
    }
});
</script>
