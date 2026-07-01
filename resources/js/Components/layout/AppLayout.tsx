import { PropsWithChildren } from 'react';
import { Link } from '@inertiajs/react';
import { BarChart3, Bell, Building2, ChevronUp, LayoutDashboard, LogOut, Menu, Package, Settings, UserCircle, Users, WalletCards, Warehouse } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Separator } from '@/Components/ui/separator';
import { Sheet, SheetContent, SheetTrigger } from '@/Components/ui/sheet';
import { cn } from '@/lib/utils';
import { User } from '@/types';

const navItems = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { label: 'Finanzas', href: '/financial-movements', icon: WalletCards },
    { label: 'Compras', href: '/purchases', icon: Package },
    { label: 'Inventario', href: '/inventory-items', icon: Warehouse },
    { label: 'Departamentos', href: '/departments', icon: Building2 },
    { label: 'Usuarios', href: '/users', icon: Users },
    { label: 'Reportes', href: '/reports', icon: BarChart3 },
];

function SidebarContent() {
    const currentPath = window.location.pathname;

    return (
        <div className="flex h-full flex-col">
            <div className="px-4 py-5">
                <div className="text-lg font-semibold text-white">The Velvet Studio</div>
                <p className="text-xs text-muted-foreground">Financial OS</p>
            </div>
            <Separator />
            <nav className="flex-1 space-y-1 p-3">
                {navItems.map((item) => (
                    <Link key={item.label} href={item.href} className={cn('flex items-center gap-3 rounded-md px-3 py-2 text-sm text-muted-foreground transition hover:bg-white/10 hover:text-white', currentPath === item.href && 'bg-[#8B0018]/25 text-white')}>
                        <item.icon className="h-4 w-4" />
                        {item.label}
                    </Link>
                ))}
            </nav>
            <div className="p-3">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="outline" className={cn('w-full justify-between border-white/10 bg-white/5 text-muted-foreground hover:bg-white/10 hover:text-white', currentPath === '/profile' && 'bg-[#8B0018]/25 text-white')}>
                            <span className="flex items-center gap-2">
                                <Settings className="h-4 w-4" />
                                Ajustes
                            </span>
                            <ChevronUp className="h-4 w-4 opacity-60" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent side="top" align="start" className="w-64 border-white/10 bg-[#0D0A0A] shadow-2xl shadow-black/50">
                        <DropdownMenuLabel className="text-xs text-muted-foreground">Cuenta</DropdownMenuLabel>
                        <DropdownMenuItem asChild className="gap-2 px-3 py-2 text-sm hover:bg-[#8B0018]/20">
                            <Link href="/profile">
                                <UserCircle className="h-4 w-4 text-[#D4AF37]" />
                                Perfil
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild className="gap-2 px-3 py-2 text-sm text-red-200 hover:bg-[#C1002B]/15">
                            <Link href={route('logout')} method="post" as="button" className="w-full">
                                <LogOut className="h-4 w-4" />
                                Cerrar sesión
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    );
}

function UserMenu({ user }: { user: User }) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="h-10 gap-2 px-2">
                    <Avatar>
                        <AvatarFallback>{user.name.slice(0, 2).toUpperCase()}</AvatarFallback>
                    </Avatar>
                    <ChevronUp className="h-4 w-4 text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56 border-white/10 bg-[#0D0A0A] shadow-2xl shadow-black/50">
                <DropdownMenuLabel>
                    <div className="text-sm text-white">{user.name}</div>
                    <div className="text-xs font-normal text-muted-foreground">{user.email}</div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild className="gap-2 px-3 py-2 hover:bg-[#8B0018]/20">
                    <Link href="/profile">
                        <UserCircle className="h-4 w-4 text-[#D4AF37]" />
                        Perfil
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild className="gap-2 px-3 py-2 text-red-200 hover:bg-[#C1002B]/15">
                    <Link href={route('logout')} method="post" as="button" className="w-full">
                        <LogOut className="h-4 w-4" />
                        Cerrar sesión
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

export function AppLayout({ user, children }: PropsWithChildren<{ user: User }>) {
    return (
        <div className="min-h-screen">
            <aside className="fixed inset-y-0 left-0 hidden w-72 border-r border-white/10 bg-[#080606]/90 backdrop-blur lg:block">
                <SidebarContent />
            </aside>
            <div className="lg:pl-72">
                <header className="sticky top-0 z-30 border-b border-white/10 bg-[#050505]/80 backdrop-blur">
                    <div className="flex h-16 items-center justify-between px-4 sm:px-6">
                        <div className="flex items-center gap-3">
                            <Sheet>
                                <SheetTrigger asChild>
                                    <Button variant="ghost" size="icon" className="lg:hidden">
                                        <Menu className="h-5 w-5" />
                                    </Button>
                                </SheetTrigger>
                                <SheetContent className="left-0 right-auto w-72 border-r border-white/10 p-0">
                                    <SidebarContent />
                                </SheetContent>
                            </Sheet>
                            <div className="text-sm text-muted-foreground">Operación financiera</div>
                        </div>
                        <div className="flex items-center gap-3">
                            <Button variant="ghost" size="icon">
                                <Bell className="h-4 w-4" />
                            </Button>
                            <UserMenu user={user} />
                        </div>
                    </div>
                </header>
                <main className="px-4 py-6 sm:px-6 lg:px-8">{children}</main>
            </div>
        </div>
    );
}
