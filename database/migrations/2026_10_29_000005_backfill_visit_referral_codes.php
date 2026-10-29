<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = (string) config('claims.referral_prefix', 'CHO');

        $visits = DB::table('visits')
            ->whereNull('referral_code')
            ->whereNotNull('service_date')
            ->orderBy('service_date')
            ->orderBy('id')
            ->get();

        foreach ($visits as $visit) {
            $date = Carbon::parse($visit->service_date);
            $codePrefix = $prefix.'-'.$date->format('mdY').'-';

            $max = DB::table('visits')
                ->where('referral_code', 'like', $codePrefix.'%')
                ->pluck('referral_code')
                ->map(fn ($code) => (int) substr((string) $code, strlen($codePrefix)))
                ->max() ?? 0;

            $sequence = $max + 1;
            $code = $codePrefix.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);

            while (DB::table('visits')
                ->where('referral_code', $code)
                ->where('id', '!=', $visit->id)
                ->exists()) {
                $sequence++;
                $code = $codePrefix.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
            }

            DB::table('visits')
                ->where('id', $visit->id)
                ->update(['referral_code' => $code]);
        }
    }

    public function down(): void
    {
        // Backfilled codes are retained; nothing to reverse.
    }
};
