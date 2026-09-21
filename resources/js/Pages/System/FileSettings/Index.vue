<template>
  <ToolSettingsHeader :title="$t('File settings')">
    <div v-if="usePage().props.flash.success"
         class="mt-4 w-full font-bold text-sm border-1 border-success rounded bg-success p-2 text-white mb-3">
      {{ usePage().props.flash.success }}
    </div>
    <SettingsGuideBanner
        class="mt-6"
        storage-key="settings-guide.tool.file-settings"
        title="How does this area work?"
        :paragraphs="[
            'For each application area you define which file types may be uploaded and how large a file may be. Both limits are enforced server-side on every upload.',
            'The maximum possible limit is additionally restricted by the server configuration (e.g. nginx client_max_body_size, PHP upload_max_filesize/post_max_size).',
            'Changes are saved automatically.',
        ]"
    />
    <!-- Transparenz über die Serverobergrenze (Abnahme RG-04): PHP-Limit ist auslesbar,
         nginx kann zusätzlich begrenzen -->
    <div v-if="serverUploadLimitMb" class="mt-4 flex items-start gap-x-2 rounded-lg border border-info-border bg-info-surface/70 px-3 py-2 text-xs text-text-muted">
        <IconInfoCircle stroke-width="1.5" class="mt-0.5 size-4 shrink-0 text-info"/>
        <span>
            {{ $t('With the current server settings a maximum of {0} MB per file is possible. If you need larger uploads, ask your IT to raise the server limits first.', [serverUploadLimitMb]) }}
        </span>
    </div>
    <div v-for="area in areas" :key="area.name" class="mt-8">
      <h3 class="font-lexend font-semibold text-[clamp(16px,2vw,18px)]/[21px] text-text">{{ $t(area.name) }}</h3>
      <SettingsGuideBanner
          v-if="area.name === 'branding'"
          class="mt-2"
          variant="static"
          title="Applies to the Branding tab"
          :paragraphs="[
              'The file types and maximum size set here restrict the logo and illustration uploads in the Branding tab.',
          ]"
      />
      <div class="mt-4">
        <Listbox as="div">
          <div class="relative mt-2 w-1/2">
            <ListboxButton class="menu-button">
              <span class="block truncate text-left">{{$t('Select file types')}}</span>
              <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                    <IconChevronDown  stroke-width="1.5" class="h-5 w-5 text-text" aria-hidden="true"/>
                                </span>
            </ListboxButton>

            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
              <ListboxOptions class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                <ListboxOption as="template" v-for="fileType in imageFileTypes" :key="fileType" :value="fileType" v-slot="{ active, selected }">
                  <li @click="addFileTypeToArea(area, fileType)" :class="[active ? 'bg-accent-600 text-white' : 'text-text', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                    <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ fileType }}</span>
                    <span v-if="selected" :class="[active ? 'text-white' : 'text-accent-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                <IconCheck stroke-width="1.5" class="h-5 w-5" aria-hidden="true" />
                                            </span>
                  </li>
                </ListboxOption>
              </ListboxOptions>
            </transition>
          </div>
        </Listbox>
        <div class="flex">
          <div class="mt-2">
            <SliderInput
                v-model="area.fileSize"
                :min="1"
                :max="1024"
                :step="1"
                :property="{area: area, fileSize: area.fileSize}"
                :show-value="true"
                :label="$t('Max file size in MB')"
                :method="handleSlideValueUpdate"
            />
            <!-- Eingestellter Wert liegt über der Serverobergrenze → greift erst nach IT-Anpassung -->
            <p v-if="serverUploadLimitMb && area.fileSize > serverUploadLimitMb" class="mt-1 flex items-center gap-x-1 text-xs text-warning">
              <IconAlertTriangle stroke-width="1.5" class="size-4 shrink-0"/>
              {{ $t('The server currently only accepts up to {0} MB — values above this only take effect after your IT raises the server limits.', [serverUploadLimitMb]) }}
            </p>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-x-5">
        <div class="flex mt-4">
          <TagComponent v-for="fileType in area.fileTypes"
                        :key="fileType.name"
                        :method="removeFileTypeFromArea"
                        :hide-x="false"
                        :property="{area: area, fileType: fileType, fileSize: area.fileSize}"
                        :displayed-text="fileType.name"
          />
        </div>
      </div>
    </div>
  </ToolSettingsHeader>
</template>

<script setup>
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";
import {computed, ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import {useTranslation} from "@/Composeables/Translation.js";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import {IconAlertTriangle, IconCheck, IconChevronDown, IconInfoCircle} from "@tabler/icons-vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import SliderInput from "@/Components/Form/SliderInput.vue";
import debounce from "lodash.debounce";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";

const $t = useTranslation(),
    props = defineProps({
      areas: {
        type: Object,
        required: true
      },
      imageFileTypes: {
        type: Object,
        required: true
      },
      otherFileTypes: {
        type: Object,
        required: true
      },
    });

const areas = ref(props.areas);

// Effektives PHP-Upload-Limit des Servers (shared Inertia-Prop; nginx kann zusätzlich begrenzen)
const serverUploadLimitMb = computed(() => usePage().props.server_upload_limit_mb ?? null);

const addFileTypeToArea = (area, fileType) => {
  const targetArea = areas.value.find(a => a.name === area.name);
  if (!targetArea.fileTypes.find((m) => m.name === fileType)) {
    targetArea.fileTypes.push({ name: fileType });
  }
  updateArea(area);
}

const removeFileTypeFromArea = (data) => {
  const targetArea = areas.value.find(area => area.name === data.area.name);
  const fileType = data.fileType;
  targetArea.fileTypes = targetArea.fileTypes.filter((m) => m.name !== fileType.name);
  updateArea(targetArea);
}

const handleSlideValueUpdate = (property, value) => {
  const targetArea = areas.value.find(area => area.name === property.area.name);
  targetArea.fileSize = parseInt(value);
  updateArea(targetArea);
}

const updateArea = debounce((area) => {
  router.put(route('tool.file-settings.store', {}), {
    data: area
  })
}, 500);
</script>
