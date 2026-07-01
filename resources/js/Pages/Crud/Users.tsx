import ResourceIndex, { ResourceColumn, ResourceField, ResourceRow } from './ResourceIndex';
import { PageProps } from '@/types';

type Props = PageProps<{
    users: ResourceRow[];
    departments: ResourceRow[];
    roles: ResourceRow[];
}>;

export default function Users({ auth, users, departments, roles }: Props) {
    const departmentOptions = departments.map((department) => ({ label: department.name, value: String(department.id) }));
    const roleOptions = roles.map((role) => ({ label: role.name, value: role.name }));

    const fields: ResourceField[] = [
        { name: 'name', label: 'Nombre', required: true },
        { name: 'email', label: 'Correo', required: true },
        {
            name: 'role',
            label: 'Rol',
            type: 'select',
            required: true,
            options: roleOptions,
            initialValue: (row) => row.roles?.[0]?.name ?? '',
        },
        {
            name: 'department_ids',
            label: 'Departamentos',
            type: 'multiselect',
            options: departmentOptions,
            initialValue: (row) => row.departments?.map((department: ResourceRow) => String(department.id)) ?? [],
        },
        { name: 'password', label: 'Contraseña', type: 'password', requiredOnCreate: true },
    ];

    const columns: ResourceColumn[] = [
        { key: 'name', header: 'Usuario' },
        { key: 'email', header: 'Correo' },
        { key: 'roles', header: 'Rol', render: (row) => row.roles?.map((role: ResourceRow) => role.name).join(', ') || '-' },
        { key: 'departments', header: 'Departamentos', render: (row) => row.departments?.map((department: ResourceRow) => department.name).join(', ') || '-' },
        { key: 'created_at', header: 'Creado', type: 'date' },
    ];

    return (
        <ResourceIndex
            auth={auth}
            title="Usuarios"
            description="Gestiona usuarios, roles y departamentos asignados."
            items={users}
            fields={fields}
            columns={columns}
            storeUrl="/users"
            resourceUrl="/users"
            searchPlaceholder="Buscar usuarios..."
        />
    );
}
