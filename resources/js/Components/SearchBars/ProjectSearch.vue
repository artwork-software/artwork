<template>
    <div class="relative">
        <div class="my-auto w-full relative">
            <BaseInput
                id="project_search"
                v-model="project_search_query"
                :label="label"
                class="w-full"
                @focus="project_search_query = ''"/>
        </div>
        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="projects.length > 0" class="absolute rounded-lg z-10 w-full max-h-60 bg-artwork-navigation-background shadow-lg text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                <div class="border-gray-200">
                    <div v-for="(project, index) in projects" :key="index" class="flex items-center cursor-pointer">
                        <div v-if="checkIfMustListed(project)">
                            <div class="flex-1 text-sm py-4" @click="selectProject(project)">
                                <p class="font-bold px-4 flex text-white items-center hover:border-l-4 hover:border-l-success">
                                    <span class="ml-2 truncate">{{ project.name }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import IconLib from "@/Mixins/IconLib.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default {
    name: "ProjectSearch",
    mixins: [IconLib],
    components: {BaseInput, AlertComponent, TextInputComponent, TeamIconCollection},
    data() {
        return {
            project_search_query: '',
            projects: []
        }
    },
    props: {
        label: {
            type: String,
            default: 'Search for projects'
        },
        noProjectGroups: {
            type: Boolean,
            default: false
        },
        getFirstLastEvent: {
            type: Boolean,
            default: false
        },
        onlyProjectGroups: {
            type: Boolean,
            default: false
        }
    },
    emits: ['project-selected'],
    methods: {
        selectProject(selectedProject) {
            this.$emit('project-selected', selectedProject);
            this.project_search_query = '';
        },
        checkIfMustListed(project) {
            if (this.noProjectGroups && !project.is_group) {
                return true;
            }

            if (this.onlyProjectGroups && project.is_group) {
                return true;
            }

            return true;
        }
    },
    watch: {
        project_search_query: {
            handler() {
                axios.post(route('project.scoutSearch'),{
                    project_search: this.project_search_query,
                    get_first_last_event: this.getFirstLastEvent,
                    wantsJson: true,
                }).then(response => {
                    this.projects = response.data;
                });
            },
            deep: true
        }
    }
}
</script>
