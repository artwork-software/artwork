<template>
    <div class="my-2 px-1" @click="openTextField" v-if="!showTextField && $can('can plan shifts') || hasAdminRole()">
        <div v-if="shift.description?.length === 0 || shift.description === null && !showTextField">
            <IconNote class="w-4 h-4 cursor-pointer text-artwork-buttons-context" />
        </div>
        <p v-else-if="!showTextField" class="text-xs cursor-pointer">
            {{ cutDescription }}
        </p>
    </div>
    <div v-if="showTextField">
        <div class="cursor-pointer px-1">
            <textarea ref="descriptionField" v-model="shiftDescription.description" class="w-full h-20 p-1 text-sm border-artwork-buttons-context/30 rounded-lg" maxlength="250" @focusout="updateDescription" />
            <div class="text-xs text-end text-artwork-buttons-context">
                {{ shiftDescription.description.length }} / 250
            </div>
        </div>
    </div>
</template>

<script>
import IconLib from "@/Mixins/IconLib.vue";
import {useForm} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";

export default {
    name: "ShiftNoteComponent",
    props: {
        shift: {
            type: Object,
            required: true
        },
        isPreset: {
            type: Boolean,
            default: false
        }
    },
    mixins: [IconLib, Permissions],
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
                if (this.isPreset) {
                    this.shiftDescription.patch(route('preset.shift.update.updateDescription', this.shift.id), {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            this.showTextField = false
                        }
                    })
                } else {
                    this.shiftDescription.patch(route('event.shift.update.updateDescription', this.shift.id), {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            this.showTextField = false
                        }
                    })
                }
            } else {
                this.showTextField = false
            }
        },
        openTextField(){
            if(this.$can('can plan shifts') || this.hasAdminRole()){
                this.showTextField = true
                this.$nextTick(() => {
                    this.$refs.descriptionField.focus()
                })
            }
        }
    }
}
</script>
