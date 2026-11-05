<?php

namespace App\Services;

use App\Models\Sequence;
use Illuminate\Support\Facades\DB;

class NumberGenerator
{
    /**
     * Generate the next sequential number for the given key.
     *
     * The row is locked inside a transaction so concurrent requests receive
     * different numbers and never duplicate a receipt.
     */
    public function next(string $key, string $prefix, int $padding = 6): string
    {
        $sequence = Sequence::query()->firstOrCreate(['key' => $key], ['value' => 0]);

        return DB::transaction(function () use ($sequence, $prefix, $padding): string {
            $locked = Sequence::query()->lockForUpdate()->findOrFail($sequence->getKey());

            $locked->value++;
            $locked->save();

            return sprintf(
                '%s-%s',
                $prefix,
                str_pad((string) $locked->value, $padding, '0', STR_PAD_LEFT),
            );
        });
    }
}
