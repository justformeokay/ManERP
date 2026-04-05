<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $ptkpStatus = $this->faker->randomElement(array_keys(Employee::PTKP_OPTIONS));

        return [
            'nik'               => $this->faker->unique()->numerify('EMP####'),
            'name'              => $this->faker->name(),
            'position'          => $this->faker->jobTitle(),
            'department'        => $this->faker->randomElement(['IT', 'Finance', 'HR', 'Production', 'Marketing']),
            'join_date'         => $this->faker->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'resign_date'       => null,
            'npwp'              => $this->faker->numerify('##.###.###.#-###.###'),
            'bpjs_tk_number'    => $this->faker->numerify('TK##########'),
            'bpjs_kes_number'   => $this->faker->numerify('KS##########'),
            'ptkp_status'       => $ptkpStatus,
            'ter_category'      => Employee::deriveTerCategory($ptkpStatus),
            'bank_name'         => 'BCA',
            'bank_account_number' => $this->faker->numerify('###########'),
            'bank_account_name'   => $this->faker->name(),
            'status'            => 'active',
            'user_id'           => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function withPtkp(string $ptkpStatus): static
    {
        return $this->state([
            'ptkp_status'  => $ptkpStatus,
            'ter_category' => Employee::deriveTerCategory($ptkpStatus),
        ]);
    }
}
