import { useMemo } from 'react';
import { FileText } from 'lucide-react';
import ResourceIndex, { ResourceColumn, ResourceField, ResourceRow } from './ResourceIndex';
import { PageProps } from '@/types';

type Props = PageProps<{
    movements: ResourceRow[];
    departments: ResourceRow[];
    paymentMethods: ResourceRow[];
    ocrDraft?: Record<string, any> | null;
}>;

const statuses = ['Pendiente', 'Aprobado', 'Pagado', 'Rechazado', 'Cancelado'];

export default function FinancialMovements({ auth, movements, departments, paymentMethods, ocrDraft }: Props) {
    const ocrInitialData = useMemo(() => ocrDraft ? { ...ocrDraft, amount: ocrDraft.total_amount ?? '', movement_date: ocrDraft.date ?? '', concept: ocrDraft.supplier_name ? `Gasto - ${ocrDraft.supplier_name}` : 'Gasto escaneado', type: 'Gasto', status: 'Pendiente', ocr_token: ocrDraft.token } : null, [ocrDraft]);
    const departmentOptions = departments.map((department) => ({ label: department.name, value: String(department.id) }));
    const paymentMethodOptions = paymentMethods.map((paymentMethod) => ({ label: paymentMethod.name, value: paymentMethod.code }));
    const fields: ResourceField[] = [
        { name: 'concept', label: 'Concepto', required: true },
        { name: 'supplier_name', label: 'Proveedor' },
        { name: 'supplier_tax_id', label: 'NIT / CC' },
        { name: 'invoice_number', label: 'Factura' },
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
        { key: 'support_url', header: 'Soporte', render: (row) => row.support_url ? <a href={String(row.support_url)} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 text-[#D4AF37] hover:text-white"><FileText className="h-4 w-4" />Ver</a> : <span className="text-white/40">-</span> },
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
            initialData={ocrInitialData}
        />
    );
}
