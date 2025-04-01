<template>
    <div v-if="multiEdit">
        <div class="flex items-center mx-2" v-if="checkPermissionForMultiEdit">
            <input v-model="item.checked" aria-describedby="comments-description" name="comments" type="checkbox" class="input-checklist-dark" />
        </div>
    </div>
    <div class="drag-item w-48 p-2 bg-gray-50/10 text-white text-xs rounded-lg flex items-center gap-2" draggable="true" @dragstart="onDragStart">
        <div class="cursor-pointer w-full">
            <div class="w-full flex items-center justify-between h-8">
                <div>
                    {{ item?.name }}
                </div>
                <div class="text-[9px] bg-gray-100/10 rounded-full px-2 py-1">
                    {{ item?.count }}
                </div>
            </div>
        </div>
    </div>
    <error-component v-if="showInsufficientPermissionsErrorComponent"
                     :confirm="$t('OK')"
                     :titel="$t('No permissions')"
                     :description="$t('Contact your administrator so that you can plan the inventory.')"
                     @closed="showInsufficientPermissionsErrorComponent = false;"
    />
</template>

<script setup>
import {usePermission} from "@/Composeables/Permission.js";
import {usePage} from "@inertiajs/vue3";
import {computed, onMounted, ref} from "vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";
const { can, canAny, hasAdminRole } = usePermission(usePage().props);

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    multiEdit: {
        type: Boolean,
        required: false,
        default: false
    },
    inventory_planer_ids: {
        type: Array,
        required: true,
    },
    inventory_planned_by_all: {
        type: Boolean,
        required: false,
        default: false
    }
})

const showInsufficientPermissionsErrorComponent = ref(false);
const onDragStart = (event) => {
    if (
        !can('can plan inventory') ||
        !hasAdminRole() ||
        !props.inventory_planer_ids.includes(usePage().props.auth.user.id)
    ) {
        showInsufficientPermissionsErrorComponent.value = true;
        return;
    }

    const transferItem = {
        id: props.item.id,
        name: props.item.name,
    }
    event.dataTransfer.setData('application/json', JSON.stringify(transferItem));
}

const checkPermissionForMultiEdit = computed(() => {
    if(props.inventory_planned_by_all) {
        return can('can plan inventory') || hasAdminRole();
    } else {
        return hasAdminRole() || props.inventory_planer_ids.includes(usePage().props.auth.user.id)
    }
})

// can('can plan inventory') || hasAdminRole() || inventory_planer_ids.includes(usePage().props.auth.user.id)
</script>

<style scoped>

</style>
