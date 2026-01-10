export interface OrderItem {
    id: number;
    order_id?: number;
    product_id: number;
    quantity: number;
    price: string;
    product?: {
        id: number;
        name: string;
        price?: string;
    };
}

export interface Order {
    id: number;
    user_id: number;
    status: string;
    total: string;
    customer_name?: string;
    customer_phone?: string;
    address_line?: string;
    city?: string;
    postal_code?: string;
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
    };
    items?: OrderItem[];
}
