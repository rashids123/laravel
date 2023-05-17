<?php

namespace App\Http\Api\v1\Controllers;

use App\Models\ProgramPathway;
use App\Http\Resources\ProgramPathwayResource;
use App\Http\Requests\ProgramPathwayRequest;

use App\Models\ProgramPathwayStep;
use App\Http\Resources\ProgramPathwayStepResource;
use App\Http\Requests\ProgramPathwayStepRequest;

use App\Services\AlertService;

use App\Http\Controllers\ApiController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PathwayController extends ApiController
{
    /**
     * Get all Pathways for the given Program, including Pathway Steps
     * @param $programId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $programId)
    {
        $pathways = ProgramPathway::where('program_id', $programId)
                                  ->with(['programPathwaySteps'])
                                  ->paginate($this->perPage)
                                  ->appends($request->all());

        return ProgramPathwayResource::collection($pathways);
    }

    /**
     * Get a specific pathway, including Pathway Steps
     * @param $programId
     * @param $pathwayId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $programId, $pathwayId)
    {
        $pathway = ProgramPathway::where('program_id', $programId)
                                  ->where('id', $pathwayId)
                                  ->with(['programPathwaySteps'])
                                  ->firstOrFail();

        return new ProgramPathwayResource($pathway);
    }

    /**
     * Create a new pathway
     * @param $programId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ProgramPathwayRequest $request, $programId)
    {
        $validated = $request->validated();

        try {
            $pathway = new ProgramPathway($validated);

            $pathway->save();
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return $this->respondWithValidationErrors([
                'message' => 'Could not create the pathway, please try again later.'
            ]);
        }

        return $this->respondCreated(new ProgramPathwayResource($pathway));
    }

    /*
     * Delete a pathway
     * @param $programId
     * @param $pathwayId
    */
    public function delete($programId, $pathwayId)
    {
        // check if the pathway belongs to this program
        // or return 404 Not Found
        $pathway = ProgramPathway::where('program_id', $programId)
                                 ->where('id', $pathwayId)
                                 ->firstOrFail();

        try {
            $pathway->delete();
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return $this->respondWithValidationErrors([
                'message' => 'Could not delete the pathway, please try again later.'
            ]);
        }

        return $this->respondNoContent();
    }

    /**
     * Get all Pathway Steps for the given Program Pathway
     * @param $programId
     * @param $pathwayId
     * @return \Illuminate\Http\JsonResponse
     */
    public function stepsIndex(Request $request, $programId, $pathwayId)
    {
        // check if the pathway belongs to this program
        // or return 404 Not Found
        $pathway = ProgramPathway::where('program_id', $programId)
                                 ->where('id', $pathwayId)
                                 ->firstOrFail();

        // get the steps ordered by step asc
        $steps = ProgramPathwayStep::where('program_pathway_id', $pathwayId)
                                   ->ordered();

        return ProgramPathwayStepResource::collection($steps);
    }

    /**
     * Get a specific Pathway Step
     * @param $programId
     * @param $pathwayId
     * @param $stepId
     * @return \Illuminate\Http\JsonResponse
     */
    public function stepsShow(Request $request, $programId, $pathwayId, $stepId)
    {
        // check if the pathway belongs to this program
        // or return 404 Not Found
        $pathway = ProgramPathway::where('program_id', $programId)
                                 ->where('id', $pathwayId)
                                 ->firstOrFail();

        // get the step or return 404 Not Found
        $step = ProgramPathwayStep::where('program_pathway_id', $pathwayId)
                                   ->where('id', $stepId)
                                   ->firstOrFail();

        return new ProgramPathwayStepResource($step);
    }

    /**
     * Create a new pathway step
     * @param $programId
     * @param $pathwayId
     * @return \Illuminate\Http\JsonResponse
     */
    public function stepsStore(
        ProgramPathwayStepRequest $request,
        AlertService $alertService,
        $programId,
        $pathwayId
    ) {
        $validated = $request->validated();

        // check if the pathway belongs to this program
        // or return 404 Not Found
        $pathway = ProgramPathway::where('program_id', $programId)
                                 ->where('id', $pathwayId)
                                 ->firstOrFail();
        try {
            DB::beginTransaction();

            $step = new ProgramPathwayStep($validated);
            $step->program_pathway_id = $pathwayId;
            $step->save();

            $alertService->adjustSteps($pathwayId);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error($e->getMessage());

            return $this->respondWithValidationErrors([
                'message' => 'Could not create the pathway step, please try again later.'
            ]);
        }

        return $this->respondCreated(new ProgramPathwayStepResource($step));
    }

    /**
     * Update a new pathway step
     * @param $programId
     * @param $pathwayId
     * @param $stepId
     * @return \Illuminate\Http\JsonResponse
     */
    public function stepsUpdate(
        ProgramPathwayStepRequest $request,
        AlertService $alertService,
        $programId,
        $pathwayId,
        $stepId
    ) {
        $validated = $request->validated();

        // check if the pathway belongs to this program
        // or return 404 Not Found
        $pathway = ProgramPathway::where('program_id', $programId)
                                 ->where('id', $pathwayId)
                                 ->firstOrFail();

        $step = ProgramPathwayStep::where('program_pathway_id', $pathwayId)
                                  ->where('id', $stepId)
                                  ->firstOrFail();

        try {
            DB::beginTransaction();

            $step->update($validated);

            $alertService->adjustSteps($pathwayId);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error($e->getMessage());

            return $this->respondWithValidationErrors([
                'message' => 'Could not create the pathway step, please try again later.'
            ]);
        }

        return $this->respondOk(new ProgramPathwayStepResource($step));
    }

    /*
     * Delete a pathway step
     * @param $programId
     * @param $pathwayId
     * @param $stepId
    */
    public function stepsDelete(AlertService $alertService, $programId, $pathwayId, $stepId)
    {
        // check if the pathway belongs to this program
        // or return 404 Not Found
        $pathway = ProgramPathway::where('program_id', $programId)
                                 ->where('id', $pathwayId)
                                 ->firstOrFail();

        $step = ProgramPathwayStep::where('program_pathway_id', $pathwayId)
                                  ->where('id', $stepId)
                                  ->firstOrFail();

        try {
            DB::beginTransaction();

            $step->delete();

            $alertService->adjustSteps($pathwayId);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error($e->getMessage());

            return $this->respondWithValidationErrors([
                'message' => 'Could not delete the pathway step, please try again later.'
            ]);
        }

        return $this->respondNoContent();
    }
}
