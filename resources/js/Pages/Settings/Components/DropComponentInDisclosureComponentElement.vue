<template>
    <div class="flex items-center h-4 min-h-4 rounded" @dragleave="dropOver = false" @dragover="onDragOver" @drop="onDrop">
        <span v-if="dropOver" class="text-xs text-gray-300 w-full flex items-center justify-center pointer-events-none">
            Zum hinzufügen hier loslassen
        </span>
    </div>
</template>

<script setup>

import {ref} from "vue";
import {router} from "@inertiajs/vue3";

const props = defineProps({
    element: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        required: true,
    }
})

const dropOver = ref(false);

const onDragOver = (event) => {
    dropOver.value = true;
    event.preventDefault();
}

const onDrop = (event) => {
    event.preventDefault();

    // add check if JSON is valid
    if (!event.dataTransfer?.getData('application/json')) {
        dropOver.value = false;
        return false;
    }

    const data = JSON?.parse(event.dataTransfer?.getData('application/json'));

    if(data.drop_type !== 'component') {
        dropOver.value = false;
        return false;
    }

    if(data.type === 'DisclosureComponent') {
        dropOver.value = false;
        return false;
    }

    if(data.special){
        dropOver.value = false;
        return false;
    }


    router.post(route('project-management-builder.add.disclosure.component'), {
        order: props.index,
        component_id: data.id,
        disclosure_id: props.element.component.id
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            dropOver.value = false;
        }
    });


    /*if(!this.isSidebar){
        if(data.type === 'ProjectDocumentsComponent' || data.type === 'CommentTab' || data.type === 'ChecklistComponent') {
            this.componentData = data;
            this.showSelectTabsModal = true;
            this.dropOver = false;
            return;
        }
    }

    if(this.isSidebar && !data.sidebar_enabled) {
        this.dropOver = false;
        return;
    }



    if(this.isSidebar) {
        this.$inertia.post(route('tab.add.component.sidebar', {projectTabSidebarTab: this.tab.id}), {
            component_id: data.id,
            order: this.order
        }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                this.dropOver = false;
            }
        });
    } else {
        this.$inertia.post(route('tab.add.component', {projectTab: this.tab.id}), {
            component_id: data.id,
            order: this.order
        }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                this.dropOver = false;
            }
        });
    }
    this.openTab(this.tab.id)*/
}

</script>

<style scoped>

</style>
