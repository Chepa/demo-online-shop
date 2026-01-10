<script setup lang="ts">
import AdminHeader from '@/components/AdminHeader.vue';
</script>

<template>
    <div class="min-h-screen flex flex-col bg-gray-100">
        <AdminHeader />

        <main class="flex-1">
            <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>


