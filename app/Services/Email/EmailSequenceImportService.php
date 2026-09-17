<?php

namespace App\Services\Email;

use App\Models\EmailSequenceTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmailSequenceImportService
{
    public const MAX_STEPS = 14;

    public function __construct(private EmailTemplateRenderer $renderer)
    {
    }

    public function import(array $payload, User $user): EmailSequenceTemplate
    {
        $sequenceData = $payload['sequence'] ?? $payload;
        $steps = $payload['steps'] ?? ($sequenceData['steps'] ?? null);

        if (! is_array($sequenceData) || ! is_array($steps)) {
            throw ValidationException::withMessages(['payload' => 'JSON must contain a sequence object and a steps array.']);
        }

        $this->validateSequence($sequenceData);
        $this->validateSteps($steps);

        if (EmailSequenceTemplate::query()->where('code', $sequenceData['code'])->exists()) {
            throw ValidationException::withMessages(['payload' => 'A sequence with this code already exists. Choose a new code before importing.']);
        }

        return DB::transaction(function () use ($sequenceData, $steps, $user) {
            $sequence = EmailSequenceTemplate::create([
                'name' => $sequenceData['name'],
                'code' => $sequenceData['code'],
                'description' => $sequenceData['description'] ?? null,
                'status' => 'draft',
                'version' => (int) ($sequenceData['version'] ?? 1),
                'priority' => (int) ($sequenceData['priority'] ?? 100),
                'entry_conditions' => $sequenceData['entry_conditions'] ?? null,
                'exit_conditions' => $sequenceData['exit_conditions'] ?? null,
                'timezone' => $sequenceData['timezone'] ?? 'Asia/Bangkok',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach (array_values($steps) as $index => $stepData) {
                $sequence->steps()->create([
                    'step_number' => (int) ($stepData['step_number'] ?? ($index + 1)),
                    'content_mode' => 'custom',
                    'email_template_id' => null,
                    'subject' => $stepData['subject'],
                    'preview_text' => $stepData['preview_text'] ?? null,
                    'html_content' => $this->renderer->sanitize($stepData['html_content']),
                    'plain_text_content' => $stepData['plain_text_content'] ?? null,
                    'variables' => $stepData['variables'] ?? $this->variablesIn($stepData['subject'].' '.$stepData['html_content']),
                    'delay_value' => (int) ($stepData['delay_value'] ?? 0),
                    'delay_unit' => $stepData['delay_unit'] ?? 'days',
                    'timezone' => $stepData['timezone'] ?? ($sequenceData['timezone'] ?? 'Asia/Bangkok'),
                    'business_days_only' => (bool) ($stepData['business_days_only'] ?? false),
                    'conditions' => $stepData['conditions'] ?? null,
                    'skip_conditions' => $stepData['skip_conditions'] ?? null,
                    'actions' => $stepData['actions'] ?? null,
                ]);
            }

            return $sequence;
        });
    }

    private function validateSequence(array $sequence): void
    {
        $errors = [];
        if (! isset($sequence['name']) || ! is_string($sequence['name']) || trim($sequence['name']) === '' || mb_strlen($sequence['name']) > 150) {
            $errors['payload'][] = 'sequence.name is required.';
        }
        if (! isset($sequence['code']) || ! is_string($sequence['code']) || mb_strlen($sequence['code']) > 100 || ! preg_match('/^[A-Za-z0-9_-]+$/', $sequence['code'])) {
            $errors['payload'][] = 'sequence.code is required and may contain only letters, numbers, underscores, and hyphens.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function validateSteps(array $steps): void
    {
        $errors = [];
        if (count($steps) < 1 || count($steps) > self::MAX_STEPS) {
            $errors['payload'][] = 'The imported sequence must contain between 1 and '.self::MAX_STEPS.' steps.';
        }

        foreach (array_values($steps) as $index => $step) {
            $number = $index + 1;
            if (! is_array($step)) {
                $errors['payload'][] = "steps.{$number} must be an object.";
                continue;
            }

            if (($step['step_number'] ?? $number) !== $number) {
                $errors['payload'][] = "steps.{$number}.step_number must be {$number}.";
            }
            if (! isset($step['subject']) || ! is_string($step['subject']) || trim($step['subject']) === '') {
                $errors['payload'][] = "steps.{$number}.subject is required.";
            }
            if (isset($step['subject']) && is_string($step['subject']) && mb_strlen($step['subject']) > 255) {
                $errors['payload'][] = "steps.{$number}.subject may not exceed 255 characters.";
            }
            if (isset($step['preview_text']) && is_string($step['preview_text']) && mb_strlen($step['preview_text']) > 255) {
                $errors['payload'][] = "steps.{$number}.preview_text may not exceed 255 characters.";
            }
            if (! isset($step['html_content']) || ! is_string($step['html_content']) || trim($step['html_content']) === '') {
                $errors['payload'][] = "steps.{$number}.html_content is required.";
            }
            if (isset($step['delay_value']) && (! is_int($step['delay_value']) && ! ctype_digit((string) $step['delay_value']) || (int) $step['delay_value'] < 0)) {
                $errors['payload'][] = "steps.{$number}.delay_value must be a non-negative integer.";
            }
            if (isset($step['delay_unit']) && ! in_array($step['delay_unit'], ['minutes', 'hours', 'days'], true)) {
                $errors['payload'][] = "steps.{$number}.delay_unit must be minutes, hours, or days.";
            }
            if (isset($step['subject'], $step['html_content'])) {
                $unknown = $this->renderer->unknownVariablesInContent($step['subject'], $step['html_content']);
                if ($unknown) {
                    $errors['payload'][] = "steps.{$number} contains unsupported variables: {{".implode('}}, {{', $unknown).' }}.';
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function variablesIn(string $content): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $content, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
