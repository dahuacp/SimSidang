<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revision_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revision_note_id')->constrained()->cascadeOnDelete();
            $table->text('keterangan_mahasiswa')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revision_attachments');
    }
};
