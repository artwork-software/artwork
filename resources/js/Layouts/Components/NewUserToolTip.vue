<template>
        <div class="flex flex-col items-start md:pl-0 md:items-center" :id="id">
            <div class="flex-col md:flex-row flex items-center md:justify-center">
                <!--Code Block for white tooltip starts-->
                <div class="z-20 mt-20 md:mt-0">
                    <div class="mr-1 cursor-pointer">
                        <img :src="user.profile_photo_url" alt="" class="mx-auto shrink-0 flex object-cover rounded-full" :class="['h-' + this.height, 'w-' + this.width]" @mouseover="showTooltip(id)" @mouseout="hideTooltip(id)">
                    </div>
                    <div :id="'tooltip' + id" role="tooltip" class="w-96 hidden z-20 -mt-40 absolute transition duration-150 ease-in-out left-0 ml-8 shadow-lg p-4 rounded text-secondary bg-primary">
                        <div class="grid grid-cols-4 gap-x-2 flex items-center">
                            <div class="col-span-1 shrink-0 flex">
                                <img class="mx-auto shrink-0 flex  h-14 w-14 mt-2 object-cover rounded-full"
                                     :src="user.profile_photo_url"
                                     alt=""/>
                            </div>
                            <div class="col-span-3">
                                <div class=" font-black font-lexend text-white text-lg">
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
                <!--Code Block for white tooltip ends-->
            </div>
        </div>
</template>


<script>
export default {
    name: "NewUserToolTip",
    props: ['user', 'id', 'height', 'width'],
    methods: {
        showTooltip(flag) {
            document.getElementById("tooltip" + flag).classList.remove("hidden");
        },
        hideTooltip(flag) {
            document.getElementById("tooltip" + flag).classList.add("hidden");
        },
    },
}
</script>

<style scoped>

</style>
