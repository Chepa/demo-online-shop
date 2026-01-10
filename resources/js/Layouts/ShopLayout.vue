<script setup lang="ts">
import { onMounted } from 'vue';
import { RouterView } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useCartStore } from '@/stores/cart';
import ShopHeader from '@/components/ShopHeader.vue';

const auth = useAuthStore();
const cart = useCartStore();

onMounted(async () => {
    await auth.fetchUser();

    if (auth.isAuthenticated && cart.items.length === 0 && !cart.loading) {
        await cart.fetch();
    }
});
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <ShopHeader />
        <main class="flex-1 bg-gray-50">
            <RouterView />
        </main>
    </div>
</template>


