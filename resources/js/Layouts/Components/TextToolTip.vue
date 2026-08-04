<template>
        <div class="flex flex-col items-start md:pl-0 md:items-center" :id="id">
            <div class="flex-col md:flex-row flex items-center md:justify-center">
                <!--Code Block for white tooltip starts-->
                <div class="relative md:mt-0">
                    <div class="mr-1 z-0 cursor-pointer text-text-subtle">
                        <IconInfoCircle aria-hidden="true" class="mx-auto shrink-0 flex object-cover rounded-full" :class="['h-' + this.height, 'w-' + this.width]" @mouseover="showTooltip(id)" @mouseout="hideTooltip(id)" />
                    </div>
                    <div :id="'tooltip' + id" role="tooltip" class="w-60 hidden z-30 -ml-60 -mt-12 absolute transition duration-150 ease-in-out left-0 ml-8 shadow-lg p-4 rounded text-text-subtle bg-surface-inverse">
                        <div role="tooltip" class="z-30 text-sm font-medium text-text-subtle bg-surface-inverse rounded-lg shadow-md">
                            {{$t(tooltipText)}}
                            <div class="tooltip-arrow" data-popper-arrow></div>
                        </div>
                    </div>
                </div>
                <!--Code Block for white tooltip ends-->
            </div>
        </div>
</template>


<script>
import {IconInfoCircle} from "@tabler/icons-vue";

export default {
    name: "TextToolTip",
    props: ['id', 'height', 'width','tooltipText'],
    components: {
        IconInfoCircle
    },
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
