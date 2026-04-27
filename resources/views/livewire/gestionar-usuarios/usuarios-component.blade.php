<div>
    <!-- Header -->
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold dark:text-white">Gestión de Usuarios</h1>
        <flux:button icon="plus" wire:click="openCreateModal" variant="primary">Nuevo Usuario</flux:button>
    </div>

    <!-- Filtros -->
    <div class="mb-4 flex gap-4">
        <flux:input icon="magnifying-glass" placeholder="Buscar por nombre, email o teléfono..." 
                    wire:model.live.debounce.300ms="search" class="flex-1" />
        <flux:select wire:model.live="roleFilter" class="w-48">
            <flux:select.option value="">Todos los roles</flux:select.option>
            @foreach($roles as $rol)
                <flux:select.option value="{{ $rol->nombre }}">{{ ucfirst($rol->nombre) }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">ID</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Nombre</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Email</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Teléfono</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Roles</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Activo</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($users as $user)
                <tr wire:key="user-{{ $user->id }}">
                    <td class="px-4 py-2">{{ $user->id }}</td>
                    <td class="px-4 py-2">{{ $user->name }}</td>
                    <td class="px-4 py-2">{{ $user->email }}</td>
                    <td class="px-4 py-2">{{ $user->telefono }}</td>
                    <td class="px-4 py-2">
                        @foreach($user->roles as $role)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $role->nombre }}
                            </span>
                        @endforeach
                    </td>
                    <td class="px-4 py-2">
                        <flux:badge :color="$user->activo ? 'green' : 'red'">
                            {{ $user->activo ? 'Activo' : 'Inactivo' }}
                        </flux:badge>
                    </td>
                    <td class="px-4 py-2 space-x-2">
                        <flux:button icon="pencil-square" wire:click="openEditModal({{ $user->id }})" size="sm" variant="outline">Editar</flux:button>
                        <flux:button icon="trash" wire:click="confirmarEliminar({{ $user->id }})" size="sm" variant="danger">Eliminar</flux:button>
                        <flux:button icon="{{ $user->activo ? 'pause-circle' : 'play-circle' }}" 
                                    wire:click="confirmarToggleActivo({{ $user->id }})" size="sm" variant="outline">
                            {{ $user->activo ? 'Desactivar' : 'Activar' }}
                        </flux:button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-4">
        {{ $users->links() }}
    </div>

    <!-- Modal Flux -->
    <flux:modal wire:model="showModal" class="max-w-2xl">
        <form wire:submit.prevent="saveUser">
            <div class="space-y-4 p-6">
                <h2 class="text-lg font-medium">{{ $this->modalTitle }}</h2>
                <flux:separator />
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model="name" label="Nombre" required class="md:col-span-2" />
                    <flux:input wire:model="email" label="Email" type="email" required />
                    <flux:input wire:model="telefono" label="Teléfono" required />
                    <flux:input wire:model="password" label="Contraseña" type="password" 
                               helper-text="{{ $userId ? ($isSelf ? 'Dejar en blanco para mantener la actual' : 'Opcional, dejar en blanco para mantener') : 'Obligatorio solo para mecánicos o administradores' }}" />
                    
                    @if(!$isSelf)
                        <flux:checkbox wire:model="activo" label="Activo" class="md:col-span-2" />
                    @endif
                    
                    <!-- Roles -->
                    <div class="md:col-span-2">
                        <div class="flex justify-between items-center mb-2">
                            <flux:label>Roles</flux:label>
                            @if(!$isSelf)
                                <div class="space-x-2">
                                    <flux:button type="button" size="xs" wire:click="selectAllRoles" variant="subtle">Seleccionar todos</flux:button>
                                    <flux:button type="button" size="xs" wire:click="unselectAllRoles" variant="subtle">Quitar todos</flux:button>
                                </div>
                            @else
                                <span class="text-xs text-amber-600">No puedes quitarte el rol administrador</span>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            @foreach($roles as $role)
                                <flux:checkbox 
                                    wire:model="selectedRoles" 
                                    value="{{ $role->id }}"
                                    label="{{ ucfirst($role->nombre) }}"
                                    :disabled="$isSelf && $role->nombre === 'admin'"
                                />
                            @endforeach
                        </div>
                        @error('selectedRoles') 
                            <span class="text-xs text-red-600">{{ $message }}</span> 
                        @enderror
                    </div>
                </div>
                
                <flux:separator />
                
                <div class="flex justify-end gap-2">
                    <flux:button type="button" wire:click="$set('showModal', false)" variant="subtle">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Guardar</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- SweetAlert Script -->
    @push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('alerta', (data) => {
                const datos = Array.isArray(data) ? data[0] : data;
                Swal.fire({
                    title: datos.titulo,
                    text: datos.texto,
                    icon: datos.icono,
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Aceptar'
                });
            });

            Livewire.on('confirmar-eliminar', (data) => {
                const datos = Array.isArray(data) ? data[0] : data;
                Swal.fire({
                    title: datos.titulo,
                    text: datos.texto,
                    icon: datos.icono,
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (datos.accion === 'toggle') {
                            Livewire.dispatch('toggleUserStatus', { id: datos.id });
                        } else {
                            Livewire.dispatch('eliminar', { id: datos.id });
                        }
                    }
                });
            });
        });
    </script>
    @endpush
</div>