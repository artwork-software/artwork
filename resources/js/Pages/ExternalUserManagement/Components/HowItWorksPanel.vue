<template>
    <div class="bg-white border border-border-subtle rounded-lg shadow-sm">
        <button
            type="button"
            class="w-full flex items-center justify-between gap-3 px-6 py-4 text-left"
            @click="open = !open"
        >
            <span class="flex items-center gap-2">
                <component :is="IconInfoCircle" class="h-5 w-5 text-accent-600 shrink-0" />
                <span class="text-base font-medium text-text">
                    {{ $t('How does the external user management work?') }}
                </span>
            </span>
            <component
                :is="IconChevronDown"
                class="h-5 w-5 text-text-subtle shrink-0 transition-transform"
                :class="{ 'rotate-180': open }"
            />
        </button>

        <div v-if="open" class="px-6 pb-6 text-sm text-text-muted space-y-6">
            <p>
                {{ $t('artwork does not check the password itself for connected users – the identity check is done by the connected provider (OIDC or LDAP/Active Directory). The artwork account with all its roles, permissions and assignments is fully kept: the provider only replaces the password check, not the account.') }}
            </p>

            <!-- How accounts are created -->
            <div>
                <h4 class="font-semibold text-text mb-2">{{ $t('How are user accounts created?') }}</h4>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>
                        <span class="font-medium">OIDC:</span>
                        {{ $t('The account is created automatically the first time someone successfully signs in via the SSO button on the login page.') }}
                    </li>
                    <li>
                        <span class="font-medium">LDAP / Active Directory:</span>
                        {{ $t('The account is created on the first login through the normal login form (e-mail + directory password) – or in advance via the synchronization.') }}
                    </li>
                    <li>
                        {{ $t('The synchronization ("Sync now" or automatically every 30 minutes) imports all users matched by the user filter. Newly imported users receive a one-time welcome e-mail.') }}
                    </li>
                    <li>
                        {{ $t('Newly created accounts automatically receive the default role configured for the connection.') }}
                    </li>
                </ul>
            </div>

            <!-- Existing accounts / e-mail matching -->
            <div>
                <h4 class="font-semibold text-text mb-2">
                    {{ $t('What happens with existing accounts (same e-mail address)?') }}
                </h4>
                <p class="mb-2">{{ $t('Every login and every synchronization runs through the same three steps:') }}</p>
                <ol class="list-decimal pl-5 space-y-1.5">
                    <li>
                        {{ $t('The provider identity is already known (via its stable ID)? Then the linked artwork account is used and its profile (name, e-mail) is updated.') }}
                    </li>
                    <li>
                        {{ $t('Otherwise: an artwork account with the same e-mail address exists? Then it is linked to the provider and keeps all its roles, permissions and assignments. With OIDC this only happens if the provider reports the e-mail address as verified.') }}
                    </li>
                    <li>
                        {{ $t('Otherwise a new account is created and receives the default role of the connection.') }}
                    </li>
                </ol>
                <div class="mt-3 p-3 rounded bg-accent-50 text-accent-700">
                    {{ $t('The e-mail address is only used once – for the very first link. Afterwards the account is recognized exclusively via the stable provider ID (OIDC "sub" or entryUUID/objectGUID). A changed e-mail address at the provider can therefore never take over a different artwork account.') }}
                </div>
                <p class="mt-3">
                    {{ $t('If the e-mail address belongs to an account that is already bound to a different provider, the login is rejected – an account can only be bound to one provider at a time.') }}
                </p>
            </div>

            <!-- Consequences for linked accounts -->
            <div>
                <h4 class="font-semibold text-text mb-2">{{ $t('What changes for linked accounts?') }}</h4>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>
                        {{ $t('Password login and password reset in artwork are disabled – the password lives at the provider. OIDC users sign in exclusively via the SSO button.') }}
                    </li>
                    <li>
                        {{ $t('For LDAP the directory takes precedence: if the e-mail address is found in an active directory, only the directory password counts – the local artwork password does not work as a fallback. Users that do not exist in any directory continue to log in with their artwork password as usual.') }}
                    </li>
                    <li>
                        {{ $t('In the user overview, linked accounts are marked with an SSO badge; its tooltip shows the provider.') }}
                    </li>
                </ul>
            </div>

            <!-- Good to know -->
            <div>
                <h4 class="font-semibold text-text mb-2">{{ $t('Good to know') }}</h4>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>
                        {{ $t('LDAP group mappings: roles and permissions configured in the group mappings are assigned and revoked automatically with every synchronization, based on the directory group membership.') }}
                    </li>
                    <li>
                        {{ $t('Admin protection: the last admin who can log in locally cannot be bound to a provider – this way at least one administrator with a classic password always remains.') }}
                    </li>
                    <li>
                        {{ $t('Emergency access: the console command "php artisan auth:break-glass" detaches an account from the provider and resets it to local password login.') }}
                    </li>
                    <li>
                        {{ $t('OIDC: after saving a connection artwork shows the redirect URI in the dialog – it has to be registered with the provider exactly as shown, otherwise the provider rejects the login.') }}
                    </li>
                    <li>
                        {{ $t('Deactivating or deleting a connection: linked accounts have no local password, so these users cannot sign in anymore until the connection is reactivated. The accounts themselves are kept. In an emergency, the break-glass command restores local password login for individual accounts.') }}
                    </li>
                    <li>
                        {{ $t('Stored passwords and client secrets are never displayed again for security reasons – every edit of a connection requires re-entering them.') }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
import {defineComponent} from "vue";
import {IconChevronDown, IconInfoCircle} from "@tabler/icons-vue";

export default defineComponent({
    data() {
        return {
            open: false,
            IconChevronDown,
            IconInfoCircle,
        }
    },
})
</script>
