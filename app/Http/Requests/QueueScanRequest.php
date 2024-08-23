<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QueueScanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'ciUploadId' => 'required|string|exists:file_uploads,ciUploadId',
            "repositoryZip"=>'nullable|file|mimes:zip,tar,tar.gz,tar.bz2,tgz,tbz,tbz2,txz,rar,7z,tar.xz,tar.Z,tar.lz4,tar.sz,tar.zst',
            "repositoryName"=>'nullable|string',
            'commitName'=>'nullable|string',
            'integrationName'=>'nullable|string',
            'debrickedIntegrationId'=>'nullable|string',
            'author'=>'nullable|string',
            'returnCommitData'=>'nullable|boolean',
            'versionHint'=>'nullable|boolean',
            'debrickedConfig'=>'nullable',
        ];
    }
}
