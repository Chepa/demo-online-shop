import {defineStore} from 'pinia';
import {ref} from 'vue';
import {api} from '@/lib/api';
import type {Category, Product, ProductFilters} from '@/types/product';
import type {PaginationData, PaginationLink, PaginationMeta} from '@/types/pagination';

export type {Category, Product, ProductFilters};

export const useProductStore = defineStore('product', () => {
    const products = ref<Product[]>([]);
    const categories = ref<Category[]>([]);
    const currentProduct = ref<Product | null>(null);
    const loading = ref(false);
    const filters = ref<ProductFilters>({});
    const pagination = ref<PaginationMeta | null>(null);
    const paginationLinks = ref<PaginationLink[]>([]);
    const currentPage = ref(1);

    async function fetchCategories() {
        try {
            const {data} = await api.get<Category[]>('/categories');
            categories.value = data;
        } catch (error) {
            console.error('Ошибка загрузки категорий:', error);
        }
    }

    async function fetchProducts(productFilters?: ProductFilters & { page?: number }) {
        loading.value = true;
        try {
            if (productFilters) {
                const {page, ...rest} = productFilters;
                filters.value = rest;
                if (page && page > 0) {
                    currentPage.value = page;
                }
            }

            const params: Record<string, any> = {};
            if (filters.value.category_id) params.category_id = filters.value.category_id;
            if (filters.value.search) params.search = filters.value.search;
            if (filters.value.min_price) params.min_price = filters.value.min_price;
            if (filters.value.max_price) params.max_price = filters.value.max_price;
            if (currentPage.value && currentPage.value > 1) params.page = currentPage.value;

            const {data} = await api.get<PaginationData<Product> | Product[]>('/products', {params});

            if (Array.isArray(data)) {
                products.value = data;
                pagination.value = null;
                paginationLinks.value = [];
            } else {
                products.value = data.data || [];
                pagination.value = {
                    current_page: data.current_page || 1,
                    last_page: data.last_page || 1,
                    per_page: data.per_page || data.data.length || 0,
                    total: data.total || data.data.length || 0,
                    from: data.from || 0,
                    to: data.to || 0,
                };
                paginationLinks.value = data.links || [];
            }
        } catch (error) {
            console.error('Ошибка загрузки товаров:', error);
            products.value = [];
            pagination.value = null;
            paginationLinks.value = [];
        } finally {
            loading.value = false;
        }
    }

    async function fetchProduct(id: number) {
        loading.value = true;
        try {
            const {data} = await api.get<Product>(`/products/${id}`);
            currentProduct.value = data;
            return data;
        } catch (error) {
            console.error('Ошибка загрузки товара:', error);
            currentProduct.value = null;
            throw error;
        } finally {
            loading.value = false;
        }
    }

    function setFilters(newFilters: ProductFilters) {
        filters.value = {...filters.value, ...newFilters};
    }

    function clearFilters() {
        filters.value = {};
    }

    return {
        products,
        categories,
        currentProduct,
        loading,
        filters,
        pagination,
        paginationLinks,
        currentPage,
        fetchCategories,
        fetchProducts,
        fetchProduct,
        setFilters,
        clearFilters,
    };
});

