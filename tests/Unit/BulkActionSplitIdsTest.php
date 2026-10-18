<?php

namespace Tests\Unit;

use App\Http\Controllers\Backend\ParcelBulkActionController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Guards the bulk-action id parser against the prefix regression: tracking ids
 * are matched by any brand prefix (RL-, RDS-, ...), not only RL. splitIds has no
 * instance state, so we invoke it without booting the controller/app or a DB.
 */
class BulkActionSplitIdsTest extends TestCase
{
    /** @return array{0: array<int,string>, 1: array<int,int>} */
    private function split(string $raw): array
    {
        $controller = (new ReflectionClass(ParcelBulkActionController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($controller, 'splitIds');
        $method->setAccessible(true);

        return $method->invoke($controller, $raw);
    }

    public function test_rds_prefixed_tracking_ids_are_recognised(): void
    {
        [$tracking, $numeric] = $this->split('RDS-10000013394 RDS-10000013393 RDS-10000013392');

        $this->assertSame(['RDS-10000013394', 'RDS-10000013393', 'RDS-10000013392'], $tracking);
        $this->assertSame([], $numeric);
    }

    public function test_rl_prefixed_and_numeric_ids_still_work(): void
    {
        [$tracking, $numeric] = $this->split("RL-831416028686\nRL559900\n4071, 4072");

        $this->assertSame(['RL-831416028686', 'RL559900'], $tracking);
        $this->assertSame([4071, 4072], $numeric);
    }

    public function test_strips_label_and_handles_mixed_prefixes(): void
    {
        [$tracking, $numeric] = $this->split('Tracking IDs: RDS-10000013394, RL559900, 4071');

        $this->assertSame(['RDS-10000013394', 'RL559900'], $tracking);
        $this->assertSame([4071], $numeric);
    }
}
