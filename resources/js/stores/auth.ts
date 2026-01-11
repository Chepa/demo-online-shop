import {defineStore} from 'pinia'
import {computed, ref} from 'vue'
import {api} from '@/lib/api';
import type {
    User,
    AuthSuccessResponse,
    RegisterErrorResponse,
    LoginErrorResponse,
    UserSuccessResponse,
    UserErrorResponse
} from '@/types/auth';

export type { User };

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null)
    const token = ref<string | null>(localStorage.getItem('auth_token'))
    const loading = ref(false)
    const isAuthenticated = computed(() => !!token.value && !!user.value)
    const isAdmin = computed(() => user.value?.role === 'admin')

    async function register(
        name: string, email: string, password: string, passwordConfirmation: string, role: string    ): Promise<
        | { success: true }
        | { success: false; errors: Record<string, string[]> }
    > {
        loading.value = true
        try {
            const response = await fetch('/api/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    name,
                    email,
                    password,
                    password_confirmation: passwordConfirmation,
                    role
                })
            })

            const result = await response.json() as AuthSuccessResponse | RegisterErrorResponse

            if (result.success) {
                token.value = result.data.token
                user.value = result.data.user
                localStorage.setItem('auth_token', result.data.token)
                return {success: true}
            } else {
                return {success: false, errors: result.errors}
            }
        } catch (error) {
            console.error('Ошибка регистрации:', error)
            return {success: false, errors: {general: ['Произошла ошибка при регистрации']}}
        } finally {
            loading.value = false
        }
    }
    async function login(
        email: string,
        password: string
    ): Promise<
        | { success: true }
        | { success: false; message: string }
    > {
        loading.value = true
        try {
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({email, password})
            })

            const result = await response.json() as AuthSuccessResponse | LoginErrorResponse

            if (result.success) {
                token.value = result.data.token
                user.value = result.data.user
                localStorage.setItem('auth_token', result.data.token)

                return {success: true}
            } else {
                return {
                    success: false,
                    message: result.message || 'Неверные учетные данные'
                }
            }
        } catch (error) {
            console.error('Ошибка входа:', error)
            return {success: false, message: 'Произошла ошибка при входе'}
        } finally {
            loading.value = false
        }
    }

    async function logout(): Promise<void> {
        try {
            await api.post('/logout')
        } catch (error) {
            console.error('Ошибка выхода:', error)
        } finally {
            token.value = null
            user.value = null
            localStorage.removeItem('auth_token')
        }
    }
    async function fetchUser(): Promise<void> {
        if (!token.value) return

        try {
            const response = await api.get<UserSuccessResponse | UserErrorResponse>('/user')
            const data = response.data

            if (data.success) {
                user.value = data.data.user
            } else {
                await logout()
            }
        } catch (error) {
            console.error('Ошибка загрузки пользователя:', error)
            await logout()
        }
    }

    return {
        user,
        token,
        loading,
        isAuthenticated,
        isAdmin,
        register,
        login,
        logout,
        fetchUser
    }
})

