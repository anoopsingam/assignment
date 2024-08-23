<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * FileUploads Model to track file uploads in the system for 3rd party api
 */
class FileUploads extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * @var string
     */
    protected $table = 'file_uploads';

    /**
     * @var string[]
     */
    protected $fillable = ['CiUploadId', 'uploadId', 'fileName', 'fileType'];


    /**
     * @var string[]
     */
    protected $casts = [
        'CiUploadId' => 'integer',
        'uploadId' => 'integer',
        'fileName' => 'string',
        'fileType' => 'string',
    ];


}
