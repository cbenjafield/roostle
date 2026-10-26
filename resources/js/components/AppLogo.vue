<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const name = usePage().props.name;
</script>

<template>
    <div
        class="flex size-8 items-center justify-center rounded-[50%/60%_60%_40%_40%] border-2 border-foreground bg-sidebar-primary"
    >
        <AppLogoIcon class="size-5" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-extrabold tracking-tight">{{
            name
        }}</span>
    </div>
</template>
