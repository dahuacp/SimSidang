<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('users')->cascadeOnDelete();
            $table->enum('tipe_penilai', ['dospem', 'penguji']);
            $table->foreignId('template_id')->constrained('assessment_templates')->cascadeOnDelete();
            $table->json('skor_per_item');
            $table->float('skor_total')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'dosen_id', 'tipe_penilai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_forms');
    }
};
