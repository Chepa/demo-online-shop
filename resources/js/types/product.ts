export interface Category {
    id: number;
    name: string;
    slug?: string;
    parent_id?: number | null;
    products_count?: number;
    created_at?: string;
    updated_at?: string;
    parent?: Category;
}

export interface Product {
    id: number;
    name: string;
    slug: string;
    description?: string;
    price: string;
    stock: number;
    category_id: number;
    category?: Category;
    image?: string;
    is_active: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface ProductFilters {
    category_id?: number | null;
    search?: string;
    min_price?: number;
    max_price?: number;
}
