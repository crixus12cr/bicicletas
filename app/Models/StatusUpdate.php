<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusUpdate extends Model
{
    protected $table = 'actualizaciones_estado';

    protected $fillable = ['servicio_id', 'estado_anterior', 'estado_nuevo', 'mensaje_enviado'];

    public function service()
    {
        return $this->belongsTo(Service::class, 'servicio_id');
    }
}