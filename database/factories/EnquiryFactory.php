<?php

namespace Database\Factories;

use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enquiry>
 */
class EnquiryFactory extends Factory
{
    protected $model = Enquiry::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'business_type' => ['retail'],
            'email' => $this->faker->safeEmail(),
            'country' => 'Thailand',
            'phone' => '+66812345678',
            'company' => $this->faker->company(),
            'company_website' => 'https://example.com',
            'description' => $this->faker->sentence(),
            'interest_in' => ['crm'],
        ];
    }
}
