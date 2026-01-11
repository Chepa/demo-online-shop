<script setup lang="ts">
import {reactive, ref} from 'vue';
import {useRouter} from 'vue-router';
import {useAuthStore} from '@/stores/auth';

const router = useRouter();
const auth = useAuthStore();
const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
});
const errors = ref<Record<string, string>>({});
const processing = ref(false);
const roles = ref<[string, string]>(['admin', 'user']);

const submit = async () => {
    processing.value = true;
    errors.value = {};
    try {
        const response = await auth.register(
            form.name,
            form.email,
            form.password,
            form.password_confirmation,
            form.role,
        );

        if (response.success) {
            await router.push('/');
        }

    } catch (e: any) {
        const data = e?.response?.data;
        if (data?.errors) {
            Object.keys(data.errors).forEach((key) => {
                errors.value[key] = data.errors[key][0];
            });
        } else if (data?.message) {
            errors.value['general'] = data.message;
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
                Регистрация
            </h1>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Имя</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :class="{ 'border-red-500': errors.name }"
                    >
                    <p v-if="errors.name" class="mt-1 text-sm text-red-600">
                        {{ errors.name }}
                    </p>
                </div>

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
                        {{ errors.email }}
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
                        {{ errors.password }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Подтверждение пароля</label>
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :class="{ 'border-red-500': errors.password_confirmation }"
                    >
                    <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600">
                        {{ errors.password_confirmation }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Роль</label>
                    <select
                        v-model="form.role"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :class="{ 'border-red-500': errors.password_confirmation }"
                    >
                        <option v-for="role in roles" :value="role">{{ role }}
                        </option>
                    </select>
                    <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600">
                        {{ errors.password_confirmation }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="processing"
                    class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                    <span v-if="processing">Регистрация...</span>
                    <span v-else>Зарегистрироваться</span>
                </button>
            </form>
        </div>
    </div>
</template>

