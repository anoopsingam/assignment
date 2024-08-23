<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
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
            'commitName'=>'nullable|string',
            'ciUploadId'=>'nullable|string',
            'repositoryUrl'=>'required|string',
            'fileData'=>'required|file',
            'fileRelativePath'=>'required|string',
            'branchName'=>'nullable|string',
            'defaultBranchName'=>'nullable|string',
            'releaseName'=>'nullable|string' ,
            'repositoryName'=>'nullable|string',
            'productName'=>'nullable|string',
        ];
    }
}
