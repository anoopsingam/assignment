<?php

namespace App\Jobs;

use App\Mail\UploadQueueMail;
use App\Models\ScanTracker;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use function PHPUnit\Framework\matches;

class ProcessUploadQueue implements ShouldQueue
{
    use Queueable;


    public  $timeout = 0;

    /**
     * Create a new job instance.
     * @throws \Exception if the tracker id is invalid
     */
    public function __construct(
        private  $trackerId,
    )
    {
        // Retrieve the ScanTracker instance
        $this->trackerId = ScanTracker::find($this->trackerId);
        if (!$this->trackerId) {
            throw new \Exception('Invalid tracker id to process the queue');
        }
    }

    /**
     * Execute the job.
     */
  public function handle(): void
{
    try {
        // Initialize a new Guzzle HTTP client
        $client = new Client();
        $token = $this->trackerId->auth_token;

        // Send the initial email
        Mail::to($this->trackerId->respondent_email)->send(new UploadQueueMail($this->trackerId));

        // Start the scan
        $scanResponse = $client->post(config('services.debricked.url') . '/1.0/open/finishes/dependencies/files/uploads', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ],
            'form_params' => [
                'ciUploadId' => $this->trackerId->ciUploadId
            ]
        ]);

        if (!in_array($scanResponse->getStatusCode(), [202, 204])) {
            throw new \Exception('Failed to start the scan');
        }

        $this->trackerId->update([
            'status' => 'pending',
            'progress' => 0
        ]);

        do {
            // Make the GET request to fetch the scan status
            $response = $client->get(config('services.debricked.url') . '/1.0/open/ci/upload/status', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json'
                ],
                'query' => [
                    'ciUploadId' => $this->trackerId->ciUploadId,
                    'extendedOutput' => false
                ]
            ]);

            // Decode the response
            $responseBody = json_decode($response->getBody()->getContents(), true);
            $progress = $responseBody['progress'];

            // Log the progress
            echo "Progress: $progress\n";

            // Determine the status based on the response code
            $status = match ($response->getStatusCode()) {
                200 => "completed",
                202, 201 => "pending",
                default => "failed"
            };

            // Update the tracker with the progress and status
            $this->trackerId->update([
                'progress' => $progress,
                'status' => $status
            ]);

            if ($status == 'failed') {
                throw new \Exception('Failed to scan the file');
            }

            // If progress is 100, update additional fields and send the final email
            if ($progress == 100) {
                $this->trackerId->update([
                    'no_of_threats_found' => $responseBody['vulnerabilitiesFound'],
                    'details_url' => $responseBody['detailsUrl'],
                    'status' => 'completed'
                ]);
                Mail::to($this->trackerId->respondent_email)->send(new UploadQueueMail($this->trackerId));
            }

        } while ($progress < 100);

    } catch (RequestException|GuzzleException|\Exception|\Throwable $e) {
        // Handle request exceptions
        $this->trackerId->update([
            'status' => 'failed',
            'progress' => 0
        ]);
        Mail::to($this->trackerId->respondent_email)->send(new UploadQueueMail($this->trackerId));
    } finally {
        $this->trackerId->update(['auth_token' => null]);
    }
}
}
