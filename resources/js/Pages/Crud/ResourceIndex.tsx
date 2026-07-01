import { FormEvent, ReactNode, useEffect, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { type CellContext, type ColumnDef } from '@tanstack/react-table';
import { Edit, Image, Plus, Trash2 } from 'lucide-react';
import toast from 'react-hot-toast';
import { AppLayout } from '@/Components/layout/AppLayout';
import { DataTable } from '@/Components/data-table/DataTable';
import { MoneyDisplay } from '@/Components/shared/MoneyDisplay';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import { formatDate } from '@/lib/format';
import { PageProps } from '@/types';

export type FieldOption = {
    label: string;
    value: string;
};

export type ResourceField = {
    name: string;
    label: string;
    type?: 'text' | 'number' | 'date' | 'textarea' | 'select' | 'multiselect' | 'file' | 'password';
    options?: FieldOption[];
    required?: boolean;
    requiredOnCreate?: boolean;
    accept?: string;
    previewKey?: string;
    initialValue?: (row: ResourceRow) => any;
};

export type ResourceColumn = {
    key: string;
    header: string;
    type?: 'money' | 'status' | 'date' | 'image';
    render?: (row: ResourceRow) => ReactNode;
};

export type ResourceRow = Record<string, any> & {
    id: number;
};

type ResourceIndexProps = PageProps<{
    title: string;
    description: string;
    items: ResourceRow[];
    fields: ResourceField[];
    columns: ResourceColumn[];
    storeUrl: string;
    resourceUrl: string;
    searchPlaceholder?: string;
}>;

function emptyData(fields: ResourceField[]) {
    return fields.reduce<Record<string, any>>((carry, field) => {
        carry[field.name] = field.type === 'file' ? null : field.type === 'multiselect' ? [] : '';
        return carry;
    }, {});
}

function valueFor(row: ResourceRow, key: string) {
    return key.split('.').reduce<any>((value, part) => value?.[part], row);
}

function formValue(row: ResourceRow, field: ResourceField) {
    if (field.type === 'file') {
        return null;
    }

    if (field.initialValue) {
        return field.initialValue(row);
    }

    const value = row[field.name] ?? '';

    if (field.type === 'date' && typeof value === 'string') {
        return value.slice(0, 10);
    }

    return value;
}

export default function ResourceIndex({
    auth,
    title,
    description,
    items,
    fields,
    columns,
    storeUrl,
    resourceUrl,
    searchPlaceholder = 'Buscar...',
}: ResourceIndexProps) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<ResourceRow | null>(null);
    const { data, setData, post, put, processing, errors, reset, clearErrors, transform } = useForm<Record<string, any>>(emptyData(fields));
    const hasFileFields = fields.some((field) => field.type === 'file');

    useEffect(() => {
        if (new URLSearchParams(window.location.search).get('create') === '1') {
            setOpen(true);
        }
    }, []);

    useEffect(() => {
        if (!open) {
            setEditing(null);
            reset();
            clearErrors();
        }
    }, [open]);

    const tableColumns = useMemo<ColumnDef<ResourceRow>[]>(
        () => [
            ...columns.map((column) => ({
                accessorKey: column.key,
                header: column.header,
                cell: ({ row }: CellContext<ResourceRow, unknown>) => {
                    if (column.render) {
                        return column.render(row.original);
                    }

                    const value = valueFor(row.original, column.key);

                    if (column.type === 'money') {
                        return <MoneyDisplay value={Number(value ?? 0)} />;
                    }

                    if (column.type === 'status') {
                        return <StatusBadge status={String(value ?? 'Pendiente')} />;
                    }

                    if (column.type === 'date') {
                        return formatDate(value);
                    }

                    if (column.type === 'image') {
                        return value ? (
                            <a href={String(value)} target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 text-sm text-[#FFD000] hover:text-white">
                                <img src={String(value)} alt="" className="h-10 w-10 rounded-md border border-white/10 object-cover" />
                                Ver
                            </a>
                        ) : (
                            <span className="inline-flex items-center gap-2 text-sm text-white/50">
                                <Image className="h-4 w-4" />
                                Sin imagen
                            </span>
                        );
                    }

                    return value ?? '-';
                },
            })),
            {
                id: 'actions',
                header: '',
                cell: ({ row }) => (
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="outline" size="icon" onClick={() => edit(row.original)} aria-label="Editar">
                            <Edit className="h-4 w-4" />
                        </Button>
                        <Button type="button" variant="destructive" size="icon" onClick={() => destroy(row.original)} aria-label="Eliminar">
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    </div>
                ),
            },
        ],
        [columns],
    );

    function edit(row: ResourceRow) {
        setEditing(row);
        fields.forEach((field) => {
            setData(field.name, formValue(row, field));
        });
        setOpen(true);
    }

    function destroy(row: ResourceRow) {
        toast(
            (confirmationToast) => (
                <div className="space-y-3">
                    <div>
                        <p className="text-sm font-semibold text-white">Eliminar registro</p>
                        <p className="text-sm text-white/70">Esta acción no se puede deshacer.</p>
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button type="button" size="sm" variant="outline" onClick={() => toast.dismiss(confirmationToast.id)}>
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="destructive"
                            onClick={() => {
                                toast.dismiss(confirmationToast.id);
                                router.delete(`${resourceUrl}/${row.id}`, {
                                    preserveScroll: true,
                                    onError: () => toast.error('No se pudo eliminar el registro. Intenta nuevamente.'),
                                });
                            }}
                        >
                            Eliminar
                        </Button>
                    </div>
                </div>
            ),
            { duration: Infinity },
        );
    }

    function submit(event: FormEvent) {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
            onError: () => toast.error('Revisa los campos marcados antes de guardar.'),
        };

        if (editing) {
            if (hasFileFields) {
                transform((currentData) => ({ ...currentData, _method: 'put' }));
                post(`${resourceUrl}/${editing.id}`, {
                    ...options,
                    forceFormData: true,
                    onFinish: () => transform((currentData) => currentData),
                });
                return;
            }

            put(`${resourceUrl}/${editing.id}`, options);
            return;
        }

        post(storeUrl, { ...options, forceFormData: hasFileFields });
    }

    function toggleMultiselectValue(fieldName: string, optionValue: string, checked: boolean | 'indeterminate') {
        const values = Array.isArray(data[fieldName]) ? data[fieldName] : [];

        setData(
            fieldName,
            checked === true ? Array.from(new Set([...values, optionValue])) : values.filter((value: string) => value !== optionValue),
        );
    }

    return (
        <AppLayout user={auth.user}>
            <Head title={title} />

            <div className="mx-auto flex max-w-7xl flex-col gap-6">
                <PageHeader
                    title={title}
                    description={description}
                    actions={
                        <Button type="button" onClick={() => setOpen(true)}>
                            <Plus className="h-4 w-4" />
                            Nuevo
                        </Button>
                    }
                />

                <DataTable columns={tableColumns} data={items} searchPlaceholder={searchPlaceholder} />
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent
                    className="max-h-[90vh] overflow-y-auto sm:max-w-2xl"
                    onInteractOutside={(event) => event.preventDefault()}
                >
                    <form onSubmit={submit} className="space-y-5">
                        <DialogHeader>
                            <DialogTitle>{editing ? 'Editar registro' : 'Nuevo registro'}</DialogTitle>
                            <DialogDescription>Completa los datos y guarda los cambios.</DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-4 sm:grid-cols-2">
                            {fields.map((field) => (
                                <label key={field.name} className={field.type === 'textarea' || field.type === 'multiselect' ? 'space-y-2 sm:col-span-2' : 'space-y-2'}>
                                    <span className="text-sm font-medium text-white">
                                        {field.label}
                                        {(field.required || (!editing && field.requiredOnCreate)) && <span className="text-[#FFD000]"> *</span>}
                                    </span>
                                    {field.type === 'textarea' ? (
                                        <Textarea value={data[field.name] ?? ''} onChange={(event) => setData(field.name, event.target.value)} />
                                    ) : field.type === 'file' ? (
                                        <div className="space-y-3">
                                            {editing?.[field.previewKey ?? `${field.name}_url`] && (
                                                <a
                                                    href={editing[field.previewKey ?? `${field.name}_url`]}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="block w-fit"
                                                >
                                                    <img
                                                        src={editing[field.previewKey ?? `${field.name}_url`]}
                                                        alt=""
                                                        className="h-24 w-24 rounded-md border border-white/10 object-cover"
                                                    />
                                                </a>
                                            )}
                                            <Input
                                                type="file"
                                                accept={field.accept ?? 'image/*'}
                                                onChange={(event) => setData(field.name, event.target.files?.[0] ?? null)}
                                            />
                                        </div>
                                    ) : field.type === 'select' ? (
                                        <Select value={String(data[field.name] || 'none')} onValueChange={(value) => setData(field.name, value === 'none' ? '' : value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder={`Seleccionar ${field.label.toLowerCase()}`} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">Sin seleccionar</SelectItem>
                                                {(field.options ?? []).map((option) => (
                                                    <SelectItem key={option.value} value={option.value}>
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    ) : field.type === 'multiselect' ? (
                                        <div className="grid max-h-48 gap-2 overflow-y-auto rounded-md border border-white/10 bg-white/5 p-3 sm:grid-cols-2">
                                            {(field.options ?? []).map((option) => (
                                                <span key={option.value} className="flex items-center gap-2 text-sm text-white/85">
                                                    <Checkbox
                                                        checked={Array.isArray(data[field.name]) && data[field.name].includes(option.value)}
                                                        onCheckedChange={(checked) => toggleMultiselectValue(field.name, option.value, checked)}
                                                    />
                                                    {option.label}
                                                </span>
                                            ))}
                                        </div>
                                    ) : (
                                        <Input
                                            type={field.type ?? 'text'}
                                            value={data[field.name] ?? ''}
                                            onChange={(event) => setData(field.name, event.target.value)}
                                        />
                                    )}
                                    {errors[field.name] && <p className="text-xs text-red-300">{errors[field.name]}</p>}
                                </label>
                            ))}
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Guardando...' : 'Guardar'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
