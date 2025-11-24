import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

export function useShiftPlanRequest() {
    const { t } = useI18n();

    const statusClasses = (status) => {
        switch (status) {
            case 'pending':
                return 'bg-amber-50 text-amber-700 ring-amber-200';
            case 'approved':
            case 'accepted':
                return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
            case 'rejected':
            case 'denied':
                return 'bg-rose-50 text-rose-700 ring-rose-200';
            default:
                return 'bg-gray-100 text-gray-600 ring-gray-200';
        }
    };

    const formatDateTime = (value) => {
        if (!value) return '-';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return String(value);
        }
        return date.toLocaleString(undefined, {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const formatDateShort = (value) => {
        if (!value) return '-';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return String(value);
        }
        return date.toLocaleDateString(undefined, {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    };

    const computeDurationHours = (shift) => {
        if (!shift.start || !shift.end) return 0;
        const [sh, sm] = String(shift.start).split(':').map(Number);
        const [eh, em] = String(shift.end).split(':').map(Number);
        const startMinutes = sh * 60 + sm;
        const endMinutes = eh * 60 + em;
        let diff = endMinutes - startMinutes;
        if (shift.break_minutes) diff -= shift.break_minutes;
        return diff > 0 ? diff / 60 : 0;
    };

    const fieldLabel = (fieldName) => {
        if (typeof fieldName === 'string') {
            if (fieldName.startsWith('qualifications:')) {
                const name = fieldName.split(':', 2)[1] || '';
                return name ? `${t('Qualification')}: ${name}` : t('Qualification');
            }
            if (fieldName.startsWith('global_qualifications:')) {
                const name = fieldName.split(':', 2)[1] || '';
                return name ? `${t('Global qualification')}: ${name}` : t('Global qualification');
            }
        }
        const map = {
            start: t('Start time'),
            end: t('End time'),
            start_time: t('Start time'),
            end_time: t('End time'),
            start_date: t('Start date'),
            end_date: t('End date'),
            event_start_day: t('Start date'),
            event_end_day: t('End date'),
            break_minutes: t('Break'),
            description: t('Description'),
            'shiftGroup.name': t('Shift group'),
            'craft.name': t('Craft'),
            'project.name': t('Project'),
            'room.name': t('Room'),
            current_request_id: t('Request'),
            project_id: t('Project'),
            craft_id: t('Craft'),
            room_id: t('Room'),
            global_qualifications: t('Global qualification'),
            qualifications: t('Qualification'),
            in_workflow: t('In approval workflow'),
            workflow_rejection_reason: t('workflow_rejection_reason'),
            shift_qualification_id: t('shift_qualification_id'),
            assignment: t('Assignment'),
        };
        return map[fieldName] || fieldName;
    };

    const formatFieldValue = (fieldName, value) => {
        if (value === null || typeof value === 'undefined') return '–';

        // Zeiten
        if (['start', 'end', 'start_time', 'end_time'].includes(fieldName)) {
            return t(String(value));
        }

        // Pause
        if (fieldName === 'break_minutes') {
            const minutes = Number(value) || 0;
            if (!minutes) return t('No break');
            const h = Math.floor(minutes / 60);
            const m = minutes % 60;
            if (h === 0) return `${m} min`;
            return `${h} h ${m} min`;
        }

        // Datumsfelder
        if (fieldName.includes('date') || fieldName.endsWith('_day')) {
            return formatDateShort(value);
        }

        // Primitive Werte
        if (['string', 'number', 'boolean'].includes(typeof value)) {
            return t(String(value));
        }

        // Objekt mit Label-Feldern (z.B. assignment, wenn du mal direkt ein Objekt bekommst)
        if (value && typeof value === 'object') {
            const labelCandidate = value.label || value.before_label || value.after_label;
            if (labelCandidate) {
                return t(String(labelCandidate));
            }
            try {
                return JSON.stringify(value);
            } catch {
                return t(String(value));
            }
        }

        // Fallback
        try {
            return JSON.stringify(value);
        } catch {
            return t(String(value));
        }
    };


    const hasOpenPostCommitChange = (shift, affectedUserId = null) => {
        const changes = shift.committed_shift_changes || [];
        if (!Array.isArray(changes) || !changes.length) return false;
        return changes.some((change) => {
            if (change.acknowledged_at) return false;
            if (affectedUserId && change.affected_user_id) return change.affected_user_id === affectedUserId;
            return true;
        });
    };

    const hasOpenWorkflowChange = (shift, affectedUserId = null) => {
        const changes = shift.shift_plan_request_changes || [];
        if (!Array.isArray(changes) || !changes.length) return false;
        return changes.some((change) => {
            if (affectedUserId && change.affected_user_id) return change.affected_user_id === affectedUserId;
            return true;
        });
    };

    const extractFieldEntries = (fieldChanges = {}) => {
        const entries = [];

        const translateIfPossible = (value) => {
            if (value === null || value === undefined) return null;
            if (typeof value === 'string' && value.trim() !== '') {
                return t(value);
            }
            return value;
        };

        Object.entries(fieldChanges).forEach(([fieldName, field]) => {
            if (fieldName === '_initial') return;

            // Spezieller Fall: qualifications
            if (fieldName === 'qualifications' && Array.isArray(field)) {
                field.forEach((item) => {
                    const name =
                        item?.label ||
                        (item?.qualification_id ? `ID ${item.qualification_id}` : '');

                    entries.push({
                        fieldName: `qualifications:${name}`,
                        old: item?.old ?? null,
                        new: item?.new ?? null,
                        new_label: item?.new_label ?? null,
                        old_label: item?.old_label ?? null,
                    });
                });
                return;
            }

            // Spezieller Fall: global_qualifications
            if (fieldName === 'global_qualifications' && Array.isArray(field)) {
                field.forEach((item) => {
                    const name =
                        item?.label ||
                        (item?.global_qualification_id ? `ID ${item.global_qualification_id}` : '');

                    entries.push({
                        fieldName: `global_qualifications:${name}`,
                        old: item?.old ?? null,
                        new: item?.new ?? null,
                        new_label: item?.new_label ?? null,
                        old_label: item?.old_label ?? null,
                    });
                });
                return;
            }

            // NEU: Spezieller Fall: assignment
            if (fieldName === 'assignment' && field && typeof field === 'object') {
                const buildLabelFromPayload = (payload, { fallbackKey = 'before_label', includeUser = false } = {}) => {
                    if (!payload || typeof payload !== 'object') return null;

                    // Basis-Label über Keys wie before_label / after_label
                    let base =
                        payload.label ||
                        payload[fallbackKey] ||
                        payload.before_label ||
                        payload.after_label ||
                        null;

                    // Falls noch kein Label, baue eins aus Datum + Zeit
                    if (!base) {
                        const parts = [];
                        const date = payload.start_date || payload.event_start_day;
                        const startTime = payload.start_time || payload.start;
                        const endTime = payload.end_time || payload.end;

                        if (date) parts.push(date);
                        if (startTime || endTime) {
                            parts.push([startTime, endTime].filter(Boolean).join(' - '));
                        }

                        base = parts.length ? parts.join(' ') : null;
                    }

                    const pieces = [];

                    // WICHTIG: Nur in "Vorher" den Usernamen anzeigen
                    if (includeUser && payload.user_name) {
                        pieces.push(payload.user_name);
                    }

                    if (base) {
                        pieces.push(base);
                    }

                    return pieces.length ? pieces.join(' · ') : null;
                };

                // zwei mögliche Shapes:
                // 1) { old: {...}, new: {...} }
                // 2) { before_label: '...', after_label: '...', ... } – dann steckt alles direkt in field
                const oldPayload = 'old' in field ? field.old : field;
                const newPayload = 'new' in field ? field.new : field;

                const oldLabel = buildLabelFromPayload(oldPayload, {
                    fallbackKey: 'before_label',
                    includeUser: true,  // -> Hier wird "Max Schmidt" etc. eingefügt
                });

                const newLabel = buildLabelFromPayload(newPayload, {
                    fallbackKey: 'after_label',
                    includeUser: false, // -> Nachher nur "free" o.ä.
                });

                entries.push({
                    fieldName,
                    old: null,
                    new: null,
                    old_label: translateIfPossible(oldLabel),
                    new_label: translateIfPossible(newLabel),
                });

                return;
            }

            // Generischer Fall: { old: ..., new: ..., old_label, new_label }
            if (field && typeof field === 'object' && ('old' in field || 'new' in field)) {
                entries.push({
                    fieldName,
                    old: translateIfPossible(field.old),
                    new: translateIfPossible(field.new),
                    new_label: translateIfPossible(field.new_label),
                    old_label: translateIfPossible(field.old_label),
                });
            } else {
                // Primitive Änderungen (z.B. direkt ein String, Zahl etc.)
                entries.push({
                    fieldName,
                    old: null,
                    new: translateIfPossible(field),
                    new_label: null,
                    old_label: null,
                });
            }
        });

        return entries;
    };



    const extractInitialState = (fieldChanges = {}) => fieldChanges && typeof fieldChanges === 'object' ? fieldChanges._initial || null : null;

    const pickInitialFields = (initialState) => {
        if (!initialState || typeof initialState !== 'object') return {};
        const keysToShow = ['start','end','start_date','end_date','event_start_day','event_end_day','break_minutes'];
        const result = {};
        keysToShow.forEach((k) => { if (k in initialState) result[k] = initialState[k]; });
        return result;
    };

    const activityContext = (activity) => {
        const ctx = activity?.properties?.context;
        if (!ctx) return null;
        switch (ctx) {
            case 'workflow': return t('Workflow');
            case 'post_commit': return t('Post-commit');
            case 'normal': return t('Normal');
            default: return ctx;
        }
    };

    const extractActivityChanges = (activity) => {
        const attrs = activity?.properties?.attributes || {};
        const old = activity?.properties?.old || {};
        return Object.keys(attrs).map((fieldName) => ({ fieldName, old: old[fieldName], new: attrs[fieldName] }));
    };

    const hasActivityTranslations = (activity) => !!activity?.properties?.translation_key;

    const activityTranslation = (activity) => {
        const translationKey = activity?.properties?.translation_key || null;
        const translationKeyValues = activity?.properties?.translation_key_placeholder_values || [];
        if (!translationKey) return '';
        return t(translationKey, translationKeyValues);
    };

    const formatDrawerHeader = (shift, formatDateShortFn = formatDateShort) => {
        if (!shift) return '';
        const date = shift.formatted_dates?.start || shift.formatted_dates?.frontend_start || shift.event_start_day;
        return `${formatDateShortFn(date)} · ${shift.start} – ${shift.end}`;
    };

    return {
        t,
        statusClasses,
        formatDateTime,
        formatDateShort,
        computeDurationHours,
        fieldLabel,
        formatFieldValue,
        hasOpenPostCommitChange,
        hasOpenWorkflowChange,
        extractFieldEntries,
        extractInitialState,
        pickInitialFields,
        activityContext,
        extractActivityChanges,
        hasActivityTranslations,
        activityTranslation,
        formatDrawerHeader,
    };
}
