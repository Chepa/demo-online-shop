import axios from 'axios'
import {ref} from "vue"

export const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
})

// Перехватчик для добавления CSRF токена и авторизационного токена
api.interceptors.request.use(async (config) => {
    const csrfToken: string = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    if (csrfToken) {
        config.headers['X-CSRF-TOKEN'] = csrfToken
    }

    const token= ref<string | null>(localStorage.getItem('auth_token'))
    if (token.value) {
        config.headers['Authorization'] = `Bearer ${token.value}`
    }

    return config
})


