import { useRef, useState } from 'react';
import axios from 'axios';
import { Plus } from 'lucide-react';
import toast from 'react-hot-toast';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import ResourceIndex, { ResourceColumn, ResourceField, ResourceRow } from './ResourceIndex';
import { PageProps } from '@/types';

type Props = PageProps<{
    items: ResourceRow[];
    departments: ResourceRow[];
    categories: { id: number; name: string; department_id?: number | null }[];
}>;

const statuses = ['Disponible', 'Agotado', 'En mantenimiento', 'Cancelado'];
const locations = ['Oficina', 'Fuera de la oficina', 'Otro'];

function searchableText(value: unknown): string {
    return String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
}

export default function InventoryItems({ auth, items, departments, categories }: Props) {
    const [categoryOptions, setCategoryOptions] = useState(categories);
    const [categoryDialogOpen, setCategoryDialogOpen] = useState(false);
    const [newCategoryName, setNewCategoryName] = useState('');
    const [newCategoryDepartmentId, setNewCategoryDepartmentId] = useState('');
    const categorySetter = useRef<((value: string) => void) | null>(null);
    const departmentOptions = departments.map((department) => ({ label: department.name, value: String(department.id) }));
    const categorySelectOptions = categoryOptions.map((category) => ({ label: category.name, value: String(category.id) }));

    function requestCategory(setValue: (value: string) => void, values: Record<string, any>) {
        categorySetter.current = setValue;
        setNewCategoryName('');
        setNewCategoryDepartmentId(String(values.department_id ?? ''));
        setCategoryDialogOpen(true);
    }

    async function createCategory() {
        const name = newCategoryName.trim();
        if (!name || !newCategoryDepartmentId) {
            toast.error('Selecciona un departamento antes de crear la categoría.');
            return;
        }

        try {
            const response = await axios.post('/inventory-categories', { name, department_id: Number(newCategoryDepartmentId) });
            const category = response.data;
            setCategoryOptions((current) => [...current, category].sort((a, b) => a.name.localeCompare(b.name)));
            categorySetter.current?.(String(category.id));
            setCategoryDialogOpen(false);
        } catch (error: any) {
            toast.error(error?.response?.data?.message ?? 'No se pudo crear la categoría.');
        }
    }
    const fields: ResourceField[] = [
        { name: 'name', label: 'Nombre', required: true },
        { name: 'department_id', label: 'Departamento', type: 'select', required: true, options: departmentOptions },
        { name: 'category_id', label: 'Categoría', type: 'select', options: categorySelectOptions, initialValue: (row) => row.category_id ? String(row.category_id) : '', createOption: { label: '+ Nueva categoría', onCreate: requestCategory }, suggestOption: (values, options) => {
            const department = departmentOptions.find((option) => option.value === String(values.department_id));
            const productText = searchableText(values.name);
            const departmentText = searchableText(department?.label);
            const source = `${productText} ${departmentText}`;
            if (!source.trim()) return null;
            const hints: Record<string, string[]> = {
                tecnologia: ['tecnologia', 'tech', 'laptop', 'computador', 'monitor', 'celular', 'impresora', 'electronico'],
                mobiliario: ['mueble', 'silla', 'escritorio', 'mesa', 'estante', 'mobiliario'],
                papeleria: ['papel', 'cuaderno', 'lapiz', 'carpeta', 'papeleria'],
                herramientas: ['herramienta', 'taladro', 'martillo', 'destornillador'],
            };
            let best: { value: string; score: number } | null = null;
            options.forEach((option) => {
                const category = searchableText(option.label);
                const categoryRecord = categoryOptions.find((item) => String(item.id) === option.value);
                const tokens = category.split(/[^a-z0-9]+/).filter((token) => token.length > 2);
                let score = tokens.reduce((total, token) => total + (productText.includes(token) ? 3 : 0) + (departmentText.includes(token) ? 5 : 0), 0);
                if (categoryRecord?.department_id && String(categoryRecord.department_id) === String(values.department_id)) score += 10;
                Object.entries(hints).forEach(([group, words]) => {
                    if (category.includes(group) && words.some((word) => source.includes(word))) score += 2;
                });
                if (score > (best?.score ?? 0)) best = { value: option.value, score };
            });
            return (best as { value: string; score: number } | null)?.value ?? null;
        } },
        { name: 'quantity', label: 'Cantidad', type: 'number', required: true },
        { name: 'unit_cost', label: 'Costo unitario', type: 'number', required: true },
        { name: 'location', label: 'Ubicación', type: 'select', options: locations.map((value) => ({ label: value, value })) },
        { name: 'status', label: 'Estado', type: 'select', required: true, options: statuses.map((value) => ({ label: value, value })) },
        { name: 'image', label: 'Imagen', type: 'file', accept: 'image/*', previewKey: 'image_url' },
        { name: 'notes', label: 'Notas', type: 'textarea' },
    ];
    const columns: ResourceColumn[] = [
        { key: 'image_url', header: 'Imagen', type: 'image' },
        { key: 'name', header: 'Item' },
        { key: 'sku', header: 'SKU' },
        { key: 'department.name', header: 'Departamento' },
        { key: 'quantity', header: 'Cantidad' },
        { key: 'category_name', header: 'Categoría' },
        { key: 'unit_cost', header: 'Costo', type: 'money' },
        { key: 'status', header: 'Estado', type: 'status' },
    ];

    return (
        <>
            <ResourceIndex
            auth={auth}
            title="Inventario"
            description="Controla los bienes disponibles en bodega, su cantidad, ubicación y estado."
            items={items}
            fields={fields}
            columns={columns}
            storeUrl="/inventory-items"
            resourceUrl="/inventory-items"
            searchPlaceholder="Buscar inventario..."
            />
            <Dialog open={categoryDialogOpen} onOpenChange={setCategoryDialogOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Nueva categoría</DialogTitle>
                        <DialogDescription>Crea una categoría y quedará seleccionada en el artículo.</DialogDescription>
                    </DialogHeader>
                    <Input autoFocus value={newCategoryName} onChange={(event) => setNewCategoryName(event.target.value)} placeholder="Ej. Tecnología" onKeyDown={(event) => { if (event.key === 'Enter') { event.preventDefault(); void createCategory(); } }} />
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setCategoryDialogOpen(false)}>Cancelar</Button>
                        <Button type="button" disabled={!newCategoryName.trim()} onClick={() => void createCategory()}><Plus className="h-4 w-4" />Crear categoría</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
