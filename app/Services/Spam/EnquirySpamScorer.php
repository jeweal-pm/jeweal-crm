<?php

namespace App\Services\Spam;

use Illuminate\Database\Eloquent\Model;

class EnquirySpamScorer
{
    public const STATUS_CLEAN = 'clean';
    public const STATUS_SUSPECTED = 'suspected';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_NOT_SPAM = 'not_spam';

    public const SUSPECTED_THRESHOLD = 50;

    public static function statuses(): array
    {
        return [
            self::STATUS_CLEAN,
            self::STATUS_SUSPECTED,
            self::STATUS_CONFIRMED,
            self::STATUS_NOT_SPAM,
        ];
    }

    public function score(Model $enquiry): array
    {
        $name = $this->nameFor($enquiry);
        $email = (string) ($enquiry->email ?? '');
        $phone = (string) ($enquiry->phone ?? $enquiry->phone_number ?? '');
        $message = (string) ($enquiry->description ?? $enquiry->message ?? '');
        $company = (string) ($enquiry->company ?? '');

        $score = 0;
        $reasons = [];

        $this->addNameSignals($name, $score, $reasons);
        $this->addEmailSignals($email, $score, $reasons);
        $this->addPhoneSignals($phone, $score, $reasons);
        $this->addTextSignals($message, 'message', $score, $reasons);
        $this->addTextSignals($company, 'company', $score, $reasons);

        $score = max(0, min(100, $score));

        return [
            'status' => $score >= self::SUSPECTED_THRESHOLD ? self::STATUS_SUSPECTED : self::STATUS_CLEAN,
            'score' => $score,
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    public function apply(Model $enquiry, bool $force = false): array
    {
        if (! $force && in_array($enquiry->spam_status, [self::STATUS_CONFIRMED, self::STATUS_NOT_SPAM], true)) {
            return [
                'status' => $enquiry->spam_status,
                'score' => (int) $enquiry->spam_score,
                'reasons' => (array) $enquiry->spam_reasons,
            ];
        }

        $result = $this->score($enquiry);

        $enquiry->forceFill([
            'spam_status' => $result['status'],
            'spam_score' => $result['score'],
            'spam_reasons' => $result['reasons'] ?: null,
            'spam_checked_at' => now(),
            'spam_reviewed_by' => null,
            'spam_reviewed_at' => null,
        ])->save();

        return $result;
    }

    private function nameFor(Model $enquiry): string
    {
        if (isset($enquiry->name)) {
            return (string) $enquiry->name;
        }

        return trim((string) ($enquiry->first_name ?? '').' '.(string) ($enquiry->last_name ?? ''));
    }

    private function addNameSignals(string $name, int &$score, array &$reasons): void
    {
        $compact = preg_replace('/\s+/', '', $name);
        $lettersOnly = preg_replace('/[^A-Za-z]/', '', $compact);
        $length = strlen($lettersOnly);

        if ($name === '') {
            $score += 35;
            $reasons[] = 'missing_name';
            return;
        }

        if (preg_match('/https?:\/\//i', $name)) {
            $score += 45;
            $reasons[] = 'url_in_name';
        }

        if (preg_match('/[0-9_@#$%^*={}[\]<>]/', $name)) {
            $score += 20;
            $reasons[] = 'name_contains_digits_or_symbols';
        }

        if ($length >= 14 && preg_match('/[a-z]/', $lettersOnly) && preg_match('/[A-Z]/', $lettersOnly)) {
            $transitions = preg_match_all('/[a-z][A-Z]|[A-Z][a-z][A-Z]/', $lettersOnly);

            if ($transitions >= 3 && ! $this->looksLikeHumanCasing($name)) {
                $score += 35;
                $reasons[] = 'random_mixed_case_name';
            }
        }

        if ($length >= 12 && $this->looksLikeMixedCaseGibberish($lettersOnly) && ! $this->looksLikeHumanCasing($name)) {
            $score += 25;
            $reasons[] = 'unnatural_case_name';
        }

        if ($length >= 10 && $this->vowelRatio($lettersOnly) < 0.18) {
            $score += 20;
            $reasons[] = 'low_vowel_name';
        }

        if ($length >= 12 && $this->uniqueRatio($lettersOnly) > 0.78 && ! $this->looksLikeHumanCasing($name)) {
            $score += 18;
            $reasons[] = 'high_entropy_name';
        }

        if (preg_match('/([bcdfghjklmnpqrstvwxyz]{5,})/i', $lettersOnly)) {
            $score += 15;
            $reasons[] = 'hard_to_read_name';
        }
    }

    private function addEmailSignals(string $email, int &$score, array &$reasons): void
    {
        if ($email === '') {
            $score += 20;
            $reasons[] = 'missing_email';
            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $score += 25;
            $reasons[] = 'invalid_email';
            return;
        }

        [$local] = explode('@', $email, 2);
        $localLetters = preg_replace('/[^A-Za-z]/', '', $local);
        $segments = array_filter(explode('.', $local), fn (string $segment) => $segment !== '');

        if (strlen($localLetters) >= 14 && $this->uniqueRatio($localLetters) > 0.8 && $this->vowelRatio($localLetters) < 0.25) {
            $score += 18;
            $reasons[] = 'random_email_local_part';
        }

        if (
            substr_count($local, '.') >= 4
            && count(array_filter($segments, fn (string $segment) => strlen($segment) <= 2)) >= 4
        ) {
            $score += 25;
            $reasons[] = 'dot_chopped_email_local_part';
        }
    }

    private function addPhoneSignals(string $phone, int &$score, array &$reasons): void
    {
        $digits = preg_replace('/\D/', '', $phone);

        if ($phone === '') {
            $score += 10;
            $reasons[] = 'missing_phone';
            return;
        }

        if (strlen($digits) < 7) {
            $score += 15;
            $reasons[] = 'phone_too_short';
        }

        if ($digits !== '' && preg_match('/^(\d)\1{6,}$/', $digits)) {
            $score += 20;
            $reasons[] = 'repeated_phone_digits';
        }
    }

    private function addTextSignals(string $text, string $field, int &$score, array &$reasons): void
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            if ($field === 'message') {
                $score += 10;
                $reasons[] = 'empty_message';
            }

            return;
        }

        $letters = preg_replace('/[^A-Za-z]/', '', $trimmed);

        if (strlen($letters) >= 12 && $this->looksLikeMixedCaseGibberish($letters)) {
            $score += 25;
            $reasons[] = 'mixed_case_'.$field;
        }

        if (strlen($letters) >= 12 && $this->uniqueRatio($letters) > 0.76 && $this->vowelRatio($letters) < 0.30) {
            $score += 18;
            $reasons[] = 'random_'.$field;
        }
    }

    private function looksLikeHumanCasing(string $value): bool
    {
        $words = preg_split('/\s+/', trim($value));

        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }

            if (! preg_match('/^[A-Z][a-z]+$/', $word) && ! preg_match('/^[A-Z][a-z]+[A-Z][a-z]+$/', $word)) {
                return false;
            }
        }

        return true;
    }

    private function looksLikeMixedCaseGibberish(string $value): bool
    {
        $letters = preg_replace('/[^A-Za-z]/', '', $value);

        if (strlen($letters) < 12 || ! preg_match('/[a-z]/', $letters) || ! preg_match('/[A-Z]/', $letters)) {
            return false;
        }

        $transitions = preg_match_all('/[a-z][A-Z]|[A-Z][a-z][A-Z]/', $letters);
        $upperClusters = preg_match_all('/[A-Z]{2,}/', $letters);
        $longLowerRuns = preg_match_all('/[a-z]{5,}/', $letters);

        return $transitions >= 3 && ($upperClusters >= 2 || $longLowerRuns === 0);
    }

    private function vowelRatio(string $value): float
    {
        $letters = preg_replace('/[^A-Za-z]/', '', $value);
        $length = strlen($letters);

        if ($length === 0) {
            return 0.0;
        }

        return preg_match_all('/[aeiou]/i', $letters) / $length;
    }

    private function uniqueRatio(string $value): float
    {
        $letters = str_split(strtolower(preg_replace('/[^A-Za-z]/', '', $value)));
        $count = count($letters);

        if ($count === 0) {
            return 0.0;
        }

        return count(array_unique($letters)) / $count;
    }
}
