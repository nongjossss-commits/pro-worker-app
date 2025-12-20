@php
    // Verify that $profile is available
    if(!isset($profile)) {
        throw new \Exception("Profile variable missing in view");
    }
    echo "Profile Name: " . $profile->name;
@endphp
