<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ScanTracker
 *
 * Represents a scan tracker model.
 *
 * @package App\Models
 */
class ScanTracker extends Model
{
    use HasFactory;

    /**
     * @var string The table associated with the model.
     */
    protected $table = 'scan_trackers';

    /**
     * @var array The attributes that are mass assignable.
     */
    protected $fillable = [
        'ciUploadId',
        'status',
        'respondent_email',
        'progress',
        'no_of_threats_found',
        'details_url',
        'processing_id',
        'auth_token',
    ];

    /**
     * @var array The attributes that should be cast to native types.
     */
    protected $casts = [
        'ciUploadId' => 'string',
        'status' => 'string',
        'respondent_email' => 'string',
        'progress' => 'string',
        'no_of_threats_found' => 'string',
        'details_url' => 'string',
        'processing_id' => 'string',
    ];

    /**
     * Retrieves a ScanTracker by processing ID.
     *
     * @param string $processingId The processing ID.
     * @return ScanTracker|null The ScanTracker instance or null if not found.
     */
    public function getScanTrackerByProcessingId(string $processingId): ?ScanTracker
    {
        return $this->where('processing_id', $processingId)->first();
    }

    /**
     * Updates a ScanTracker by processing ID.
     *
     * @param string $processingId The processing ID.
     * @param array $data The data to update.
     * @return bool True if the update was successful, false otherwise.
     */
    public function updateScanTrackerByProcessingId(string $processingId, array $data): bool
    {
        return $this->where('processing_id', $processingId)->update($data);
    }
}
