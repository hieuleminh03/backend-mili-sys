<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClassDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Get the authenticated user's role
        $userRole = auth()->user()->role;
        
        // Basic class details that all roles can see
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
        
        // Include manager info for all roles
        if ($this->manager) {
            $data['manager'] = [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
                'email' => $this->manager->email,
                'image' => $this->manager->image,
            ];
        }
        
        // Get monitor info using the model's monitor() method
        $monitor = $this->monitor();
        if ($monitor) {
            $data['monitor'] = [
                'id' => $monitor->id,
                'name' => $monitor->name,
                'email' => $monitor->email,
                'image' => $monitor->image,
            ];
        } else {
            $data['monitor'] = null;
        }
        
        // Get vice monitors using the model's viceMonitors() method
        $viceMonitors = $this->viceMonitors();
        if ($viceMonitors->count() > 0) {
            $data['vice_monitors'] = $viceMonitors->map(function ($vm) {
                return [
                    'id' => $vm->id,
                    'name' => $vm->name,
                    'email' => $vm->email,
                    'image' => $vm->image,
                ];
            });
        } else {
            $data['vice_monitors'] = [];
        }
        
        // Include all students in the class for all roles
        if ($this->relationLoaded('students')) {
            $data['students'] = $this->students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'image' => $student->image,
                    'role' => $student->pivot->role ?? 'student',
                    'status' => $student->pivot->status ?? 'active',
                ];
            });
            
            $data['student_count'] = $this->students->count();
        } else {
            // If students not eager loaded, get them now
            $studentClasses = $this->studentClasses()->with('student:id,name,email,image')->get();
            $data['students'] = $studentClasses->map(function ($sc) {
                return [
                    'id' => $sc->student->id,
                    'name' => $sc->student->name,
                    'email' => $sc->student->email,
                    'image' => $sc->student->image,
                    'role' => $sc->role,
                    'status' => $sc->status,
                ];
            });
            
            $data['student_count'] = $studentClasses->count();
        }
        
        return $data;
    }
}
