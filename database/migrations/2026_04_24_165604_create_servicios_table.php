<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('mecanico_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('descripcion_bicicleta');
            $table->decimal('precio_base', 8, 2);
            $table->decimal('cargo_extra', 8, 2)->default(0);
            $table->decimal('precio_total', 8, 2)->storedAs('precio_base + cargo_extra');
            $table->text('trabajo_realizado')->nullable();
            $table->text('notas')->nullable();
            $table->enum('estado', ['pendiente', 'en_progreso', 'esperando_piezas', 'listo', 'entregado'])->default('pendiente');
            $table->date('fecha_entrega_estimada');
            $table->date('entregado_en')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('mecanico_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
