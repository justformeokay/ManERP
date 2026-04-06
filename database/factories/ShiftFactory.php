<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'name'              => 'Shift Pagi',
            'start_time'        => '08:00',
            'end_time'          => '17:00',
            'grace_period'      => 15,
            'is_night_shift'    => false,
            'night_shift_bonus' => 0,
            'is_active'         => true,
        ];
    }

    public function night(): static
    {
        return $this->state([
            'name'              => 'Shift Malam',
            'start_time'        => '22:00',
            'end_time'          => '06:00',
            'grace_period'      => 15,
            'is_night_shift'    => true,
            'night_shift_bonus' => 50000,
        ]);
    }

    public function afternoon(): static
    {
        return $this->state([
            'name'              => 'Shift Siang',
            'start_time'        => '14:00',
            'end_time'          => '22:00',
            'grace_period'      => 15,
            'is_night_shift'    => false,
            'night_shift_bonus' => 0,
        ]);
    }
}
