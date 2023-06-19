<script>
import {defineComponent} from 'vue'
import Input from "@/Jetstream/Input.vue";

export default defineComponent({
    name: "SingleTimeLine",
    components: {Input},
    props: ['time'],
    data(){
        return {
            helpText: ''
        }
    },
    methods: {
        checkTime(start, end){
            if(start === '' || end === ''){
                this.helpText = 'Bitte fülle beide Felder aus.'
                return;
            }
            if(start > end){
                this.helpText = 'Die Startzeit muss vor der Endzeit liegen.'
                return;
            } else {
                this.helpText = ''
                return;
            }
        }
    }
})
</script>

<template>
    <div>
        <input type="time"
               placeholder="Start*"
               v-model="time.start"
               class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
               required
               @focusout="checkTime(time.start, time.end)"
        />
    </div>
    <div>
        <input type="time"
               placeholder="Ende*"
               v-model="time.end"
               maxlength="3"
               required
               @focusout="checkTime(time.start, time.end)"
               class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
    </div>
    <span class="text-red-500 text-xs" v-show="helpText.length > 0">{{ helpText }}</span>
    <div class="mt-2 col-span-2">
        <textarea v-model="time.description" rows="4" name="comment" id="comment" class="block w-full inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300" />
    </div>
</template>

<style scoped>

</style>
