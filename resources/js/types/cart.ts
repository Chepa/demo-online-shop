export interface CartProduct {
    id: number;
    name: string;
    price: string;
    slug?: string;
    image?: string;
}

export interface CartItem {
    id: number;
    product_id: number;
    quantity: number;
    product: CartProduct;
}
