<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as CalendarEvent;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\ConferenceSolutionKey;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Carbon\Carbon;

class GoogleCalendarService
{
    protected GoogleClient $client;
    protected ?GoogleCalendar $service = null;

    public function __construct()
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.calendar_redirect_uri') ?: route('admin.settings.google-calendar.callback');

        if (!$clientId || !$clientSecret) {
            throw new RuntimeException('Google OAuth Client ID or Client Secret is not configured in .env file.');
        }

        $this->client = new GoogleClient();
        $this->client->setApplicationName('Chetan Imitation - Video Call Scheduling');
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setRedirectUri($redirectUri);
        $this->client->setScopes([GoogleCalendar::CALENDAR_EVENTS, 'email']);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        $token = Setting::getValue('google_calendar_oauth_token');
        if ($token) {
            $this->client->setAccessToken($token);

            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = $this->client->getRefreshToken() ?? ($token['refresh_token'] ?? null);
                if ($refreshToken) {
                    $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    if (isset($newToken['error'])) {
                        Log::error('Google Calendar token refresh failed: ' . ($newToken['error_description'] ?? $newToken['error']));
                    } else {
                        if (!isset($newToken['refresh_token'])) {
                            $newToken['refresh_token'] = $refreshToken;
                        }
                        Setting::setValue('google_calendar_oauth_token', $newToken);
                        $this->client->setAccessToken($newToken);
                        $this->service = new GoogleCalendar($this->client);
                    }
                }
            } else {
                $this->service = new GoogleCalendar($this->client);
            }
        }
    }

    public function getClient(): GoogleClient
    {
        return $this->client;
    }

    public function isConnected(): bool
    {
        return $this->service !== null && !$this->client->isAccessTokenExpired();
    }

    public function getConnectedEmail(): ?string
    {
        return Setting::getValue('google_calendar_connected_email');
    }

    public function createMeetingEvent(string $summary, string $description, Carbon $startDateTime, Carbon $endDateTime, ?string $attendeeEmail = null): array
    {
        if (!$this->service) {
            return [
                'event_id' => null,
                'meet_link' => null,
            ];
        }

        $tz = config('app.timezone', 'Asia/Kolkata');

        $event = new CalendarEvent();
        $event->setSummary($summary);
        $event->setDescription($description);

        $start = new EventDateTime();
        $start->setDateTime($startDateTime->toRfc3339String());
        $start->setTimeZone($tz);
        $event->setStart($start);

        $end = new EventDateTime();
        $end->setDateTime($endDateTime->toRfc3339String());
        $end->setTimeZone($tz);
        $event->setEnd($end);

        if ($attendeeEmail) {
            $attendee = new EventAttendee();
            $attendee->setEmail($attendeeEmail);
            $event->setAttendees([$attendee]);
        }

        $conferenceData = new ConferenceData();
        $createRequest = new CreateConferenceRequest();
        $createRequest->setRequestId('meet-' . uniqid());
        $solutionKey = new ConferenceSolutionKey();
        $solutionKey->setType('hangoutsMeet');
        $createRequest->setConferenceSolutionKey($solutionKey);
        $conferenceData->setCreateRequest($createRequest);
        $event->setConferenceData($conferenceData);

        try {
            $createdEvent = $this->service->events->insert('primary', $event, [
                'conferenceDataVersion' => 1,
                'sendUpdates' => $attendeeEmail ? 'all' : 'none',
            ]);

            $meetLink = $createdEvent->getHangoutLink();
            if (!$meetLink && $createdEvent->getConferenceData()) {
                $entryPoints = $createdEvent->getConferenceData()->getEntryPoints();
                if (!empty($entryPoints)) {
                    $meetLink = $entryPoints[0]->getUri();
                }
            }

            return [
                'event_id' => $createdEvent->getId(),
                'meet_link' => $meetLink,
            ];
        } catch (\Throwable $e) {
            Log::error('Google Calendar event creation failed: ' . $e->getMessage());
            return [
                'event_id' => null,
                'meet_link' => null,
            ];
        }
    }

    public function cancelMeetingEvent(?string $eventId): bool
    {
        if (!$this->service || !$eventId) {
            return false;
        }

        try {
            $this->service->events->delete('primary', $eventId, [
                'sendUpdates' => 'all',
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Google Calendar event deletion failed: ' . $e->getMessage());
            return false;
        }
    }
}
