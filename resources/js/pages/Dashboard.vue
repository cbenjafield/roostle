<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const page = usePage();
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    return hour < 18 ? 'Good afternoon' : 'Good evening';
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-8 p-6 md:p-10">
        <h1 class="text-4xl font-extrabold tracking-tighter md:text-5xl">
            {{ greeting }}, {{ firstName }}.
        </h1>

        <div
            class="flex max-w-xl items-center gap-5 rounded-2xl border-2 border-foreground bg-card p-6 shadow-ink-lg"
        >
            <div
                class="flex size-16 shrink-0 items-center justify-center rounded-[50%/60%_60%_40%_40%] border-2 border-foreground bg-secondary"
            >
                <AppLogoIcon class="size-10" />
            </div>
            <div class="space-y-1">
                <h2 class="text-lg font-bold">Nothing here yet</h2>
                <p class="text-sm text-muted-foreground">
                    This is where your things will show up once you start
                    using Roostle.
                </p>
            </div>
        </div>
    </div>
</template>
