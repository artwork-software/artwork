<template>
    <div>
    <div class="flex justify-between items-center my-1.5 h-5">
        <div class="flex justify-start">
        <input @change="changeStyle(item)" :key="item.name" v-model="item.checked" type="checkbox"
               class="focus:ring-green-400 h-5 w-5 text-green-400 border-2 border-gray-300"/>
        <p :class="[checkedStyle ? 'text-black' : 'text-gray-400']" class="ml-2 font-medium">{{ item.name }}</p>
        </div>
        <InformationCircleIcon v-if="item.showIcon" class="h-7 w-7 flex text-gray-400"
                               aria-hidden="true"/>
    </div>


    </div>
</template>

<script>
import {InformationCircleIcon} from "@heroicons/vue/outline";

export default {
    name: "Checkbox",
    components: {
        InformationCircleIcon
    },
    props:['item'],
    data() {
        return {
            checkedStyle: false,
        }
    },
    methods:{
        changeStyle(){
            this.checkedStyle = !this.checkedStyle
        }
    }
}
</script>

<style scoped>

</style>
