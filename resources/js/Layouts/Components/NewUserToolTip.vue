<template>
    <div class="relative">
        <div class="flex flex-col items-start md:pl-0 md:items-center" :id="id">
            <div class="flex justify-center">
                <div class="mt-20 md:mt-0">
                    <div class="mr-1 cursor-pointer">
                        <img :src="user.profile_photo_url" alt="" class="mx-auto shrink-0 flex object-cover rounded-full" :class="['h-' + this.height, 'w-' + this.width]" @mouseover="showTooltip(id)" @mouseout="hideTooltip(id)">
                    </div>
                </div>
            </div>
        </div>
        <div v-if="type === 1" :id="'tooltip' + id" role="tooltip" class="-right-80 hidden absolute -mt-40 z-100 w-fit transition duration-150 ease-in-out shadow-lg p-4 rounded text-secondary bg-primary">
            <div class="grid grid-cols-4 gap-x-2 flex items-center w-96">
                <div class="col-span-1 shrink-0 flex">
                    <img class="mx-auto shrink-0 flex h-14 w-14 mt-2 object-cover rounded-full" :src="user.profile_photo_url" alt=""/>
                </div>
                <div class="col-span-3">
                    <div class="font-black font-lexend text-white text-lg">
                        {{ user.first_name }} {{ user.last_name }}
                    </div>
                    <div class="-mt-1">
                        {{ user.business }}, {{ user.position }}
                    </div>
                    <div class="mt-2">
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
        </div>
        <div v-else :id="'tooltip' + id" role="tooltip" class="-right-12 hidden absolute -mt-40 z-100 w-fit transition duration-150 ease-in-out shadow-lg p-4 rounded text-secondary bg-primary">
            <div class="grid grid-cols-4 gap-x-2 flex items-center w-96">
                <div class="col-span-1 shrink-0 flex">
                    <img class="mx-auto shrink-0 flex h-14 w-14 mt-2 object-cover rounded-full" :src="user.profile_photo_url" alt=""/>
                </div>
                <div class="col-span-3">
                    <div class="font-black font-lexend text-white text-lg">
                        {{ user.first_name }} {{ user.last_name }}
                    </div>
                    <div class="-mt-1">
                        {{ user.business }}, {{ user.position }}
                    </div>
                    <div class="mt-2">
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
        </div>
    </div>


</template>


<script>
import Permissions from "@/Mixins/Permissions.vue";

export default {
    mixins: [Permissions],
    name: "NewUserToolTip",
    props: ['user', 'id', 'height', 'width','type'],
    methods: {
        showTooltip(id) {
            document.getElementById("tooltip" + id).classList.remove("hidden");
        },
        hideTooltip(id) {
            document.getElementById("tooltip" + id).classList.add("hidden");
        },
    },
}
</script>

<style scoped>

</style>
