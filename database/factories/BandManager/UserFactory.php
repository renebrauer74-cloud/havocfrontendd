<?php

namespace Database\Factories\BandManager;

use App\Enums\BandManager\UserRoleEnum;
use App\Models\BandManager\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRoleEnum::Artist,
            'is_active' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => ['role' => UserRoleEnum::Admin]);
    }

    /**
     * Zugang einer Band mit freigegebenen Bereichen, z. B. ['gallery' => 'edit'].
     *
     * @param  array<string, string>  $areaAccess
     */
    public function bandAccess(UserRoleEnum $role = UserRoleEnum::BandMember, array $areaAccess = []): static
    {
        return $this->state(fn (): array => ['role' => $role, 'area_access' => $areaAccess]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
