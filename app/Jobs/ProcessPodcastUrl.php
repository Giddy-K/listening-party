<?php

namespace App\Jobs;

use App\Models\Podcast;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessPodcastUrl implements ShouldQueue
{
    use Queueable;

    public $rssUrl;
    public $listeningParty;
    public $episode;

    public function __construct($rssUrl, $listeningParty, $episode)
    {
        $this->rssUrl = $rssUrl;
        $this->listeningParty = $listeningParty;
        $this->episode = $episode;
    }

    public function handle(): void
    {
        try {
            $response = Http::timeout(15)->get($this->rssUrl);

            if (!$response->successful()) {
                throw new \Exception("RSS fetch failed: HTTP {$response->status()}");
            }

            $xml = simplexml_load_string($response->body());

            if (!$xml) {
                throw new \Exception('Failed to parse RSS XML');
            }

            $podcastTitle = (string) $xml->channel->title;
            $podcastArtworkUrl = (string) $xml->channel->image->url;
            $latestEpisode = $xml->channel->item[0];
            $episodeTitle = (string) $latestEpisode->title;
            $episodeMediaUrl = (string) $latestEpisode->enclosure['url'];

            $namespaces = $xml->getNamespaces(true);
            $itunesNamespace = $namespaces['itunes'] ?? null;
            $episodeLength = null;

            if ($itunesNamespace) {
                $episodeLength = (string) $latestEpisode->children($itunesNamespace)->duration;
            }

            if (empty($episodeLength)) {
                $fileSize = (int) $latestEpisode->enclosure['length'];
                $durationInSeconds = $fileSize > 0 ? (int) ceil($fileSize * 8 / 128000) : 3600;
                $episodeLength = (string) $durationInSeconds;
            }

            $interval = $this->parseDuration($episodeLength);

            $podcast = Podcast::updateOrCreate(
                ['rss_url' => $this->rssUrl],
                ['title' => $podcastTitle, 'artwork_url' => $podcastArtworkUrl]
            );

            $this->episode->podcast()->associate($podcast);
            $this->episode->update(['title' => $episodeTitle, 'media_url' => $episodeMediaUrl]);
            $this->listeningParty->update(['end_time' => $this->listeningParty->start_time->add($interval)]);

        } catch (\Throwable $e) {
            Log::error('ProcessPodcastUrl failed', ['url' => $this->rssUrl, 'error' => $e->getMessage()]);
            // Set a 1-hour fallback so the party isn't stuck on "creating..."
            $this->listeningParty->update(['end_time' => $this->listeningParty->start_time->addHour()]);
        }
    }

    private function parseDuration(string $episodeLength): CarbonInterval
    {
        try {
            if (str_contains($episodeLength, ':')) {
                $parts = explode(':', $episodeLength);
                return match(count($parts)) {
                    2 => CarbonInterval::createFromFormat('i:s', $episodeLength),
                    3 => CarbonInterval::createFromFormat('H:i:s', $episodeLength),
                    default => CarbonInterval::hour(),
                };
            }
            return CarbonInterval::seconds((int) $episodeLength);
        } catch (\Throwable) {
            return CarbonInterval::hour();
        }
    }
}
