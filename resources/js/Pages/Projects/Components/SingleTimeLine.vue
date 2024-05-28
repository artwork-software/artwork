<template>
   <div class="w-full group flex">
       <div class="grid grid-cols-1 sm:grid-cols-2 w-full gap-2">
           <div>
               <input type="text" onfocus="(this.type='time')"
                      :placeholder="$t('Start*')"
                      v-model="time.start"
                      class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                      required
                      @focusout="checkTime(time.start, time.end)"
               />
           </div>
           <div>
               <input type="text" onfocus="(this.type='time')"
                      :placeholder="$t('Ende*')"
                      v-model="time.end"
                      maxlength="3"
                      required
                      @focusout="checkTime(time.start, time.end)"
                      class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
           </div>
           <span class="mt-2 text-red-500 text-xs" v-show="helpText.length > 0">{{ helpText }}</span>
           <div class="mt-2 col-span-2">
               <textarea v-model="time.description_without_html"
                         rows="4"
                         :placeholder="$t('Comment')"
                         name="comment"
                         id="comment"
                         class="block w-full inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"
               />
           </div>
       </div>
       <div class="hidden group-hover:block ml-3">
           <XCircleIcon @click="deleteTime" class="mt-2 h-5 w-5 text-artwork-buttons-create hover:text-error cursor-pointer"/>
       </div>
   </div>
</template>

<script>
import {defineComponent} from 'vue'
import Input from "@/Jetstream/Input.vue";
import {XCircleIcon} from "@heroicons/vue/solid";

export default defineComponent({
    name: "SingleTimeLine",
    components: {
        Input,
        XCircleIcon
    },
    props: [
        'time',
        'preset'
    ],
    data(){
        return {
            helpText: ''
        }
    },
    methods: {
        checkTime(start, end){
            this.helpText = start === '' || end === '' ?
                this.$t('Please fill in both fields.') :
                    start > end ?
                    this.$t('The start time must be before the end time.') :
                    '';
        },
        deleteTime(){
            if (this.preset === true) {
                this.$inertia.delete(route('preset.delete.timeline.row', this.time))
            } else {
                this.$inertia.delete(
                    route('delete.timeline.row', this.time),
                    {
                        preserveState: true,
                        preserveScroll: true
                    }
                )
            }
        }
    }
})
</script>
