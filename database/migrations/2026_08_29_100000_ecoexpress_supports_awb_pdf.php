<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add 'awb_pdf' to what EcoExpress advertises.
 *
 * 2026_08_28_120000 seeded it as ['tracking'] on the evidence available then:
 * the published spec lists no label endpoint, and there is indeed no way to ask
 * for a label by shipment id. Creating a real shipment showed the create
 * response carries a `pdfURL` — a ready-made label — so the capability exists,
 * just delivered at create time rather than on demand.
 *
 * EcoExpressProvider::printAwb() reads the URL persisted on the shipment and
 * downloads it, so the capability is real from a caller's point of view.
 * 'cancel' stays absent: that endpoint genuinely does not exist.
 */
return new class extends Migration {
    private const CODE = 'ecoexpress';

    public function up(): void
    {
        $this->setSupports(['tracking', 'awb_pdf']);
    }

    public function down(): void
    {
        $this->setSupports(['tracking']);
    }

    private function setSupports(array $supports): void
    {
        if (! Schema::hasTable('shipping_providers')) {
            return;
        }

        $exists = DB::table('shipping_providers')->where('code', self::CODE)->exists();
        if (! $exists) {
            return;
        }

        DB::table('shipping_providers')
            ->where('code', self::CODE)
            ->update([
                'supports'   => json_encode($supports),
                'updated_at' => now(),
            ]);
    }
};
