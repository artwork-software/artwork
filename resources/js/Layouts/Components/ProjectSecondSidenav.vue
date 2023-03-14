<template>
    <div class="w-full mt-24">
        <div class="w-full pb-10 mb-5 border-b-2 border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="mb-3 xWhiteBold">Projektteam</h2>
                <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                               @click="showTeamModal = true"/>
            </div>
            <div class="flex w-full mt-2 flex-wrap">
                <span
                    class="flex font-black w-full xxsLightSidebar subpixel-antialiased tracking-widest">PROJEKTLEITUNG</span>
                <div class="flex flex-wrap mt-2 -mr-3" v-for="user in this.project.project_managers">
                    <img :data-tooltip-target="user?.id" :src="user?.profile_photo_url" :alt="user?.name"
                         class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                    <UserTooltip :user="user"/>
                </div>
            </div>
            <div class="flex w-full mt-2 flex-wrap">
                <span class="flex font-black xxsLightSidebar w-full subpixel-antialiased tracking-widest uppercase">Projekteam</span>
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
                    <div class="flex -mr-3 mt-2" v-for="user in projectMembers">
                        <img :data-tooltip-target="user?.id" :src="user?.profile_photo_url" :alt="user?.name"
                             class="rounded-full ring-white ring-2 h-11 w-11 object-cover"/>
                        <UserTooltip :user="user"/>
                    </div>
                </div>
            </div>
        </div>
        <div class="w-full pb-10 mb-5 border-b-2 border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="mb-3 xWhiteBold">Projekteigenschaften</h2>
                <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                               @click="openProjectAttributeEditModal"/>
            </div>
            <div class="flex mt-3">
                <div>
                    <SidebarTagComponent v-for="category in projectCategories"
                                         :displayed-text="category.name" :property="category"
                                         :hide-x="true"></SidebarTagComponent>
                    <SidebarTagComponent v-for="genre in projectGenres"
                                         :displayed-text="genre.name" :property="genre"
                                         :hide-x="true"></SidebarTagComponent>
                    <SidebarTagComponent v-for="sector in projectSectors"
                                         :displayed-text="sector.name" :property="sector"
                                         :hide-x="true"></SidebarTagComponent>
                </div>
            </div>
        </div>
        <div class="w-full flex items-center mb-4">
            <div class="xWhiteBold">Eintritt & Anmeldung</div>
            <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                           @click="openEntranceModal"/>
        </div>
        <div>
            <div class="text-secondary text-sm mb-1">Gäste:
                {{ project.num_of_guests ? project.num_of_guests : 'Nicht definiert' }}
            </div>
            <div class="text-secondary text-sm mb-1">Eintritt:
                {{ project.entry_fee ? project.entry_fee : 'Nicht definiert' }}
            </div>
            <div class="text-secondary text-sm mb-1">Anmeldung erforderlich:
                {{ project.registration_required ? 'Ja' : 'Nein' }}
            </div>
            <div v-if="project.registration_required">
                <div class="text-secondary text-sm mb-1">Anmeldung über:
                    {{ project.register_by ? project.register_by : 'Nicht definiert' }}
                </div>
                <div class="text-secondary text-sm mb-1">Anmeldefrist:
                    {{ project.registration_deadline ? project.registration_deadline : 'Keine Frist' }}
                </div>
            </div>
            <div class="text-secondary text-sm">Geschlossene Gesellschaft: {{
                    project.closed_society ? 'Ja' : 'Nein'
                }}
            </div>
        </div>
    </div>
    <ProjectEntranceModal :show="show" :close-modal="closeEntranceModal" :project="project"/>
    <ProjectAttributeEditModal :show="showAttributeEditModal" @closed="closeProjectAttributeEditModal"
                               :project="project" :projectCategoryIdArray="projectCategoryIds"
                               :projectSectorIdArray="projectSectorIds" :projectGenreIdArray="projectGenreIds"
                               :categories="categories" :sectors="sectors" :genres="genres"/>
    <!-- Change Project Team Modal -->
    <ProjectEditTeamModal :show="showTeamModal" @closed="showTeamModal = false" :assigned-users="project.users"
                          :project-manager-ids="projectManagerIds" :departments="project.departments"
                          :project-id="project.id"/>
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

export default {
    props: ['project', 'projectMembers', 'projectManagerIds', 'projectCategories', 'projectGenres', 'projectSectors', 'categories', 'sectors', 'genres', 'projectCategoryIds', 'projectGenreIds', 'projectSectorIds'],
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
        }
    }
}


</script>

<style scoped>

</style>
