<script>
import {nextTick} from "vue";
import Permissions from "@/Mixins/Permissions.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";

export default {
    name: "TextArea",
    components: {TextareaComponent},
    mixins: [Permissions],
    props: ['data', 'projectId', 'inSidebar', 'canEditComponent'],
    data() {
        return {
            textData: {
                text: this.data.project_value ? this.data.project_value.text_without_html : this.data.data.text,
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
             v-html="data.project_value?.data.text ? data.project_value?.data.text : $t('Click here to add text')">
        </span>
        <TextareaComponent
            v-else
            :disabled="!this.canEditComponent"
            :label="data.data.placeholder"
            :ref="`description-${this.projectId}`"
            :class="inSidebar ? 'bg-primary text-white' : ''"
            id="placeholder"
            @focusout="updateTextData()"
            v-model="textData.text">
        </TextareaComponent>
    </div>
</template>

<style scoped>

</style>
