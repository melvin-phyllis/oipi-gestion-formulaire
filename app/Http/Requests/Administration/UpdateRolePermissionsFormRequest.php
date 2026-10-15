<?php

namespace App\Http\Requests\Administration;

use App\Enums\Administration\PermissionCode;
use App\Models\Administration\RoleModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateRolePermissionsFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionCode::RolesManage->value) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'role_uuid' => [
                'sometimes',
                'string',
                Rule::in([$role instanceof RoleModel ? $role->uuid : '']),
            ],
            'permissions' => ['present', 'array'],
            'permissions.*' => [
                'required',
                'string',
                'distinct',
                Rule::enum(PermissionCode::class),
                Rule::exists('adm_permission_models', 'permission_code')
                    ->where('is_active', true),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'permissions.present' => 'La liste des permissions doit être transmise.',
            'permissions.array' => 'La sélection des permissions est invalide.',
            'permissions.*.distinct' => 'Une permission ne peut être sélectionnée qu’une seule fois.',
            'permissions.*.enum' => 'Une permission sélectionnée est inconnue.',
            'permissions.*.exists' => 'Une permission sélectionnée est indisponible.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $permissions = $this->input('permissions');

        if (! is_array($permissions)) {
            return;
        }

        $this->merge([
            'permissions' => array_values(array_filter(
                array_map(
                    fn (mixed $permission): string => trim((string) $permission),
                    $permissions,
                ),
                fn (string $permission): bool => $permission !== '',
            )),
        ]);
    }
}
