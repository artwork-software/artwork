<template>
    <div>
        <div :id="team.name" role="tooltip"
             class="max-w-md inline-block flex flex-wrap absolute invisible z-10 py-3 px-3 text-sm font-medium text-secondary bg-artwork-navigation-background shadow-sm opacity-0 transition-opacity duration-300 tooltip">
            <div class="flex flex-wrap">
                <div class="flex w-full mb-4">
                    <TeamIconCollection class="ml-2  h-14 w-14" :iconName="team.svg_name"/>
                    <div class="ml-5 my-auto w-full justify-start mr-6">
                        <div class="flex my-auto">
                            <p class="text-lg subpixel-antialiased text-white font-lexend font-semibold">{{
                                    team.name
                                }}</p>
                        </div>
                    </div>
                </div>
                <div v-if="team.users" class="my-auto flex -mr-3" v-for="user in team.users.slice(0,9)">
                    <img class="h-9 w-9 rounded-full"
                         :src="user.profile_photo_url"
                         alt=""/>
                    <div v-if="team.users.length > 9" class="flex items-center rounded-full focus:outline-none">
                        <ChevronDownIcon
                            class="ml-1 flex-shrink-0 h-11 w-11 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-artwork-navigation-background"></ChevronDownIcon>
                    </div>
                </div>

            </div>
            <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
    </div>
</template>

<script>

import {ChevronDownIcon} from "@heroicons/vue/outline";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";

export default {
    name: "TeamTooltip",
    components: {
        TeamIconCollection,
        ChevronDownIcon
    },
    props: ['team'],
    data() {
        return {}
    },
    methods: {}
}
</script>

<style scoped>

</style>
