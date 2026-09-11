import ResourceIndex, { ResourceColumn, ResourceField, ResourceRow } from './ResourceIndex';
import { PageProps } from '@/types';

type Props = PageProps<{
    movements: ResourceRow[];
    departments: ResourceRow[];
    paymentMethods: ResourceRow[];
}>;

const statuses = ['Pendiente', 'Aprobado', 'Pagado', 'Rechazado', 'Cancelado'];

export default function FinancialMovements({ auth, movements, departments, paymentMethods }: Props) {
    const departmentOptions = departments.map((department) => ({ label: department.name, value: String(department.id) }));
    const paymentMethodOptions = paymentMethods.map((paymentMethod) => ({ label: paymentMethod.name, value: paymentMethod.code }));
    const fields: ResourceField[] = [
        { name: 'concept', label: 'Concepto', required: true },
        { name: 'department_id', label: 'Departamento', type: 'select', options: departmentOptions },
        { name: 'type', label: 'Tipo', type: 'select', required: true, defaultValue: 'Ingreso', options: ['Ingreso', 'Gasto'].map((value) => ({ label: value, value })) },
        { name: 'amount', label: 'Valor', type: 'number', required: true },
        { name: 'movement_date', label: 'Fecha', type: 'date', required: true },
        { name: 'status', label: 'Estado', type: 'select', required: true, options: statuses.map((value) => ({ label: value, value })) },
        { name: 'payment_method', label: 'Medio de pago', type: 'select', options: paymentMethodOptions },
        { name: 'image', label: 'Imagen', type: 'file', accept: 'image/*', previewKey: 'image_url' },
        { name: 'description', label: 'Descripción', type: 'textarea' },
    ];
    const columns: ResourceColumn[] = [
        { key: 'image_url', header: 'Imagen', type: 'image' },
        { key: 'concept', header: 'Movimiento' },
        { key: 'department.name', header: 'Departamento' },
        { key: 'type', header: 'Tipo' },
        { key: 'amount', header: 'Valor', type: 'money' },
        { key: 'status', header: 'Estado', type: 'status' },
        { key: 'movement_date', header: 'Fecha', type: 'date' },
    ];

    return (
        <ResourceIndex
            auth={auth}
            title="Finanzas"
            description="Registra ingresos, gastos, estádos y medios de pago."
            items={movements}
            fields={fields}
            columns={columns}
            storeUrl="/financial-movements"
            resourceUrl="/financial-movements"
            searchPlaceholder="Buscar movimientos..."
        />
    );
}
