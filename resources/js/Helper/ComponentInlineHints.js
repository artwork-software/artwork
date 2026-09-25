/**
 * Eingabe-Komponenten, deren Hinweis (Notiz aus den Tab-Einstellungen) direkt sichtbar unter dem Feld
 * steht statt im Info-Popover — wie die Hinweis-Spalte eines Abfrageformulars. Gilt intern und extern.
 */
export const INLINE_HINT_COMPONENT_TYPES = [
    'TextField',
    'TextArea',
    'DropDown',
    'Checkbox',
    'Link',
    'LinkList',
    'CrmContactListComponent',
    'ProjectDocumentsComponent',
]

export function showsInlineHint(componentType) {
    return INLINE_HINT_COMPONENT_TYPES.includes(componentType)
}
