export interface User {
    id: number
    name: string
    email: string
    role?: string
    email_verified_at?: string
    created_at?: string
    updated_at?: string
}

export type AuthSuccessResponse = {
    success: boolean
    data: {
        user: User
        token: string
    }
}

export type RegisterErrorResponse = {
    success: boolean
    errors: Record<string, string[]>
}

export type LoginErrorResponse = {
    success: boolean
    message?: string
}

export type UserSuccessResponse = {
    success: boolean
    data: { user: User }
}

export type UserErrorResponse = {
    success: boolean
}
