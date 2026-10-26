<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center bg-background p-4 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div
                class="flex flex-col gap-8 rounded-2xl border-2 border-foreground bg-card p-6 shadow-ink-lg sm:p-8"
            >
                <div class="flex flex-col items-center gap-5">
                    <Link
                        :href="home()"
                        class="flex size-16 items-center justify-center rounded-[50%/60%_60%_40%_40%] border-2 border-foreground bg-secondary shadow-ink"
                    >
                        <AppLogoIcon class="size-10" />
                        <span class="sr-only">Roostle home</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-2xl font-extrabold">{{ title }}</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
