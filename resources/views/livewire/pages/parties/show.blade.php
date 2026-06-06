<?php

use Livewire\Volt\Component;
use App\Models\ListeningParty;
use Livewire\Attributes\Validate;
use App\Events\NewMessageEvent;
use App\Events\EmojiReactionEvent;
use App\Models\Message;
use Livewire\Attributes\On;

new class extends Component {
    public ListeningParty $listeningParty;

    public $userId;
    public $emojis = [];

    public $isFinished = false;

    #[Validate('required|string|max:255')]
    public $message = '';

    public function authenticateUser()
    {
        session()->put('auth_redirect', route('parties.show', $this->listeningParty));
        return redirect()->route('register');
    }

    public function sendEmoji($emoji)
    {
        $newEmoji = [
            'id' => uniqid(),
            'emoji' => $emoji,
            'x' => rand(100, 300),
            'y' => rand(100, 300),
        ];

        event(new EmojiReactionEvent($this->listeningParty->id, $newEmoji, $this->userId));
    }

    public function sendMessage()
    {
        $this->validate();

        $this->listeningParty->messages()->create([
            'user_id' => auth()->user()->id,
            'message' => $this->message,
        ]);

        event(new NewMessageEvent($this->listeningParty->id, $this->message));

        $this->message = '';
    }

    public function getListeners(): array
    {
        return [
            'echo-private:listening-party.{listeningParty.id},.new-message' => 'refresh',
        ];
    }

    public function finish(): void
    {
        $this->listeningParty->update(['is_active' => false]);
        $this->isFinished = true;
    }

    public function mount(ListeningParty $listeningParty)
    {
        if ($this->listeningParty->end_time && $this->listeningParty->end_time->isPast()) {
            $this->isFinished = true;
        }

        if (!auth()->check()) {
            if (!Session::has('user_id')) {
                $this->userId = uniqid('user_', true);
                Session::put('user_id', $this->userId);
            } else {
                $this->userId = Session::get('user_id');
            }
        } else {
            $this->userId = auth()->id();
        }

        $this->listeningParty->load('episode.podcast', 'messages.user');
    }

    public function with()
    {
        return [
            'messages' => $this->listeningParty->messages()->with('user')->orderBy('created_at', 'asc')->get(),
        ];
    }
}; ?>

<div x-data="{
    audio: null,
    syncInterval: null,
    isLoading: true,
    isLive: false,
    isPlaying: false,
    playBlocked: false,
    countdownText: '',
    isReady: false,
    audioMetadataLoaded: false,
    currentTime: 0,
    audioDuration: 0,
    startTimestamp: {{ $listeningParty->start_time->timestamp }},
    endTimestamp: {{ $listeningParty->end_time ? $listeningParty->end_time->timestamp : 'null' }},
    copyNotification: false,
    emojis: [],
    userId: {{ json_encode($userId) }},
    addEmoji(emoji, event) {
        this.showEmoji({ id: Date.now(), emoji, x: event.clientX, y: event.clientY });
        $wire.sendEmoji(emoji);
    },

    showEmoji(e) {
        this.emojis.push(e);
        setTimeout(() => { this.emojis = this.emojis.filter(x => x.id !== e.id); }, 3000);
    },


    init() {
        this.startCountdown();
        if (this.$refs.audioPlayer && !this.isFinished) {
            this.initializeAudioPlayer();
        }
        if (window.Echo) {
            window.Echo.private(`listening-party.{{ $listeningParty->id }}`)
                .listen('.emoji-reaction', (data) => {
                    if (String(data.userId) !== String(this.userId)) {
                        this.showEmoji({ id: data.emoji.id, emoji: data.emoji.emoji, x: data.emoji.x, y: data.emoji.y });
                    }
                });
        }
    },

    initializeAudioPlayer() {
        this.audio = this.$refs.audioPlayer;
        this.audio.addEventListener('loadedmetadata', () => {
            this.isLoading = false;
            this.audioMetadataLoaded = true;
            this.audioDuration = this.audio.duration;
            this.checkLiveStatus();
        });

        this.audio.addEventListener('timeupdate', () => {
            this.currentTime = this.audio.currentTime;
            if (this.endTimestamp && this.currentTime >= (this.endTimestamp - this.startTimestamp)) {
                this.finishListeningParty();
            }
        });

        this.audio.addEventListener('play', () => {
            this.isPlaying = true;
            this.isReady = true;
        });

        this.audio.addEventListener('pause', () => {
            this.isPlaying = false;
        });

        this.audio.addEventListener('ended', () => {
            this.isPlaying = false;
            this.finishListeningParty();
        });
    },

    finishListeningParty() {
        clearInterval(this.syncInterval);
        this.syncInterval = null;
        $wire.finish();
        this.isPlaying = false;
        if (this.audio) {
            this.audio.pause();
        }
    },

    startCountdown() {
        this.checkLiveStatus();
        setInterval(() => this.checkLiveStatus(), 1000);
    },

    checkLiveStatus() {
        if (!this.audio && this.$refs.audioPlayer) {
            this.initializeAudioPlayer();
        }
        const now = Math.floor(Date.now() / 1000);
        const timeUntilStart = this.startTimestamp - now;

        if (timeUntilStart <= 0) {
            this.isLive = true;
            this.countdownText = 'Live';
            if (this.audio && !this.isPlaying && !this.isFinished && !this.playBlocked) {
                this.playAudio();
            }
        } else {
            const days = Math.floor(timeUntilStart / 86400);
            const hours = Math.floor((timeUntilStart % 86400) / 3600);
            const minutes = Math.floor((timeUntilStart % 3600) / 60);
            const seconds = timeUntilStart % 60;
            this.countdownText = `${days}d ${hours}h ${minutes}m ${seconds}s`;
        }
    },

    playAudio() {
        const audio = this.$refs.audioPlayer || this.audio;
        if (!audio) return;
        this.audio = audio;
        const now = Date.now() / 1000;
        const elapsedTime = Math.max(0, now - this.startTimestamp);
        audio.currentTime = elapsedTime;
        audio.play().catch(error => {
            console.error('Playback failed:', error);
            this.isPlaying = false;
            this.isReady = false;
            this.playBlocked = true;
        });
        this.startSync();
    },

    startSync() {
        if (this.syncInterval) return;
        this.syncInterval = setInterval(() => {
            if (!this.audio || !this.isPlaying || this.isFinished) return;
            const expected = (Date.now() / 1000) - this.startTimestamp;
            const drift = this.audio.currentTime - expected;
            if (Math.abs(drift) > 2) {
                this.audio.currentTime = expected;
            }
        }, 5000);
    },

    joinAndBeReady() {
        this.isReady = true;
        this.playBlocked = false;
        if (!this.audio && this.$refs.audioPlayer) {
            this.initializeAudioPlayer();
        }
        if (this.isLive && this.audio && !this.isFinished) {
            this.playAudio();
        }
    },

    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    },

    copyToClipboard() {
        navigator.clipboard.writeText(window.location.href);
        this.copyNotification = true;
        setTimeout(() => {
            this.copyNotification = false;
        }, 3000);
    },

}" x-init="init()">
    @if ($listeningParty->end_time === null)
        <div class="flex items-center justify-center min-h-screen bg-emerald-50" wire:poll.5s>
            <div class="w-full max-w-2xl p-8 mx-8 bg-white rounded-lg shadow-lg">
                <div class="flex items-center justify-center space-x-8">
                    <div class="relative flex items-center justify-center w-16 h-16">
                        <span class="absolute inline-flex rounded-full opacity-50 size-14 bg-emerald-400 animate-ping"></span>
                        <img src="/logo.png" class="relative size-12">
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-serif text-lg font-semibold text-slate-900">Creating your listening party</p>
                        <p class="mt-1 text-sm text-slate-600">
                            The TogetherCast.io room <span class="font-bold"> {{ $listeningParty->name }}</span> is being put
                            together...
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @elseif($isFinished)
        <div class="flex items-center justify-center min-h-screen bg-emerald-50">
            <div class="w-full max-w-2xl p-8 mx-8 text-center bg-white rounded-lg shadow-lg">
                <div class="flex justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 text-slate-300"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <h2 class="mb-4 font-serif text-2xl font-bold text-slate-900">This listening party has finished.</h2>
                <p class="mt-2 text-slate-600">The TogetherCast.io room <span
                        class="font-bold">{{ $listeningParty->name }}</span> is no longer live.
                </p>
            </div>
        </div>
    @else
        <audio wire:ignore x-ref="audioPlayer" :src="'{{ $listeningParty->episode->media_url }}'" preload="auto"></audio>


        <div x-show="!isLive" class="flex items-center justify-center min-h-screen bg-emerald-50" x-cloak>
            <div class="relative w-full max-w-2xl p-6 bg-white rounded-lg shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5" width="14" height="14">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                        Leave party
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <x-avatar src="{{ $listeningParty->episode->podcast->artwork_url }}" size="xl"
                            rounded="sm" alt="Podcast Artwork" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[0.9rem] font-semibold truncate text-slate-900">
                            {{ $listeningParty->name }}</p>
                        <div class="mt-0.8">
                            <p class="max-w-xs text-sm truncate text-slate-600">
                                {{ $listeningParty->episode->title }}</p>
                            <p class="text-[0.7rem] tracking-tighter uppercase text-slate-400">
                                {{ $listeningParty->episode->podcast->title }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <p class="font-serif font-semibold tracking-tight text-slate-600">Starting in:</p>
                    <p class="font-mono text-3xl font-semibold tracking-wider text-emerald-700" x-text="countdownText">
                    </p>
                </div>

                <div class="mt-6">
                    <x-button x-show="!isReady" class="w-full" @click="joinAndBeReady()">Join and Be Ready</x-button>
                </div>

                <h2 x-show="isReady"
                    class="mt-8 font-serif text-lg tracking-tight text-center text-slate-900 font-bolder">
                    Ready to start the TogetherCast.io party! Stay tuned.</h2>

                <div class="mt-6 pt-5 border-t border-slate-100">
                    <p class="mb-2 text-xs font-semibold tracking-wide uppercase text-slate-400">Share this party</p>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg min-w-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 shrink-0 text-slate-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                            </svg>
                            <span class="text-xs text-slate-500 truncate" x-text="window.location.href"></span>
                        </div>
                        <button @click="copyToClipboard()"
                            class="shrink-0 flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg border transition-colors"
                            :class="copyNotification ? 'bg-emerald-50 border-emerald-200 text-emerald-600' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'">
                            <svg x-show="!copyNotification" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                            </svg>
                            <svg x-show="copyNotification" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            <span x-show="!copyNotification">Copy</span>
                            <span x-show="copyNotification" x-cloak>Copied!</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <div x-show="isLive" x-cloak class="flex items-center justify-center min-h-screen bg-emerald-50">
            <div class="w-full max-w-6xl p-6 space-y-6">
                <div class="flex space-x-6">
                    <!-- Left Column: Listening Party Info and Emoji Picker -->
                    <div class="w-1/2 space-y-6">
                        <!-- Listening Party Info -->
                        <div class="p-6 bg-white rounded-lg shadow-lg">
                            <div class="flex items-center justify-between mb-4">
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5" width="14" height="14">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                                    </svg>
                                    Leave party
                                </a>
                            </div>
                            <div class="flex items-center mb-6 space-x-4">
                                <div class="flex-shrink-0">
                                    <x-avatar src="{{ $listeningParty->episode->podcast->artwork_url }}" size="xl"
                                        rounded="sm" alt="Podcast Artwork" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-lg font-semibold truncate text-slate-900">
                                        {{ $listeningParty->name }}</p>
                                    <p class="text-sm truncate text-slate-600">{{ $listeningParty->episode->title }}
                                    </p>
                                    <p class="text-xs tracking-tighter uppercase text-slate-400">
                                        {{ $listeningParty->episode->podcast->title }}
                                    </p>
                                </div>
                            </div>

                            <div class="mb-6" x-show="audioMetadataLoaded">
                                <div class="flex items-center justify-between mb-2">
                                    <span x-text="formatTime(currentTime)" class="text-sm text-slate-600"></span>
                                    <span class="text-sm text-slate-600">
                                        @php
                                            $duration = $listeningParty->start_time->diffInSeconds(
                                                $listeningParty->end_time,
                                            );
                                            $minutes = floor($duration / 60);
                                            $seconds = $duration % 60;
                                        @endphp
                                        {{ sprintf('%02d:%02d', $minutes, $seconds) }}
                                    </span>
                                </div>
                                <div class="h-2 rounded-full bg-emerald-100">
                                    <div class="h-2 rounded-full bg-emerald-500"
                                        :style="audioDuration ? `width: ${(currentTime / audioDuration) * 100}%` : 'width: 0%'"></div>
                                </div>
                            </div>

                            <div x-show="!isPlaying" class="mt-6">
                                <x-button class="w-full" primary label="Join Listening Party" @click="joinAndBeReady()" />
                            </div>

                            <div class="mt-6 pt-5 border-t border-slate-100">
                                <p class="mb-2 text-xs font-semibold tracking-wide uppercase text-slate-400">Share this party</p>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg min-w-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 shrink-0 text-slate-400">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                        </svg>
                                        <span class="text-xs text-slate-500 truncate" x-text="window.location.href"></span>
                                    </div>
                                    <button @click="copyToClipboard()"
                                        class="shrink-0 flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg border transition-colors"
                                        :class="copyNotification ? 'bg-emerald-50 border-emerald-200 text-emerald-600' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'">
                                        <svg x-show="!copyNotification" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                                        </svg>
                                        <svg x-show="copyNotification" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4" x-cloak>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        <span x-show="!copyNotification">Copy</span>
                                        <span x-show="copyNotification" x-cloak>Copied!</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Emoji Picker -->
                        <div class="p-4 bg-white rounded-lg shadow-lg">
                            <div class="grid grid-cols-6 gap-2">
                                @foreach (['👍', '❤️', '😂', '😮', '😢', '😡'] as $emoji)
                                    <button @click="addEmoji('{{ $emoji }}', $event)"
                                        class="p-2 text-2xl transition-colors rounded-full hover:bg-emerald-100">
                                        {{ $emoji }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="fixed inset-0 pointer-events-none" aria-hidden="true">
                            <template x-for="emoji in emojis" :key="emoji.id">
                                <div class="absolute text-4xl animate-fall"
                                    :style="`left: ${emoji.x}px; top: ${emoji.y}px;`" x-text="emoji.emoji"></div>
                            </template>
                        </div>
                    </div>

                    <!-- Right Column: Chat Room -->
                    <div class="w-1/2">
                        <div class="bg-white rounded-lg shadow-lg h-[600px] flex flex-col">
                            <div class="flex flex-col justify-end flex-1 p-4 overflow-y-auto" id="message-container">
                                <div class="space-y-0.5">
                                    @foreach ($messages as $message)
                                        <div class="px-2 py-2 rounded hover:bg-slate-100"
                                            wire:key="{{ $message->id }}">
                                            <div class="flex items-center">
                                                <x-avatar xs
                                                    label="{{ strtoupper(substr($message->user->name, 0, 1)) }}" />
                                                <div class="flex items-center ml-2 space-x-2">
                                                    <span
                                                        class="text-xs font-bold text-slate-900">{{ $message->user->name }}:</span>
                                                    <p class="text-sm text-slate-700">{{ $message->message }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="p-4 border-t">
                                @auth
                                    <form class="flex space-x-2" wire:submit='sendMessage'>
                                        <x-input type="text" placeholder="Type your message..." wire:model='message'
                                            class="w-full" />
                                        <x-button primary label="Send" type="submit" />
                                    </form>
                                @else
                                    <x-button wire:click="authenticateUser" label="Login to Chat" class="w-full" />
                                @endauth
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    @endif
</div>
