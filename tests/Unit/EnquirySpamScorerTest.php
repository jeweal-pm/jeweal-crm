<?php

namespace Tests\Unit;

use App\Models\Enquiry;
use App\Services\Spam\EnquirySpamScorer;
use PHPUnit\Framework\TestCase;

class EnquirySpamScorerTest extends TestCase
{
    public function test_human_readable_long_names_are_not_flagged(): void
    {
        $scorer = new EnquirySpamScorer();

        $first = $scorer->score(new Enquiry([
            'name' => 'Vasuda Leedhirakul',
            'email' => 'vasuda@example.com',
            'phone' => '+66812345678',
            'company' => 'Jeweal',
            'description' => 'Interested in CRM services.',
            'business_type' => ['retail'],
            'interest_in' => ['crm'],
        ]));

        $second = $scorer->score(new Enquiry([
            'name' => 'VasudaLerpankul',
            'email' => 'vasuda@example.com',
            'phone' => '+66812345678',
            'company' => 'Jeweal',
            'description' => 'Interested in CRM services.',
            'business_type' => ['retail'],
            'interest_in' => ['crm'],
        ]));

        $this->assertSame(EnquirySpamScorer::STATUS_CLEAN, $first['status']);
        $this->assertSame(EnquirySpamScorer::STATUS_CLEAN, $second['status']);
    }

    public function test_random_mixed_case_names_are_flagged(): void
    {
        $result = (new EnquirySpamScorer())->score(new Enquiry([
            'name' => 'cWgMqSDJybwMKELixZ',
            'email' => 'lead@example.com',
            'phone' => '+66812345678',
            'company' => 'Example',
            'description' => '',
            'business_type' => ['retail'],
            'interest_in' => ['crm'],
        ]));

        $this->assertSame(EnquirySpamScorer::STATUS_SUSPECTED, $result['status']);
        $this->assertContains('random_mixed_case_name', $result['reasons']);
    }

    public function test_dot_chopped_email_and_random_fields_are_flagged(): void
    {
        $result = (new EnquirySpamScorer())->score(new Enquiry([
            'name' => 'sEQEXjIClkLAasGgE',
            'email' => 'a.we.pu.d.u.qo.t.ad0.4@gmail.com',
            'phone' => '8913520115',
            'company' => 'GdDLJYiEXZzJXXCM',
            'description' => 'FZQZIjrHXCbeatMl',
            'business_type' => ['retail'],
            'interest_in' => ['crm'],
        ]));

        $this->assertSame(EnquirySpamScorer::STATUS_SUSPECTED, $result['status']);
        $this->assertContains('dot_chopped_email_local_part', $result['reasons']);
    }
}
