<template>
        <div class="container mb-12 mx-auto px-6 flex flex-col items-start pl-12 md:pl-0 md:items-center">
            <div class="flex-col md:flex-row flex items-center md:justify-center">
                <!--Code Block for white tooltip starts-->
                <div class="relative mt-20 md:mt-0" @mouseover="showTooltip(user.id)" @mouseout="hideTooltip(user.id)">
                    <div class="mr-2 cursor-pointer">
                        <svg aria-haspopup="true" xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-info-circle" width="25" height="25" viewBox="0 0 24 24" stroke-width="1.5" stroke="#A0AEC0" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" />
                            <circle cx="12" cy="12" r="9" />
                            <line x1="12" y1="8" x2="12.01" y2="8" />
                            <polyline points="11 12 12 12 12 16 13 16" />
                        </svg>
                    </div>
                    <div id="tooltip1" role="tooltip" class="z-20 -mt-20 w-64 absolute transition duration-150 ease-in-out left-0 ml-8 shadow-lg bg-white p-4 rounded">
                        <p class="text-sm font-bold text-gray-800 pb-1">Keep track of follow ups</p>
                        <p class="text-xs leading-4 text-gray-600 pb-3">Reach out to more prospects at the right moment.</p>
                        <div class="flex justify-between">
                            <div class="flex items-center">
                                <span class="text-xs font-bold text-indigo-700">Step 1 of 4</span>
                            </div>
                            <div class="flex items-center">
                                <span class="text-xs text-gray-600 underline mr-2 cursor-pointer">Skip Tour</span>
                                <button class="focus:outline-none bg-indigo-700 transition duration-150 ease-in-out hover:bg-indigo-600 rounded text-white px-5 py-1 text-xs">Next</button>
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
    props: ['user'],
    methods: {
        showTooltip(flag) {
            document.getElementById("tooltip" + flag).classList.remove("opacity-0");
        },
        hideTooltip(flag) {
            document.getElementById("tooltip" + flag).classList.add("opacity-0");
        },
    },
}
</script>

<style scoped>

</style>
