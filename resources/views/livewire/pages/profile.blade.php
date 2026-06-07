<?php

use Livewire\Volt\Component;

new class extends Component {};
?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <livewire:profile.update-profile-information-form />
        <livewire:profile.update-password-form />
        <livewire:profile.delete-user-form />
    </div>
</div>
