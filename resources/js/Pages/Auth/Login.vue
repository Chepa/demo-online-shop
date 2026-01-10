<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { api } from '@/lib/api';

const router = useRouter();
const auth = useAuthStore();

const form = ref({
    email: '',
    password: '',
    remember: false,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const submit = async () => {
    processing.value = true;
    errors.value = {};

    try {
        const result = await auth.login(form.value.email, form.value.password);
        
        if (result.success) {
            const redirect = router.currentRoute.value.query.redirect as string | undefined;
            await router.push(redirect || '/');
        } else {
            errors.value = { email: result.message || 'Неверные учетные данные' };
        }
    } catch (error: any) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            errors.value = { email: error.response?.data?.message || 'Произошла ошибка при входе' };
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <div class="min-h-[70vh] flex items-center justify-center">
        <div class="w-full max-w-md bg-white shadow rounded-lg p-6 space-y-4">
            <h1 class="text-xl font-semibold text-gray-900">
                Вход
            </h1>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :class="{ 'border-red-500': errors.email }"
                    >
                    <p v-if="errors.email" class="mt-1 text-sm text-red-600">
                        {{ Array.isArray(errors.email) ? errors.email[0] : errors.email }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Пароль</label>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :class="{ 'border-red-500': errors.password }"
                    >
                    <p v-if="errors.password" class="mt-1 text-sm text-red-600">
                        {{ Array.isArray(errors.password) ? errors.password[0] : errors.password }}
                    </p>
                </div>

                <div>
                    <label class="flex items-center">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                        <span class="ml-2 text-sm text-gray-600">Запомнить меня</span>
                    </label>
                </div>

                <button
                    type="submit"
                    :disabled="processing"
                    class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                    <span v-if="processing">Вход...</span>
                    <span v-else>Войти</span>
                </button>
            </form>
        </div>
    </div>
</template>
