<?php

namespace App\Livewire;

use App\Models\Service;
use App\Models\User;
use Livewire\Component;
use Flux\Flux;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        
        if ($user->hasRole('admin')) {
            // Datos para administrador
            $serviciosActivos = Service::whereNotIn('estado', ['entregado'])->count();
            $serviciosHoy = Service::whereDate('fecha_entrega_estimada', today())->count();
            $ingresosHoy = Service::whereDate('entregado_en', today())->sum('precio_total');
            $serviciosPorEstado = Service::selectRaw('estado, count(*) as total')
                ->groupBy('estado')
                ->pluck('total', 'estado')
                ->toArray();
                
            $serviciosRecientes = Service::with(['cliente', 'mecanico'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
                
            return view('livewire.dashboard', [
                'esAdmin' => true,
                'serviciosActivos' => $serviciosActivos,
                'serviciosHoy' => $serviciosHoy,
                'ingresosHoy' => $ingresosHoy,
                'serviciosPorEstado' => $serviciosPorEstado,
                'serviciosRecientes' => $serviciosRecientes,
            ]);
        }
        
        // Vista para mecánicos (y clientes si tuvieran dashboard, pero solo mecánicos y admin)
        // Servicios disponibles del día: pendientes, sin mecánico, fecha estimada = hoy
        $serviciosDisponibles = Service::with('cliente')
            ->where('estado', 'pendiente')
            ->whereNull('mecanico_user_id')
            ->whereDate('fecha_entrega_estimada', today())
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('livewire.dashboard', [
            'esAdmin' => false,
            'serviciosDisponibles' => $serviciosDisponibles,
        ]);
    }
    
    // Tomar servicio (asignar al mecánico autenticado)
    public function tomarServicio($servicioId)
    {
        $servicio = Service::findOrFail($servicioId);
        
        // Verificar que sigue disponible (por si alguien más lo tomó mientras)
        if ($servicio->estado !== 'pendiente' || $servicio->mecanico_user_id !== null) {
            Flux::toast(variant: 'error', text: 'Este servicio ya no está disponible.');
            return;
        }
        
        $servicio->mecanico_user_id = auth()->id();
        $servicio->estado = 'en_progreso';
        $servicio->save();
        
        // Registrar cambio de estado
        \App\Models\StatusUpdate::create([
            'servicio_id' => $servicio->id,
            'estado_anterior' => 'pendiente',
            'estado_nuevo' => 'en_progreso',
            'mensaje_enviado' => 'Servicio asignado al mecánico ' . auth()->user()->name,
        ]);
        
        Flux::toast(variant: 'success', text: 'Servicio asignado correctamente. Ahora aparece en "Mis Servicios Activos".');
        
        // Refrescar la lista
        $this->render();
    }
}