<?php

namespace Database\Factories;

use App\Models\GmsStoneEnquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GmsStoneEnquiry>
 */
class GmsStoneEnquiryFactory extends Factory
{
    protected $model = GmsStoneEnquiry::class;

    public function definition()
    {
        $accountType = $this->faker->randomElement(['personal', 'business']);

        return [
            'full_name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'phone_number' => $this->faker->phoneNumber,
            'country_code' => $this->faker->countryCode,
            'account_type' => $accountType,
            'business_name' => $accountType === 'business' ? $this->faker->company : null,
            'company_name' => $accountType === 'business' ? $this->faker->company : null,
            'tax_id' => $accountType === 'business' ? $this->faker->numerify('##########') : null,
            'mailing_name' => $this->faker->name,
            'website' => $accountType === 'business' ? $this->faker->url : null,
            'office_type' => $accountType === 'business' ? $this->faker->randomElement(['Head Office', 'Branch']) : null,
            'branch_code' => $accountType === 'business' ? $this->faker->bothify('BR-###') : null,
            'address' => $this->faker->address,
            'country' => $this->faker->country,
            'city' => $this->faker->city,
            'province' => $this->faker->state,
            'postcode' => $this->faker->postcode,
            'contact_name' => $this->faker->name,
            'contact_email' => $this->faker->safeEmail,
            'contact_phone' => $this->faker->phoneNumber,
            'is_seen' => false,
            'is_approved' => false,
            'privacy_policy_accepted' => true,
            'terms_conditions_accepted' => true,
        ];
    }
}
