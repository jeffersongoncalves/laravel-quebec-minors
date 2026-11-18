<?php

namespace JeffersonGoncalves\QuebecMinors\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\QuebecMinors\MinorsManager;

class MinorsCommand extends Command
{
    public $signature = 'quebec:minors {--json : Print the report as JSON}';

    public $description = 'List users under 14 without a verified person having parental authority (Quebec Law 25, ss. 4.1 and 14)';

    public function handle(MinorsManager $children): int
    {
        $missing = $children->missingParentalConsent()
            ->map(fn (Model $subject): array => ['type' => $subject->getMorphClass(), 'id' => $subject->getKey(), 'age' => $children->age($subject)])
            ->all();

        if ($this->option('json')) {
            $this->line((string) json_encode($missing, JSON_PRETTY_PRINT));
        } else {
            $this->components->info(count($missing).' user(s) under the consent age ('.$children->consentAge().') without parental consent.');
            $this->table(['Type', 'ID', 'Age'], $missing);
        }

        return $missing === [] ? self::SUCCESS : self::FAILURE;
    }
}
