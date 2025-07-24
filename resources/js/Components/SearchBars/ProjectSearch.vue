<template>
    <div class="relative">
        <div class="my-auto w-full relative">
            <BaseInput
                id="project_search"
                v-model="projectSearchQuery"
                :label="label"
                class="w-full"
                @focus="() => projectSearchQuery = ''"
            />
        </div>

        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showDropdown" class="absolute rounded-lg z-10 w-full max-h-60 bg-artwork-navigation-background shadow-lg text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                <div class="border-gray-200" v-if="filteredProjects.length > 0">
                    <div v-for="(project, index) in filteredProjects" :key="index" class="flex items-center cursor-pointer">
                        <div class="flex-1 text-sm py-4" @click="selectProject(project)">
                            <p class="font-bold px-4 flex text-white items-center hover:border-l-4 hover:border-l-success">
                                <span class="ml-2 truncate">{{ project.name }}</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <BaseAlertComponent
                        v-if="projectSearchQuery.trim() !== ''"
                        message="No Projects or Groups found"
                        type="info"
                        use-translation
                        class="!mb-0"
                    />
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

// Props
const props = defineProps({
    label: {
        type: String,
        default: 'Search for projects'
    },
    noProjectGroups: {
        type: Boolean,
        default: false
    },
    onlyProjectGroups: {
        type: Boolean,
        default: false
    },
    getFirstLastEvent: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['project-selected']);

const projectSearchQuery = ref('');
const projects = ref([]);
const checkIfMustListed = (project) => {
    if (props.noProjectGroups) return !project.is_group;
    if (props.onlyProjectGroups) return !!project.is_group;
    return true;
};

const selectProject = (project) => {
    emit('project-selected', project);
    projectSearchQuery.value = '';
    projects.value = [];
};

const filteredProjects = computed(() => {
    return projects.value.filter(checkIfMustListed);
});

const showDropdown = computed(() => {
    return projectSearchQuery.value.trim() !== '' || filteredProjects.value.length > 0;
});

watch(projectSearchQuery, async (newValue) => {
    if (newValue.trim() === '') {
        projects.value = [];
        return;
    }

    try {
        const response = await axios.post(route('project.scoutSearch'), {
            project_search: newValue,
            get_first_last_event: props.getFirstLastEvent,
            wantsJson: true
        });
        projects.value = response.data;
    } catch (error) {
        console.error('Project search failed', error);
        projects.value = [];
    }
});
</script>
