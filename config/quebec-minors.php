<?php

return [

    'tables' => [
        'assurances' => 'quebec_age_assurances',
        'guardians' => 'quebec_guardian_links',
    ],

    /*
     * Under 14, personal information may not be collected from the minor without the consent of the
     * person having parental authority or the tutor (s. 4.1), who also consents for them (s. 14).
     */
    'consent_age' => 14,

    /*
     * Methods that count as reliable age checks. Self-declaration never does.
     */
    'reliable_methods' => [
        'os_signal',
        'store_signal',
        'document',
        'facial_estimation',
        'verifiable_credential',
    ],

    /*
     * How long a result stays valid, per method (CarbonInterval string, null = forever).
     */
    'valid_for' => [
        'self_declaration' => '1 year',
        'os_signal' => '30 days',
        'store_signal' => '30 days',
        'document' => '1 year',
        'facial_estimation' => '1 year',
        'verifiable_credential' => '1 year',
        'guardian' => '1 year',
    ],

    /*
     * The service is likely to be used by minors: users with no age result are treated
     * as minors where it matters (they need parental consent, no profiling).
     */
    'likely_access' => true,

    /*
     * Where `quebec.adult` sends users without a reliable adult result. Null = 403.
     */
    'verify_route' => null,

];
