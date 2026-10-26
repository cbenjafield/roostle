<script setup lang="ts">
import { Form, Head, Link, usePage, usePoll } from '@inertiajs/vue3';
import { Paperclip, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InboxController from '@/actions/App/Http/Controllers/InboxController';
import InboxPasswordController from '@/actions/App/Http/Controllers/InboxPasswordController';
import MessageController from '@/actions/App/Http/Controllers/MessageController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { index, show } from '@/routes/inboxes';
import { raw } from '@/routes/inboxes/messages';
import { show as attachmentShow } from '@/routes/inboxes/messages/attachments';
import type {
    InboxDetails,
    MessageDetails,
    MessageParty,
    MessageSummary,
    Paginated,
} from '@/types/inbox';

const props = defineProps<{
    inbox: InboxDetails;
    messages: Paginated<MessageSummary>;
    message: MessageDetails | null;
}>();

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

usePoll(5000, { only: ['messages'] });

const page = usePage();
const revealedPassword = ref<string | null>(null);

watch(
    () => (page.flash as { smtpPassword?: string }).smtpPassword,
    (password) => {
        if (password) {
            revealedPassword.value = password;
        }
    },
    { immediate: true },
);

const tabs = ['HTML', 'Text', 'Headers', 'Attachments'] as const;
const activeTab = ref<(typeof tabs)[number]>('HTML');

watch(
    () => props.message?.id,
    () => {
        activeTab.value = props.message?.html_body ? 'HTML' : 'Text';
    },
    { immediate: true },
);

const formatParties = (parties: MessageParty[]): string =>
    parties.map((party) => party.name ? `${party.name} <${party.address}>` : party.address).join(', ');

const formatSize = (bytes: number): string =>
    bytes < 1024 ? `${bytes} B` : `${(bytes / 1024).toFixed(1)} KB`;

const formatDate = (date: string): string => new Date(date).toLocaleString();

const sender = computed(() =>
    props.message
        ? formatParties([
              { address: props.message.from_address, name: props.message.from_name },
          ])
        : '',
);
</script>

<template>
    <Head :title="inbox.name" />

    <div class="flex flex-1 flex-col gap-6 p-6 md:p-10">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <h1 class="text-4xl font-extrabold tracking-tighter">
                    {{ inbox.name }}
                </h1>
                <p class="font-mono text-sm text-muted-foreground">
                    {{ inbox.catch_address }}
                </p>
            </div>

            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive" size="sm">Delete inbox</Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="InboxController.destroy.form(inbox.id)"
                        class="space-y-6"
                        v-slot="{ processing }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Delete this inbox?</DialogTitle>
                            <DialogDescription>
                                The inbox, its messages and its SMTP credentials
                                will be permanently deleted.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary">Cancel</Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                            >
                                Delete inbox
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <section
            class="grid gap-3 rounded-2xl border-2 border-foreground bg-card p-5 text-sm shadow-ink-lg"
        >
            <h2 class="font-bold">SMTP credentials</h2>
            <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-1 font-mono">
                <dt class="text-muted-foreground">Host</dt>
                <dd>{{ inbox.smtp_host }}</dd>
                <dt class="text-muted-foreground">Port</dt>
                <dd>{{ inbox.smtp_port }}</dd>
                <dt class="text-muted-foreground">Username</dt>
                <dd>{{ inbox.smtp_username }}</dd>
                <dt class="text-muted-foreground">Password</dt>
                <dd v-if="revealedPassword" data-test="smtp-password">
                    {{ revealedPassword }}
                </dd>
                <dd v-else class="text-muted-foreground">
                    Hidden. Regenerate it to see a new one.
                </dd>
            </dl>
            <p v-if="revealedPassword" class="text-muted-foreground">
                Copy this password now. It won't be shown again.
            </p>
            <Form
                v-bind="InboxPasswordController.update.form(inbox.id)"
                :options="{ preserveScroll: true }"
                v-slot="{ processing }"
            >
                <Button
                    type="submit"
                    variant="outline"
                    size="sm"
                    :disabled="processing"
                >
                    Regenerate password
                </Button>
            </Form>
        </section>

        <div class="grid min-h-96 gap-6 lg:grid-cols-[22rem_1fr]">
            <nav aria-label="Messages" class="space-y-2">
                <p
                    v-if="messages.data.length === 0"
                    class="text-muted-foreground"
                >
                    No messages yet. Send one to
                    <span class="font-mono">{{ inbox.catch_address }}</span>.
                </p>

                <Link
                    v-for="item in messages.data"
                    :key="item.id"
                    :href="show(inbox.id, { query: { message: item.id } })"
                    preserve-scroll
                    :class="[
                        'block rounded-xl border-2 p-3',
                        message?.id === item.id
                            ? 'border-foreground bg-secondary'
                            : 'border-transparent hover:border-foreground/40',
                    ]"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span
                            :class="[
                                'truncate text-sm',
                                item.is_read ? '' : 'font-bold',
                            ]"
                        >
                            {{ item.from_name || item.from_address }}
                        </span>
                        <Paperclip
                            v-if="item.attachments_count > 0"
                            class="size-3.5 shrink-0"
                        />
                    </div>
                    <p
                        :class="[
                            'truncate text-sm',
                            item.is_read ? 'text-muted-foreground' : 'font-semibold',
                        ]"
                    >
                        {{ item.subject || '(no subject)' }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ formatDate(item.received_at) }}
                    </p>
                </Link>

                <div
                    v-if="messages.prev_page_url || messages.next_page_url"
                    class="flex justify-between pt-2 text-sm"
                >
                    <Link v-if="messages.prev_page_url" :href="messages.prev_page_url">
                        Newer
                    </Link>
                    <span v-else />
                    <Link v-if="messages.next_page_url" :href="messages.next_page_url">
                        Older
                    </Link>
                </div>
            </nav>

            <article
                v-if="message"
                class="min-w-0 space-y-4 rounded-2xl border-2 border-foreground bg-card p-5 shadow-ink-lg"
            >
                <header class="space-y-2">
                    <div class="flex items-start justify-between gap-4">
                        <h2 class="text-xl font-bold">
                            {{ message.subject || '(no subject)' }}
                        </h2>
                        <Form
                            v-bind="MessageController.destroy.form({ inbox: inbox.id, message: message.id })"
                            v-slot="{ processing }"
                        >
                            <Button
                                type="submit"
                                variant="ghost"
                                size="icon"
                                aria-label="Delete message"
                                :disabled="processing"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </Form>
                    </div>
                    <dl class="grid grid-cols-[auto_1fr] gap-x-4 text-sm">
                        <dt class="text-muted-foreground">From</dt>
                        <dd class="break-all">{{ sender }}</dd>
                        <dt class="text-muted-foreground">To</dt>
                        <dd class="break-all">{{ formatParties(message.to) }}</dd>
                        <template v-if="message.cc.length > 0">
                            <dt class="text-muted-foreground">Cc</dt>
                            <dd class="break-all">{{ formatParties(message.cc) }}</dd>
                        </template>
                        <dt class="text-muted-foreground">Received</dt>
                        <dd>
                            {{ formatDate(message.received_at) }} ·
                            {{ formatSize(message.size) }} ·
                            <a :href="raw.url({ inbox: inbox.id, message: message.id })" class="underline">
                                Download .eml
                            </a>
                        </dd>
                    </dl>
                </header>

                <div role="tablist" class="flex gap-1 border-b-2 border-foreground">
                    <button
                        v-for="tab in tabs"
                        :key="tab"
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === tab"
                        :class="[
                            'rounded-t-lg px-3 py-1.5 text-sm font-medium',
                            activeTab === tab ? 'bg-foreground text-background' : 'hover:bg-secondary',
                        ]"
                        @click="activeTab = tab"
                    >
                        {{ tab }}
                        <template v-if="tab === 'Attachments'">
                            ({{ message.attachments.length }})
                        </template>
                    </button>
                </div>

                <div role="tabpanel">
                    <template v-if="activeTab === 'HTML'">
                        <iframe
                            v-if="message.html_body"
                            sandbox=""
                            :srcdoc="message.html_body"
                            title="HTML preview"
                            class="h-[32rem] w-full rounded-lg border bg-white"
                        />
                        <p v-else class="text-sm text-muted-foreground">
                            This message has no HTML part.
                        </p>
                    </template>

                    <template v-else-if="activeTab === 'Text'">
                        <pre
                            v-if="message.text_body"
                            class="overflow-x-auto whitespace-pre-wrap text-sm"
                            >{{ message.text_body }}</pre
                        >
                        <p v-else class="text-sm text-muted-foreground">
                            This message has no plain text part.
                        </p>
                    </template>

                    <dl
                        v-else-if="activeTab === 'Headers'"
                        class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 font-mono text-xs"
                    >
                        <template
                            v-for="(header, position) in message.headers"
                            :key="position"
                        >
                            <dt class="font-semibold">{{ header.name }}</dt>
                            <dd class="break-all">{{ header.value }}</dd>
                        </template>
                    </dl>

                    <ul v-else class="space-y-2 text-sm">
                        <li v-if="message.attachments.length === 0" class="text-muted-foreground">
                            No attachments.
                        </li>
                        <li v-for="attachment in message.attachments" :key="attachment.id">
                            <a
                                :href="attachmentShow.url({ inbox: inbox.id, message: message.id, attachment: attachment.id })"
                                class="underline"
                            >
                                {{ attachment.filename }}
                            </a>
                            <span class="text-muted-foreground">
                                · {{ attachment.content_type }} · {{ formatSize(attachment.size) }}
                            </span>
                        </li>
                    </ul>
                </div>
            </article>

            <p v-else class="text-muted-foreground">
                Select a message to read it.
            </p>
        </div>
    </div>
</template>
