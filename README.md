# TogetherCast.io

A real-time podcast listening party app. Create a room, share the link, and listen to any podcast episode in perfect sync with friends — with live chat and emoji reactions.

## Tech Stack

- **Laravel 11** — backend framework
- **Livewire 3 + Volt** — reactive single-file components
- **Alpine.js** — client-side interactivity (audio player, countdown, reactions)
- **Laravel Reverb** — WebSocket broadcasting
- **Tailwind CSS v3 + WireUI 2** — UI components
- **Laravel Echo + pusher-js** — WebSocket client

## Getting Started

### Requirements

- PHP 8.2+
- Node.js 18+
- Composer

### Installation

```bash
git clone https://github.com/Giddy-K/listening-party.git
cd listening-party

composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Running Locally

The app requires four processes running simultaneously:

```bash
php artisan serve          # Laravel dev server
npm run dev                # Vite (frontend assets)
php artisan reverb:start   # WebSocket server (real-time features)
php artisan queue:listen   # Queue worker (RSS feed parsing)
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000) in your browser.

## How It Works

1. **Create a party** — paste a podcast RSS feed URL and set a start time
2. **Share the link** — guests see a countdown to the start time
3. **Listen together** — audio syncs automatically; chat and emoji reactions appear in real time

Audio sync works by computing `(now - start_time)` on the client, so all listeners are at the same position without a central coordinator.

## Key Commands

```bash
# Testing
php artisan test
php artisan test --filter=TestName

# Code style
./vendor/bin/pint

# Database reset
php artisan migrate:fresh --seed
```

## Security

If you discover a security vulnerability, please send an e-mail to [gideonkipamet@gmail.com](mailto:gideonkipamet@gmail.com). All security vulnerabilities will be promptly addressed.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
