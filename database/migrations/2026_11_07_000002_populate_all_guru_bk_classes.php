<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Classes;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        $dbClasses = Classes::all()->keyBy(function($c) {
            return strtolower(str_replace([' ', '_'], '', $c->class_name));
        });

        $bks = User::whereHas('role', fn($q) => $q->where('role_name', 'Guru BK'))->get();

        $pdfEntries = [
            1 => ['X AKL 1', 'CANDRA RAHADIAN, S.Pd'],
            2 => ['X AKL 2', 'CANDRA RAHADIAN, S.Pd'],
            3 => ['X AKL 3', 'CANDRA RAHADIAN, S.Pd'],
            4 => ['X LPS 1', 'NURHIDAYAT SULISTIAWAN, S.Pd'],
            5 => ['X LPS 2', 'RYAWIDYANINGTYAS, S.Pd.'],
            6 => ['X MP 1', 'AISYAH WULANDARI, S.Pd'],
            7 => ['X MP 2', 'AISYAH WULANDARI, S.Pd'],
            8 => ['X BR 1', 'ALIVI SAZKIA HAWIE, S.Pd'],
            9 => ['X BR 2', 'ALIVI SAZKIA HAWIE, S.Pd'],
            10 => ['X BD 1', 'ALIVI SAZKIA HAWIE, S.Pd'],
            11 => ['X TKJ 1', 'NURHIDAYAT SULISTIAWAN, S.Pd'],
            12 => ['X TKJ 2', 'CANDRA RAHADIAN, S.Pd'],
            13 => ['X DKV 1', 'NURHIDAYAT SULISTIAWAN, S.Pd'],
            14 => ['X DKV 2', 'NURHIDAYAT SULISTIAWAN, S.Pd'],
            15 => ['X RPL 1', 'RYAWIDYANINGTYAS, S.Pd.'],
            16 => ['X RPL 2', 'RYAWIDYANINGTYAS, S.Pd.'],
            17 => ['XI AKL 1', 'AISYAH WULANDARI, S.Pd'],
            18 => ['XI AKL 2', 'AISYAH WULANDARI, S.Pd'],
            19 => ['XI AKL 3', 'CANDRA RAHADIAN, S.Pd'],
            20 => ['XI LPS 1', 'AISYAH WULANDARI, S.Pd'],
            21 => ['XI LPS 2', 'CANDRA RAHADIAN, S.Pd'],
            22 => ['XI MP 1', 'AISYAH WULANDARI, S.Pd'],
            23 => ['XI MP 2', 'AISYAH WULANDARI, S.Pd'],
            24 => ['XI BR 1', 'ALIVI SAZKIA HAWIE, S.Pd'],
            25 => ['XI BR 2', 'ALIVI SAZKIA HAWIE, S.Pd'],
            26 => ['XI BD 1', 'ALIVI SAZKIA HAWIE, S.Pd'],
            27 => ['XI TKJ 1', 'NURHIDAYAT SULISTIAWAN, S.Pd'],
            28 => ['XI TKJ 2', 'CANDRA RAHADIAN, S.Pd'],
            29 => ['XI DKV 1', 'NURHIDAYAT SULISTIAWAN, S.Pd'],
            30 => ['XI DKV 2', 'NURHIDAYAT SULISTIAWAN, S.Pd'],
            31 => ['XI RPL 1', 'RYAWIDYANINGTYAS, S.Pd.'],
            32 => ['XI RPL 2', 'RYAWIDYANINGTYAS, S.Pd.'],
            33 => ['XII AKL 1', 'RYAWIDYANINGTYAS, S.Pd.'],
            34 => ['XII AKL 2', 'RYAWIDYANINGTYAS, S.Pd.'],
            35 => ['XII AKL 3', 'AISYAH WULANDARI, S.Pd'],
            36 => ['XII AKL 4', 'CANDRA RAHADIAN, S.Pd'],
            37 => ['XII LPS 1', 'CANDRA RAHADIAN, S.Pd'],
            38 => ['XII MP 1', 'AISYAH WULANDARI, S.Pd'],
            39 => ['XII MP 2', 'AISYAH WULANDARI, S.Pd'],
            40 => ['XII BR 1', 'ALIVI SAZKIA HAWIE, S.Pd'],
            41 => ['XII BR 2', 'ALIVI SAZKIA HAWIE, S.Pd'],
            42 => ['XII BD 1', 'ALIVI SAZKIA HAWIE, S.Pd'],
            43 => ['XII TKJ 1', 'NURHIDAYAT SULISTIAWAN, S.Pd'],
            44 => ['XII TKJ 2', 'CANDRA RAHADIAN, S.Pd'],
            45 => ['XII DKV 1', 'NURHIDAYAT SULISTIAWAN, S.Pd'],
            46 => ['XII DKV 2', 'NURHIDAYAT SULISTIAWAN, S.Pd'],
            47 => ['XII RPL 1', 'RYAWIDYANINGTYAS, S.Pd.'],
            48 => ['XII RPL 2', 'RYAWIDYANINGTYAS, S.Pd.'],
        ];

        DB::table('guru_bk_classes')->truncate();

        $rows = [];
        foreach ($pdfEntries as [$targetClass, $bkName]) {
            $norm = strtolower(str_replace([' ', '_'], '', $targetClass));
            $cls = $dbClasses->get($norm);
            if (!$cls) continue;

            $bkMatch = $bks->first(function($b) use ($bkName) {
                $n1 = strtolower(str_replace([' ', '.', ','], '', $b->fullname));
                $n2 = strtolower(str_replace([' ', '.', ','], '', $bkName));
                return str_contains($n1, $n2) || str_contains($n2, $n1);
            });
            if (!$bkMatch) continue;

            $rows[] = [
                'user_id' => $bkMatch->id,
                'class_id' => $cls->id,
            ];
        }

        if (!empty($rows)) {
            DB::table('guru_bk_classes')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        DB::table('guru_bk_classes')->truncate();
    }
};

