<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
</script>

<template>
    <Head title="Welcome" />

    <div class="flex min-h-svh flex-col bg-background p-6 md:p-10">
        <header class="flex justify-end">
            <Button v-if="$page.props.auth.user" variant="outline" as-child>
                <Link :href="dashboard()">Dashboard</Link>
            </Button>
            <Button v-else variant="outline" as-child>
                <Link :href="login()">Log in</Link>
            </Button>
        </header>

        <main
            class="mx-auto flex w-full max-w-3xl flex-1 flex-col items-start justify-center gap-8"
        >
            <div
                class="flex size-28 items-center justify-center rounded-[50%/60%_60%_40%_40%] border-2 border-foreground bg-secondary shadow-ink-lg"
            >
                <AppLogoIcon class="size-20" />
            </div>

            <h1
                class="text-7xl leading-none font-extrabold tracking-tighter sm:text-8xl"
            >
                Roostle
            </h1>

            <p class="max-w-md text-lg text-muted-foreground">
                Sign in with just your email. We'll send you a 6 digit code, so
                there's no password to remember.
            </p>

            <Button v-if="!$page.props.auth.user" size="lg" as-child>
                <Link :href="login()">Log in or sign up</Link>
            </Button>
        </main>
    </div>
</template>
