<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import { Form, Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    status?: string;
    isLocal?: boolean;
}>();
</script>

<template>
    <AuthLayout
        title="Verify email"
        description="Please verify your email address by clicking on the link we just emailed to you."
    >
        <Head title="Email verification" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            A new verification link has been sent to the email address you
            provided during registration.
        </div>

        <div
            v-if="isLocal"
            class="mb-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 p-4 text-center text-sm text-yellow-800 dark:text-yellow-200"
        >
            <p class="font-medium mb-2">Development Mode</p>
            <p class="mb-3">Email verification is disabled in local development.</p>
            <Link
                href="/dev/verify-email"
                class="inline-block rounded bg-yellow-600 px-4 py-2 text-white hover:bg-yellow-700"
            >
                Verify Email Now
            </Link>
        </div>

        <Form
            v-bind="send.form()"
            class="space-y-6 text-center"
            v-slot="{ processing }"
        >
            <Button :disabled="processing" variant="secondary">
                <Spinner v-if="processing" />
                Resend verification email
            </Button>

            <TextLink
                :href="logout()"
                as="button"
                class="mx-auto block text-sm"
            >
                Log out
            </TextLink>
        </Form>
    </AuthLayout>
</template>
