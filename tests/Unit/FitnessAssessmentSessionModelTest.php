<?php

namespace Tests\Unit;

use App\Models\FitnessAssessmentSession;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class FitnessAssessmentSessionModelTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test the getCurrentWeekSession method creates a new session when none exists.
     *
     * @return void
     */
    public function test_get_current_week_session_creates_new_session_when_none_exists()
    {
        // Initial check - no sessions should exist
        $this->assertEquals(0, FitnessAssessmentSession::count());
        
        // Call the method
        $session = FitnessAssessmentSession::getCurrentWeekSession();
        
        // Should now have one session
        $this->assertEquals(1, FitnessAssessmentSession::count());
        
        // Check that the session is correctly configured
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek(); // Monday
        $endOfWeek = $now->copy()->endOfWeek();     // Sunday
        
        $this->assertEquals($startOfWeek->toDateString(), $session->week_start_date->toDateString());
        $this->assertEquals($endOfWeek->toDateString(), $session->week_end_date->toDateString());
        
        // Check that the name is formatted correctly
        $weekNumber = $now->weekOfYear;
        $monthName = $now->translatedFormat('F');
        $year = $now->year;
        
        $this->assertStringContainsString("Tuần $weekNumber", $session->name);
        $this->assertStringContainsString($year, $session->name);
    }
    
    /**
     * Test the getCurrentWeekSession method returns existing session when it exists.
     *
     * @return void
     */
    public function test_get_current_week_session_returns_existing_session()
    {
        // Create a session for the current week
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek()->startOfDay();
        $endOfWeek = $now->copy()->endOfWeek()->startOfDay();
        
        // Clear any existing sessions (to fix the test)
        FitnessAssessmentSession::query()->delete();
        
        $existingSession = FitnessAssessmentSession::create([
            'name' => "Existing Week Session",
            'week_start_date' => $startOfWeek->format('Y-m-d'),
            'week_end_date' => $endOfWeek->format('Y-m-d'),
        ]);
        
        // Call the method
        $session = FitnessAssessmentSession::getCurrentWeekSession();
        
        // Should still have only one session
        $this->assertEquals(1, FitnessAssessmentSession::count());
        
        // Should return the existing session
        $this->assertEquals($existingSession->id, $session->id);
        $this->assertEquals("Existing Week Session", $session->name);
    }
    
    /**
     * Test the isCurrentWeek method.
     *
     * @return void
     */
    public function test_is_current_week_method()
    {
        // Clear any sessions from previous tests
        FitnessAssessmentSession::query()->delete();
        
        // Create a session for the current week
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        
        $currentSession = FitnessAssessmentSession::create([
            'name' => "Current Week Session",
            'week_start_date' => $startOfWeek->format('Y-m-d'),
            'week_end_date' => $endOfWeek->format('Y-m-d'),
        ]);
        
        // Create a session for last week
        $lastWeek = $now->copy()->subWeek();
        $startOfLastWeek = $lastWeek->copy()->startOfWeek();
        $endOfLastWeek = $lastWeek->copy()->endOfWeek();
        
        $pastSession = FitnessAssessmentSession::create([
            'name' => "Past Week Session",
            'week_start_date' => $startOfLastWeek->format('Y-m-d'),
            'week_end_date' => $endOfLastWeek->format('Y-m-d'),
        ]);
        
        // Create a session for next week
        $nextWeek = $now->copy()->addWeek();
        $startOfNextWeek = $nextWeek->copy()->startOfWeek();
        $endOfNextWeek = $nextWeek->copy()->endOfWeek();
        
        $futureSession = FitnessAssessmentSession::create([
            'name' => "Future Week Session",
            'week_start_date' => $startOfNextWeek->format('Y-m-d'),
            'week_end_date' => $endOfNextWeek->format('Y-m-d'),
        ]);
        
        // Test isCurrentWeek method
        $this->assertTrue($currentSession->isCurrentWeek());
        $this->assertFalse($pastSession->isCurrentWeek());
        $this->assertFalse($futureSession->isCurrentWeek());
    }
}