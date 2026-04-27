<div>
    <!-- Encabezado -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold dark:text-white">Todos los Servicios</h1>
        <div class="space-x-2">
            <flux:button icon="plus" wire:click="openCreateModal" variant="primary">Nuevo Servicio</flux:button>
            <flux:button icon="funnel" wire:click="$toggle('showFilters')" variant="subtle">Filtros</flux:button>
        </div>
    </div>

    <!-- Filtros colapsables -->
    <div x-show="$wire.showFilters" x-cloak class="mb-6 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select wire:model.live="estadoFiltro" label="Estado">
                <option value="">Todos</option>
                @foreach($estados as $est)
                    <option value="{{ $est }}">{{ ucwords(str_replace('_', ' ', $est)) }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="mecanicoFiltro" label="Mecánico">
                <option value="">Todos</option>
                @foreach($mecanicos as $mec)
                    <option value="{{ $mec->id }}">{{ $mec->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="clienteFiltro" label="Cliente">
                <option value="">Todos</option>
                @foreach($clientes as $cli)
                    <option value="{{ $cli->id }}">{{ $cli->name }}</option>
                @endforeach
            </flux:select>

            <flux:input type="date" wire:model.live="fechaDesde" label="Fecha desde" />
            <flux:input type="date" wire:model.live="fechaHasta" label="Fecha hasta" />
            <flux:input wire:model.live.debounce.300ms="search" label="Buscar" placeholder="Cliente o bicicleta..." />
        </div>
        <div class="flex justify-end">
            <flux:button wire:click="resetFilters" variant="subtle">Limpiar filtros</flux:button>
        </div>
    </div>

    <!-- Tabla de servicios (sin cambios) -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium">ID</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Cliente</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Bicicleta</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Mecánico</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Total</th>
                    <th class="px-4 py-2 text-left text-xs font-medium">Fecha estimada</th>
                    <th class="px-4 py-2 text-right text-xs font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($servicios as $servicio)
                <tr wire:key="servicio-{{ $servicio->id }}">
                    <td class="px-4 py-2">{{ $servicio->id }}</td>
                    <td class="px-4 py-2">
                        {{ $servicio->cliente->name ?? 'N/A' }}<br>
                        <small class="text-zinc-500">{{ $servicio->cliente->telefono ?? '' }}</small>
                    </td>
                    <td class="px-4 py-2">{{ $servicio->descripcion_bicicleta }}</td>
                    <td class="px-4 py-2">{{ $servicio->mecanico->name ?? 'Sin asignar' }}</td>
                    <td class="px-4 py-2">
                        <flux:badge :color="match($servicio->estado) {
                            'pendiente' => 'gray',
                            'en_progreso' => 'blue',
                            'esperando_piezas' => 'yellow',
                            'listo' => 'green',
                            'entregado' => 'zinc',
                            default => 'gray'
                        }">{{ ucfirst($servicio->estado) }}</flux:badge>
                    </td>
                    <td class="px-4 py-2">${{ number_format($servicio->precio_total, 0, ',', '.') }}</td>
                    <td class="px-4 py-2">{{ $servicio->fecha_entrega_estimada->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-right space-x-2">
                        <flux:button icon="eye" wire:click="verServicio({{ $servicio->id }})" size="sm" variant="outline">Ver</flux:button>
                        <flux:button icon="arrow-path" wire:click="abrirModalCambioEstado({{ $servicio->id }})" size="sm" variant="outline">Estado</flux:button>
                        @if($servicio->estado !== 'entregado')
                            <flux:button icon="trash" wire:click="confirmarEliminar({{ $servicio->id }})" size="sm" variant="danger">Eliminar</flux:button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-6 text-center text-zinc-500">No hay servicios registrados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $servicios->links() }}
    </div>

    <!-- Modal de detalle/edición (sin cambios) -->
    <flux:modal wire:model="showModal" class="max-w-5xl">
        <!-- ... mismo código que antes ... -->
    </flux:modal>

    <!-- Modal rápido de cambio de estado (sin cambios) -->
    <flux:modal wire:model="showStatusModal" class="max-w-md">
        <!-- ... mismo código que antes ... -->
    </flux:modal>

    <!-- Modal para crear nuevo servicio (MEJORADO: más ancho y con grid de 2 columnas) -->
    <flux:modal wire:model="showCreateModal" class="max-w-4xl">
        <form wire:submit.prevent="guardarNuevoServicio" class="space-y-6 p-6">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold">Nuevo Servicio</h2>
                <flux:button type="button" wire:click="closeModals" icon="x-mark" variant="subtle" class="!p-1" />
            </div>
            <flux:separator />

            <!-- SECCIÓN CLIENTE -->
            <div class="border-l-4 border-blue-400 pl-4 space-y-4">
                <h3 class="text-md font-medium">Datos del Cliente</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <div class="flex gap-2 items-end">
                            <div class="flex-1">
                                <flux:input wire:model="newTelefonoCliente" label="Teléfono (buscar o crear)" placeholder="Ej: 3001234567" />
                            </div>
                            <flux:button type="button" wire:click="buscarClientePorTelefono" variant="subtle" class="mb-1">Buscar</flux:button>
                        </div>
                    </div>
                    @if(!$newClienteId)
                        <flux:input wire:model="newNombreCliente" label="Nombre completo *" required />
                        <flux:input wire:model="newEmailCliente" label="Email (opcional)" type="email" />
                    @else
                        <div class="md:col-span-2 bg-zinc-100 dark:bg-zinc-800 p-3 rounded">
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="font-semibold">Cliente seleccionado:</span> {{ $newNombreCliente }}<br>
                                    <span class="text-sm text-zinc-600">{{ $newTelefonoCliente }} | {{ $newEmailCliente ?? 'Sin email' }}</span>
                                </div>
                                <flux:button type="button" wire:click="reset(['newClienteId', 'newNombreCliente', 'newTelefonoCliente', 'newEmailCliente'])" variant="subtle" size="sm">Cambiar</flux:button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- SECCIÓN SERVICIO -->
            <div class="space-y-4">
                <h3 class="text-md font-medium">Datos del Servicio</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <flux:textarea wire:model="newDescripcionBicicleta" label="Descripción de la bicicleta *" required rows="2" placeholder="Marca, modelo, color, número de serie..." />
                    </div>
                    <flux:textarea wire:model="newNotas" label="Notas adicionales" rows="2" />
                    <flux:input type="date" wire:model="newFechaEntrega" label="Fecha estimada de entrega *" required />
                    <flux:select wire:model="newMecanicoId" label="Mecánico asignado (opcional)">
                        <option value="">Sin asignar</option>
                        @foreach($mecanicos as $mec)
                            <option value="{{ $mec->id }}">{{ $mec->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="newPrecioBase" label="Precio base (COP) *" type="number" step="1000" min="0" required />
                </div>
            </div>

            <flux:separator />
            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="closeModals" variant="subtle">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Crear Servicio</flux:button>
            </div>
        </form>
    </flux:modal>

    @push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('swal:confirm', (data) => {
                const datos = Array.isArray(data) ? data[0] : data;
                Swal.fire({
                    title: datos.title,
                    text: datos.message,
                    icon: datos.type,
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: datos.confirmButtonText || 'Sí, continuar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed && datos.callback) {
                        eval(datos.callback);
                    }
                });
            });
        });
    </script>
    @endpush
</div>