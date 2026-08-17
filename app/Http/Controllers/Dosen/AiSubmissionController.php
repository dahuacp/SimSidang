<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiReadSubmissionRequest;
use App\Models\Submission;
use App\Services\AiReadServiceException;
use App\Services\AiSubmissionService;

class AiSubmissionController extends Controller
{
    public function __construct(protected AiSubmissionService $service) {}

    public function analyze(AiReadSubmissionRequest $request, Submission $submission)
    {
        $this->authorize('view', $submission);

        return $this->handle(fn () => $this->service->analyze($submission));
    }

    public function refresh(AiReadSubmissionRequest $request, Submission $submission)
    {
        $this->authorize('view', $submission);

        return $this->handle(fn () => $this->service->analyze($submission, forceRefresh: true));
    }

    protected function handle(callable $callback)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $callback(),
            ]);
        } catch (AiReadServiceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->status);
        }
    }
}
