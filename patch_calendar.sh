#!/bin/bash
sed -i "s/->whereIn('status', \['registration_pending', 'registration_completed'\])//g" app/Http/Controllers/Production/RegistrationController.php
sed -i "s/->whereIn('status', \['renewal_pending', 'renewal_completed'\])//g" app/Http/Controllers/Production/RenewalController.php
