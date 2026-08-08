<?php

namespace Database\Seeders;

use App\Models\EmailAutomationConfig;
use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailManagementSeeder extends Seeder
{
    public function run()
    {
        $gisInternalHtml = <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#8ed8d4;margin:0;padding:32px 12px;color:#172033;font-family:Arial,Helvetica,sans-serif;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:520px;">
<tr><td style="padding:4px 2px 28px;">
<img src="https://gis247.net/assets/v2/images/gis-xl-logo.png" alt="GIS" width="145" style="display:block;height:auto;max-width:145px;">
<div style="color:#ffffff;font-size:13px;font-weight:bold;text-align:right;margin-top:-18px;">{{submitted_at}}</div>
</td></tr>
<tr><td style="background:#ffffff;border-radius:24px;padding:34px 40px 30px;font-size:14px;line-height:1.55;">
<p style="margin:0 0 22px;">Dear GIS Organization,</p>
<p style="margin:0 0 18px;">I trust this message finds you well.</p>
<p style="margin:0 0 20px;">A new GIS enquiry has been submitted through the website. Please review the details below and follow up with the requester.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px;line-height:1.45;">
<tr><td style="color:#667085;width:145px;padding:3px 0;">Reference</td><td style="color:#24aeb0;font-weight:bold;padding:3px 0;">{{enquiry_number}}</td></tr>
<tr><td style="color:#667085;padding:3px 0;">Name</td><td style="color:#24aeb0;font-weight:bold;padding:3px 0;">{{first_name}} {{last_name}}</td></tr>
<tr><td style="color:#667085;padding:3px 0;">Email</td><td style="color:#168bd0;font-weight:bold;padding:3px 0;">{{email}}</td></tr>
<tr><td style="color:#667085;padding:3px 0;">Phone number</td><td style="color:#24aeb0;font-weight:bold;padding:3px 0;">{{phone}}</td></tr>
<tr><td style="color:#667085;padding:3px 0;">Country</td><td style="color:#24aeb0;font-weight:bold;padding:3px 0;">{{country}}</td></tr>
<tr><td style="color:#667085;padding:3px 0;vertical-align:top;">Inquiry</td><td style="color:#24aeb0;font-weight:bold;padding:3px 0;">{{inquiry}}</td></tr>
<tr><td style="color:#667085;padding:3px 0;vertical-align:top;">Message</td><td style="color:#172033;padding:3px 0;">{{message}}</td></tr>
</table>
<p style="margin:24px 0 18px;">Please review this enquiry and contact the requester when appropriate. If you need any further information, please reply to this email.</p>
<p style="margin:0;">Best regards,<br><strong style="color:#24b8b7;">GIS Manage Pro</strong></p>
</td></tr>
<tr><td style="padding:28px 2px 0;color:#24aeb0;font-size:13px;font-weight:bold;">GIS Manage Pro</td></tr>
<tr><td style="padding:16px 0 12px;color:#24aeb0;font-size:12px;">This is an automated notification from the GIS enquiry system.</td></tr>
</table>
</td></tr>
</table>
HTML;

        $gisCustomerHtml = <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#8ed8d4;margin:0;padding:32px 12px;color:#172033;font-family:Arial,Helvetica,sans-serif;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:520px;">
<tr><td style="padding:4px 2px 28px;">
<img src="https://gis247.net/assets/v2/images/gis-xl-logo.png" alt="GIS" width="145" style="display:block;height:auto;max-width:145px;">
<div style="color:#ffffff;font-size:13px;font-weight:bold;text-align:right;margin-top:-18px;">{{submitted_at}}</div>
</td></tr>
<tr><td style="background:#ffffff;border-radius:24px;padding:34px 40px 30px;font-size:14px;line-height:1.55;">
<p style="margin:0 0 22px;">Dear {{first_name}},</p>
<p style="margin:0 0 18px;">Thank you for contacting GIS Manage Pro.</p>
<p style="margin:0 0 18px;">We have received your enquiry and our team is reviewing your request. A member of our team will contact you as soon as possible.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1fbfa;border-left:3px solid #24b8b7;font-size:14px;line-height:1.45;margin:22px 0;padding:12px 16px;">
<tr><td style="color:#667085;padding:3px 0;">Reference number</td><td style="color:#24aeb0;font-weight:bold;padding:3px 0;">{{enquiry_number}}</td></tr>
<tr><td style="color:#667085;padding:3px 0;">Enquiry topic</td><td style="color:#172033;padding:3px 0;">{{inquiry}}</td></tr>
<tr><td style="color:#667085;padding:3px 0;">Submitted</td><td style="color:#172033;padding:3px 0;">{{submitted_at}}</td></tr>
</table>
<p style="margin:0 0 18px;">If you need to provide additional information, simply reply to this email and include your reference number.</p>
<p style="margin:0 0 22px;">We appreciate your interest and look forward to assisting you.</p>
<p style="margin:0;">Best regards,<br><strong style="color:#24b8b7;">GIS Manage Pro Team</strong></p>
</td></tr>
<tr><td style="padding:28px 2px 0;color:#24aeb0;font-size:13px;font-weight:bold;">GIS Manage Pro</td></tr>
<tr><td style="padding:16px 0 12px;color:#24aeb0;font-size:12px;">Thank you for choosing GIS Manage Pro.</td></tr>
</table>
</td></tr>
</table>
HTML;

        $templates = [
            'general-enquiry-confirmation' => ['General Enquiry Confirmation', 'general', 'transactional', 'enquiry_confirmation', null, null],
            'gis-enquiry-confirmation' => ['GIS Enquiry Confirmation', 'gis', 'transactional', 'enquiry_confirmation', $gisCustomerHtml, 'Thank you for contacting GIS Manage Pro - {{enquiry_number}}'],
            'gms-enquiry-confirmation' => ['GMS Enquiry Confirmation', 'gms', 'transactional', 'enquiry_confirmation', null, null],
            'general-internal-notification' => ['General Enquiry Internal Notification', 'general_internal', 'internal', 'internal_notification', null, null],
            'gis-internal-notification' => ['GIS Enquiry Internal Notification', 'gis_internal', 'internal', 'internal_notification', $gisInternalHtml, 'New GIS enquiry received - {{enquiry_number}}'],
            'gms-internal-notification' => ['GMS Enquiry Internal Notification', 'gms_internal', 'internal', 'internal_notification', null, null],
        ];

        $ids = [];
        foreach ($templates as $code => [$name, $subjectType, $emailType, $category, $customHtml, $customSubject]) {
            $html = $customHtml ?: '<p>Dear {{first_name}},</p><p>Thank you for your enquiry. Our team has received your request and will follow up shortly.</p><p>Reference: <strong>{{enquiry_number}}</strong></p><p>Best regards,<br>{{sales_owner_name}}</p><p><a href="{{unsubscribe_url}}">Manage email preferences</a></p>';
            $template = EmailTemplate::updateOrCreate(['code' => $code], [
                'name' => $name,
                'email_type' => $emailType,
                'category' => $category,
                'subject' => $customSubject ?: $name.' - {{enquiry_number}}',
                'preview_text' => 'Thank you for contacting us.',
                'html_content' => $html,
                'plain_text_content' => $code === 'gis-enquiry-confirmation'
                    ? "Dear {{first_name}},\n\nThank you for contacting GIS Manage Pro. We have received your enquiry. Reference: {{enquiry_number}}. Our team will contact you as soon as possible.\n\nBest regards,\nGIS Manage Pro Team"
                    : ($code === 'gis-internal-notification'
                        ? "New GIS enquiry received.\n\nReference: {{enquiry_number}}\nName: {{first_name}} {{last_name}}\nEmail: {{email}}\nPhone: {{phone}}\nCountry: {{country}}\nInquiry: {{inquiry}}\nMessage: {{message}}"
                        : 'Dear {{first_name}},\n\nThank you for your enquiry. Reference: {{enquiry_number}}\n\nBest regards,\n{{sales_owner_name}}'),
                'status' => 'published',
                'sender_name' => str_starts_with($code, 'gis-') ? 'GIS Manage Pro' : null,
                'variables' => ['first_name', 'last_name', 'email', 'enquiry_number', 'enquiry_type', 'submitted_at', 'sales_owner_name', 'unsubscribe_url', 'country', 'phone', 'inquiry', 'message'],
            ]);
            $ids[$subjectType] = $template->id;
        }

        $recipients = config('email_management.internal_recipients');
        foreach (['general', 'gis', 'gms'] as $type) {
            $config = EmailAutomationConfig::firstOrNew(['enquiry_type' => $type]);
            $existing = $config->exists;
            $config->fill([
                'customer_enabled' => $existing ? $config->customer_enabled : true,
                'customer_template_id' => $ids[$type],
                'internal_enabled' => $existing ? $config->internal_enabled : ! empty($recipients),
                'internal_template_id' => $ids[$type.'_internal'],
                'internal_to' => $config->exists && $config->internal_to !== null ? $config->internal_to : $recipients,
                'internal_cc' => $config->exists && $config->internal_cc !== null ? $config->internal_cc : [],
                'internal_bcc' => $config->exists && $config->internal_bcc !== null ? $config->internal_bcc : [],
                'welcome_enabled' => $existing ? $config->welcome_enabled : false,
            ]);
            $config->save();
        }
    }
}
