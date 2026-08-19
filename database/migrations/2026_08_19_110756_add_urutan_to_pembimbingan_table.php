<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembimbingan', function (Blueprint $table) {
            $table->unsignedTinyInteger('urutan')
                ->default(1)
                ->after('dosen_id');
        });

        // Assign urutan otomatis: dosen dengan id terkecil = urutan 1
        $groups = DB::table('pembimbingan')
            ->select('mahasiswa_id', 'dosen_id', 'id')
            ->orderBy('mahasiswa_id')
            ->orderBy('dosen_id')
            ->get()
            ->groupBy('mahasiswa_id');

        foreach ($groups as $mahasiswaId => $rows) {
            $urutan = 1;
            foreach ($rows as $row) {
                DB::table('pembimbingan')
                    ->where('id', $row->id)
                    ->update(['urutan' => $urutan]);
                $urutan++;
            }
        }

        Schema::table('pembimbingan', function (Blueprint $table) {
            $table->unique(['mahasiswa_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::table('pembimbingan', function (Blueprint $table) {
            $table->dropUnique(['mahasiswa_id', 'urutan']);
            $table->dropColumn('urutan');
        });
    }
};
