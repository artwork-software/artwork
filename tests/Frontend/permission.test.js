import assert from 'node:assert/strict'
import test from 'node:test'
import {usePermission} from '../../resources/js/Composeables/Permission.js'

const props = (overrides = {}) => ({
    permissionsArray: ['can view crm'],
    rolesArray: [],
    auth: {user: {id: 7}},
    headerObject: {canWriteProject: true},
    ...overrides,
})

test('permission and role checks read the shared page props', () => {
    const {can, canAny, hasAdminRole} = usePermission(props())

    assert.equal(can('can view crm'), true)
    assert.equal(can('crm manager'), false)
    assert.equal(canAny(['crm manager', 'can view crm']), true)
    assert.equal(hasAdminRole(), false)
    assert.equal(usePermission({}).can('can view crm'), false)
})

test('component write rights match user ids regardless of number or string', () => {
    const component = {
        permission_type: 'someSeeSomeEdit',
        users: [{id: '7', pivot: {can_write: true}}],
        departments: [],
    }

    // das frühere Options-API-Mixin verglich mit === und scheiterte an "7" vs. 7
    assert.equal(usePermission(props()).canEditComponent(component), true)
    assert.equal(usePermission(props()).canSeeComponent(component), true)
})

test('without project write right a component is never editable', () => {
    const component = {permission_type: 'allSeeAndEdit', users: [], departments: []}

    assert.equal(usePermission(props({headerObject: {canWriteProject: false}})).canEditComponent(component), false)
    assert.equal(usePermission(props({rolesArray: ['artwork admin'], headerObject: {}})).canEditComponent(component), true)
})

test('write projects does not reveal components restricted to listed viewers', () => {
    const restricted = {permission_type: 'someSeeSomeEdit', users: [], departments: []}
    const listed = {permission_type: 'someSeeSomeEdit', users: [{id: 7, pivot: {can_write: false}}], departments: []}
    const writeAll = usePermission(props({permissionsArray: ['write projects'], headerObject: {}}))

    assert.equal(writeAll.canSeeComponent(restricted), false)
    assert.equal(writeAll.canEditComponent(restricted), false)
    assert.equal(writeAll.canSeeComponent(listed), true)
    assert.equal(writeAll.canEditComponent(listed), true)
    assert.equal(usePermission(props({rolesArray: ['artwork admin']})).canSeeComponent(restricted), true)
})
