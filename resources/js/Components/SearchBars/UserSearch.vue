<script>
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {router} from "@inertiajs/vue3";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import IconLib from "@/Mixins/IconLib.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";

export default {
    name: "UserSearch",
    mixins: [IconLib],
    components: {AlertComponent, TextInputComponent, TeamIconCollection},
    data() {
        return {
            user_search_query: '',
            users: []
        }
    },
    props: {
        onlyManager: {
            type: Boolean,
            default: false
        },
        onlyTeam: {
            type: Boolean,
            default: false
        },
        teamMember: {
            type: Object,
            required: false,
            default: []
        },
        label: {
            type: String,
            default: 'Search for users'
        }
    },
    computed: {
        projectManagementText() {
            return this.$t('Only users who have the "Project management" right are displayed');
        }
    },
    emits: ['user-selected'],
    methods: {
        selectUser(user) {
            this.$emit('user-selected', user);
            this.user_search_query = '';
        },
        checkIfOnlyProp(user) {
            if (this.onlyManager) {
                return user.project_manager_permission;
            }
            if (this.onlyTeam) {
                return this.teamMember.includes(user.id)
            }
            return true;
        }
    },
    watch: {
        user_search_query: {
            handler() {
                axios.post(route('user.scoutSearch'),{
                    user_search: this.user_search_query,
                    wantsJson: true,
                }).then(response => {
                    this.users = response.data;
                });
            },
            deep: true
        }
    }
}
</script>

<template>
    <div class="relative">
        <div class="my-auto w-full relative">
            <TextInputComponent
                id="userSearch"
                v-model="user_search_query"
                :label="$t(label)"
                class="w-full"
                @focus="user_search_query = ''"/>
            <div class="absolute right-2 top-3">
                <IconX class="h-6 w-6 text-gray-400" v-if="user_search_query.length > 0" @click="user_search_query = ''"/>
            </div>
        </div>
        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="users?.length > 0" class="absolute rounded-lg z-10 w-full max-h-60 bg-artwork-navigation-background shadow-lg text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                <div class="border-gray-200">
                    <div v-for="(user, index) in users" :key="index" class="flex items-center cursor-pointer">
                        <div class="flex-1 text-sm py-4" @click="selectUser(user)" v-if="checkIfOnlyProp(user)">
                            <p class="font-bold px-4 flex text-white items-center hover:border-l-4 hover:border-l-success">
                                <img :src="user.profile_photo_url" :alt="user.name" class="rounded-full h-8 w-8 object-cover"/>
                                <span class="ml-2 truncate">{{ user.first_name }} {{ user.last_name }}</span>
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

<style scoped>

</style>
