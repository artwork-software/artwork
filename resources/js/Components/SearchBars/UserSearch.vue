<script>
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {router} from "@inertiajs/vue3";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "UserSearch",
    mixins: [IconLib],
    components: {TextInputComponent, TeamIconCollection},
    data() {
        return {
            user_search_query: '',
            users: []
        }
    },
    emits: ['user-selected'],
    methods: {
        selectUser(user) {
            this.$emit('user-selected', user);
            this.user_search_query = '';
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
                v-model="user_search_query"
                :label="$t('Search for users')"
                class="w-full"
                @focus="user_search_query = ''"/>
            <div class="absolute right-2 top-1.5">
                <IconX class="h-6 w-6 text-gray-400" v-if="user_search_query.length > 0" @click="user_search_query = ''"/>
            </div>
        </div>
        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="users?.length > 0" class="absolute z-10 mt-1 w-full max-h-60 bg-artwork-navigation-background shadow-lg text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                <div class="border-gray-200">
                    <div v-for="(user, index) in users" :key="index" class="flex items-center cursor-pointer">
                        <div class="flex-1 text-sm py-4" @click="selectUser(user)">
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
</template>

<style scoped>

</style>
