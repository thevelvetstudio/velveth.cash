import { Head, Link } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { AlertTriangle, ArrowDownRight, ArrowUpRight, Package, WalletCards } from 'lucide-react';
import { Line, LineChart, ResponsiveContainer, Tooltip as ChartTooltip, XAxis, YAxis, Bar, BarChart } from 'recharts';
import { AppLayout } from '@/Components/layout/AppLayout';
import { FinancialCard } from '@/Components/dashboard/FinancialCard';
import { BudgetProgress } from '@/Components/dashboard/BudgetProgress';
import { DataTable } from '@/Components/data-table/DataTable';
import { MoneyDisplay } from '@/Components/shared/MoneyDisplay';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { SectionTitle } from '@/Components/shared/SectionTitle';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { QuickActionMenu } from '@/Components/shared/QuickActionMenu';
import { ScanExpenseDialog } from '@/Components/shared/ScanExpenseDialog';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { formatDate } from '@/lib/format';
import { PageProps } from '@/types';

type Movement = {
    id: number;
    concept: string;
    department?: {
        name: string;
    } | null;
    amount: number | string;
    status: string;
    movement_date: string;
};

type DashboardDepartment = {
    id: number;
    name: string;
    manager?: string | null;
    monthly_budget: number | string;
    status: string;
    spent?: number | string | null;
};

type DashboardMetrics = {
    monthlyIncome: number | string;
    previousMonthlyIncome: number | string;
    monthlyExpenses: number | string;
    pendingPurchasesAmount: number | string;
    pendingPurchasesCount: number;
    netFlow: number | string;
    totalBudget: number | string;
    inventoryItemCount: number;
};

type ChartPoint = {
    month: string;
    ingresos: number | string;
    gastos: number | string;
};

type SectorPoint = {
    name: string;
    value: number | string;
};

const columns: ColumnDef<Movement>[] = [
    { accessorKey: 'concept', header: 'Movimiento' },
    { accessorKey: 'department.name', header: 'Departamento', cell: ({ row }) => row.original.department?.name ?? '-' },
    { accessorKey: 'amount', header: 'Valor', cell: ({ row }) => <MoneyDisplay value={Number(row.original.amount)} /> },
    { accessorKey: 'status', header: 'Estado', cell: ({ row }) => <StatusBadge status={row.original.status} /> },
    { accessorKey: 'movement_date', header: 'Fecha', cell: ({ row }) => formatDate(row.original.movement_date) },
    { id: 'actions', cell: () => <QuickActionMenu /> },
];

const emptyMetrics: DashboardMetrics = {
    monthlyIncome: 0,
    previousMonthlyIncome: 0,
    monthlyExpenses: 0,
    pendingPurchasesAmount: 0,
    pendingPurchasesCount: 0,
    netFlow: 0,
    totalBudget: 0,
    inventoryItemCount: 0,
};

function incomeDetail(currentIncome: number, previousIncome: number) {
    if (previousIncome <= 0) {
        return currentIncome > 0 ? 'Sin comparativo del mes anterior' : 'Sin ingresos registrados este mes';
    }

    const variation = ((currentIncome - previousIncome) / previousIncome) * 100;
    const sign = variation >= 0 ? '+' : '';

    return `${sign}${variation.toFixed(1)}% vs mes anterior`;
}

function budgetDetail(expenses: number, budget: number) {
    if (budget <= 0) {
        return 'Sin presupuesto mensual configurado';
    }

    return `${Math.round((expenses / budget) * 100)}% del presupuesto mensual`;
}

function netFlowDetail(netFlow: number) {
    if (netFlow > 0) {
        return 'Margen operativo positivo';
    }

    if (netFlow < 0) {
        return 'Flujo negativo este mes';
    }

    return 'Ingresos y gastos equilibrados';
}

export default function Dashboard({
    auth,
    departments = [],
    metrics = emptyMetrics,
    cashFlow = [],
    sectors = [],
    latestMovements = [],
}: PageProps<{
    departments: DashboardDepartment[];
    metrics: DashboardMetrics;
    cashFlow: ChartPoint[];
    sectors: SectorPoint[];
    latestMovements: Movement[];
}>) {
    const monthlyIncome = Number(metrics.monthlyIncome ?? 0);
    const previousMonthlyIncome = Number(metrics.previousMonthlyIncome ?? 0);
    const monthlyExpenses = Number(metrics.monthlyExpenses ?? 0);
    const pendingPurchasesAmount = Number(metrics.pendingPurchasesAmount ?? 0);
    const pendingPurchasesCount = Number(metrics.pendingPurchasesCount ?? 0);
    const netFlow = Number(metrics.netFlow ?? 0);
    const totalBudget = Number(metrics.totalBudget ?? 0);

    return (
        <AppLayout user={auth.user}>
            <Head title="Dashboard" />

            <div className="mx-auto flex max-w-7xl flex-col gap-6">
                <PageHeader
                    title="Dashboard financiero"
                    description="Resumen operativo, compras pendientes, alertas e inventario crítico."
                    actions={
                        <div className="flex flex-wrap gap-2"><ScanExpenseDialog /><Button asChild><Link href="/financial-movements?create=1">Nuevo movimiento</Link></Button></div>
                    }
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <FinancialCard title="Ingresos del mes" amount={monthlyIncome} detail={incomeDetail(monthlyIncome, previousMonthlyIncome)} />
                    <FinancialCard title="Gastos del mes" amount={monthlyExpenses} detail={budgetDetail(monthlyExpenses, totalBudget)} />
                    <FinancialCard title="Compras pendientes" amount={pendingPurchasesAmount} detail={`${pendingPurchasesCount} solicitudes por aprobar`} />
                    <FinancialCard title="Flujo neto" amount={netFlow} detail={netFlowDetail(netFlow)} />
                </div>

                <Tabs defaultValue="general" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="general">General</TabsTrigger>
                        <TabsTrigger value="departamentos">Departamentos</TabsTrigger>
                        <TabsTrigger value="alertas">Alertas</TabsTrigger>
                    </TabsList>

                    <TabsContent value="general" className="grid gap-4 xl:grid-cols-[1.5fr_1fr]">
                        <Card>
                            <CardHeader>
                                <CardTitle>Ingresos vs gastos</CardTitle>
                            </CardHeader>
                            <CardContent className="h-80">
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={cashFlow}>
                                        <XAxis dataKey="month" stroke="#B7B7B7" />
                                        <YAxis stroke="#B7B7B7" tickFormatter={(value) => `$${Number(value) / 1000000}M`} />
                                        <ChartTooltip formatter={(value) => <MoneyDisplay value={Number(value)} />} />
                                        <Line type="monotone" dataKey="ingresos" stroke="#D4AF37" strokeWidth={3} dot={false} />
                                        <Line type="monotone" dataKey="gastos" stroke="#C1002B" strokeWidth={3} dot={false} />
                                    </LineChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Gastos por sector</CardTitle>
                            </CardHeader>
                            <CardContent className="h-80">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={sectors}>
                                        <XAxis dataKey="name" stroke="#B7B7B7" />
                                        <YAxis stroke="#B7B7B7" tickFormatter={(value) => `$${Number(value) / 1000000}M`} />
                                        <ChartTooltip formatter={(value) => <MoneyDisplay value={Number(value)} />} />
                                        <Bar dataKey="value" fill="#8B0018" radius={[6, 6, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="departamentos" className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {departments.length > 0 ? (
                            departments.map((department) => (
                                <Card key={department.id}>
                                    <CardHeader>
                                        <div className="flex items-start justify-between gap-3">
                                            <SectionTitle title={department.name} description={department.manager || 'Presupuesto mensual'} />
                                            <StatusBadge status={department.status} />
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <BudgetProgress
                                            label="Consumido"
                                            spent={Number(department.spent ?? 0)}
                                            budget={Number(department.monthly_budget ?? 0)}
                                        />
                                    </CardContent>
                                </Card>
                            ))
                        ) : (
                            <div className="md:col-span-2 xl:col-span-3">
                                <EmptyState title="Sin departamentos" description="Crea departamentos para ver su presupuesto y consumo en este panel." />
                            </div>
                        )}
                    </TabsContent>

                    <TabsContent value="alertas" className="grid gap-4 md:grid-cols-3">
                        <Alert>
                            <AlertTriangle className="h-4 w-4" />
                            <AlertTitle>Bienes en bodega</AlertTitle>
                            <AlertDescription>{Number(metrics.inventoryItemCount ?? 0)} referencias registradas.</AlertDescription>
                        </Alert>
                        <Alert>
                            <Package className="h-4 w-4" />
                            <AlertTitle>Compras pendientes</AlertTitle>
                            <AlertDescription>{pendingPurchasesCount} solicitudes esperan aprobación financiera.</AlertDescription>
                        </Alert>
                        <Alert>
                            <WalletCards className="h-4 w-4" />
                            <AlertTitle>{netFlow >= 0 ? 'Flujo positivo' : 'Flujo negativo'}</AlertTitle>
                            <AlertDescription>{netFlowDetail(netFlow)}.</AlertDescription>
                        </Alert>
                    </TabsContent>
                </Tabs>

                <div className="grid gap-4 xl:grid-cols-[1fr_22rem]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Últimos movimientos</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <DataTable columns={columns} data={latestMovements} searchPlaceholder="Buscar movimientos..." />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Señales rápidas</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between rounded-md border border-white/10 p-3">
                                <span className="flex items-center gap-2 text-sm text-muted-foreground"><ArrowUpRight className="h-4 w-4 text-emerald-300" /> Ingresos</span>
                                <MoneyDisplay value={monthlyIncome} />
                            </div>
                            <div className="flex items-center justify-between rounded-md border border-white/10 p-3">
                                <span className="flex items-center gap-2 text-sm text-muted-foreground"><ArrowDownRight className="h-4 w-4 text-red-300" /> Gastos</span>
                                <MoneyDisplay value={monthlyExpenses} />
                            </div>
                            <div className="flex items-center justify-between rounded-md border border-white/10 p-3">
                                <span className="text-sm text-muted-foreground">Estado general</span>
                                <StatusBadge status={netFlow >= 0 ? 'Aprobado' : 'Pendiente'} />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
