<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailAutomationConfigRequest;
use App\Http\Requests\EmailSequenceRequest;
use App\Http\Requests\EmailSequenceStepRequest;
use App\Http\Requests\EmailTemplateRequest;
use App\Http\Requests\EmailTestSendRequest;
use App\Mail\ManagedEmailMailable;
use App\Models\EmailAuditLog;
use App\Models\EmailAutomationConfig;
use App\Models\EmailCampaign;
use App\Models\EmailEnrollment;
use App\Models\EmailMessage;
use App\Models\EmailSegment;
use App\Models\EmailSequenceTemplate;
use App\Models\EmailSubscriber;
use App\Models\EmailTemplate;
use App\Services\Email\EmailCampaignService;
use App\Services\Email\EmailSegmentService;
use App\Services\Email\EmailSenderResolver;
use App\Services\Email\EmailTemplateRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailManagementController extends Controller
{
    public function dashboard()
    {
        return view('administrator.email.index', [
            'stats' => [
                'templates' => EmailTemplate::query()->count(), 'subscribers' => EmailSubscriber::query()->count(),
                'campaigns' => EmailCampaign::query()->count(), 'queued' => EmailMessage::query()->where('status', 'queued')->count(),
                'sent' => EmailMessage::query()->whereIn('status', ['sent', 'delivered'])->count(),
                'opens' => DB::table('email_events')->where('event_type', 'opened')->count(),
                'clicks' => DB::table('email_events')->where('event_type', 'clicked')->count(),
            ],
        ]);
    }

    public function templates()
    {
        return view('administrator.email.templates.index', ['templates' => EmailTemplate::latest()->paginate(20)]);
    }

    public function createTemplate()
    {
        return view('administrator.email.templates.form', ['template' => new EmailTemplate(['status' => 'draft', 'email_type' => 'transactional'])]);
    }

    public function storeTemplate(EmailTemplateRequest $request, EmailTemplateRenderer $renderer)
    {
        $data = $request->validated();
        $data['html_content'] = $renderer->sanitize($data['html_content']);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $template = EmailTemplate::create($data);
        $template->versions()->create(array_merge($template->only(['subject', 'preview_text', 'html_content', 'plain_text_content', 'sender_name', 'sender_email', 'reply_to_email', 'variables']), ['version' => $template->version ?: 1, 'created_by' => $request->user()->id, 'created_at' => now()]));
        $this->audit('template.created', $template, $request);

        return redirect()->route('email.templates')->with('status', 'Template created.');
    }

    public function editTemplate(int $id)
    {
        return view('administrator.email.templates.form', ['template' => EmailTemplate::findOrFail($id)]);
    }

    public function updateTemplate(EmailTemplateRequest $request, EmailTemplateRenderer $renderer, int $id)
    {
        $template = EmailTemplate::findOrFail($id);
        $data = $request->validated();
        $data['html_content'] = $renderer->sanitize($data['html_content']);
        $data['version'] = $template->version + 1;
        $data['updated_by'] = $request->user()->id;
        $template->update($data);
        $template->versions()->create(array_merge($template->only(['version', 'subject', 'preview_text', 'html_content', 'plain_text_content', 'sender_name', 'sender_email', 'reply_to_email', 'variables']), ['created_by' => $request->user()->id, 'created_at' => now()]));
        $this->audit('template.updated', $template, $request);

        return redirect()->route('email.templates')->with('status', 'Template version saved.');
    }

    public function publishTemplate(Request $request, int $id)
    {
        abort_unless($request->user()->hasCrmPermission('email.template.publish'), 403);
        $template = EmailTemplate::findOrFail($id);
        abort_if(app(EmailTemplateRenderer::class)->unknownVariables($template), 422, 'Template contains unsupported variables.');
        $template->update(['status' => $template->status === 'published' ? 'draft' : 'published', 'updated_by' => $request->user()->id]);
        $this->audit('template.status_changed', $template, $request);

        return redirect()->back();
    }

    public function previewTemplate(Request $request, int $id, EmailTemplateRenderer $renderer)
    {
        $template = EmailTemplate::findOrFail($id);
        $rendered = $renderer->render($template, [
            'first_name' => 'Demo', 'last_name' => 'Recipient', 'email' => 'demo@example.com',
            'company_name' => 'Demo Company', 'enquiry_number' => 'DEMO-001', 'enquiry_type' => 'general',
            'submitted_at' => now()->format('Y-m-d H:i'), 'sales_owner_name' => 'Sales Team',
            'unsubscribe_url' => url('/unsubscribe/demo-token'), 'country' => 'Thailand', 'phone' => '+66 00 000 0000',
            'fair_code' => 'GIS74-DEMO01', 'event_name' => 'Bangkok Gems & Jewelry Fair',
            'event_code' => 'bgjf-74', 'event_dates' => '10-14 September 2026',
            'event_hall' => 'X', 'event_booth' => 'A00', 'business_type' => 'Retail',
            'stores' => 3, 'current_system' => 'None', 'interests' => 'POS, Inventory',
        ]);

        return view('administrator.email.templates.preview', compact('template', 'rendered'));
    }

    public function testSend(EmailTestSendRequest $request, int $id, EmailTemplateRenderer $renderer, EmailSenderResolver $senders)
    {
        if (app()->environment('testing') === false && config('app.env') !== 'production' && config('email_management.test_allowlist') && ! in_array($request->validated('email'), config('email_management.test_allowlist'), true)) {
            abort(422, 'Test recipient is not on the allowlist.');
        }
        $template = EmailTemplate::findOrFail($id);
        $senderEmail = $senders->resolve($request->validated('enquiry_type'), $template->sender_email);
        $rendered = $renderer->render($template, [
            'first_name' => 'Test', 'last_name' => 'Recipient', 'email' => $request->validated('email'),
            'enquiry_number' => 'TEST-001', 'unsubscribe_url' => url('/unsubscribe/test-token'),
            'fair_code' => 'GIS74-TEST01', 'event_name' => 'Bangkok Gems & Jewelry Fair',
            'event_dates' => '10-14 September 2026', 'event_hall' => 'X', 'event_booth' => 'A00',
            'company' => 'Test Company', 'phone' => '+66 00 000 0000', 'business_type' => 'Retail',
            'stores' => 3, 'interests' => 'POS, Inventory',
        ]);
        Mail::to($request->validated('email'))->send(new ManagedEmailMailable($rendered['html_content'], $rendered['plain_text_content'], '[TEST] '.$rendered['subject'], $senderEmail, $template->sender_name, $template->reply_to_email));

        return redirect()->back()->with('status', 'Test email sent.');
    }

    public function duplicateTemplate(Request $request, int $id)
    {
        abort_unless($request->user()->hasCrmPermission('email.template.manage'), 403);
        $source = EmailTemplate::findOrFail($id);
        $copy = $source->replicate(['code', 'version', 'status', 'created_by', 'updated_by']);
        $copy->fill(['name' => $source->name.' Copy', 'code' => $source->code.'-copy-'.Str::lower(Str::random(5)), 'version' => 1, 'status' => 'draft', 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        $copy->save();

        return redirect()->route('email.templates.edit', $copy->id);
    }

    public function restoreTemplateVersion(Request $request, int $id, int $version)
    {
        abort_unless($request->user()->hasCrmPermission('email.template.manage'), 403);
        $template = EmailTemplate::findOrFail($id);
        $source = $template->versions()->where('version', $version)->firstOrFail();
        $template->update(array_merge($source->only(['subject', 'preview_text', 'html_content', 'plain_text_content', 'sender_name', 'sender_email', 'reply_to_email', 'variables']), ['version' => $template->version + 1, 'status' => 'draft', 'updated_by' => $request->user()->id]));

        return redirect()->route('email.templates.edit', $template->id)->with('status', 'Previous version restored as a new draft version.');
    }

    public function config()
    {
        return view('administrator.email.config.index', [
            'configs' => EmailAutomationConfig::with(['customerTemplate', 'internalTemplate'])->orderBy('enquiry_type')->get(),
            'templates' => EmailTemplate::where('status', 'published')->orderBy('name')->get(),
        ]);
    }

    public function updateConfig(EmailAutomationConfigRequest $request, string $type)
    {
        $config = EmailAutomationConfig::firstOrCreate(['enquiry_type' => $type]);
        $config->update($request->validated());
        $this->audit('automation_config.updated', $config, $request);

        return redirect()->route('email.config')->with('status', ucfirst($type).' config updated.');
    }

    public function segments()
    {
        return view('administrator.email.segments.index', ['segments' => EmailSegment::latest()->paginate(20)]);
    }

    public function storeSegment(Request $request, EmailSegmentService $segments)
    {
        abort_unless($request->user()->hasCrmPermission('email.segment.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'], 'code' => ['required', 'alpha_dash', 'max:100'],
            'segment_type' => ['required', 'in:dynamic,static'], 'subscription_status' => ['nullable', 'string'],
            'source_type' => ['nullable', 'in:general,gis,gms,gis_fair'], 'customer_status' => ['nullable', 'in:lead_mql,sql,prospect,customer'],
            'created_after_days' => ['nullable', 'integer', 'min:1'],
        ]);
        $conditions = array_filter(Arr::only($data, ['subscription_status', 'source_type', 'customer_status', 'created_after_days']), fn ($value) => $value !== null && $value !== '');
        $segment = EmailSegment::create(['name' => $data['name'], 'code' => $data['code'], 'segment_type' => $data['segment_type'], 'conditions' => $conditions, 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        if ($segment->segment_type === 'static') {
            $segments->refreshStatic($segment);
        }

        return redirect()->route('email.segments')->with('status', 'Segment created.');
    }

    public function campaigns()
    {
        return view('administrator.email.campaigns.index', ['campaigns' => EmailCampaign::with(['segment', 'template', 'sequence', 'variants'])->latest()->paginate(20), 'templates' => EmailTemplate::where('status', 'published')->get()]);
    }

    public function createCampaign()
    {
        return view('administrator.email.campaigns.form', ['segments' => EmailSegment::where('status', 'active')->get(), 'templates' => EmailTemplate::where('status', 'published')->get(), 'sequences' => EmailSequenceTemplate::where('status', 'published')->get()]);
    }

    public function storeCampaign(Request $request)
    {
        abort_unless($request->user()->hasCrmPermission('email.campaign.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'], 'campaign_type' => ['required', 'in:single,sequence'],
            'email_segment_id' => ['required', 'exists:email_segments,id'], 'email_template_id' => ['nullable', 'exists:email_templates,id'],
            'email_sequence_template_id' => ['nullable', 'exists:email_sequence_templates,id'], 'scheduled_at' => ['nullable', 'date'],
            'sending_limit' => ['nullable', 'integer', 'min:1'],
        ]);
        $campaign = EmailCampaign::create(array_merge($data, ['owner_id' => $request->user()->id, 'approval_status' => 'draft', 'status' => 'draft']));
        $this->audit('campaign.created', $campaign, $request);

        return redirect()->route('email.campaigns')->with('status', 'Campaign created.');
    }

    public function approveCampaign(Request $request, int $id)
    {
        abort_unless($request->user()->hasCrmPermission('email.campaign.approve'), 403);
        $campaign = EmailCampaign::findOrFail($id);
        $campaign->update(['approval_status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now(), 'status' => 'scheduled']);

        return redirect()->back();
    }

    public function runCampaign(Request $request, EmailCampaignService $service, int $id)
    {
        abort_unless($request->user()->hasCrmPermission('email.campaign.send'), 403);
        $campaign = EmailCampaign::findOrFail($id);
        abort_unless($campaign->approval_status === 'approved', 422, 'Campaign must be approved before sending.');
        $service->run($campaign);

        return redirect()->back()->with('status', 'Campaign processing started.');
    }

    public function storeVariant(Request $request, int $id)
    {
        abort_unless($request->user()->hasCrmPermission('email.campaign.manage'), 403);
        $data = $request->validate([
            'variant_key' => ['required', 'alpha_dash', 'max:16'], 'email_template_id' => ['nullable', 'exists:email_templates,id'],
            'subject' => ['nullable', 'string', 'max:255'], 'allocation' => ['required', 'integer', 'min:1', 'max:100'],
            'success_metric' => ['required', 'in:click_rate,reply_rate,conversion_rate'], 'minimum_sample_size' => ['required', 'integer', 'min:1'],
        ]);
        $campaign = EmailCampaign::findOrFail($id);
        $campaign->variants()->updateOrCreate(['variant_key' => $data['variant_key']], $data);

        return redirect()->back()->with('status', 'Campaign variant saved.');
    }

    public function sequences()
    {
        return view('administrator.email.sequences.index', [
            'sequences' => EmailSequenceTemplate::query()->withCount('steps')->latest()->paginate(20),
        ]);
    }

    public function createSequence()
    {
        return view('administrator.email.sequences.form');
    }

    public function storeSequence(EmailSequenceRequest $request)
    {
        $sequence = EmailSequenceTemplate::create(array_merge($request->validated(), [
            'status' => 'draft',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]));
        $this->audit('sequence.created', $sequence, $request);

        return redirect()->route('email.sequences.show', $sequence->id)->with('status', 'Sequence created. Add the first step before publishing it.');
    }

    public function showSequence(int $id)
    {
        $sequence = EmailSequenceTemplate::query()->with('steps.template')->findOrFail($id);
        $nextStepNumber = ((int) $sequence->steps->max('step_number')) + 1;

        return view('administrator.email.sequences.show', [
            'sequence' => $sequence,
            'templates' => EmailTemplate::query()->where('status', 'published')->orderBy('name')->get(),
            'nextStepNumber' => max(1, $nextStepNumber),
        ]);
    }

    public function updateSequence(EmailSequenceRequest $request, int $id)
    {
        $sequence = EmailSequenceTemplate::findOrFail($id);
        $data = $request->validated();
        if (($data['status'] ?? $sequence->status) === 'published' && ! $sequence->steps()->exists()) {
            return redirect()->back()->withErrors(['status' => 'Add at least one step before publishing this sequence.'])->withInput();
        }

        $sequence->update(array_merge($data, ['updated_by' => $request->user()->id]));
        $this->audit('sequence.updated', $sequence, $request);

        return redirect()->route('email.sequences.show', $sequence->id)->with('status', 'Sequence settings saved.');
    }

    public function storeSequenceStep(EmailSequenceStepRequest $request, int $id)
    {
        $sequence = EmailSequenceTemplate::findOrFail($id);
        $data = $request->validated();
        if ($sequence->steps()->where('step_number', $data['step_number'])->exists()) {
            return redirect()->back()->withErrors(['step_number' => 'This step number already exists.'])->withInput();
        }

        $step = $sequence->steps()->create($data);
        $this->audit('sequence.step.created', $step, $request);

        return redirect()->route('email.sequences.show', $sequence->id)->with('status', 'Sequence step added.');
    }

    public function updateSequenceStep(EmailSequenceStepRequest $request, int $id, int $stepId)
    {
        $sequence = EmailSequenceTemplate::findOrFail($id);
        $step = $sequence->steps()->findOrFail($stepId);
        $data = $request->validated();
        $data['step_number'] = $step->step_number;
        $step->update($data);
        $this->audit('sequence.step.updated', $step, $request);

        return redirect()->route('email.sequences.show', $sequence->id)->with('status', 'Sequence step updated.');
    }

    public function destroySequenceStep(Request $request, int $id, int $stepId)
    {
        abort_unless($request->user()->hasCrmPermission('email.sequence.manage'), 403);
        $sequence = EmailSequenceTemplate::findOrFail($id);
        $step = $sequence->steps()->findOrFail($stepId);
        if (EmailEnrollment::query()->where('email_sequence_template_id', $sequence->id)->where('status', 'active')->exists()) {
            return redirect()->back()->withErrors(['steps' => 'Pause or complete active enrollments before removing a step.']);
        }

        $removedStepNumber = $step->step_number;
        $step->delete();
        $sequence->steps()->where('step_number', '>', $removedStepNumber)->orderBy('step_number')->each(function ($remainingStep) {
            $remainingStep->update(['step_number' => $remainingStep->step_number - 1]);
        });
        $this->audit('sequence.step.deleted', $step, $request);

        return redirect()->route('email.sequences.show', $sequence->id)->with('status', 'Sequence step removed.');
    }

    public function enrollments()
    {
        return view('administrator.email.enrollments.index', ['enrollments' => EmailEnrollment::with(['subscriber', 'sequence'])->latest()->paginate(25), 'sequences' => EmailSequenceTemplate::where('status', 'published')->get()]);
    }

    public function enroll(Request $request)
    {
        abort_unless($request->user()->hasCrmPermission('email.sequence.manage'), 403);
        $data = $request->validate(['email' => ['required', 'email'], 'email_sequence_template_id' => ['required', 'exists:email_sequence_templates,id']]);
        $subscriber = EmailSubscriber::firstOrCreate(['email' => strtolower($data['email'])], ['unsubscribe_token_hash' => hash('sha256', Str::random(64)), 'subscription_status' => 'subscribed']);
        EmailEnrollment::firstOrCreate(['email_subscriber_id' => $subscriber->id, 'email_sequence_template_id' => $data['email_sequence_template_id']], ['status' => 'active', 'enrolled_at' => now(), 'next_scheduled_at' => now()]);

        return redirect()->route('email.enrollments')->with('status', 'Subscriber enrolled.');
    }

    public function destroyEnrollment(Request $request, int $id)
    {
        abort_unless($request->user()->hasCrmPermission('email.sequence.manage'), 403);
        $enrollment = EmailEnrollment::findOrFail($id);

        DB::transaction(function () use ($enrollment, $request) {
            EmailMessage::query()
                ->where('email_enrollment_id', $enrollment->id)
                ->whereIn('status', ['queued', 'deferred', 'processing'])
                ->update(['status' => 'suppressed', 'failure_reason' => 'Sequence enrollment removed', 'updated_at' => now()]);
            $enrollment->delete();
            $this->audit('sequence.enrollment.deleted', $enrollment, $request);
        });

        return redirect()->route('email.enrollments')->with('status', 'Enrollment removed and pending messages suppressed.');
    }

    public function logs()
    {
        return view('administrator.email.logs.index', ['messages' => EmailMessage::with('subscriber')->latest()->paginate(30)]);
    }

    private function audit(string $action, $model, Request $request): void
    {
        EmailAuditLog::create(['user_id' => $request->user()->id, 'action' => $action, 'auditable_type' => get_class($model), 'auditable_id' => $model->getKey(), 'ip_hash' => hash('sha256', (string) $request->ip())]);
    }
}
