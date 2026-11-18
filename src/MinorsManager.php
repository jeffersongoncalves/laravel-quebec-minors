<?php

namespace JeffersonGoncalves\QuebecMinors;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JeffersonGoncalves\QuebecMinors\Enums\AssuranceMethod;
use JeffersonGoncalves\QuebecMinors\Models\AgeAssurance;
use JeffersonGoncalves\QuebecMinors\Models\GuardianLink;

class MinorsManager
{
    /**
     * Record the result of an age check. Pass the age, never the document.
     */
    public function assure(Model $subject, int $age, AssuranceMethod $method, ?string $provider = null): AgeAssurance
    {
        if ($age < 0 || $age > 130) {
            throw new InvalidArgumentException("Invalid age [{$age}].");
        }

        $validFor = config('quebec-minors.valid_for.'.$method->value);

        return AgeAssurance::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'age' => $age,
            'method' => $method,
            'provider' => $provider,
            'assured_at' => now(),
            'expires_at' => $validFor === null ? null : now()->add(CarbonInterval::make((string) $validFor)),
        ]);
    }

    /**
     * Current age, or null when unknown. Reliable results outweigh self-declaration; among the
     * results that count, the youngest wins (the most protective reading).
     */
    public function age(?Model $subject): ?int
    {
        return $this->decide($subject)['age'];
    }

    public function isVerifiedAdult(?Model $subject): bool
    {
        $decision = $this->decide($subject);

        return $decision['reliable'] && $decision['age'] !== null && $decision['age'] >= 18;
    }

    /** A known minor, or nobody knows and the service is likely used by children. */
    public function treatAsMinor(?Model $subject): bool
    {
        $age = $this->age($subject);

        return $age === null ? (bool) config('quebec-minors.likely_access', true) : $age < 18;
    }

    /** Under this age, consent comes from the person having parental authority or the tutor (ss. 4.1 and 14): 14. */
    public function consentAge(): int
    {
        return (int) config('quebec-minors.consent_age', 14);
    }

    /** Under the consent age (unknown counts as under when likely_access). */
    public function belowConsentAge(?Model $subject): bool
    {
        $age = $this->age($subject);

        return $age === null ? (bool) config('quebec-minors.likely_access', true) : $age < $this->consentAge();
    }

    /**
     * Personal information about a minor under 14 may not be collected from them, nor used on their
     * consent alone, without the consent of the person having parental authority or the tutor, unless
     * collecting it is clearly for the minor's benefit (ss. 4.1 and 14). Here: a verified guardian link.
     */
    public function needsParentalConsent(?Model $subject): bool
    {
        return $subject !== null && $this->belowConsentAge($subject) && ! $this->hasGuardian($subject);
    }

    public function canConsent(?Model $subject): bool
    {
        return $subject !== null && ! $this->needsParentalConsent($subject);
    }

    /**
     * Profiling technology is off by default (s. 8.1) and the highest confidentiality settings apply
     * by default (s. 9.1): keep it for verified adults.
     */
    public function allowsProfiling(?Model $subject): bool
    {
        return $this->isVerifiedAdult($subject);
    }

    public function hasGuardian(Model $minor): bool
    {
        return $this->links($minor)->scopes('active')->exists();
    }

    /**
     * Link a minor to the person having parental authority or the tutor. Verify the link before
     * relying on it.
     */
    public function link(Model $minor, Model $guardian, ?string $relationship = null, bool $verified = false): GuardianLink
    {
        return GuardianLink::create([
            'minor_type' => $minor->getMorphClass(),
            'minor_id' => $minor->getKey(),
            'guardian_type' => $guardian->getMorphClass(),
            'guardian_id' => $guardian->getKey(),
            'relationship' => $relationship,
            'verified_at' => $verified ? now() : null,
        ]);
    }

    /**
     * @return Collection<int, Model>
     */
    public function guardians(Model $minor): Collection
    {
        return $this->links($minor)->scopes('active')->with('guardian')->get()
            ->map(fn (GuardianLink $link): ?Model => $link->guardian)
            ->filter()
            ->values();
    }

    /**
     * Users with a valid age result below the default consent age and no verified guardian.
     *
     * @return Collection<int, Model>
     */
    public function missingParentalConsent(): Collection
    {
        // ponytail: loads every subject with a valid result; paginate by subject if this grows large
        return AgeAssurance::query()->scopes('valid')->with('subject')->get()
            ->map(fn (AgeAssurance $assurance): ?Model => $assurance->subject)
            ->filter()
            ->unique(fn (Model $subject): string => $subject->getMorphClass().'#'.$subject->getKey())
            ->filter(fn (Model $subject): bool => $this->needsParentalConsent($subject))
            ->values();
    }

    /**
     * @return array{age: int|null, reliable: bool}
     */
    protected function decide(?Model $subject): array
    {
        if ($subject === null) {
            return ['age' => null, 'reliable' => false];
        }

        $valid = AgeAssurance::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->scopes('valid')
            ->get();

        $reliable = $valid->filter(fn (AgeAssurance $a): bool => $a->method->isReliable());
        $counted = $reliable->isNotEmpty() ? $reliable : $valid;
        $age = $counted->map(fn (AgeAssurance $a): int => $a->currentAge())->min();

        return ['age' => $age === null ? null : (int) $age, 'reliable' => $reliable->isNotEmpty()];
    }

    /**
     * @return Builder<GuardianLink>
     */
    protected function links(Model $minor): Builder
    {
        return GuardianLink::query()
            ->where('minor_type', $minor->getMorphClass())
            ->where('minor_id', $minor->getKey());
    }
}
