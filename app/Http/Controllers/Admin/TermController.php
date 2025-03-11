<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TermRequest;
use App\Services\TermService;
use Exception;
use Illuminate\Http\JsonResponse;

class TermController extends Controller
{
    /**
     * The term service instance.
     *
     * @var TermService
     */
    protected $termService;

    /**
     * Create a new controller instance.
     *
     * @param TermService $termService
     */
    public function __construct(TermService $termService)
    {
        $this->termService = $termService;
    }

    /**
     * Display a listing of the terms.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $terms = $this->termService->getAllTerms();
        
        return response()->json([
            'status' => 'success',
            'data' => $terms
        ]);
    }

    /**
     * Store a newly created term.
     *
     * @param TermRequest $request
     * @return JsonResponse
     */
    public function store(TermRequest $request): JsonResponse
    {
        try {
            $term = $this->termService->createTerm($request->validated());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Term created successfully',
                'data' => $term
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified term.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $term = $this->termService->getTerm($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $term
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Term not found'
            ], 404);
        }
    }

    /**
     * Update the specified term.
     *
     * @param TermRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(TermRequest $request, int $id): JsonResponse
    {
        try {
            $term = $this->termService->updateTerm($id, $request->validated());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Term updated successfully',
                'data' => $term
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Term not found' ? 404 : 422);
        }
    }

    /**
     * Remove the specified term.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->termService->deleteTerm($id);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Term deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Term not found' ? 404 : 422);
        }
    }
} 