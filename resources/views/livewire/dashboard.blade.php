<?php

use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\Attributes\Validate;
use App\Models\ListeningParty;
use App\Models\Episode;
use App\Jobs\ProcessPodcastUrl;

new class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required')]
    public $startTime;

    #[Validate('required|url')]
    public string $mediaUrl = '';

    public function deleteParty(int $id): void
    {
        ListeningParty::where('id', $id)->delete();
    }

    public function createListeningParty()
    {
        $this->validate();

        $episode = Episode::create([
            'media_url' => $this->mediaUrl,
        ]);

        $listeningParty = ListeningParty::create([
            'episode_id' => $episode->id,
            'name' => $this->name,
            'start_time' => $this->startTime,
        ]);

        ProcessPodcastUrl::dispatch($this->mediaUrl, $listeningParty, $episode);

        return redirect()->route('parties.show', $listeningParty);
    }

    public function with()
    {
        return [
            'listeningParties' => ListeningParty::where('is_active', DB::raw('true'))->whereNotNull('end_time')->where('end_time', '>', now())->orderBy('start_time', 'asc')->with('episode.podcast')->get(),
        ];
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div class="flex items-center justify-center min-h-screen bg-emerald-50">

                <div class="flex items-center justify-center space-x-8">
                    <div class="relative flex items-center justify-center w-16 h-16">
                        <span class="absolute inline-flex rounded-full opacity-50 size-14 bg-emerald-400 animate-ping"></span>
                        <img src="/logo.png" class="relative size-12">
                    </div>

            </div>
        </div>
        HTML;
    }
}; ?>

<div class="flex flex-col min-h-screen pt-8 bg-emerald-50">
    {{-- Top Half: Create New Listening Party Form --}}
    <div class="flex items-center justify-center p-4">
        <div class="w-full max-w-lg">
            <x-card shadow="lg" rounded="lg">
                <h2 class="font-serif text-xl font-bold text-center">Let's listen together.</h2>
                <form wire:submit='createListeningParty' class="mt-6 space-y-6">
                    <x-input wire:model='name' placeholder="Listening Party Name" />
                    <x-input wire:model='mediaUrl' placeholder="Podcast RSS Feed URL"
                        description="Entering the RSS Feed URL will grab the latest episode" />
                    <div x-data="{
                        raw: '',
                        formatted() {
                            if (!this.raw) return '';
                            const d = new Date(this.raw);
                            if (isNaN(d)) return '';
                            return new Intl.DateTimeFormat('en', {
                                weekday: 'short', year: 'numeric', month: 'short',
                                day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true
                            }).format(d);
                        },
                        syncToWire() {
                            if (!this.raw) return;
                            const d = new Date(this.raw);
                            if (!isNaN(d)) $wire.set('startTime', d.toISOString());
                        }
                    }">
                        <label class="block mb-1 text-xs font-medium text-slate-500">Start Time</label>
                        <input
                            type="datetime-local"
                            x-model="raw"
                            @change="syncToWire()"
                            min="{{ now()->format('Y-m-d\TH:i') }}"
                            class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 cursor-text"
                        >
                        <p x-show="raw" class="mt-1 text-xs font-medium text-emerald-600" x-text="'→ ' + formatted()"></p>
                        <p x-show="!raw" class="mt-1 text-xs text-slate-400">Click any field then scroll or type. Preview shows exact time in AM/PM.</p>
                    </div>
                    <x-button type="submit" class="w-full">Create Listening Party</x-button>
                </form>
            </x-card>
        </div>
    </div>
    {{-- Bottom Half: Existing Listening Parties --}}
    <div class="my-20">
        <div class="max-w-lg mx-auto">
            <h3 class="mb-4 font-serif text-[0.9rem] font-bold">Upcoming Listening Parties</h3>
            <div class="bg-white rounded-lg shadow-lg">
                @if ($listeningParties->isEmpty())
                    <div class="flex flex-col items-center justify-center gap-2 p-6 font-serif text-sm text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8 text-slate-300"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0 0 12.016 15a4.486 4.486 0 0 0-3.198 1.318M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" /></svg>
                        No listening parties started yet.
                    </div>
                @else
                    @foreach ($listeningParties as $listeningParty)
                        <div wire:key="{{ $listeningParty->id }}"
                            class="flex items-center justify-between p-4 border-b border-gray-200 hover:bg-gray-50 transition-all duration-150 ease-in-out">
                            <a href="{{ route('parties.show', $listeningParty) }}" class="flex items-center space-x-4 flex-1 min-w-0">
                                <div class="flex-shrink-0">
                                    <x-avatar src="{{ $listeningParty->episode->podcast->artwork_url }}"
                                        size="xl" rounded="sm" alt="Podcast Artwork" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[0.9rem] font-semibold truncate text-slate-900">
                                        {{ $listeningParty->name }}</p>
                                    <div class="mt-0.8">
                                        <p class="max-w-xs text-sm truncate text-slate-600">
                                            {{ $listeningParty->episode->title }}</p>
                                        <p class="text-[0.7rem] tracking-tighter uppercase text-slate-400">
                                            {{ $listeningParty->podcast->title }}</p>
                                    </div>
                                    <div class="mt-1 text-xs text-slate-600" x-data="{
                                        startTime: {{ $listeningParty->start_time->timestamp }},
                                        countdownText: '',
                                        isLive: {{ $listeningParty->start_time->isPast() && $listeningParty->is_active ? 'true' : 'false' }},
                                        updateCountdown() {
                                            const now = Math.floor(Date.now() / 1000);
                                            const timeUntilStart = this.startTime - now;
                                            if (timeUntilStart <= 0) {
                                                this.countdownText = 'Live';
                                                this.isLive = true;
                                            } else {
                                                const days = Math.floor(timeUntilStart / 86400);
                                                const hours = Math.floor((timeUntilStart % 86400) / 3600);
                                                const minutes = Math.floor((timeUntilStart % 3600) / 60);
                                                const seconds = timeUntilStart % 60;
                                                this.countdownText = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                                            }
                                        }
                                    }" x-init="updateCountdown(); setInterval(() => updateCountdown(), 1000);">
                                        <div x-show="isLive">
                                            <x-badge flat rose label="Live">
                                                <x-slot name="prepend" class="relative flex items-center w-2 h-2">
                                                    <span class="absolute inline-flex w-full h-full rounded-full opacity-75 bg-rose-500 animate-ping"></span>
                                                    <span class="relative inline-flex w-2 h-2 rounded-full bg-rose-500"></span>
                                                </x-slot>
                                            </x-badge>
                                        </div>
                                        <div x-show="!isLive">
                                            Starts in: <span x-text="countdownText"></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            <div class="flex items-center gap-2 ml-3 flex-shrink-0">
                                <a href="{{ route('parties.show', $listeningParty) }}" class="px-3 py-1 text-xs font-medium text-slate-600 border border-slate-200 rounded-md hover:bg-slate-50 transition-colors">Join</a>
                                <button
                                    wire:click="deleteParty({{ $listeningParty->id }})"
                                    wire:confirm="Delete this listening party?"
                                    class="p-1.5 text-slate-300 hover:text-red-500 transition-colors rounded"
                                    title="Delete party">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4" width="16" height="16">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
