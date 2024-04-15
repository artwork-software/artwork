<script>
export default {
    name: "Checkbox",
    props: [
        'data',
        'projectId',
        'inSidebar',
        'canEditComponent'
    ],
    data() {
        return {
            checkedData: {
                checked: this.data.project_value ? this.data.project_value.data.checked : this.data.data.checked
            }
        }
    },
    methods: {
        updateCheckedData() {
            this.$inertia.patch(route('project.tab.component.update', {project: this.projectId, component: this.data.id}), {
                data: this.checkedData
            }, {
                preserveScroll: true,
                preserveState: false
            })
        }
    },
}
</script>

<template>
    <div class="relative flex items-start">
        <div class="flex h-6 items-center">
            <input :disabled="!this.canEditComponent"
                   id="comments"
                   aria-describedby="comments-description"
                   v-model="checkedData.checked"
                   @change="updateCheckedData"
                   :checked="checkedData.checked"
                   name="comments"
                   type="checkbox"
                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
            />
        </div>
        <div class="ml-3 text-sm leading-6">
            <label for="comments" class="font-medium " :class="inSidebar ? 'text-white' : 'text-gray-900'">
                {{ data.data.label }}
            </label>
        </div>
    </div>
</template>

<style scoped>

</style>
