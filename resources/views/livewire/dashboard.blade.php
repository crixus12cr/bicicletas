<div>
    @if($esAdmin)
        <!-- VISTA DE ADMINISTRADOR -->
        <div class="space-y-6">
            <h1 class="text-2xl font-bold dark:text-white">Panel de Administración</h1>
            
            <!-- Tarjetas de métricas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:card class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Servicios Activos</p>
                            <p class="text-2xl font-bold">{{ $serviciosActivos }}</p>
                        </div>
                        <flux:icon name="wrench" class="size-8 text-blue-500" />
                    </div>
                </flux:card>
                
                <flux:card class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Servicios para hoy</p>
                            <p class="text-2xl font-bold">{{ $serviciosHoy }}</p>
                        </div>
                        <flux:icon name="calendar" class="size-8 text-green-500" />
                    </div>
                </flux:card>
                
                <flux:card class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Ingresos hoy</p>
                            <p class="text-2xl font-bold">${{ number_format($ingresosHoy, 0, ',', '.') }}</p>
                        </div>
                        <flux:icon name="currency-dollar" class="size-8 text-yellow-500" />
                    </div>
                </flux:card>
            </div>
            
            <!-- Servicios por estado (gráfico sencillo) -->
            <flux:card class="p-4">
                <h2 class="text-lg font-semibold mb-3">Servicios por Estado</h2>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                    @foreach(['pendiente', 'en_progreso', 'esperando_piezas', 'listo', 'entregado'] as $estado)
                        <div class="text-center p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                            <p class="text-xs text-zinc-500">{{ ucfirst($estado) }}</p>
                            <p class="text-xl font-bold">{{ $serviciosPorEstado[$estado] ?? 0 }}</p>
                        </div>
                    @endforeach
                </div>
            </flux:card>
            
            <!-- Últimos servicios registrados -->
            <flux:card class="p-4">
                <h2 class="text-lg font-semibold mb-3">Últimos Servicios</h2>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>ID</flux:table.column>
                        <flux:table.column>Cliente</flux:table.column>
                        <flux:table.column>Bicicleta</flux:table.column>
                        <flux:table.column>Mecánico</flux:table.column>
                        <flux:table.column>Estado</flux:table.column>
                        <flux:table.column>Total</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($serviciosRecientes as $servicio)
                            <flux:table.row>
                                <flux:table.cell>{{ $servicio->id }}</flux:table.cell>
                                <flux:table.cell>{{ $servicio->cliente->name ?? 'N/A' }}</flux:table.cell>
                                <flux:table.cell>{{ Str::limit($servicio->descripcion_bicicleta, 30) }}</flux:table.cell>
                                <flux:table.cell>{{ $servicio->mecanico->name ?? 'Sin asignar' }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="match($servicio->estado) {
                                        'pendiente' => 'gray',
                                        'en_progreso' => 'blue',
                                        'esperando_piezas' => 'yellow',
                                        'listo' => 'green',
                                        'entregado' => 'zinc',
                                        default => 'gray'
                                    }">{{ ucfirst($servicio->estado) }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>${{ number_format($servicio->precio_total, 0, ',', '.') }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
                <div class="mt-3 text-right">
                    <flux:button href="{{ route('admin.servicios.todos') }}" wire:navigate variant="subtle">Ver todos los servicios</flux:button>
                </div>
            </flux:card>
        </div>
    @else
        <!-- VISTA PARA MECÁNICOS -->
        <div class="space-y-6">
            <h1 class="text-2xl font-bold dark:text-white">Servicios Disponibles del Día</h1>
            <p class="text-zinc-500">Estos son los servicios pendientes con fecha estimada para hoy. Asígnate los que puedas atender.</p>
            
            @if($serviciosDisponibles->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($serviciosDisponibles as $servicio)
                        <flux:card class="p-4 hover:shadow-lg transition-shadow">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-lg">{{ $servicio->cliente->name }}</h3>
                                    <p class="text-sm text-zinc-500">{{ $servicio->cliente->telefono }}</p>
                                </div>
                                <flux:badge color="gray">Pendiente</flux:badge>
                            </div>
                            
                            <div class="mt-3 space-y-2">
                                <div>
                                    <p class="text-xs text-zinc-500">Bicicleta</p>
                                    <p class="text-sm">{{ $servicio->descripcion_bicicleta }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-zinc-500">Notas</p>
                                    <p class="text-sm">{{ $servicio->notas ?: 'Sin notas' }}</p>
                                </div>
                                <div class="flex justify-between items-center pt-2">
                                    <div>
                                        <p class="text-xs text-zinc-500">Precio base</p>
                                        <p class="font-medium">${{ number_format($servicio->precio_base, 0, ',', '.') }}</p>
                                    </div>
                                    <flux:button wire:click="tomarServicio({{ $servicio->id }})" variant="primary" size="sm">
                                        Tomar servicio
                                    </flux:button>
                                </div>
                            </div>
                        </flux:card>
                    @endforeach
                </div>
            @else
                <flux:card class="p-12 text-center">
                    <flux:icon name="check-circle" class="size-12 mx-auto text-green-500 mb-3" />
                    <h3 class="text-lg font-medium">No hay servicios disponibles hoy</h3>
                    <p class="text-zinc-500">Todos los servicios para hoy ya han sido asignados o no hay servicios programados.</p>
                </flux:card>
            @endif
            
            <div class="mt-6 text-center text-sm text-zinc-500">
                * Los servicios que tomes aparecerán en "Mis Servicios Activos" para que puedas gestionar su estado y añadir extras.
            </div>
        </div>
    @endif
</div>