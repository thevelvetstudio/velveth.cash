import { useEffect, FormEventHandler } from 'react';
import { BarChart3, LockKeyhole, Mail, ShieldCheck, Sparkles, WalletCards } from 'lucide-react';
import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }: { status?: string; canResetPassword: boolean }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    useEffect(() => {
        return () => {
            reset('password');
        };
    }, []);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'));
    };

    return (
        <main className="min-h-screen overflow-hidden bg-[#050505] text-white">
            <Head title="Login" />

            <div className="grid min-h-screen lg:grid-cols-[1.08fr_0.92fr]">
                <section className="relative hidden border-r border-white/10 px-10 py-10 lg:flex lg:flex-col lg:justify-between">
                    <div className="absolute inset-0 bg-[linear-gradient(135deg,rgba(139,0,24,0.38),transparent_42%),radial-gradient(circle_at_80%_20%,rgba(212,175,55,0.16),transparent_34rem)]" />
                    <div className="relative">
                        <div className="flex items-center gap-3">
                            <div className="flex h-11 w-11 items-center justify-center rounded-md border border-[#D4AF37]/30 bg-[#8B0018]/35">
                                <Sparkles className="h-5 w-5 text-[#D4AF37]" />
                            </div>
                            <div>
                                <div className="text-lg font-semibold">The Velvet Studio</div>
                                <div className="text-xs text-muted-foreground">Financial OS</div>
                            </div>
                        </div>
                    </div>

                    <div className="relative max-w-2xl">
                        <p className="text-sm uppercase tracking-[0.28em] text-[#D4AF37]">Operación financiera</p>
                        <h1 className="mt-5 text-5xl font-semibold leading-tight tracking-normal">
                            Control elegante para compras, inventario y flujo de caja.
                        </h1>
                        <p className="mt-5 max-w-xl text-base leading-7 text-muted-foreground">
                            Entra al panel para revisar movimientos, presupuestos, aprobaciones y reportes de The Velvet Studio.
                        </p>
                    </div>

                    <div className="relative grid gap-4 xl:grid-cols-3">
                        {[
                            { label: 'Flujo', value: 'COP', icon: WalletCards },
                            { label: 'Reportes', value: 'Live', icon: BarChart3 },
                            { label: 'Acceso', value: 'Seguro', icon: ShieldCheck },
                        ].map((item) => (
                            <div key={item.label} className="rounded-lg border border-white/10 bg-[#0D0A0A]/80 p-4 shadow-xl shadow-black/25">
                                <item.icon className="h-5 w-5 text-[#D4AF37]" />
                                <div className="mt-4 text-sm text-muted-foreground">{item.label}</div>
                                <div className="mt-1 text-xl font-semibold">{item.value}</div>
                            </div>
                        ))}
                    </div>
                </section>

                <section className="flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:px-12">
                    <div className="w-full max-w-md">
                        <div className="mb-8 lg:hidden">
                            <div className="text-xl font-semibold">The Velvet Studio</div>
                            <p className="text-sm text-muted-foreground">Financial OS</p>
                        </div>

                        <Card className="rounded-lg border-white/10 bg-[#0D0A0A]/90">
                            <CardHeader className="space-y-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-md border border-[#D4AF37]/25 bg-[#8B0018]/25">
                                    <LockKeyhole className="h-5 w-5 text-[#D4AF37]" />
                                </div>
                                <div>
                                    <CardTitle className="text-2xl">Iniciar sesión</CardTitle>
                                    <CardDescription>Accede al panel financiero y operativo.</CardDescription>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {status && <div className="mb-4 rounded-md border border-emerald-400/20 bg-emerald-400/10 px-3 py-2 text-sm text-emerald-200">{status}</div>}

                                <form onSubmit={submit} className="space-y-5">
                                    <div className="space-y-2">
                                        <label htmlFor="email" className="text-sm font-medium text-white">
                                            Email
                                        </label>
                                        <div className="relative">
                                            <Mail className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                            <Input
                                                id="email"
                                                type="email"
                                                name="email"
                                                value={data.email}
                                                className="pl-10"
                                                autoComplete="username"
                                                autoFocus
                                                placeholder="admin@velvet.test"
                                                onChange={(e) => setData('email', e.target.value)}
                                            />
                                        </div>
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="space-y-2">
                                        <label htmlFor="password" className="text-sm font-medium text-white">
                                            Contraseña
                                        </label>
                                        <div className="relative">
                                            <LockKeyhole className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                            <Input
                                                id="password"
                                                type="password"
                                                name="password"
                                                value={data.password}
                                                className="pl-10"
                                                autoComplete="current-password"
                                                placeholder="contraseña"
                                                onChange={(e) => setData('password', e.target.value)}
                                            />
                                        </div>
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="flex items-center justify-between gap-4">
                                        <label className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Checkbox checked={data.remember} onCheckedChange={(checked) => setData('remember', checked === true)} />
                                            Recordarme
                                        </label>

                                        {canResetPassword && (
                                            <Link href={route('password.request')} className="text-sm text-[#D4AF37] hover:text-[#FFD000]">
                                                Olvidaste tu contraseña?
                                            </Link>
                                        )}
                                    </div>

                                    <Button type="submit" className="w-full" disabled={processing}>
                                        {processing ? 'Entrando...' : 'Entrar'}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    </div>
                </section>
            </div>
        </main>
    );
}
