<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceAddon extends Model
{
    protected $table = 'servicio_addons';

    protected $fillable = ['servicio_id', 'descripcion', 'costo'];

    public function service()
    {
        return $this->belongsTo(Service::class, 'servicio_id');
    }

    protected static function booted()
    {
        static::saved(function ($addon) {
            $addon->service->recalculateExtraCharge();
        });
        static::deleted(function ($addon) {
            $addon->service->recalculateExtraCharge();
        });
    }
}