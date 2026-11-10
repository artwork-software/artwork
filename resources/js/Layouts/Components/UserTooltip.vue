<template>
    <div>
        <div :id="user.id" role="tooltip"
             class="inline-block flex flex-wrap absolute invisible z-50 py-3 px-3 text-sm font-medium text-white/80 bg-surface-inverse shadow-sm opacity-0 transition-opacity duration-300 tooltip">
            <div class="grid grid-cols-4 gap-x-2">
                <div class="col-span-1 shrink-0 flex">
                    <img class="mx-auto shrink-0 flex  h-14 w-14 mt-2 object-cover rounded-full"
                         :class="deactivatedAvatarClasses(user)"
                         :src="user.profile_photo_url"
                         alt=""/>
                </div>
                <div class="col-span-3 ">
                    <div class=" font-black font-lexend text-white text-lg">
                        {{ user.first_name }} {{ user.last_name }}
                    </div>
                    <div class="-mt-1">
                        {{ user.business }}, {{ user.position }}
                    </div>
                    <div v-if="isUserDeactivated(user)" class="mt-2">
                        <DeactivatedAccountBadge on-dark/>
                    </div>
                    <div v-else class="mt-2">
                        {{ user.email }}
                    </div>
                    <div>
                        {{ user.phone_number }}
                    </div>
                </div>
                <div class="col-span-4 mt-2">
                    {{ user.description }}
                </div>
            </div>
            <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
    </div>
</template>

<script>
import {deactivatedAvatarClasses, isUserDeactivated} from "@/Helper/UserDeactivation.js";
import DeactivatedAccountBadge from "@/Layouts/Components/DeactivatedAccountBadge.vue";

export default {
    name: "UserTooltip",
    components: {DeactivatedAccountBadge},
    props: ['user'],
    data() {
        return {}
    },
    methods: {deactivatedAvatarClasses, isUserDeactivated}
}
</script>

<style scoped>

</style>
