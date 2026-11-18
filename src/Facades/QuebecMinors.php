<?php

namespace JeffersonGoncalves\QuebecMinors\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\QuebecMinors\MinorsManager;

/**
 * @method static \JeffersonGoncalves\QuebecMinors\Models\AgeAssurance assure(\Illuminate\Database\Eloquent\Model $subject, int $age, \JeffersonGoncalves\QuebecMinors\Enums\AssuranceMethod $method, ?string $provider = null)
 * @method static int|null age(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool isVerifiedAdult(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool treatAsMinor(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static int consentAge()
 * @method static bool belowConsentAge(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool needsParentalConsent(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool canConsent(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool allowsProfiling(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool hasGuardian(\Illuminate\Database\Eloquent\Model $minor)
 * @method static \JeffersonGoncalves\QuebecMinors\Models\GuardianLink link(\Illuminate\Database\Eloquent\Model $minor, \Illuminate\Database\Eloquent\Model $guardian, ?string $relationship = null, bool $verified = false)
 * @method static \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model> guardians(\Illuminate\Database\Eloquent\Model $minor)
 * @method static \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model> missingParentalConsent()
 *
 * @see MinorsManager
 */
class QuebecMinors extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MinorsManager::class;
    }
}
