<script>
import ProjectHeaderComponent from "@/Pages/Projects/TabTest/Components/ProjectHeaderComponent.vue";
import TextField from "@/Pages/Projects/TabTest/Components/TextField.vue";
import Checkbox from "@/Pages/Projects/TabTest/Components/Checkbox.vue";
export default {
    name: "TabContent",
    components: {
        ProjectHeaderComponent,
        TextField, Checkbox
    },
    props: {
        headerObject: {
            type: Object,
            required: true
        },
        dataObject: {
            type: Object,
            required: true
        }
    }
}
</script>

<template>
    <ProjectHeaderComponent :header-object="headerObject">

        <div class="my-10">
            <div v-for="component in dataObject.currentTab.components">

                <Component :is="component.component?.type" :label="component?.label" :defaults="component.component.defaults" :value="component?.project_value?.value" />
            </div>
        </div>

        <pre>
            {{ dataObject }}
        </pre>


    </ProjectHeaderComponent>
</template>

<style scoped>

</style>
