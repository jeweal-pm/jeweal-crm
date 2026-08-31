<?php

namespace App\Services\Email;

use App\Models\EmailTemplate;

class EmailTemplateRenderer
{
    public const VARIABLES = [
        'first_name', 'last_name', 'email', 'company_name', 'enquiry_number', 'enquiry_type',
        'submitted_at', 'sales_owner_name', 'unsubscribe_url', 'country', 'phone', 'inquiry', 'message',
        'fair_code', 'event_name', 'event_code', 'event_dates', 'event_hall', 'event_booth',
        'company', 'business_type', 'stores', 'current_system', 'interests',
    ];

    public function render(EmailTemplate $template, array $data): array
    {
        $values = [];
        foreach (self::VARIABLES as $variable) {
            $values[$variable] = $this->stringify($data[$variable] ?? '');
        }

        $subject = $this->replace($template->subject, $values, false);
        $html = $this->replace($template->html_content, $values);
        $plain = $this->replace($template->plain_text_content ?: strip_tags($template->html_content), $values, false);

        return [
            'subject' => $subject,
            'html_content' => $this->sanitize($html),
            'plain_text_content' => trim(strip_tags($plain)),
            'missing_variables' => $this->missing($template, $data),
        ];
    }

    public function unknownVariables(EmailTemplate $template): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $template->subject.' '.$template->html_content, $matches);

        return array_values(array_diff(array_unique($matches[1]), self::VARIABLES));
    }

    public function sanitize(string $html): string
    {
        $html = preg_replace('/<\s*(script|style|iframe|object|embed|form)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1="#"', $html);

        return strip_tags($html, '<a><abbr><b><br><div><em><h1><h2><h3><hr><i><img><li><ol><p><span><strong><table><tbody><td><tfoot><th><thead><tr><u><ul>');
    }

    private function replace(?string $content, array $values, bool $escapeHtml = true): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function (array $matches) use ($values, $escapeHtml) {
            $value = $values[$matches[1]] ?? '';

            return $escapeHtml
                ? htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : str_replace(["\r", "\n"], ' ', $value);
        }, (string) $content);
    }

    private function missing(EmailTemplate $template, array $data): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $template->subject.' '.$template->html_content, $matches);

        return array_values(array_unique(array_filter($matches[1], fn (string $name) => ! array_key_exists($name, $data))));
    }

    private function stringify(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => $this->stringify($item), $value));
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return is_scalar($value) ? (string) $value : '';
    }
}
