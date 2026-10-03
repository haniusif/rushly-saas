<?php

namespace Tests\Unit\Shipping;

use App\Shipping\DTOs\ConnectionDTO;
use PHPUnit\Framework\TestCase;

class ConnectionDTOTest extends TestCase
{
    public function test_with_methods_return_new_instances(): void
    {
        $a = new ConnectionDTO(
            id: 1, companyId: 1, providerCode: 'logestechs', connectionName: 'x',
            remoteCompanyId: null, domain: 'foo.logestechs.com', email: null, password: null,
        );

        $b = $a->withRemoteCompanyId('496');
        $c = $a->withCredentials('e@x.com', 'pw');

        $this->assertNull($a->remoteCompanyId);
        $this->assertSame('496', $b->remoteCompanyId);
        $this->assertSame('e@x.com', $c->email);
        $this->assertSame('pw', $c->password);

        // Originals untouched
        $this->assertNotSame($a, $b);
        $this->assertNotSame($a, $c);
    }

    public function test_setting_returns_default_when_missing(): void
    {
        $dto = new ConnectionDTO(
            id: null, companyId: 1, providerCode: 'logestechs', connectionName: 'x',
            remoteCompanyId: null, domain: null, email: null, password: null,
            settings: ['service_type' => 'STANDARD'],
        );
        $this->assertSame('STANDARD', $dto->setting('service_type'));
        $this->assertSame('API', $dto->setting('integration_source', 'API'));
    }
}
