<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ScanInfo to track the scan count assigned by the 3rd party api
 */
class ScanInfo extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'scan_infos';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string[]
     */
    protected $fillable = ['totalScansCount', 'remainingScansCount', 'scansCountPercentage', 'estimatedDaysLeftToUtilize'];


}
