<template>
    <div class="my-2" @click="openTextField" v-if="!showTextField" :class="canEditDescription ? '' : 'cursor-default'">
        <div v-if="event.description?.length === 0 || event.description === null">
            <PropertyIcon v-if="canEditDescription" name="IconNote" class="w-4 h-4 text-artwork-buttons-context"/>
        </div>
        <p v-else class="text-xs">
            {{ cutDescription }}
        </p>
    </div>
    <div v-if="showTextField">
        <div>
            <textarea ref="descriptionField" v-model="eventDescription.description"
                      class="w-[95%] h-20 p-1 text-sm border-artwork-buttons-context/30 rounded-lg" maxlength="250"
                      @focusout="updateDescription"/>
            <div class="text-xs text-end text-artwork-buttons-context">
                {{ eventDescription.description.length }} / 250
            </div>
        </div>
    </div>
</template>

<script>
import IconLib from "@/Mixins/IconLib.vue";
import Permissions from "@/Mixins/Permissions.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import axios from "axios";

export default {
    name: "EventNoteComponent",
    components: {PropertyIcon},
    props: {
        event: {
            type: Object,
            required: true
        },
    },
    mixins: [IconLib, Permissions],
    computed: {
        cutDescription() {
            return this.event.description?.length > 70 ? this.event.description.substring(0, 70) + '...' : this.event.description;
        },
        // Spiegelt die EventPolicy::update-Prüfung des Backends (event.update.description
        // antwortet sonst mit 403); Raum-Admins sind clientseitig nicht auflösbar und
        // erhalten das Textfeld daher nur, wenn sie zusätzlich eine der Rechte/Rollen haben.
        canEditDescription() {
            return this.hasAdminRole() ||
                this.$canAny([
                    'management projects',
                    'can edit planning calendar',
                    'create events without request'
                ]) ||
                this.event?.created_by?.id === this.$page.props.auth.user.id;
        }
    },
    data() {
        return {
            showTextField: false,
            eventDescription: {
                description: this.event.description ? this.event.description : '',
                originalDescription: this.event.description ? this.event.description : ''
            }
        }
    },
    watch: {
        'event.description'(newVal) {
            if (!this.showTextField) {
                this.eventDescription.description = newVal ?? '';
                this.eventDescription.originalDescription = newVal ?? '';
            }
        }
    },
    methods: {
        async updateDescription() {
            if (this.eventDescription.description !== this.eventDescription.originalDescription) {
                try {
                    await axios.patch(route('event.update.description', this.event.id), {
                        description: this.eventDescription.description
                    });
                    this.eventDescription.originalDescription = this.eventDescription.description;
                    this.showTextField = false;
                } catch (error) {
                    console.error('Failed to update description:', error);
                }
            } else {
                this.showTextField = false;
            }
        },
        openTextField() {
            if (!this.canEditDescription) {
                return;
            }
            this.showTextField = true
            this.$nextTick(() => {
                this.$refs.descriptionField.focus()
            })
        }
    }
}
</script>
