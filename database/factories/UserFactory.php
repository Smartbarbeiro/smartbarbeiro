<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        $name = fake()->name();

        return [
            'name' => $name,
            'username' => Str::slug(fake()->unique()->userName()),
            'email' => fake()->unique()->safeEmail(),
            'tax_document' => fake()->unique()->numerify('###########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_barbershop' => true,
        ];
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'username' => null,
            'is_barbershop' => false,
            'tax_document' => fake()->unique()->numerify('###########'),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
            'is_barbershop' => false,
            'username' => null,
        ]);
    }

    public function frozen(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_frozen' => true,
        ]);
    }
}
