<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible="mobile"
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <!-- ========== DASHBOARD ========== -->
            <flux:sidebar.group heading="Plataforma">
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            <!-- ========== SERVICIOS ========== -->
            <flux:sidebar.group heading="Servicios" expandable icon="wrench"
                :expanded="request()->routeIs('servicios*')">
                
                <!-- Todos los servicios (solo administrador) -->
                @if(auth()->user()->hasRole('admin'))
                    <flux:sidebar.item icon="clipboard-document-list" href="#"
                        :current="request()->routeIs('servicios.todos')" wire:navigate>
                        {{ __('Todos los Servicios') }}
                    </flux:sidebar.item>
                @endif

                <!-- Mis servicios activos (visible para admin y mecánico) -->
                <flux:sidebar.item icon="clock" href="#"
                    :current="request()->routeIs('servicios.mis-activos')" wire:navigate>
                    {{ __('Mis Servicios Activos') }}
                </flux:sidebar.item>

                <!-- Servicios disponibles (solo mecánico) -->
                @if(auth()->user()->hasRole('mecanico'))
                    <flux:sidebar.item icon="arrow-right-circle" href="#"
                        :current="request()->routeIs('servicios.disponibles')" wire:navigate>
                        {{ __('Servicios Disponibles') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.group>

            <!-- ========== USUARIOS Y ROLES (solo admin) ========== -->
            @if(auth()->user()->hasRole('admin'))
                <flux:sidebar.group heading="Usuarios y Roles" expandable icon="users"
                    :expanded="request()->routeIs('admin.usuarios*')">
                    <flux:sidebar.item icon="user-group" href="{{ route('admin.usuarios.gestion') }}"
                        :current="request()->routeIs('admin.usuarios.gestion')" wire:navigate>
                        {{ __('Gestión de Usuarios') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            @endif

            <!-- ========== REPORTES ========== -->
            <flux:sidebar.group heading="Reportes" expandable icon="document-text"
                :expanded="request()->routeIs('reportes*')">
                
                <flux:sidebar.item icon="chart-pie" href="#"
                    :current="request()->routeIs('reportes.ingresos')" wire:navigate>
                    {{ __('Ingresos') }}
                </flux:sidebar.item>
                
                <flux:sidebar.item icon="clock" href="#"
                    :current="request()->routeIs('reportes.retrasos')" wire:navigate>
                    {{ __('Servicios Retrasados') }}
                </flux:sidebar.item>

                @if(auth()->user()->hasRole('admin'))
                    <flux:sidebar.item icon="users" href="#"
                        :current="request()->routeIs('reportes.por-mecanico')" wire:navigate>
                        {{ __('Desempeño por Mecánico') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-down-tray" href="#"
                        :current="request()->routeIs('reportes.exportar')" wire:navigate>
                        {{ __('Exportar Datos') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.group>

            <!-- ========== CONFIGURACIÓN (solo admin) ========== -->
            @if(auth()->user()->hasRole('admin'))
                <flux:sidebar.group heading="Configuración" expandable icon="cog-6-tooth"
                    :expanded="request()->routeIs('config*')">
                    <flux:sidebar.item icon="currency-dollar" href="#"
                        :current="request()->routeIs('config.precio-base')" wire:navigate>
                        {{ __('Precio Base') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chat-bubble-left-right" href="#"
                        :current="request()->routeIs('config.plantillas-whatsapp')" wire:navigate>
                        {{ __('Plantillas WhatsApp') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="device-phone-mobile" href="#"
                        :current="request()->routeIs('config.telefono-taller')" wire:navigate>
                        {{ __('Teléfono del Taller') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            @endif
        </flux:sidebar.nav>

        <flux:sidebar.spacer />

        <!-- Modo oscuro -->
        <flux:button x-data x-on:click="$flux.dark = ! $flux.dark" icon="moon" variant="subtle"
            aria-label="Toggle dark mode" class="justify-center" />

        <!-- Perfil de usuario con menú desplegable -->
        <flux:dropdown position="top" align="start">
            <flux:sidebar.profile :name="auth()->user()->name" :avatar="auth()->user()->avatar" />

            <flux:menu class="min-w-64">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Configuración') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('Cerrar Sesión') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Configuración') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('Cerrar Sesión') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @fluxScripts
</body>

</html>