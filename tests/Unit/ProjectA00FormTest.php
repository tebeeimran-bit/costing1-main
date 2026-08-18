<?php

namespace Tests\Unit;

use App\Models\ProjectA00Form;
use PHPUnit\Framework\TestCase;

class ProjectA00FormTest extends TestCase
{
    public function test_customer_pt_suffix_is_formatted_as_prefix(): void
    {
        $form = new ProjectA00Form(['customer' => 'Toyota Astra Motor, PT']);

        $this->assertSame('PT Toyota Astra Motor', $form->formattedCustomerName());
    }

    public function test_customer_without_pt_suffix_is_not_changed(): void
    {
        $form = new ProjectA00Form(['customer' => 'Honda Motor Company']);

        $this->assertSame('Honda Motor Company', $form->formattedCustomerName());
    }
}
