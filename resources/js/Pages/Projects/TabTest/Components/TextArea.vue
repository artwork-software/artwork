<script>
import {nextTick} from "vue";
import Permissions from "@/mixins/Permissions.vue";

export default {
    name: "TextArea",
    mixins: [Permissions],
    props: ['data', 'projectId', 'inSidebar', 'canEditComponent'],
    data() {
        return {
            textData: {
                text: this.data.project_value ? this.data.project_value.data.text : this.data.data.text,
            },
            descriptionClicked: false
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
        async handleDescriptionClick() {
            if (this.$can('write projects') || this.$role('artwork admin') || this.$can('admin projects') || this.projectWriteIds.includes(this.$page.props.user.id) || this.projectManagerIds.includes(this.$page.props.user.id) || this.project.isMemberOfADepartment){
                this.descriptionClicked = true;

                await nextTick()

                this.$refs[`description-${this.projectId}`].select();
            }
        },
    },
}
</script>

<template>
    <div class="my-2">
        <label for="email" class="block text-sm font-medium leading-6" :class="inSidebar ? 'text-white' : 'text-gray-900'">
            {{ data.data.label }}
        </label>
        <span v-if="descriptionClicked === false"
             class="mt-2 subpixel-antialiased text-secondary"
             @click="handleDescriptionClick()"
             v-html="textData.text ? textData.text : $t('Click here to add text')">
        </span>
        <textarea v-else
                  :disabled="!this.canEditComponent"
                  :placeholder="data.data.placeholder"
                  :ref="`description-${this.projectId}`"
                  class="placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                  :class="inSidebar ? 'bg-primary text-white' : 'inputMain '"
                  @focusout="updateTextData()"
                  v-model="textData.text">
        </textarea>
    </div>
</template>

<style scoped>

</style>
