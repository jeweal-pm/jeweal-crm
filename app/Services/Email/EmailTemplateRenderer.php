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
        return $this->renderContent($template->subject, $template->html_content, $template->plain_text_content, $data);
    }

    public function renderCustom(string $subject, string $htmlContent, ?string $plainTextContent, array $data): array
    {
        return $this->renderContent($subject, $htmlContent, $plainTextContent, $data);
    }

    public function renderContent(string $subject, string $htmlContent, ?string $plainTextContent, array $data): array
    {
        $values = [];
        foreach (self::VARIABLES as $variable) {
            $values[$variable] = $this->stringify($data[$variable] ?? '');
        }

        $renderedSubject = $this->replace($subject, $values, false);
        $html = $this->replace($htmlContent, $values);
        $plain = $this->replace($plainTextContent ?: strip_tags($htmlContent), $values, false);

        return [
            'subject' => $renderedSubject,
            'html_content' => $this->formatHtml($this->sanitize($html)),
            'plain_text_content' => trim(strip_tags($plain)),
            'missing_variables' => $this->missingContent($subject, $htmlContent, $data),
        ];
    }

    public function unknownVariables(EmailTemplate $template): array
    {
        return $this->unknownVariablesInContent($template->subject, $template->html_content);
    }

    public function unknownVariablesInContent(string $subject, string $htmlContent): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $subject.' '.$htmlContent, $matches);

        return array_values(array_diff(array_unique($matches[1]), self::VARIABLES));
    }

    public function sanitize(string $html): string
    {
        $html = preg_replace('/<\s*(script|iframe|object|embed|form)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1="#"', $html);

        $html = preg_replace_callback('/\sstyle\s*=\s*(["\'])(.*?)\1/is', function (array $matches) {
            return ' style="'.$this->sanitizeCss($matches[2]).'"';
        }, $html);
        $html = preg_replace('/\sstyle\s*=\s*(?!["\'])[^\s>]+/i', '', $html);
        $html = preg_replace_callback('/<\s*style\b[^>]*>(.*?)<\s*\/\s*style\s*>/is', function (array $matches) {
            return '<style>'.$this->sanitizeCss($matches[1]).'</style>';
        }, $html);

        return strip_tags($html, '<a><abbr><b><br><div><em><h1><h2><h3><hr><i><img><li><ol><p><span><strong><style><table><tbody><td><tfoot><th><thead><tr><u><ul>');
    }

    private function formatHtml(string $html): string
    {
        $visibleHtml = preg_replace('/<\s*style\b[^>]*>.*?<\s*\/\s*style\s*>/is', '', $html);
        if (trim(strip_tags($visibleHtml)) === '' || preg_match('/<\s*(a|abbr|b|br|div|em|h[1-3]|hr|i|img|li|ol|p|span|strong|table|u|ul)\b/i', $visibleHtml)) {
            return $html;
        }

        $paragraphs = preg_split('/(?:\r\n|\r|\n){2,}/', trim($visibleHtml)) ?: [];
        preg_match_all('/<\s*style\b[^>]*>.*?<\s*\/\s*style\s*>/is', $html, $styleMatches);
        $styleBlocks = implode('', $styleMatches[0] ?? []);

        return $styleBlocks.implode('', array_map(function (string $paragraph): string {
            return '<p style="margin:0 0 16px;line-height:1.6;">'.nl2br($paragraph).'</p>';
        }, $paragraphs));
    }

    private function sanitizeCss(string $css): string
    {
        $css = preg_replace('/@import\b[^;]+;?/i', '', $css);
        $css = preg_replace('/(?:expression|javascript|vbscript)\s*:[^;)}]+/i', '', $css);
        $css = preg_replace('/(?:behavior|-moz-binding)\s*:[^;]+;?/i', '', $css);
        $css = preg_replace('/url\s*\(\s*["\']?\s*(?:javascript|vbscript):[^)]*\)/i', '', $css);

        return trim($css);
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

    private function missingContent(string $subject, string $htmlContent, array $data): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $subject.' '.$htmlContent, $matches);

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
