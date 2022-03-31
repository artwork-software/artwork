<template>
    <div>
    <div class="flex justify-between items-center my-1.5 h-5">
        <div class="flex justify-start">
        <input @change="changeStyle(item)" :key="item.name" v-model="item.checked" type="checkbox"
               class="ring-offset-0 focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-primaryText"/>
        <p :class="[checkedStyle ? 'text-primary' : 'text-secondary']" class="ml-2 font-medium my-auto subpixel-antialiased">{{ item.name }}</p>
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
