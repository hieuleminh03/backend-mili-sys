<?php

namespace App\Http\Controllers;

class StudentController extends Controller
{
    /**
     * Display student dashboard
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/api/student/dashboard",
     *     summary="Get student dashboard data",
     *     tags={"Student"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Student dashboard")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Access denied")
     *         )
     *     )
     * )
     */
    public function dashboard()
    {
        return response()->json(['message' => 'Student dashboard data']);
    }
}