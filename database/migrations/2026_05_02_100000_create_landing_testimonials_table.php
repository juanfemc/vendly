<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_testimonials', function (Blueprint $table) {
            $table->id();
            $table->uuid('admin_token')->unique();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('initials', 8)->nullable();
            $table->text('quote');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('landing_testimonials')->insert([
            [
                'admin_token' => (string) Str::uuid(),
                'name' => 'Laura Medina',
                'role' => 'Moda y accesorios',
                'initials' => 'LM',
                'quote' => 'Ahora mis clientes ven todo el catalogo sin pedirme fotos una por una. Me escriben con el pedido mas claro.',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'admin_token' => (string) Str::uuid(),
                'name' => 'Carlos Rojas',
                'role' => 'Tecnologia',
                'initials' => 'CR',
                'quote' => 'La tienda se ve mas profesional y es mas facil compartir un solo enlace en redes y estados.',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'admin_token' => (string) Str::uuid(),
                'name' => 'Valentina Perez',
                'role' => 'Belleza',
                'initials' => 'VP',
                'quote' => 'Me ayudo a ordenar productos, precios y promociones. El cliente entiende rapido que puede comprar.',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_testimonials');
    }
};
