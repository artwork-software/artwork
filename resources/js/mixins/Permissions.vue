<script>
export default {
    data() {
        return {
            permissions: this.$page.props.permissionsArray,
            rolesArray: this.$page.props.rolesArray
        };
    },
    methods: {
        $can(permissionName) {
            return this.$page.props.permissionsArray.includes(permissionName);
        },
        $role(roleName) {
            return this.$page.props.rolesArray.includes(roleName);
        },
        $canAny(permissionNames) {
            for (const permission of permissionNames) {
                if (this.$can(permission)) {
                    return true;
                }
            }
            return false;
        },
        $roleAny(roleNames) {
            for (const role of roleNames) {
                if (this.$role(role)) {
                    return true;
                }
            }
            return false;
        },
        hasAdminRole(){
            return this.$role('artwork admin');
        }
    }
};
</script>
