<template>
    <tr :draggable="categoryIsDraggable()"
        @dragstart="categoryDragStart"
        @dragend="categoryDragEnd"
        @mouseover="showCategoryMenu()"
        @mouseout="closeCategoryMenu()"
        :class="'cursor-grab ' + trCls">
        <td :colspan="colspan"
            :class="[categoryShown ? 'rounded-t-xl' : 'rounded-xl', 'category-td']">
            <div class="category-td-container">
                <div class="name"
                    @click="toggleCategoryEdit()">
                    {{ category.name }}
                </div>
                <div @click="toggleCategory()"
                     class="cursor-pointer">
                    <IconChevronUp v-if="categoryShown" class="icon"/>
                    <IconChevronDown v-else class="icon"/>
                </div>
                <div :class="[categoryClicked ? '' : '!hidden', 'category-input-container']">
                    <input
                        type="text"
                        ref="categoryInputRef"
                        class="category-input"
                        v-model="categoryValue"
                        @focusout="applyCategoryValueChange()"
                        @keyup.enter="applyCategoryValueChange()">
                </div>
            </div>
        </td>
        <td class="relative">
            <Menu v-show="categoryMenuShown && !categoryDragged"
                  as="div"
                  class="inventory-menu-container">
                <MenuButton as="div">
                    <IconDotsVertical class="menu-button"
                                      stroke-width="1.5"
                                      aria-hidden="true"/>
                </MenuButton>
                <div class="inventory-menu">
                    <transition enter-active-class="transition-enter-active"
                                enter-from-class="transition-enter-from"
                                enter-to-class="transition-enter-to"
                                leave-active-class="transition-leave-active"
                                leave-from-class="transition-leave-from"
                                leave-to-class="transition-leave-to">
                        <MenuItems class="menu-items">
                            <MenuItem v-slot="{ active }"
                                      as="div">
                                <a @click="showCategoryDeleteConfirmModal()"
                                   :class="[active ? 'active' : 'not-active', 'default group']">
                                    <IconTrash class="icon group-hover:text-white"/>
                                    {{ $t('Delete') }}
                                </a>
                            </MenuItem>
                        </MenuItems>
                    </transition>
                </div>
            </Menu>
        </td>
    </tr>
    <AddNewResource v-if="categoryShown"
            @click="openAddCategoryOrGroupModal()"
            :text="$t('Add new group')"
            :colspan="colspan"/>
    <DropGroup v-if="showFirstDropGroup"
               :colspan="colspan"
               :destination-index="0"
               @group-requests-drag-move="moveGroupToDestination"/>
    <tr>
        <td :colspan="colspan" class="empty-row-xxs-td"/>
    </tr>
    <template v-if="categoryShown"
              v-for="(group, index) in category.groups"
              :key="group.id">
        <InventoryGroup :index="index"
                        :group="group"
                        :colspan="colspan"
                        :tr-cls="getGroupOnDragCls(index)"
                        @group-dragging="handleGroupDragging"
                        @group-drag-end="handleGroupDragEnd"/>
        <tr>
            <td :colspan="colspan" class="empty-row-xxs-td"/>
        </tr>
        <DropGroup v-if="showTemplateDropGroup(index)"
                   :colspan="colspan"
                   :destination-index="(index + 1)"
                   @group-requests-drag-move="moveGroupToDestination"/>
    </template>
    <ConfirmDeleteModal v-if="categoryConfirmDeleteModalShown"
                        :title="$t('Delete category?')"
                        :button="$t('Yes')"
                        :description="$t('Really delete this category? This cannot be undone.')"
                        @delete="deleteCategory()"
                        @closed="closeCategoryDeleteConfirmModal()"
    />
</template>

<script setup>
import InventoryGroup from "@/Pages/Inventory/InventoryManagement/InventoryGroup.vue";
import {
    IconChevronDown,
    IconChevronUp,
    IconCopy,
    IconDotsVertical, IconEdit,
    IconTrash,
    IconTrashXFilled
} from "@tabler/icons-vue";
import {computed, ref} from "vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import DropGroup from "@/Pages/Inventory/InventoryManagement/DropGroup.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router} from "@inertiajs/vue3";
import AddNewResource from "@/Pages/Inventory/InventoryManagement/AddNewResource.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";

const emits = defineEmits(['categoryDragging', 'categoryDragEnd', 'wantsToAddNewGroup']),
    props = defineProps({
        index: Number,
        category: Object,
        colspan: Number,
        trCls: String
    }),
    categoryMenuShown = ref(false),
    categoryInputRef = ref(null),
    categoryShown = ref(true),
    categoryDragged = ref(false),
    categoryClicked = ref(false),
    categoryValue = ref(props.category.name),
    categoryConfirmDeleteModalShown = ref(false),
    groupDragging = ref(false),
    draggedGroupIndex = ref(null),
    showFirstDropGroup = computed(() => {
        return groupDragging.value && draggedGroupIndex.value > 0;
    }),
    showTemplateDropGroup = computed(() => {
        return (index) => groupDragging.value &&
            index !== draggedGroupIndex.value &&
            index !== (draggedGroupIndex.value - 1);
    }),
    toggleCategory = () => {
        categoryShown.value = !categoryShown.value;
    },
    toggleCategoryEdit = () => {
        categoryClicked.value = !categoryClicked.value;

        if (categoryClicked.value) {
            setTimeout(() => {
                categoryInputRef.value.select();
            }, 5);
        }
    },
    applyCategoryValueChange = () => {
        if (props.category.name === categoryValue.value || categoryValue.value.length === 0) {
            toggleCategoryEdit();
            return;
        }
        router.patch(
            route(
                'inventory-management.inventory.category.update.name',
                {
                    craftInventoryCategory: props.category.id
                }
            ),
            {
                name: categoryValue.value
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toggleCategoryEdit();
                }
            }
        );
    },
    showCategoryMenu = () => {
        categoryMenuShown.value = true;
    },
    closeCategoryMenu = () => {
        categoryMenuShown.value = false;
    },
    showCategoryDeleteConfirmModal = () => {
        categoryConfirmDeleteModalShown.value = true;
    },
    deleteCategory = () => {
        router.delete(
            route('inventory-management.inventory.category.delete',
                {
                    craftInventoryCategory: props.category.id
                }
            ),
            {
                onSuccess: closeCategoryDeleteConfirmModal
            }
        )
    },
    closeCategoryDeleteConfirmModal = () => {
        categoryConfirmDeleteModalShown.value = false;
    },
    categoryIsDraggable = () => {
        return !categoryClicked.value;
    },
    categoryDragStart = (e) => {
        categoryDragged.value = true;

        //fix for chrome engine, timeout 1ms before emit otherwise dragend is called immediately
        //causing drag and drop not working properly if items in between are dragged
        //@see: https://stackoverflow.com/a/36617714
        setTimeout(() => emits.call(this, 'categoryDragging', props.index), 1);

        e.dataTransfer.setData('categoryId', props.category.id);
        e.dataTransfer.setData('currentCategoryIndex', props.index.toString());
    },
    categoryDragEnd = () => {
        categoryDragged.value = false;
        emits.call(this, 'categoryDragEnd')
    },
    handleGroupDragging = (index) => {
        draggedGroupIndex.value = index;
        groupDragging.value = true;
    },
    getGroupOnDragCls = (index) => {
        return groupDragging.value && draggedGroupIndex.value !== index ? 'onDragBackground' : '';
    },
    handleGroupDragEnd = () => {
        draggedGroupIndex.value = null;
        groupDragging.value = false;
    },
    openAddCategoryOrGroupModal = () => {
        emits.call(this, 'wantsToAddNewGroup', 'group', props.category.id);
    },
    moveGroupToDestination = (groupId, toIndex) => {
        router.patch(
            route(
                'inventory-management.inventory.group.update.order',
                {
                    craftInventoryGroup: groupId
                }
            ),
            {
                order: toIndex
            },
            {
                preserveScroll: true
            }
        );
    };
</script>
