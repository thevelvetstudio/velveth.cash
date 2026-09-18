import { ChangeEvent, DragEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { Camera, CheckCircle2, FileImage, FileUp, Loader2, ScanLine, UploadCloud } from 'lucide-react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';

type Draft = { token: string; supplier_name?: string | null; supplier_tax_id?: string | null; invoice_number?: string | null; date?: string | null; subtotal?: number | null; discount_amount?: number | null; tax_amount?: number | null; total_amount?: number | null; line_items?: { quantity: number; description: string; unit_price: number; tax_rate?: number | null; total: number }[]; totals_valid?: boolean | null; totals_difference?: number | null; confidence?: string | null; support_name?: string };

const money = (value?: number | null) => value == null ? 'No identificado' : new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(value));

export function ScanExpenseDialog({ label = 'Escanear gasto', className = '' }: { label?: string; className?: string }) {
    const [open, setOpen] = useState(false);
    const [file, setFile] = useState<File | null>(null);
    const [preview, setPreview] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [draft, setDraft] = useState<Draft | null>(null);

    function choose(next: File | null) {
        if (!next) return;
        setError(null); setDraft(null); setFile(next);
        setPreview(next.type.startsWith('image/') ? URL.createObjectURL(next) : null);
    }
    function onInput(event: ChangeEvent<HTMLInputElement>) { choose(event.target.files?.[0] ?? null); }
    function onDrop(event: DragEvent<HTMLLabelElement>) { event.preventDefault(); choose(event.dataTransfer.files?.[0] ?? null); }
    async function analyze() {
        if (!file) return;
        setLoading(true); setError(null);
        const form = new FormData(); form.append('document', file);
        try { const response = await axios.post('/financial-movements/analyze-document', form, { headers: { 'Content-Type': 'multipart/form-data' } }); setDraft(response.data.draft); }
        catch (reason: any) { setError(reason?.response?.data?.message ?? 'No pudimos leer correctamente este documento. Puedes intentar con otra imagen o registrar el gasto manualmente.'); }
        finally { setLoading(false); }
    }
    function continueToForm() { if (draft?.token) router.visit(`/financial-movements?create=1&ocr_token=${encodeURIComponent(draft.token)}`); }
    function reset() { setFile(null); setPreview(null); setDraft(null); setError(null); setLoading(false); }

    return <>
        <Button type="button" variant="outline" className={className} onClick={() => { reset(); setOpen(true); }}><ScanLine className="h-4 w-4" />{label}</Button>
        <Dialog open={open} onOpenChange={setOpen}><DialogContent className="max-w-xl border-white/10 bg-[#0D0A0A]"><DialogHeader><DialogTitle className="flex items-center gap-2"><ScanLine className="h-5 w-5 text-[#D4AF37]" />Escanear gasto</DialogTitle><DialogDescription>Sube una factura, recibo o comprobante y completaremos los datos automáticamente.</DialogDescription></DialogHeader>
            {!draft && !loading && <div className="space-y-4"><div className="grid gap-3 sm:grid-cols-2"><label className="flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-[#D4AF37]/50 bg-[#D4AF37]/10 p-4 text-sm font-medium hover:bg-[#D4AF37]/20"><Camera className="h-5 w-5 text-[#D4AF37]" />Tomar foto<input className="sr-only" type="file" accept="image/*" capture="environment" onChange={onInput} /></label><label className="flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-white/15 bg-white/5 p-4 text-sm font-medium hover:bg-white/10"><FileUp className="h-5 w-5" />Subir documento<input className="sr-only" type="file" accept="image/*,.pdf" onChange={onInput} /></label></div><label onDragOver={(event) => event.preventDefault()} onDrop={onDrop} className="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-white/20 bg-white/[0.03] p-6 text-center hover:border-[#D4AF37]/60"><UploadCloud className="mb-2 h-8 w-8 text-[#D4AF37]" /><span className="text-sm">Arrastra un archivo aquí</span><span className="mt-1 text-xs text-muted-foreground">JPG, JPEG, PNG o PDF · máximo 10 MB</span><input className="sr-only" type="file" accept="image/*,.pdf" onChange={onInput} /></label>{file && <div className="rounded-lg border border-white/10 bg-white/[0.03] p-3">{preview ? <img src={preview} alt="Vista previa del comprobante" className="mb-3 max-h-48 w-full rounded-md object-contain" /> : <div className="flex h-32 items-center justify-center"><FileImage className="h-12 w-12 text-[#D4AF37]" /></div>}<div className="flex items-center justify-between gap-3"><span className="truncate text-sm">{file.name}</span><Button type="button" onClick={analyze}><ScanLine className="h-4 w-4" />Analizar documento</Button></div></div>}</div>}
            {loading && <div className="space-y-4 py-6"><div className="flex items-center gap-3"><Loader2 className="h-5 w-5 animate-spin text-[#D4AF37]" /><span>Analizando documento...</span></div><Skeleton className="h-20 w-full" /><Skeleton className="h-20 w-full" /></div>}
            {draft && !loading && <div className="space-y-4"><div className="flex items-center gap-2 text-emerald-300"><CheckCircle2 className="h-5 w-5" />Documento analizado <span className="ml-auto text-xs text-white/60">Confianza: {draft.confidence ?? 'No disponible'}</span></div><div className="grid gap-3 rounded-lg border border-white/10 bg-white/[0.03] p-4 sm:grid-cols-2"><div><p className="text-xs text-muted-foreground">Proveedor</p><p>{draft.supplier_name ?? 'No identificado'}</p></div><div><p className="text-xs text-muted-foreground">NIT</p><p>{draft.supplier_tax_id ?? 'No identificado'}</p></div><div><p className="text-xs text-muted-foreground">Factura</p><p>{draft.invoice_number ?? 'No identificada'}</p></div><div><p className="text-xs text-muted-foreground">Fecha</p><p>{draft.date ?? 'No identificada'}</p></div><div><p className="text-xs text-muted-foreground">Subtotal</p><p>{money(draft.subtotal)}</p></div><div><p className="text-xs text-muted-foreground">IVA</p><p>{money(draft.tax_amount)}</p></div><div><p className="text-xs text-muted-foreground">Total</p><p className="text-xl font-semibold text-[#D4AF37]">{money(draft.total_amount)}</p></div><div><p className="text-xs text-muted-foreground">Validación matemática</p><p className={draft.totals_valid === false ? 'text-red-300' : 'text-emerald-300'}>{draft.totals_valid === true ? 'Subtotal + IVA = total' : draft.totals_valid === false ? `Revisar valores (${money(Math.abs(draft.totals_difference ?? 0))})` : 'No disponible'}</p></div></div>{Boolean(draft.line_items?.length) && <div className="rounded-lg border border-white/10 bg-white/[0.03] p-4"><p className="mb-2 text-sm font-medium">Conceptos detectados ({draft.line_items?.length})</p><div className="space-y-2 text-sm">{draft.line_items?.map((item, index) => <div key={`${item.description}-${index}`} className="flex items-center justify-between gap-3"><span className="truncate">{item.quantity} × {item.description}</span><span className="shrink-0 text-white/70">{money(item.total)}</span></div>)}</div></div>}<Button className="w-full" onClick={continueToForm}>Continuar con el registro</Button></div>}
            {error && <div className="rounded-lg border border-red-400/30 bg-red-500/10 p-3 text-sm text-red-200">{error}<div className="mt-3 flex gap-2"><Button variant="outline" onClick={() => setError(null)}>Intentar nuevamente</Button><Button variant="outline" onClick={() => { setOpen(false); router.visit('/financial-movements?create=1'); }}>Registrar manualmente</Button></div></div>}
        </DialogContent></Dialog>
    </>;
}
