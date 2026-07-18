<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\GisEnquiry;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GisEnquiry>
 */
class GisEnquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = GisEnquiry::class;

    public function definition()
    {
        return [
            'first_name' => $this->faker->word,
            'last_name' => $this->faker->word,
            'email' => $this->faker->word,
            'phone_number' => "+6658215487",
            'inquiry' => 'request_quotation',
            'status' => 'pending',
            'message' => 'I need to test GIS',
        ];
    }
}
