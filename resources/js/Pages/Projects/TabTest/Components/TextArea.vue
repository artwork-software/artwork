<script>
export default {
    name: "TextArea",
    props: ['data', 'projectId'],
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
            })
        }
    },
}
</script>

<template>
    <div class="my-2">
        <textarea class="w-full h-32 border border-gray-300 rounded-md" @focusout="updateTextData" v-model="textData.text"></textarea>
    </div>
</template>

<style scoped>

</style>
