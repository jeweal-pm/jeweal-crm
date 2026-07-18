<?php

namespace App\Console\Commands;

use App\Models\Enquiry;
use App\Models\GisEnquiry;
use App\Services\Spam\EnquirySpamScorer;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class ScoreExistingEnquirySpam extends Command
{
    protected $signature = 'enquiries:score-spam {--source=all : all, enquiry, or gis} {--dry-run : Report only} {--force : Re-score reviewed records}';

    protected $description = 'Score existing enquiry records and quarantine likely spam.';

    public function handle(EnquirySpamScorer $scorer): int
    {
        $source = $this->option('source');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (! in_array($source, ['all', 'enquiry', 'gis'], true)) {
            $this->error('Invalid source. Use all, enquiry, or gis.');
            return self::FAILURE;
        }

        $totals = ['scanned' => 0, 'suspected' => 0, 'clean' => 0];

        if (in_array($source, ['all', 'enquiry'], true)) {
            $this->scoreModel(Enquiry::class, $scorer, $dryRun, $force, $totals);
        }

        if (in_array($source, ['all', 'gis'], true)) {
            $this->scoreModel(GisEnquiry::class, $scorer, $dryRun, $force, $totals);
        }

        $this->info("Scanned: {$totals['scanned']}");
        $this->info("Suspected: {$totals['suspected']}");
        $this->info("Clean: {$totals['clean']}");

        if ($dryRun) {
            $this->comment('Dry run only. No records were updated.');
        }

        return self::SUCCESS;
    }

    private function scoreModel(string $modelClass, EnquirySpamScorer $scorer, bool $dryRun, bool $force, array &$totals): void
    {
        $modelClass::query()
            ->withTrashed()
            ->orderBy('id')
            ->chunkById(100, function ($records) use ($scorer, $dryRun, $force, &$totals) {
                $records->each(function (Model $record) use ($scorer, $dryRun, $force, &$totals) {
                    if (! $force && in_array($record->spam_status, [
                        EnquirySpamScorer::STATUS_CONFIRMED,
                        EnquirySpamScorer::STATUS_NOT_SPAM,
                    ], true)) {
                        return;
                    }

                    $result = $dryRun ? $scorer->score($record) : $scorer->apply($record, $force);

                    $totals['scanned']++;
                    $totals[$result['status'] === EnquirySpamScorer::STATUS_SUSPECTED ? 'suspected' : 'clean']++;

                    if ($result['status'] === EnquirySpamScorer::STATUS_SUSPECTED) {
                        $this->line(class_basename($record).' #'.$record->id.' score='.$result['score'].' reasons='.implode(',', $result['reasons']));
                    }
                });
            });
    }
}
