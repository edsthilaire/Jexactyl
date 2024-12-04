import http, { FractalResponseData } from '@/api/http';

export type OrderStatus = 'pending' | 'expired' | 'failed' | 'processed';

export interface Order {
    id: number;
    name: string;
    user_id: number;
    description: string;
    total: number;
    status: OrderStatus;
    product_id: number;
    is_renewal: boolean;
    created_at: Date;
    updated_at?: Date | null;
}

export const rawDataToOrder = ({ attributes: data }: FractalResponseData): Order => ({
    id: data.id,
    name: data.name,
    user_id: data.user_id,
    description: data.description,
    total: data.total,
    status: data.status,
    product_id: data.product_id,
    is_renewal: data.is_renewal,
    created_at: new Date(data.created_at),
    updated_at: data.updated_at ? new Date(data.updated_at) : null,
});

export default (): Promise<Order[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/billing/orders`)
            .then(({ data }) => resolve((data.data || []).map((datum: any) => rawDataToOrder(datum))))
            .catch(reject);
    });
};
