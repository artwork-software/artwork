<script>
export default {
    data() {
        return {
            permissions: new Set(Permissions),
            roles: new Set(Roles),
            cache: {}
        };
    },
    methods: {
        $can(permissionName) {
            if (!this.cache.hasOwnProperty(permissionName)) {
                this.cache[permissionName] = this.permissions.has(permissionName);
            }
            return this.cache[permissionName];
        },
        $role(roleName) {
            if (!this.cache.hasOwnProperty(roleName)) {
                this.cache[roleName] = this.roles.has(roleName);
            }
            return this.cache[roleName];
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
            this.$role('artwork admin');
        }
    }
};

</script>
