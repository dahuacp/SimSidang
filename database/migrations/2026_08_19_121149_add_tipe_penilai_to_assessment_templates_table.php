<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_templates', function (Blueprint $table) {
            $table->string('tipe_penilai', 10)->default('penguji')->after('jenis_sidang_id');
        });

        Schema::table('assessment_templates', function (Blueprint $table) {
            $table->index('prodi_id', 'assessment_templates_prodi_id_index');
            $table->dropUnique('assessment_templates_prodi_id_jenis_sidang_id_unique');
            $table->unique(['prodi_id', 'jenis_sidang_id', 'tipe_penilai'], 'assessment_templates_tipe_unique');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_templates', function (Blueprint $table) {
            $table->dropUnique('assessment_templates_tipe_unique');
            $table->unique(['prodi_id', 'jenis_sidang_id']);
            $table->dropIndex('assessment_templates_prodi_id_index');
        });

        Schema::table('assessment_templates', function (Blueprint $table) {
            $table->dropColumn('tipe_penilai');
        });
    }
};
