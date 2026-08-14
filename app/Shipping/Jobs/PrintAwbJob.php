<?php

namespace App\Shipping\Jobs;

use App\Shipping\Models\Shipment;
use App\Shipping\Services\AwbService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Bulk AWB generation. Provider returns PDF bytes; this job persists them to
 * the local 'public' disk and updates the shipments' awb_pdf_url so the UI
 * can link to the file.
 */
class PrintAwbJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param int[] $shipmentIds */
    public function __construct(public readonly array $shipmentIds) {}

    public function tries(): int     { return (int) config('shipping.retry.tries', 3); }
    public function backoff(): array { return (array) config('shipping.retry.backoff', [10, 30, 90]); }
    public function timeout(): int   { return 120; }

    public function handle(AwbService $service): void
    {
        $shipments = Shipment::with('connection.provider')
            ->whereIn('id', $this->shipmentIds)
            ->get()
            ->all();
        if (empty($shipments)) return;

        $pdfBytes = $service->printForShipments($shipments);
        if ($pdfBytes === '') return;

        $path = 'awbs/' . date('Y/m') . '/batch_' . now()->timestamp . '_' . $shipments[0]->connection_id . '.pdf';
        Storage::disk('public')->put($path, $pdfBytes);
        $url = Storage::disk('public')->url($path);

        foreach ($shipments as $s) {
            $s->awb_pdf_url = $url;
            $s->save();
        }
    }
}
