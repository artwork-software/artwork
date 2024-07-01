<template>
    <BaseModal v-if="show" @closed="close">
        <div class="add-category-or-group-modal-container">
            <h1 v-if="typeIsCategory()"
                class="headline1">
                {{ $t('Add category') }}
            </h1>
            <h1 v-if="typeIsGroup()"
                class="headline1">
                {{ $t('Add group') }}
            </h1>
            <div class="new-category-or-group-form">
                <TextInputComponent v-if="typeIsCategory()"
                                    id="new-category-name"
                                    v-model="newCategoryOrGroupForm.name"
                                    :label="$t('Category name*')"
                />
                <TextInputComponent v-if="typeIsGroup()"
                                    id="new-group-name"
                                    v-model="newCategoryOrGroupForm.name"
                                    :label="$t('Group name*')"
                />
                <span v-if="showInvalidNameErrorText"
                      class="new-category-or-group-error-text">
                    {{ $t('A name must be entered.') }}
                </span>
                <div class="button-container">
                    <FormButton :text="$t('Save')"
                                @click="saveNewCategoryOrGroup()"/>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {useForm} from "@inertiajs/vue3";
import {ref} from "vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";

const emits = defineEmits(['closed']),
    props = defineProps({
        resourceId: {
            type: Number,
            required: true
        },
        show: {
            type: Boolean,
            required: true
        },
        type: {
            type: String,
            required: true
        }
    }),
    showInvalidNameErrorText = ref(false),
    newCategoryOrGroupForm = useForm({
        craftId: null,
        categoryId: null,
        name: ''
    }),
    typeIsCategory = () => {
        return props.type === 'category';
    },
    typeIsGroup = () => {
        return props.type === 'group';
    },
    validateNewCategoryOrGroupForm = () => {
        showInvalidNameErrorText.value = !newCategoryOrGroupForm.name.length > 0;

        return !showInvalidNameErrorText.value;
    },
    saveNewCategoryOrGroup = () => {
        if (!validateNewCategoryOrGroupForm()) {
            return;
        }

        if (typeIsCategory()) {
            newCategoryOrGroupForm.craftId = props.resourceId;
            newCategoryOrGroupForm.post(
                route('inventory-management.inventory.category.create'),
                {
                    preserveScroll: true,
                    onSuccess: close
                }
            );

            return;
        }

        if (typeIsGroup()) {
            newCategoryOrGroupForm.categoryId = props.resourceId;
            newCategoryOrGroupForm.post(
                route('inventory-management.inventory.group.create'),
                {
                    preserveScroll: true,
                    onSuccess: close
                }
            );
        }
    },
    close = () => {
        newCategoryOrGroupForm.reset();
        emits.call(this, 'closed');
    };
</script>
