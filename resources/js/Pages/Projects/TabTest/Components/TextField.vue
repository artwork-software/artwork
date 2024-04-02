<script>
export default {
    name: "TextField",
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
    <div class="mb-3">
        <label for="email" class="block text-sm font-medium leading-6 text-gray-900">
            {{ data.name }}
        </label>
        <div class="mt-2">
            <input type="text" @focusout="updateTextData" v-model="textData.text" :placeholder="textData.text" name="email" id="email" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="you@example.com" />
        </div>
    </div>

</template>

<style scoped>

</style>
