import ResourceIndex, { ResourceColumn, ResourceField, ResourceRow } from './ResourceIndex';
import { PageProps } from '@/types';

type Props = PageProps<{
    departments: ResourceRow[];
}>;

const fields: ResourceField[] = [
    { name: 'name', label: 'Nombre', required: true },
    { name: 'code', label: 'Código', required: true },
    { name: 'manager', label: 'Responsable' },
    { name: 'monthly_budget', label: 'Presupuesto mensual', type: 'number', required: true },
    { name: 'status', label: 'Estado', type: 'select', required: true, options: ['Activo', 'Pausado', 'Cerrado'].map((value) => ({ label: value, value })) },
    { name: 'notes', label: 'Notas', type: 'textarea' },
];

const columns: ResourceColumn[] = [
    { key: 'name', header: 'Departamento' },
    { key: 'code', header: 'Código' },
    { key: 'manager', header: 'Responsable' },
    { key: 'monthly_budget', header: 'Presupuesto', type: 'money' },
    { key: 'status', header: 'Estado', type: 'status' },
];

export default function Departments({ auth, departments }: Props) {
    return (
        <ResourceIndex
            auth={auth}
            title="Departamentos"
            description="Gestiona áreas, responsables y presupuesto mensual."
            items={departments}
            fields={fields}
            columns={columns}
            storeUrl="/departments"
            resourceUrl="/departments"
            searchPlaceholder="Buscar departamentos..."
        />
    );
}
