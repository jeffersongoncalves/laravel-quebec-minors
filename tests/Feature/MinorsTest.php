<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\QuebecMinors\Enums\AssuranceMethod;
use JeffersonGoncalves\QuebecMinors\Facades\QuebecMinors;
use JeffersonGoncalves\QuebecMinors\Tests\Fixtures\Person;

beforeEach(function () {
    $this->person = Person::create(['name' => 'Emma']);
});

it('uses 14 as the consent age', function () {
    expect(QuebecMinors::consentAge())->toBe(14);
});

it('treats unknown users as minors in likely-access services', function () {
    expect(QuebecMinors::age($this->person))->toBeNull()
        ->and(QuebecMinors::treatAsMinor($this->person))->toBeTrue()
        ->and(QuebecMinors::needsParentalConsent($this->person))->toBeTrue();

    config()->set('quebec-minors.likely_access', false);

    expect(QuebecMinors::treatAsMinor($this->person))->toBeFalse()
        ->and(QuebecMinors::canConsent($this->person))->toBeTrue();
});

it('needs parental consent under 14 only', function () {
    QuebecMinors::assure($this->person, 13, AssuranceMethod::StoreSignal);

    expect(QuebecMinors::needsParentalConsent($this->person))->toBeTrue();

    QuebecMinors::assure($this->person, 14, AssuranceMethod::Document);

    expect(QuebecMinors::age($this->person))->toBe(13) // youngest reliable result wins
        ->and(QuebecMinors::needsParentalConsent($this->person))->toBeTrue();

    $other = Person::create(['name' => 'Léa']);
    QuebecMinors::assure($other, 14, AssuranceMethod::Document);

    expect(QuebecMinors::needsParentalConsent($other))->toBeFalse()
        ->and(QuebecMinors::treatAsMinor($other))->toBeTrue();
});

it('lets a verified guardian authorise consent', function () {
    $mother = Person::create(['name' => 'Anna']);
    QuebecMinors::assure($this->person, 12, AssuranceMethod::Document);

    $link = QuebecMinors::link($this->person, $mother, 'mother');

    expect(QuebecMinors::canConsent($this->person))->toBeFalse()
        ->and(QuebecMinors::missingParentalConsent()->pluck('id')->all())->toBe([$this->person->id]);

    $link->verify();

    expect(QuebecMinors::canConsent($this->person))->toBeTrue()
        ->and(QuebecMinors::guardians($this->person)->pluck('name')->all())->toBe(['Anna'])
        ->and(QuebecMinors::missingParentalConsent())->toBeEmpty();

    $link->revoke();

    expect(QuebecMinors::canConsent($this->person))->toBeFalse();
});

it('does not take self-declaration as verification and prefers reliable results', function () {
    QuebecMinors::assure($this->person, 30, AssuranceMethod::SelfDeclaration);

    expect(QuebecMinors::age($this->person))->toBe(30)
        ->and(QuebecMinors::isVerifiedAdult($this->person))->toBeFalse()
        ->and(QuebecMinors::allowsProfiling($this->person))->toBeFalse();

    QuebecMinors::assure($this->person, 15, AssuranceMethod::FacialEstimation);
    QuebecMinors::assure($this->person, 25, AssuranceMethod::Document);

    expect(QuebecMinors::age($this->person))->toBe(15); // youngest reliable result wins
});

it('ages results and ignores expired ones', function () {
    QuebecMinors::assure($this->person, 12, AssuranceMethod::Document);

    $this->travel(11)->months();
    expect(QuebecMinors::age($this->person))->toBe(12);

    $this->travel(2)->months();
    expect(QuebecMinors::age($this->person))->toBeNull(); // the 1-year result expired

    QuebecMinors::assure($this->person, 16, AssuranceMethod::VerifiableCredential);
    expect(QuebecMinors::canConsent($this->person))->toBeTrue();
});

it('rejects impossible ages', function () {
    QuebecMinors::assure($this->person, -1, AssuranceMethod::Document);
})->throws(InvalidArgumentException::class);

it('gates adult routes and hides ad tags from non-adults', function () {
    Route::get('/adult', fn () => 'ok')->middleware('quebec.adult');
    $view = '@quebecProfiling pixel @else none @endquebecProfiling';

    $this->actingAs($this->person)->get('/adult')->assertForbidden();
    expect(trim(Blade::render($view)))->toBe('none');

    QuebecMinors::assure($this->person, 40, AssuranceMethod::Document);

    $this->actingAs($this->person)->get('/adult')->assertOk();
    expect(trim(Blade::render($view)))->toBe('pixel');
});

it('reports users without parental authorisation', function () {
    QuebecMinors::assure($this->person, 10, AssuranceMethod::Guardian);

    $this->artisan('quebec:minors')->assertFailed();

    QuebecMinors::link($this->person, Person::create(['name' => 'Dad']), 'father', verified: true);

    $this->artisan('quebec:minors')->assertSuccessful();
});
