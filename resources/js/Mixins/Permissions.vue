<script>
import {usePermission} from "@/Composeables/Permission.js";

/**
 * Options-API-Zugang zu den Rechteprüfungen – dünne Hülle um Composeables/Permission.js (eine
 * Implementierung). permissionsArray enthält für Admins bereits alle Rechte, $can() genügt also.
 */
export default {
    data() {
        return {
            permissions: this.$page.props.permissionsArray,
            rolesArray: this.$page.props.rolesArray
        };
    },
    methods: {
        $permission() {
            return usePermission(this.$page.props);
        },
        $can(permissionName) {
            return this.$permission().can(permissionName);
        },
        $role(roleName) {
            return this.$permission().role(roleName);
        },
        $canAny(permissionNames) {
            return this.$permission().canAny(permissionNames);
        },
        $roleAny(roleNames) {
            return this.$permission().roleAny(roleNames);
        },
        hasAdminRole() {
            return this.$permission().hasAdminRole();
        }
    }
};
</script>
