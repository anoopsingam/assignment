<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileUploadRequest;
use App\Http\Requests\QueueScanRequest;
use App\Http\Requests\ScanStatusRequest;
use App\Jobs\ProcessUploadQueue;
use App\Models\FileUploads;
use App\Models\ScanInfo;
use App\Models\ScanTracker;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


/**
 * Class DocumentController
 *
 * This controller handles various document-related operations such as fetching supported file formats,
 * uploading files, queuing scans, and fetching scan statuses.
 *
 * @package App\Http\Controllers\Api
 * @version 1.0
 */
class DocumentController extends Controller
{

    /**
     * DocumentController constructor.
     * @param  string $ClientUrl The base URL for the external API client.
     * Initializes the controller with the base URL for the external API client.
     */
    public function __construct(
        private string $ClientUrl = "",
    )
    {
        $this->ClientUrl = config('services.debricked.url');
    }

    /**
     * Fetches and returns a list of supported file formats from an external API.
     * The response is cached for 60 minutes to reduce the number of API calls.
     *
     * @return JsonResponse JSON response containing the status and data or an error message.
     */
    public function getSupportedFileFormats(): JsonResponse
    {
        // Initialize a new Guzzle HTTP client
        $client = new Client();
        // Define the cache key for storing the response
        $cacheKey = 'supported_file_formats';

        try {
            // Attempt to fetch the data from the cache or make an API request if not cached
            $response = Cache::remember($cacheKey, 3600, function () use (&$client) {
                // Make an HTTP GET request to the external API
                $response = $client->request('GET', $this->ClientUrl . '/1.0/open/files/supported-formats', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . request()->bearerToken()
                    ]
                ]);
                // Decode the JSON response into an associative array
                $response = json_decode($response->getBody()->getContents(), true);

                // Process the response to extract file formats and lock file regexes
                return array_values(array_unique(array_reduce($response ?? [], function ($fileFormats, $resp) {
                    if (!empty($resp['regex'])) {
                        // Ensure the regex pattern has delimiters
                        $regexPattern = '/' . str_replace('/', '\/', $resp['regex']) . '/';
                        $fileFormats[] = $regexPattern;
                    }
                    if (!empty($resp['lockFileRegexes'])) {
                        // Ensure each lock file regex pattern has delimiters
                        $lockFileRegexes = array_map(function ($regex) {
                            return '/' . str_replace('/', '\/', $regex) . '/';
                        }, $resp['lockFileRegexes']);
                        $fileFormats = array_merge($fileFormats, $lockFileRegexes);
                    }
                    return $fileFormats;

                }, [])));
            });

            // Return a successful JSON response with the data
            return response()->json(['status' => true, 'data' => $response, 'message' => 'Supported file formats fetched successfully']);
        } catch (RequestException $e) {
            // Handle any exceptions that occur during the API request
            return response()->json(['status' => false, 'message' => 'Failed to fetch supported file formats', 'data' => []], 500);
        }
    }


    /**
     * Handles the file upload process.
     *
     * @param FileUploadRequest $request The file upload request containing validated data.
     * @return JsonResponse JSON response containing the status and data or an error message.
     * @throws GuzzleException
     */
    public function UploadFile(FileUploadRequest $request): JsonResponse
    {
        // Validate the request data
        $validated = $request->validated();
        // Initialize a new Guzzle HTTP client
        $client = new Client();
        // Create a new FileUploads model instance
        $upload = new FileUploads();
        // Retrieve the supported file formats from the cache
        $fileTypes = Cache::get('supported_file_formats');

        try {
            // Begin a database transaction
            DB::beginTransaction();

            // Check if the file name and type are supported in $fileTypes
            $isSupported = array_filter($fileTypes, function ($regex) use ($validated) {
                return preg_match($regex, $validated['fileData']->getClientOriginalName());
            });

            // If the file type is not supported, return an error response
            if (empty($isSupported)) {
                return response()->json(['status' => false, 'message' => 'File type not supported'], 400);
            }

            // Determine the file path
            $path = $validated['fileRelativePath'] ?? $validated['fileData']->getClientOriginalName();

            // Prepare the upload array for the multipart request
            $uploadArray = [
                ['name' => 'commitName', 'contents' => $validated['commitName'] ?? ''],
                ['name' => 'repositoryUrl', 'contents' => $validated['repositoryUrl']],
                ['name' => 'fileData', 'contents' => fopen($validated['fileData']->getRealPath(), 'r'), 'filename' => $validated['fileData']->getClientOriginalName()],
                ['name' => 'fileRelativePath', 'contents' => $path],
                ['name' => 'branchName', 'contents' => $validated['branchName'] ?? ''],
                ['name' => 'defaultBranchName', 'contents' => $validated['defaultBranchName'] ?? ''],
                ['name' => 'releaseName', 'contents' => $validated['releaseName'] ?? ''],
                ['name' => 'repositoryName', 'contents' => $validated['repositoryName'] ?? ''],
                ['name' => 'productName', 'contents' => $validated['productName'] ?? '']
            ];

            // Add optional ciUploadId if present
            if (!empty($validated['ciUploadId'])) {
                $uploadArray[] = ['name' => 'ciUploadId', 'contents' => $validated['ciUploadId']];
            }

            // Upload the file to the API and return the response
            $response = $client->request('POST', $this->ClientUrl . '/1.0/open/uploads/dependencies/files', [
                'headers' => ['Authorization' => 'Bearer ' . request()->bearerToken()],
                'multipart' => $uploadArray
            ]);

            // Decode the JSON response into an associative array
            $response = json_decode($response->getBody()->getContents(), true);

            // Save the upload details to the database
            $upload->CiUploadId = $response['ciUploadId'];
            $upload->uploadId = $response['uploadProgramsFileId'];
            $upload->fileName = $validated['fileData']->getClientOriginalName();
            $upload->fileType = $validated['fileData']->getClientMimeType();
            $upload->save();

            // Select the latest record from the scan_info table
            $scan = ScanInfo::latest()->first();
            if (!$scan) {
                // Create a new scan info record if none exists
                ScanInfo::create([
                    'totalScansCount' => $response['totalScans'],
                    'remainingScansCount' => $response['remainingScans'],
                    'scansCountPercentage' => $response['percentage'],
                    'estimatedDaysLeftToUtilize' => $response['estimatedDaysLeft']
                ]);
            } else {
                // Update the existing scan info record
                $scan->update([
                    'totalScansCount' => $response['totalScans'],
                    'remainingScansCount' => $response['remainingScans'],
                    'scansCountPercentage' => $response['percentage'],
                    'estimatedDaysLeftToUtilize' => $response['estimatedDaysLeft']
                ]);
            }

            //create a new scan tracker
            $tracker=ScanTracker::create([
                "ciUploadId"=>$response['ciUploadId'],
                "respondent_email"=>session('email'),
                "progress"=>0,
                "processing_id"=>Str::uuid(),
                'auth_token'=>request()->bearerToken()
            ])->id;

            //dispatch job to process the queue
            ProcessUploadQueue::dispatch($tracker)->onQueue('scan_queue');



            // Commit the database transaction
            DB::commit();

            // Return a successful JSON response with the data
            return response()->json(['status' => true, 'data' => $response, 'message' => 'File uploaded successfully']);
        } catch (RequestException $e) {
            // Rollback the database transaction in case of an exception
            DB::rollBack();
            // Return an error response
            return response()->json(['status' => false, 'message' => 'Failed to upload file: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Queues a scan for the uploaded files.
     *
     * @param QueueScanRequest $request The request containing validated data.
     * @return JsonResponse JSON response containing the status and data or an error message.
     */
    public function queueScan(QueueScanRequest $request): JsonResponse
    {
        try {
            // Initialize a new Guzzle HTTP client
            $client = new Client();
            // Validate the request data
            $validated = $request->validated();
            // Retrieve the uploaded files based on ciUploadId
            $uploadedFiles = FileUploads::select(['fileName', 'uploadId'])->where('ciUploadId', $validated['ciUploadId'])->latest()->get();

            // Prepare the upload data array
            $uploadData = [
                ["name" => "ciUploadId", "contents" => $validated['ciUploadId']],
                ["name" => "repositoryName", "contents" => $validated['repositoryName'] ?? ''],
                ["name" => "commitName", "contents" => $validated['commitName'] ?? ''],
                ["name" => "integrationName", "contents" => $validated['integrationName'] ?? ''],
                ["name" => "debrickedIntegrationId", "contents" => $validated['debrickedIntegrationId'] ?? ''],
                ["name" => "author", "contents" => $validated['author'] ?? ''],
                ["name" => "returnCommitData", "contents" => $validated['returnCommitData'] ?? false],
                ["name" => "versionHint", "contents" => $validated['versionHint'] ?? false],
                ["name" => "debrickedConfig", "contents" => $validated['debrickedConfig'] ?? ''],
            ];

            // Add optional repositoryZip if present
            if (isset($validated['repositoryZip']) && !empty($validated['repositoryZip'])) {
                $uploadData[] = [
                    "name" => "repositoryZip",
                    "contents" => fopen($validated['repositoryZip']->getRealPath(), 'r'),
                    "filename" => $validated['repositoryZip']->getClientOriginalName()
                ];
            }

            // Make the POST request to queue the scan
                $response = $client->post($this->ClientUrl . '/1.0/open/finishes/dependencies/files/uploads', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . request()->bearerToken(),
                        'Accept' => 'application/json'
                    ],
                    'multipart' => $uploadData
                ]);

            $code = $response->getStatusCode();
            // Decode the response and return a JSON response
            $response = json_decode($response->getBody()->getContents(), true);

            return response()->json(['status' => true, 'data' => [
                "response" => $response,
                "code" => $code,
                "scans" => $uploadedFiles->toArray()
            ], 'message' => 'Scan queued successfully']);
        } catch (RequestException|GuzzleException|\Exception $e) {
            // Handle request exceptions
            return response()->json(['status' => false, 'message' => 'Failed to queue scan: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Fetches the scan status for a given ciUploadId.
     *
     * @param ScanStatusRequest $request The request containing validated data.
     * @return JsonResponse JSON response containing the status and data or an error message.
     */
    public function getScanStatus(ScanStatusRequest $request): JsonResponse
    {
        try {
            // Initialize a new Guzzle HTTP client
            $client = new Client();

            // Validate the request data
            $validated = $request->validated();

            // Make the GET request to fetch the scan status
            $response = $client->get($this->ClientUrl . '/1.0/open/ci/upload/status', [
                'headers' => [
                    'Authorization' => 'Bearer ' . request()->bearerToken(),
                    'Accept' => 'application/json'
                ],
                'query' => [
                    'ciUploadId' => $validated['ciUploadId'],
                    'extendedOutput' => $validated['extendedOutput'] ?? false
                ]
            ]);

            // Decode the response and return a JSON response
            $response = json_decode($response->getBody()->getContents(), true);

            return response()->json([
                'status' => true,
                'data' => $response,
                'message' => 'Scan status fetched successfully'
            ]);
        } catch (RequestException|GuzzleException|\Exception $e) {
            // Handle request exceptions
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch scan status: ' . $e->getMessage()
            ], 500);
        }
    }
}
