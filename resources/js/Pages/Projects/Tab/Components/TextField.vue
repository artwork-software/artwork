<script>
export default {
    name: "TextField",
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
        <div class="mt-2">
            <input type="text"
                   :disabled="!this.canEditComponent"
                   @focusout="updateTextData"
                   v-model="textData.text"
                   :placeholder="textData.text"
                   name="email" id="email"
                   class="h-10 placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                   :class="inSidebar ? 'bg-primary text-white' : 'inputMain'"
            />
        </div>
    </div>

</template>

<style scoped>

</style>
