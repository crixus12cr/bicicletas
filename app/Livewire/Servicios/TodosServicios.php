<?php

namespace App\Livewire\Servicios;

use App\Models\Service;
use App\Models\User;
use App\Models\ServiceAddon;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;

class TodosServicios extends Component
{
    use WithPagination;

    // Filtros
    public $estadoFiltro = '';
    public $mecanicoFiltro = '';
    public $clienteFiltro = '';
    public $fechaDesde = '';
    public $fechaHasta = '';
    public $search = '';
    public $showFilters = false;

    // Modal de detalle/edición
    public $showModal = false;
    public $servicioId;
    public $descripcion_bicicleta;
    public $notas;
    public $fecha_entrega_estimada;
    public $mecanico_id;
    public $estado;
    public $trabajo_realizado;
    public $precio_base;
    public $cargo_extra;
    public $precio_total;
    public $addons = [];

    // Añadidos (dentro del modal)
    public $addonDescripcion = '';
    public $addonCosto = '';

    // Modal cambio de estado rápido
    public $showStatusModal = false;
    public $servicioIdStatus;
    public $nuevoEstado;
    public $enviarWhatsApp = true;
    public $mensajePersonalizado = '';

    // Modal crear nuevo servicio
    public $showCreateModal = false;
    public $newClienteId;
    public $newNombreCliente;
    public $newTelefonoCliente;
    public $newEmailCliente;
    public $newDescripcionBicicleta;
    public $newNotas;
    public $newFechaEntrega;
    public $newMecanicoId;
    public $newPrecioBase;

    protected $queryString = ['estadoFiltro', 'mecanicoFiltro', 'clienteFiltro', 'fechaDesde', 'fechaHasta', 'search'];

    public function mount()
    {
        $this->newPrecioBase = config('bike.base_maintenance_price', 25000);
        $this->newFechaEntrega = now()->addDays(3)->format('Y-m-d');
    }

    public function render()
    {
        $query = Service::with(['cliente', 'mecanico', 'addons']);

        if ($this->estadoFiltro) {
            $query->where('estado', $this->estadoFiltro);
        }
        if ($this->mecanicoFiltro) {
            $query->where('mecanico_user_id', $this->mecanicoFiltro);
        }
        if ($this->clienteFiltro) {
            $query->where('cliente_user_id', $this->clienteFiltro);
        }
        if ($this->fechaDesde) {
            $query->whereDate('fecha_entrega_estimada', '>=', $this->fechaDesde);
        }
        if ($this->fechaHasta) {
            $query->whereDate('fecha_entrega_estimada', '<=', $this->fechaHasta);
        }
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('descripcion_bicicleta', 'like', '%' . $this->search . '%')
                    ->orWhereHas('cliente', function ($cq) {
                        $cq->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('telefono', 'like', '%' . $this->search . '%');
                    });
            });
        }

        $servicios = $query->orderByRaw("FIELD(estado, 'pendiente', 'en_progreso', 'esperando_piezas', 'listo', 'entregado')")
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $mecanicos = User::whereHas('roles', fn($q) => $q->where('nombre', 'mecanico'))->get();
        $clientes = User::whereHas('roles', fn($q) => $q->where('nombre', 'cliente'))->get();
        $estados = ['pendiente', 'en_progreso', 'esperando_piezas', 'listo', 'entregado'];

        return view('livewire.servicios.todos-servicios', compact('servicios', 'mecanicos', 'clientes', 'estados'));
    }

    // Abrir modal de detalle/edición
    public function verServicio($id)
    {
        $servicio = Service::with(['cliente', 'mecanico', 'addons'])->findOrFail($id);
        $this->servicioId = $servicio->id;
        $this->descripcion_bicicleta = $servicio->descripcion_bicicleta;
        $this->notas = $servicio->notas;
        $this->fecha_entrega_estimada = $servicio->fecha_entrega_estimada->format('Y-m-d');
        $this->mecanico_id = $servicio->mecanico_user_id;
        $this->estado = $servicio->estado;
        $this->trabajo_realizado = $servicio->trabajo_realizado;
        $this->precio_base = $servicio->precio_base;
        $this->cargo_extra = $servicio->cargo_extra;
        $this->precio_total = $servicio->precio_total;
        $this->addons = $servicio->addons;
        $this->showModal = true;
    }

    // Actualizar servicio
    public function actualizarServicio()
    {
        $validated = $this->validate([
            'descripcion_bicicleta' => 'required|string',
            'notas' => 'nullable|string',
            'fecha_entrega_estimada' => 'required|date',
            'mecanico_id' => 'nullable|exists:users,id',
            'estado' => 'required|in:pendiente,en_progreso,esperando_piezas,listo,entregado',
            'trabajo_realizado' => 'nullable|string',
        ]);

        $servicio = Service::findOrFail($this->servicioId);
        $oldEstado = $servicio->estado;
        $servicio->update($validated);

        if ($oldEstado != $servicio->estado) {
            $this->registrarCambioEstado($servicio->id, $oldEstado, $servicio->estado);
        }

        Flux::toast(variant: 'success', text: 'Servicio actualizado correctamente.');
        $this->showModal = false;
        $this->resetPage();
    }

    // Agregar añadido
    public function agregarAddon()
    {
        $this->validate([
            'addonDescripcion' => 'required|string',
            'addonCosto' => 'required|integer|min:0',
        ]);

        ServiceAddon::create([
            'servicio_id' => $this->servicioId,
            'descripcion' => $this->addonDescripcion,
            'costo' => $this->addonCosto,
        ]);

        $this->addons = ServiceAddon::where('servicio_id', $this->servicioId)->get();
        $this->addonDescripcion = '';
        $this->addonCosto = '';

        $servicio = Service::find($this->servicioId);
        $this->cargo_extra = $servicio->cargo_extra;
        $this->precio_total = $servicio->precio_total;

        Flux::toast(variant: 'success', text: 'Añadido registrado.');
    }

    // Eliminar añadido
    public function eliminarAddon($addonId)
    {
        $addon = ServiceAddon::findOrFail($addonId);
        if ($addon->servicio_id == $this->servicioId) {
            $addon->delete();
            $this->addons = ServiceAddon::where('servicio_id', $this->servicioId)->get();
            $servicio = Service::find($this->servicioId);
            $this->cargo_extra = $servicio->cargo_extra;
            $this->precio_total = $servicio->precio_total;
            Flux::toast(variant: 'success', text: 'Añadido eliminado.');
        }
    }

    // Abrir modal cambio rápido de estado
    public function abrirModalCambioEstado($id)
    {
        $servicio = Service::findOrFail($id);
        $this->servicioIdStatus = $servicio->id;
        $this->nuevoEstado = $servicio->estado;
        $this->mensajePersonalizado = '';
        $this->enviarWhatsApp = true;
        $this->showStatusModal = true;
    }

    // Cambiar estado
    public function cambiarEstado()
    {
        $servicio = Service::findOrFail($this->servicioIdStatus);
        $oldEstado = $servicio->estado;
        $servicio->estado = $this->nuevoEstado;
        if ($this->nuevoEstado == 'entregado') {
            $servicio->entregado_en = now();
        }
        $servicio->save();

        $this->registrarCambioEstado($servicio->id, $oldEstado, $servicio->estado, $this->mensajePersonalizado);

        if ($this->enviarWhatsApp) {
            $this->enviarNotificacionWhatsApp($servicio);
        }

        Flux::toast(variant: 'success', text: 'Estado actualizado correctamente.');
        $this->showStatusModal = false;
        $this->resetPage();
    }

    // Eliminar servicio (con confirmación)
    public function eliminarServicio($id)
    {
        $servicio = Service::findOrFail($id);
        if ($servicio->estado === 'entregado') {
            Flux::toast(variant: 'error', text: 'No se puede eliminar un servicio ya entregado.');
            return;
        }
        $servicio->delete();
        Flux::toast(variant: 'success', text: 'Servicio eliminado correctamente.');
        $this->resetPage();
    }

    public function confirmarEliminar($id)
    {
        $this->dispatch('swal:confirm', [
            'type' => 'warning',
            'title' => '¿Eliminar servicio?',
            'message' => 'Esta acción no se puede deshacer.',
            'confirmButtonText' => 'Sí, eliminar',
            'callback' => "Livewire.dispatch('eliminarServicio', [$id])"
        ]);
    }

    // Crear nuevo servicio
    public function openCreateModal()
    {
        $this->reset(['newClienteId', 'newNombreCliente', 'newTelefonoCliente', 'newEmailCliente', 'newDescripcionBicicleta', 'newNotas']);
        $this->newFechaEntrega = now()->addDays(3)->format('Y-m-d');
        $this->newPrecioBase = config('bike.base_maintenance_price', 25000);
        $this->showCreateModal = true;
    }

    public function buscarClientePorTelefono()
    {
        if (empty($this->newTelefonoCliente)) return;
        $cliente = User::where('telefono', $this->newTelefonoCliente)->first();
        if ($cliente) {
            $this->newClienteId = $cliente->id;
            $this->newNombreCliente = $cliente->name;
            $this->newEmailCliente = $cliente->email;
            Flux::toast(variant: 'success', text: 'Cliente encontrado.');
        } else {
            $this->newClienteId = null;
        }
    }

    public function guardarNuevoServicio()
    {
        $rules = [
            'newDescripcionBicicleta' => 'required|string',
            'newFechaEntrega' => 'required|date|after:today',
            'newPrecioBase' => 'required|integer|min:0',
            'newNotas' => 'nullable|string',
            'newMecanicoId' => 'nullable|exists:users,id',
        ];

        if (!$this->newClienteId) {
            $rules['newNombreCliente'] = 'required|string';
            $rules['newTelefonoCliente'] = 'required|string|unique:users,telefono';
            $rules['newEmailCliente'] = 'nullable|email|unique:users,email';
        }

        $this->validate($rules);

        if ($this->newClienteId) {
            $cliente = User::find($this->newClienteId);
        } else {
            $cliente = User::create([
                'name' => $this->newNombreCliente,
                'email' => $this->newEmailCliente,
                'telefono' => $this->newTelefonoCliente,
                'password' => null,
                'activo' => true,
            ]);
            $cliente->assignRole('cliente');
        }

        $servicio = Service::create([
            'cliente_user_id' => $cliente->id,
            'mecanico_user_id' => $this->newMecanicoId,
            'descripcion_bicicleta' => $this->newDescripcionBicicleta,
            'precio_base' => $this->newPrecioBase,
            'cargo_extra' => 0,
            'notas' => $this->newNotas,
            'estado' => 'pendiente',
            'fecha_entrega_estimada' => $this->newFechaEntrega,
        ]);

        $this->registrarCambioEstado($servicio->id, null, 'pendiente');
        Flux::toast(variant: 'success', text: 'Servicio creado correctamente.');
        $this->showCreateModal = false;
        $this->resetPage();
    }

    // Funciones auxiliares
    private function registrarCambioEstado($servicioId, $oldEstado, $newEstado, $mensaje = null)
    {
        $mensajeEnviado = $mensaje ?? $this->generarMensajeEstado($newEstado);
        \App\Models\StatusUpdate::create([
            'servicio_id' => $servicioId,
            'estado_anterior' => $oldEstado,
            'estado_nuevo' => $newEstado,
            'mensaje_enviado' => $mensajeEnviado,
        ]);
    }

    private function generarMensajeEstado($estado)
    {
        $mensajes = [
            'pendiente' => 'Tu servicio ha sido registrado y está pendiente de asignación.',
            'en_progreso' => 'Tu bicicleta está siendo reparada. Te mantendremos informado.',
            'esperando_piezas' => 'Estamos esperando piezas para continuar con tu servicio.',
            'listo' => 'Tu bicicleta está lista para ser entregada. ¡Pásate por el taller!',
            'entregado' => 'Servicio entregado. ¡Gracias por confiar en nosotros!',
        ];
        return $mensajes[$estado] ?? 'El estado de tu servicio ha cambiado.';
    }

    private function enviarNotificacionWhatsApp($servicio)
    {
        logger("📱 WhatsApp a {$servicio->cliente->telefono}: {$this->mensajePersonalizado}");
        Flux::toast(variant: 'info', text: 'Notificación WhatsApp simulada.');
    }

    public function resetFilters()
    {
        $this->reset(['estadoFiltro', 'mecanicoFiltro', 'clienteFiltro', 'fechaDesde', 'fechaHasta', 'search']);
        $this->resetPage();
    }

    public function closeModals()
    {
        $this->showModal = false;
        $this->showStatusModal = false;
        $this->showCreateModal = false;
    }

    protected $listeners = ['eliminarServicio' => 'eliminarServicio'];
}