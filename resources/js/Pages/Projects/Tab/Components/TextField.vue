<script>
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import {useProjectDataListener} from "@/Composeables/Listener/useProjectDataListener.js";
import {reactive, ref} from "vue";

export default {
    name: "TextField",
    components: {TextInputComponent},
    props: [
        'data',
        'projectId',
        'inSidebar',
        'canEditComponent'
    ],
    data() {
        return {
            textData: {
                text: this.data.project_value ? this.data.project_value.data.text : this.data.data.text
            },
            projectData: this.data,
            text: this.data.project_value ? this.data.project_value.data.text : this.data.data.text,
        }
    },
    mounted() {
        useProjectDataListener(this.projectData, this.projectId).init();
    },
    methods: {
        updateTextData() {
            this.$inertia.patch(route('project.tab.component.update', {project: this.projectId, component: this.data.id}), {
                data: {
                    text: this.text
                }
            }, {
                preserveScroll: true,
                preserveState: false
            })
        },
    },
    watch: {
        // if the data changes, update the text
        projectData: {
            handler: function (newVal, oldVal) {
                this.text = newVal.project_value ? newVal.project_value.data.text : newVal.data.text
            },
            deep: true
        }
    }
}
</script>

<template>
    <div class="mb-3">
        <label for="email" class="block text-sm font-medium leading-6" :class="inSidebar ? 'text-white' : ' text-gray-900' ">
            {{ projectData.data.label }}
        </label>
        <div class="mt-2 w-96">

            <TextInputComponent
                type="text"
                :disabled="!this.canEditComponent"
                @focusout="updateTextData"
                v-model="text"
                :label="text"
                name="email" id="email"
                :show-label="false"
                no-margin-top
                :class="inSidebar ? 'bg-primary text-white' : ''"
            />
        </div>
    </div>


</template>

<style scoped>

</style>
