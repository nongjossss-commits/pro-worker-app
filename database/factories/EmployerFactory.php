<?php

namespace Database\Factories;

use App\Models\Employer;
use App\Models\JobOwner;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Employer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'employerNameTh' => $this->faker->company,
            'employerNameEn' => $this->faker->company,
            'employerId' => $this->faker->unique()->numerify('E#########'),
            'employerTaxId' => $this->faker->numerify('#############'),
            'businessType' => $this->faker->word,
            'signerNameTh' => $this->faker->name,
            'signerNameEn' => $this->faker->name,
            'businessTypeEn' => $this->faker->word,
            'regCapital' => $this->faker->numberBetween(100000, 10000000),
            'regDate' => $this->faker->date(),
            'minimum_wage' => $this->faker->numberBetween(300, 500),
            'job_owner_id' => JobOwner::factory(),
        ];
    }
}