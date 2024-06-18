<script>
import IconLib from "@/Mixins/IconLib.vue";
import {useForm} from "@inertiajs/vue3";

export default {
    name: "ShiftNoteComponent",
    props: ['shift'],
    mixins: [IconLib],
    computed: {
        cutDescription() {
            return this.shift.description?.length > 70 ? this.shift.description.substring(0, 70) + '...' : this.shift.description;
        }
    },
    data(){
        return {
            showTextField: false,
            shiftDescription: useForm({
                description: this.shift.description ? this.shift.description : ''
            })
        }
    },
    methods: {
        updateDescription(){
            if(this.shiftDescription.isDirty){
                this.shiftDescription.patch(route('event.shift.update.updateDescription', this.shift.id), {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.showTextField = false
                    }
                })
            } else {
                this.showTextField = false
            }
        },
        openTextField(){
            this.showTextField = true
            this.$nextTick(() => {
                this.$refs.descriptionField.focus()
            })
        }
    }
}
</script>

<template>
   <div class="my-2" @click="openTextField" v-if="!showTextField">
       <div v-if="shift.description?.length === 0 || shift.description === null">
           <IconNote class="w-4 h-4 text-artwork-buttons-context" />
       </div>
        <p v-else class="text-xs">
            {{ cutDescription }}
        </p>
   </div>

    <div v-if="showTextField">
        <div>
            <textarea ref="descriptionField" v-model="shiftDescription.description" class="w-full h-20 p-1 text-sm border-artwork-buttons-context/30 rounded-lg" maxlength="250" @focusout="updateDescription" />
            <div class="text-xs text-end mt-0.5 text-artwork-buttons-context">
                {{ shiftDescription.description.length }} / 250
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
