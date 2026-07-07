<?php

namespace Tests\Unit;

use App\Support\BarbershopDisplayName;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BarbershopDisplayNameTest extends TestCase
{
    #[Test]
    public function test_converts_username_underscores_to_spaces(): void
    {
        $this->assertSame(
            'Barbearia Centro',
            BarbershopDisplayName::from('barbearia_centro', 'Other Name'),
        );
    }

    #[Test]
    public function test_falls_back_to_name_when_username_is_blank(): void
    {
        $this->assertSame('Navalha de Ouro', BarbershopDisplayName::from(null, 'Navalha de Ouro'));
    }
}
