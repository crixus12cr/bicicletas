<?php

namespace App\Livewire\GestionarUsuarios;

use App\Models\User;
use App\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class UsuariosComponent extends Component
{
    use WithPagination;

    // Propiedades del modal
    public $showModal = false;
    public $userId;
    public $name = '';
    public $email = '';
    public $telefono = '';
    public $password = '';
    public $activo = true;
    public $selectedRoles = [];
    public $isSelf = false;

    // Filtros
    public $search = '';
    public $roleFilter = '';

    protected $queryString = ['search', 'roleFilter'];

    protected $listeners = [
        'eliminar',
        'toggleUserStatus',
    ];

    public function getModalTitleProperty()
    {
        return $this->userId ? ($this->isSelf ? 'Mi Perfil' : 'Editar Usuario') : 'Nuevo Usuario';
    }

    public function render()
    {
        $query = User::with('roles');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('telefono', 'like', "%{$this->search}%");
            });
        }

        if ($this->roleFilter) {
            $query->whereHas('roles', fn($q) => $q->where('nombre', $this->roleFilter));
        }

        $users = $query->orderBy('id', 'desc')->paginate(10);
        $roles = Role::all();

        return view('livewire.gestionar-usuarios.usuarios-component', compact('users', 'roles'));
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->userId = null;
        $this->isSelf = false;
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $user = User::with('roles')->findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->telefono = $user->telefono;
        $this->activo = (bool) $user->activo;
        $this->selectedRoles = $user->roles->pluck('id')->map(fn($r) => (string) $r)->toArray();
        $this->password = '';
        $this->isSelf = ($user->id === auth()->id());
        $this->showModal = true;
    }

    public function saveUser()
    {
        // Obtener nombres de roles seleccionados
        $rolesSeleccionados = Role::whereIn('id', $this->selectedRoles)->pluck('nombre')->toArray();
        $tieneRolConPassword = array_intersect(['mecanico', 'admin'], $rolesSeleccionados);

        $rules = [
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->userId)],
            'telefono' => 'required|string|max:20',
            'activo' => 'boolean',
            'selectedRoles' => 'required|array|min:1',
        ];

        if (!$this->userId) {
            if ($tieneRolConPassword) {
                $rules['password'] = 'required|string|min:6';
            } else {
                $rules['password'] = 'nullable|string|min:6';
            }
        } else {
            $rules['password'] = 'nullable|string|min:6';
        }

        $this->validate($rules);

        if ($this->userId) {
            $user = User::findOrFail($this->userId);

            // Protección para auto-edición
            if ($this->isSelf) {
                if (!in_array('admin', $rolesSeleccionados)) {
                    $this->addError('selectedRoles', 'No puedes quitarte el rol de administrador a ti mismo.');
                    return;
                }
                if ($this->activo == false) {
                    $this->addError('activo', 'No puedes desactivarte a ti mismo.');
                    return;
                }
            }

            $user->update([
                'name' => $this->name,
                'email' => $this->email,
                'telefono' => $this->telefono,
                'activo' => $this->activo,
            ]);
            if ($this->password) {
                $user->password = bcrypt($this->password);
                $user->save();
            }
            $user->roles()->sync($this->selectedRoles);

            $this->dispatch('alerta', [
                'titulo' => '¡Actualizado!',
                'texto' => 'Usuario actualizado correctamente.',
                'icono' => 'success'
            ]);
        } else {
            $userData = [
                'name' => $this->name,
                'email' => $this->email,
                'telefono' => $this->telefono,
                'activo' => $this->activo,
            ];
            if ($this->password) {
                $userData['password'] = bcrypt($this->password);
            } else {
                $userData['password'] = null;
            }

            $user = User::create($userData);
            $user->roles()->attach($this->selectedRoles);

            $this->dispatch('alerta', [
                'titulo' => '¡Creado!',
                'texto' => 'Usuario creado correctamente.',
                'icono' => 'success'
            ]);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->resetPage();
    }

    public function confirmarEliminar($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            $this->dispatch('alerta', [
                'titulo' => '¡No se puede eliminar!',
                'texto' => 'No puedes eliminar tu propio usuario.',
                'icono' => 'error'
            ]);
            return;
        }

        $this->dispatch('confirmar-eliminar', [
            'titulo' => '¿Eliminar usuario?',
            'texto' => "¿Estás seguro de eliminar a {$user->name}? Esta acción no se puede deshacer.",
            'icono' => 'warning',
            'id' => $id
        ]);
    }

    public function eliminar($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            $this->dispatch('alerta', [
                'titulo' => '¡Error!',
                'texto' => 'No puedes eliminar tu propio usuario.',
                'icono' => 'error'
            ]);
            return;
        }

        $user->delete();

        $this->dispatch('alerta', [
            'titulo' => '¡Eliminado!',
            'texto' => 'Usuario eliminado correctamente.',
            'icono' => 'success'
        ]);

        $this->resetPage();
    }

    public function confirmarToggleActivo($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id() && $user->activo == true) {
            $this->dispatch('alerta', [
                'titulo' => '¡No se puede desactivar!',
                'texto' => 'No puedes desactivar tu propio usuario.',
                'icono' => 'error'
            ]);
            return;
        }

        $accion = $user->activo ? 'desactivar' : 'activar';

        $this->dispatch('confirmar-eliminar', [
            'titulo' => ucfirst($accion) . ' usuario',
            'texto' => $user->activo
                ? "¿Desactivar a {$user->name}? No podrá iniciar sesión."
                : "¿Activar a {$user->name}?",
            'icono' => 'warning',
            'id' => $id,
            'accion' => 'toggle'
        ]);
    }

    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id() && $user->activo == true) {
            $this->dispatch('alerta', [
                'titulo' => '¡Error!',
                'texto' => 'No puedes desactivar tu propio usuario.',
                'icono' => 'error'
            ]);
            return;
        }

        $user->activo = !$user->activo;
        $user->save();

        $mensaje = $user->activo ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.';

        $this->dispatch('alerta', [
            'titulo' => $user->activo ? '¡Activado!' : '¡Desactivado!',
            'texto' => $mensaje,
            'icono' => 'success'
        ]);

        $this->resetPage();
    }

    public function selectAllRoles()
    {
        $this->selectedRoles = Role::all()->pluck('id')->map(fn($id) => (string) $id)->toArray();
    }

    public function unselectAllRoles()
    {
        $this->selectedRoles = [];
    }

    private function resetForm()
    {
        $this->reset(['name', 'email', 'telefono', 'password', 'activo', 'selectedRoles', 'userId', 'isSelf']);
        $this->activo = true;
        $this->selectedRoles = [];
        $this->resetValidation();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }
}
