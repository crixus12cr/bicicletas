<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'servicios';

    protected $fillable = [
        'cliente_user_id',
        'mecanico_user_id',
        'descripcion_bicicleta',
        'precio_base',
        'cargo_extra',
        'trabajo_realizado',
        'notas',
        'estado',
        'fecha_entrega_estimada',
        'entregado_en',
    ];

    protected $casts = [
        'fecha_entrega_estimada' => 'date',
        'entregado_en' => 'date',
        'precio_base' => 'decimal:2',
        'cargo_extra' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_user_id');
    }

    public function mecanico()
    {
        return $this->belongsTo(User::class, 'mecanico_user_id');
    }

    public function addons()
    {
        return $this->hasMany(ServiceAddon::class, 'servicio_id');
    }

    public function statusUpdates()
    {
        return $this->hasMany(StatusUpdate::class, 'servicio_id');
    }

    public function recalculateExtraCharge()
    {
        $this->cargo_extra = $this->addons()->sum('costo');
        $this->saveQuietly();
    }
}