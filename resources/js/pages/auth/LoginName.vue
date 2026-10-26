<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login/name';

defineOptions({
    layout: {
        title: 'Welcome!',
        description: 'Tell us your name to finish creating your account',
    },
});
</script>

<template>
    <Head title="Your name" />

    <Form
        v-bind="store.form()"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    type="text"
                    name="name"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <Button
                type="submit"
                class="w-full"
                :tabindex="2"
                :disabled="processing"
                data-test="create-account-button"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>
        </div>
    </Form>
</template>
