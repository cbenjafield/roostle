<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
        role="img"
        aria-label="Chicken"
    >
        <text
            x="20"
            y="20"
            font-size="34"
            text-anchor="middle"
            dominant-baseline="central"
        >
            🐔
        </text>
    </svg>
</template>
