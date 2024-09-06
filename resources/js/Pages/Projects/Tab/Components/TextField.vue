<script>
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";

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
            }
        }
    },
    methods: {
        updateTextData() {
            this.$inertia.patch(route('project.tab.component.update', {project: this.projectId, component: this.data.id}), {
                data: this.textData
            }, {
                preserveScroll: true,
                preserveState: false
            })
        },
    },
}
</script>

<template>
    <div class="mb-3">
        <label for="email" class="block text-sm font-medium leading-6" :class="inSidebar ? 'text-white' : ' text-gray-900' ">
            {{ data.data.label }}
        </label>
        <div class="mt-2 w-96">
            <TextInputComponent
                type="text"
                :disabled="!this.canEditComponent"
                @focusout="updateTextData"
                v-model="textData.text"
                :label="textData.text"
                name="email" id="email"
                :class="inSidebar ? 'bg-primary text-white' : ''"
            />
        </div>
    </div>

</template>

<style scoped>

</style>
