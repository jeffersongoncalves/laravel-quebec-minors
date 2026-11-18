<?php

namespace JeffersonGoncalves\QuebecMinors\Enums;

enum AssuranceMethod: string
{
    /** Never a reliable age check. */
    case SelfDeclaration = 'self_declaration';

    /** Age signal from the operating system. */
    case OsSignal = 'os_signal';

    /** Age signal from the app store. */
    case StoreSignal = 'store_signal';

    case Document = 'document';

    case FacialEstimation = 'facial_estimation';

    case VerifiableCredential = 'verifiable_credential';

    /** Age stated by a linked, verified guardian. */
    case Guardian = 'guardian';

    public function isReliable(): bool
    {
        return in_array($this->value, (array) config('quebec-minors.reliable_methods', []), true);
    }
}
