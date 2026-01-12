<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProjectResearchRequest;
use App\Models\Project;
use App\Models\Research;
use App\Services\Projects\ProjectAttachmentService;
use Illuminate\Http\JsonResponse;

class CreateResearchController extends Controller
{
    public function __invoke(
        CreateProjectResearchRequest $request,
        Project $project,
        ProjectAttachmentService $attachments,
    ): JsonResponse {
        $data = $request->validated();

        $research = Research::create([
            'topic' => (string) $data['topic'],
            'notes' => (string) ($data['notes'] ?? ''),
            'sources' => [],
        ]);

        $attachments->attach(
            project: $project,
            resource_type: Research::class,
            resource_id: (int) $research->getKey(),
        );

        return response()->json($research, 201);
    }
}
