<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Inbox as InboxIcon } from '@lucide/vue';
import InboxController from '@/actions/App/Http/Controllers/InboxController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, show } from '@/routes/inboxes';
import type { InboxSummary } from '@/types/inbox';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Inboxes',
                href: index(),
            },
        ],
    },
});

defineProps<{
    inboxes: InboxSummary[];
}>();
</script>

<template>
    <Head title="Inboxes" />

    <div class="flex flex-1 flex-col gap-8 p-6 md:p-10">
        <div class="space-y-1">
            <h1 class="text-4xl font-extrabold tracking-tighter">Inboxes</h1>
            <p class="text-muted-foreground">
                Each inbox has its own catch address and SMTP credentials.
            </p>
        </div>

        <Form
            v-bind="InboxController.store.form()"
            class="flex max-w-xl items-start gap-3"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <div class="grid flex-1 gap-2">
                <Label for="name" class="sr-only">Inbox name</Label>
                <Input
                    id="name"
                    name="name"
                    placeholder="New inbox name"
                    required
                    maxlength="255"
                />
                <InputError :message="errors.name" />
            </div>
            <Button type="submit" :disabled="processing">Create inbox</Button>
        </Form>

        <p v-if="inboxes.length === 0" class="text-muted-foreground">
            You don't have any inboxes yet. Create one to get an address to
            send mail to.
        </p>

        <ul v-else class="grid max-w-3xl gap-4">
            <li v-for="inbox in inboxes" :key="inbox.id">
                <Link
                    :href="show(inbox.id)"
                    class="flex items-center gap-4 rounded-2xl border-2 border-foreground bg-card p-5 shadow-ink-lg transition-transform hover:-translate-y-0.5"
                >
                    <InboxIcon class="size-6 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <h2 class="font-bold">{{ inbox.name }}</h2>
                        <p class="truncate font-mono text-sm text-muted-foreground">
                            {{ inbox.catch_address }}
                        </p>
                    </div>
                    <Badge v-if="inbox.unread_messages_count > 0">
                        {{ inbox.unread_messages_count }} unread
                    </Badge>
                    <span class="text-sm text-muted-foreground">
                        {{ inbox.messages_count }}
                        {{ inbox.messages_count === 1 ? 'message' : 'messages' }}
                    </span>
                </Link>
            </li>
        </ul>
    </div>
</template>
