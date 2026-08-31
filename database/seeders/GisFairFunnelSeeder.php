<?php

namespace Database\Seeders;

use App\Models\EmailAutomationConfig;
use App\Models\EmailTemplate;
use App\Models\GisFairCampaign;
use Illuminate\Database\Seeder;

class GisFairFunnelSeeder extends Seeder
{
    public function run()
    {
        $customerHtml = <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#8ed8d4;margin:0;padding:32px 12px;color:#172033;font-family:Arial,Helvetica,sans-serif;"><tr><td align="center"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:540px;"><tr><td style="padding:4px 2px 26px;"><img src="https://gis247.net/assets/v2/images/gis-xl-logo.png" alt="GIS" width="145" style="display:block;height:auto;max-width:145px;"></td></tr><tr><td style="background:#ffffff;border-radius:24px;padding:34px 40px 30px;font-size:14px;line-height:1.55;"><p style="margin:0 0 20px;">Dear {{first_name}},</p><h1 style="margin:0 0 14px;color:#172033;font-size:22px;letter-spacing:0;">Your fair registration is confirmed</h1><p style="margin:0 0 20px;">Thank you for registering to meet GIS Manage Pro at {{event_name}}.</p><div style="margin:22px 0;padding:20px;text-align:center;background:#f1fbfa;border:1px solid #c8ece8;"><div style="color:#667085;font-size:11px;font-weight:bold;text-transform:uppercase;">Your fair code</div><div style="margin-top:7px;color:#149b9c;font-size:28px;font-weight:bold;letter-spacing:0;">{{fair_code}}</div></div><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px;"><tr><td style="color:#667085;padding:4px 0;width:120px;">Event</td><td style="padding:4px 0;">{{event_name}}</td></tr><tr><td style="color:#667085;padding:4px 0;">Dates</td><td style="padding:4px 0;">{{event_dates}}</td></tr><tr><td style="color:#667085;padding:4px 0;">Location</td><td style="padding:4px 0;">Hall {{event_hall}}, Booth {{event_booth}}</td></tr></table><p style="margin:22px 0 0;">Please show this code to our team at the booth. We look forward to meeting you.</p><p style="margin:22px 0 0;">Best regards,<br><strong style="color:#24b8b7;">GIS Manage Pro Team</strong></p></td></tr></table></td></tr></table>
HTML;

        $internalHtml = <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#8ed8d4;margin:0;padding:32px 12px;color:#172033;font-family:Arial,Helvetica,sans-serif;"><tr><td align="center"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:540px;"><tr><td style="padding:4px 2px 26px;"><img src="https://gis247.net/assets/v2/images/gis-xl-logo.png" alt="GIS" width="145" style="display:block;height:auto;max-width:145px;"></td></tr><tr><td style="background:#ffffff;border-radius:24px;padding:34px 40px 30px;font-size:14px;line-height:1.55;"><h1 style="margin:0 0 18px;font-size:21px;letter-spacing:0;">New fair registration</h1><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="color:#667085;padding:4px 0;width:145px;">Fair code</td><td style="color:#149b9c;font-weight:bold;padding:4px 0;">{{fair_code}}</td></tr><tr><td style="color:#667085;padding:4px 0;">Event</td><td style="padding:4px 0;">{{event_name}}</td></tr><tr><td style="color:#667085;padding:4px 0;">Name</td><td style="padding:4px 0;">{{first_name}} {{last_name}}</td></tr><tr><td style="color:#667085;padding:4px 0;">Company</td><td style="padding:4px 0;">{{company}}</td></tr><tr><td style="color:#667085;padding:4px 0;">Email</td><td style="padding:4px 0;">{{email}}</td></tr><tr><td style="color:#667085;padding:4px 0;">Phone</td><td style="padding:4px 0;">{{phone}}</td></tr><tr><td style="color:#667085;padding:4px 0;">Business</td><td style="padding:4px 0;">{{business_type}} / {{stores}} stores</td></tr><tr><td style="color:#667085;padding:4px 0;">Interests</td><td style="padding:4px 0;">{{interests}}</td></tr></table></td></tr></table></td></tr></table>
HTML;

        $customer = EmailTemplate::updateOrCreate(['code' => 'gis-fair-registration-confirmation'], [
            'name' => 'GIS Fair Registration Confirmation',
            'email_type' => 'transactional',
            'category' => 'fair_registration',
            'subject' => 'Your {{event_name}} fair code - {{fair_code}}',
            'preview_text' => 'Your GIS fair registration and booth code are ready.',
            'html_content' => $customerHtml,
            'plain_text_content' => "Dear {{first_name}},\n\nYour registration for {{event_name}} is confirmed. Fair code: {{fair_code}}. Visit us at Hall {{event_hall}}, Booth {{event_booth}}.\n\nGIS Manage Pro Team",
            'sender_name' => 'GIS247',
            'status' => 'published',
            'variables' => ['first_name', 'event_name', 'event_dates', 'event_hall', 'event_booth', 'fair_code'],
        ]);

        $internal = EmailTemplate::updateOrCreate(['code' => 'gis-fair-registration-internal'], [
            'name' => 'GIS Fair Registration Internal Notification',
            'email_type' => 'internal',
            'category' => 'fair_registration',
            'subject' => 'New fair registration - {{fair_code}} - {{company}}',
            'preview_text' => 'A new GIS fair lead has registered.',
            'html_content' => $internalHtml,
            'plain_text_content' => "New fair registration\n\nFair code: {{fair_code}}\nEvent: {{event_name}}\nName: {{first_name}} {{last_name}}\nCompany: {{company}}\nEmail: {{email}}\nPhone: {{phone}}\nInterests: {{interests}}",
            'sender_name' => 'GIS247',
            'status' => 'published',
            'variables' => ['first_name', 'last_name', 'company', 'email', 'phone', 'business_type', 'stores', 'interests', 'event_name', 'fair_code'],
        ]);

        $recipients = config('email_management.internal_recipients');
        EmailAutomationConfig::query()->updateOrCreate(['enquiry_type' => 'gis_fair'], [
            'customer_enabled' => true,
            'customer_template_id' => $customer->id,
            'customer_delay_seconds' => 0,
            'internal_enabled' => ! empty($recipients),
            'internal_template_id' => $internal->id,
            'internal_to' => $recipients,
            'internal_cc' => [],
            'internal_bcc' => [],
            'internal_assignment_mode' => 'config',
            'welcome_enabled' => false,
        ]);

        $defaults = config('gis_fair.default_campaign');
        GisFairCampaign::query()->firstOrCreate(['code' => $defaults['code']], array_merge($defaults, [
            'edition' => 'BGJF #74',
            'status' => 'draft',
            'dates_display' => '10-14 September 2026',
            'offer_deadline' => '2026-09-14 18:00:00',
            'timezone' => 'Asia/Bangkok',
            'code_prefix' => 'GIS74',
            'accepting_submissions' => true,
        ]));
    }
}
