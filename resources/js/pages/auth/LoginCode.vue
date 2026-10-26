<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { resend, verify } from '@/routes/login/code';

defineOptions({
    layout: {
        title: 'Check your email',
        description: 'Enter the 6 digit code we sent you',
    },
});

defineProps<{
    email: string;
    status?: string;
}>();

const code = ref<string>('');
</script>

<template>
    <Head title="Enter your code" />

    <div class="space-y-6">
        <p class="text-center text-sm text-muted-foreground">
            We sent a code to
            <span class="font-medium text-foreground">{{ email }}</span>
        </p>

        <div
            v-if="status"
            class="text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Form
            v-bind="verify.form()"
            class="space-y-4"
            @error="code = ''"
            #default="{ errors, processing }"
        >
            <input type="hidden" name="code" :value="code" />
            <div
                class="flex flex-col items-center justify-center space-y-3 text-center"
            >
                <InputOTP
                    id="otp"
                    v-model="code"
                    :maxlength="6"
                    :disabled="processing"
                    autofocus
                    autocomplete="one-time-code"
                >
                    <InputOTPGroup>
                        <InputOTPSlot
                            v-for="index in 6"
                            :key="index"
                            :index="index - 1"
                        />
                    </InputOTPGroup>
                </InputOTP>
                <InputError :message="errors.code" />
            </div>
            <Button
                type="submit"
                class="w-full"
                :disabled="processing || code.length < 6"
                data-test="verify-code-button"
            >
                <Spinner v-if="processing" />
                Continue
            </Button>
        </Form>

        <div class="flex justify-between text-sm text-muted-foreground">
            <Link
                :href="login()"
                class="text-foreground underline decoration-neutral-300 underline-offset-4"
            >
                Use a different email
            </Link>
            <Link
                :href="resend()"
                method="post"
                as="button"
                class="text-foreground underline decoration-neutral-300 underline-offset-4"
            >
                Resend code
            </Link>
        </div>
    </div>
</template>
