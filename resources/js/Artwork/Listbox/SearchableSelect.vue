<template>
    <!--
      Dünner Adapter um ArtworkBaseListbox für klassische <select>-Ablösungen.
      Akzeptiert ein primitives v-model (oder ein Objekt) + eine einfache Optionsliste
      und liefert die Pflichtenheft-Anforderung 3.6 (durchsuchbare Dropdowns).

      Optionen dürfen sein:
        - Array aus Primitiven (z.B. ['A','B'])  -> Wert = Label = Eintrag
        - Array aus Objekten                     -> Wert = option[valueKey], Label = option[labelKey]
        - Objekt-Map { wert: label }             -> via :options auf Array mappen ODER as-map nutzen

      v-model wird transparent als primitiver Wert (bzw. Objekt bei value-key="$self")
      ein- und ausgereicht – funktioniert daher als Drop-in für ein <select v-model="x">.
    -->
    <ArtworkBaseListbox
        :model-value="selectedItem"
        @update:model-value="onChange"
        :items="normalizedItems"
        by="_v"
        option-label="_l"
        :option-key="item => item._k"
        :label="label"
        :placeholder="resolvedPlaceholder"
        :disabled="disabled"
        :use-translations="false"
        :search-threshold="searchThreshold"
        :show-color-indicator="showColorIndicator"
        color-property="_color"
        :button-class="buttonClass || undefined"
    />
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'

const props = defineProps({
    modelValue: { type: [String, Number, Boolean, Object, null], default: null },
    /** Array aus Primitiven oder Objekten */
    options: { type: Array, default: () => [] },
    /** Schlüssel des Wertes bei Objekt-Optionen. '$self' => ganzes Objekt ist der Wert. */
    valueKey: { type: String, default: 'id' },
    /** Schlüssel ODER Funktion des Anzeigetextes bei Objekt-Optionen. */
    labelKey: { type: [String, Function], default: 'name' },
    /** Optionaler Farb-Schlüssel (für Status o.ä.) */
    colorKey: { type: String, default: '' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: 'Please select' },
    disabled: { type: Boolean, default: false },
    searchThreshold: { type: Number, default: 5 },
    /**
     * Führende Sentinel-Option (z.B. "Alle", "Bitte wählen", "Nicht ändern").
     * { label: <i18n-key>, value: <primitiver Wert, Default null> }
     */
    emptyOption: { type: Object, default: null },
    /** Option-Labels zusätzlich übersetzen (Default: aus, da meist Daten). */
    translateOptionLabels: { type: Boolean, default: false },
    /** Optional: Button-Klasse an ArtworkBaseListbox durchreichen (leer = Default). */
    buttonClass: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'change'])

const { t } = useI18n()

const showColorIndicator = computed(() => !!props.colorKey)

const labelOf = (opt) => {
    let raw
    if (typeof props.labelKey === 'function') {
        raw = props.labelKey(opt)
    } else if (opt !== null && typeof opt === 'object') {
        raw = opt[props.labelKey]
    } else {
        raw = opt
    }
    raw = raw ?? ''
    return props.translateOptionLabels ? t(String(raw)) : String(raw)
}

const valueOf = (opt) => {
    if (opt !== null && typeof opt === 'object') {
        return props.valueKey === '$self' ? opt : opt[props.valueKey]
    }
    return opt
}

const normalizedItems = computed(() => {
    const items = []
    if (props.emptyOption) {
        items.push({
            _v: props.emptyOption.value ?? null,
            _l: t(String(props.emptyOption.label ?? '')),
            _k: '__empty__',
            _color: null,
        })
    }
    ;(props.options ?? []).forEach((opt, idx) => {
        const color = (props.colorKey && opt && typeof opt === 'object') ? opt[props.colorKey] : null
        items.push({
            _v: valueOf(opt),
            _l: labelOf(opt),
            _k: `opt_${idx}`,
            _color: color ?? null,
        })
    })
    return items
})

const isSameValue = (a, b) => {
    if (a === b) return true
    // Objekt-Werte ($self): Vergleich per Identität reicht meist; Fallback auf id
    if (a && b && typeof a === 'object' && typeof b === 'object') {
        if ('id' in a && 'id' in b) return a.id === b.id
    }
    return false
}

const selectedItem = computed(() =>
    normalizedItems.value.find(it => isSameValue(it._v, props.modelValue)) ?? null
)

// Wird im Template referenziert; ohne dieses Computed warnte Vue bei jedem
// Render und der Placeholder wurde nie angezeigt.
const resolvedPlaceholder = computed(() => t(String(props.placeholder ?? '')))

const onChange = (item) => {
    const value = item ? item._v : (props.emptyOption ? (props.emptyOption.value ?? null) : null)
    emit('update:modelValue', value)
    emit('change', value)
}
</script>
