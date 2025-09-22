<template>
    <div class="relative">
        <div class="my-auto w-full relative">
            <BaseInput
                id="userSearch"
                v-model="user_search_query"
                :label="label"
                class="w-full"
                @focus="user_search_query = ''"
                :disabled="disabled"
            />
        </div>
        <transition leave-active-class="transition ease-in duration-100" leave-fromÏ-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="users?.length > 0" class="absolute rounded-lg z-30 w-full max-h-60 bg-artwork-navigation-background shadow-lg text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                <div class="border-gray-200">
                    <div v-for="(user, index) in users" :key="index" class="flex items-center cursor-pointer">
                        <div class="flex-1 text-sm py-4" @click="selectUser(user)" v-if="checkIfOnlyProp(user)">
                            <p class="font-bold px-4 flex text-white items-center hover:border-l-4 hover:border-l-success">
                                <img :src="user.profile_photo_url" :alt="user.name" class="rounded-full h-8 w-8 object-cover"/>
                                <span v-if="user.provider_name" class="ml-2 truncate">{{ user.provider_name }}</span>
                                <span v-else class="ml-2 truncate">{{ user.first_name }} {{ user.last_name }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
    <AlertComponent
        v-if="onlyManager"
        class="mt-1.5"
        show-icon
        type="info"
        icon-size="h-4 w-4"
        :text="projectManagementText"
    />
    <AlertComponent
        v-if="onlyTeam"
        class="mt-1.5"
        show-icon
        type="info"
        icon-size="h-4 w-4"
        :text="$t('Only users who are part of the project are displayed')"
    />
</template>

<script setup lang="ts">
import { ref, computed, watch, getCurrentInstance } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

type User = {
    id: number;
    name?: string;
    first_name?: string;
    last_name?: string;
    provider_name?: string;
    profile_photo_url?: string;
    project_manager_permission?: boolean;
    use_chat?: boolean;
    manager_type?: any;
};

type Craft = {
    managersToBeAssigned?: Array<{ id: number; manager_type?: any }>;
};

const props = defineProps<{
    teamMember?: number[];
    label?: string;
    onlyManager?: boolean;
    onlyTeam?: boolean;
    searchWorkers?: boolean;
    dontCloseOnSelect?: boolean;
    onlyUseChatUsers?: boolean;
    withoutSelf?: boolean;
    currentCraft?: Craft;
    disabled?: boolean;
}>();
const emit = defineEmits(['user-selected']);

const user_search_query = ref<string>('');
const users = ref<User[]>([]);
const blockSearchWorkers = ref<boolean>(false);
const page = usePage();

const { proxy } = getCurrentInstance()!;
const $t = proxy.$t as (key: string) => string;

const projectManagementText = computed(() => {
    return $t
        ? $t('Only users who have the "Project management" right are displayed')
        : 'Only users who have the "Project management" right are displayed';
});

function selectUser(user: User) {
    emit('user-selected', user);
    if (props.dontCloseOnSelect) return;
    user_search_query.value = '';
}

function checkIfOnlyProp(user: User) {
        if (props.onlyManager) return user.project_manager_permission;
        if (props.onlyTeam && props.teamMember) return props.teamMember.includes(user.id);
        if (props.onlyUseChatUsers) return user.use_chat;
        if (props.withoutSelf) {
            const authUserId = (page.props as any)?.auth?.user?.id;
            return user.id !== authUserId;
        }
        return true;
}

function closeSearch() {
    user_search_query.value = '';
    if (props.searchWorkers) {
        blockSearchWorkers.value = true;
        users.value = [];
    }
}

watch(user_search_query, async (newVal) => {
    if (blockSearchWorkers.value) {
        blockSearchWorkers.value = false;
        return;
    }
    let desiredRoute = props.searchWorkers ? route('worker.scoutSearch') : route('user.scoutSearch');
    try {
        const response = await axios.post(desiredRoute, {
            user_search: newVal,
            wantsJson: true,
        });
        let resultUsers: User[] = response.data;
        if (props.searchWorkers && props.currentCraft?.managersToBeAssigned) {
            resultUsers = resultUsers.filter(user => {
                return props.currentCraft.managersToBeAssigned?.find(
                    (currentManagingWorker) => currentManagingWorker.id === user.id && user.manager_type === currentManagingWorker
                );
            });
        }
        users.value = resultUsers;
    } catch (e) {
        users.value = [];
    }
}, { deep: true });
</script>
