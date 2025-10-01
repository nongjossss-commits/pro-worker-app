<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'employer_id' => Employer::factory(),
            'employeeNameTh' => $this->faker->name,
            'employeeNameEn' => $this->faker->name,
            'employeeNationality' => $this->faker->randomElement(['เมียนมา', 'ลาว', 'กัมพูชา', 'เวียดนาม']),
            'employeePassport' => $this->faker->unique()->numerify('P########'),
            'passportExpiryDate' => $this->faker->date(),
            'employeeWorkPermit' => $this->faker->unique()->numerify('WP########'),
            'workPermitExpiryDate' => $this->faker->date(),
            'visaExpiryDate' => $this->faker->date(),
            'ninetyDayReportDate' => $this->faker->date(),
            'employeeDob' => $this->faker->date(),
            'startDate' => $this->faker->date(),
        ];
    }
}