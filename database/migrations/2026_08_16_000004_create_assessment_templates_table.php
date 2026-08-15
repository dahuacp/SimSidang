<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jenis_sidang_id')->constrained('jenis_sidangs')->cascadeOnDelete();
            $table->string('nama');
            $table->unsignedInteger('nilai_penyebut')->default(1);
            $table->unsignedInteger('nilai_pengali')->default(100);
            $table->json('items');
            $table->timestamps();

            $table->unique(['prodi_id', 'jenis_sidang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_templates');
    }
};
