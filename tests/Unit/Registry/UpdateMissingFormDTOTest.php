<?php

namespace Tests\Unit\Registry;

use App\Dto\Registry\UpdateMissingFormDTO;
use App\Enums\Registry\FormPriority;
use PHPUnit\Framework\TestCase;
use ValueError;

final class UpdateMissingFormDTOTest extends TestCase
{
    public function test_validated_values_are_mapped_to_typed_business_fields(): void
    {
        $data = UpdateMissingFormDTO::fromValidated([
            'title_type_code_unique' => 'TYP-TEST',
            'form_year' => '2026',
            'form_number' => 'M-2026-000001',
            'priority' => FormPriority::High->value,
        ]);

        $this->assertSame('TYP-TEST', $data->titleTypeCodeUnique);
        $this->assertSame(2026, $data->formYear);
        $this->assertSame('M-2026-000001', $data->formNumber);
        $this->assertSame(FormPriority::High, $data->priority);
    }

    public function test_unknown_priority_cannot_enter_the_service_contract(): void
    {
        $this->expectException(ValueError::class);

        UpdateMissingFormDTO::fromValidated([
            'title_type_code_unique' => 'TYP-TEST',
            'form_year' => 2026,
            'form_number' => 'M-2026-000001',
            'priority' => 'unknown',
        ]);
    }
}
