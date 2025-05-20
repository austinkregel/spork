<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Models\JobBatch;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Kregel\ExceptionProbe\Stacktrace;

class BatchJobController extends Controller
{
    public function index()
    {
        $batches = JobBatch::query()
            ->orderByDesc('created_at')
            ->paginate();

        $paginator = new LengthAwarePaginator(
            array_map(function ($batch) {
                $failedJobs = $batch->failed_job_ids;

                if (count($failedJobs) > 0) {
                    $batch->jobs = \DB::table('failed_jobs')
                        ->select('*')
                        ->whereIn('uuid')
                        ->orderByDesc('failed_at')
                        ->get()
                        ->map(function ($job) {
                            $job->parsed_exception = (new Stacktrace)->parse($job->exception);
                            $job->payload = json_decode($job->payload, true);

                            return $job;
                        });

                    $batch->failed_at = $batch->jobs->max('failed_at');
                }

                return $batch;
            }, $batches->items()),
            $batches->total(),
            $batches->perPage(),
            $batches->currentPage()
        );

        return Inertia::render('Admin/BatchJob/Index', [
            'title' => 'Batch Jobs',
            'paginator' => $paginator,
        ]);
    }

    public function show(Request $request, string $batch)
    {
        $batches = JobBatch::query()
            ->select('*')
            ->orderByDesc('created_at')
            ->where('id', $batch)
            ->paginate();

        $paginator = new LengthAwarePaginator(
            array_map(function ($batch) {

                if (!empty($batch->failed_job_ids)) {
                    $batch->jobs = \DB::table('failed_jobs')
                        ->select('*')
                        ->whereIn('uuid', $batch->failed_job_ids)
                        ->orderByDesc('failed_at')
                        ->get()
                        ->map(function ($job) {
                            $job->parsed_exception = (new Stacktrace)->parse($job->exception);
                            $job->payload = json_decode($job->payload, true);

                            return $job;
                        });
                    $batch->failed_at = $batch->jobs->max('failed_at');
                }

                return $batch;
            }, $batches->items()),
            $batches->total(),
            $batches->perPage(),
            $batches->currentPage()
        );

        return Inertia::render('Admin/BatchJob/Show', [
            'title' => 'Batch Jobs',
            'paginator' => $paginator,
        ]);
    }
}
