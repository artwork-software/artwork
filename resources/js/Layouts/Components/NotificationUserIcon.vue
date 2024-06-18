<template>
    <div>
    <img :data-tooltip-target="user.id" class="h-6 w-6 rounded-full object-cover ml-2 my-auto items-center flex -mt-1"
         :src="user.profile_photo_url"
         alt=""/>
    <UserTooltip :user="user"/>
    </div>
</template>

<script>
import Button from "@/Jetstream/Button.vue";
import {XIcon} from "@heroicons/vue/outline";
import {Link} from "@inertiajs/vue3";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import Permissions from "@/Mixins/Permissions.vue";

export default {
    mixins: [Permissions],
    name: "TagComponent",
    components: {Button, XIcon, Link, UserTooltip},
    props: ['user'],
}
</script>

<style scoped>

</style>
