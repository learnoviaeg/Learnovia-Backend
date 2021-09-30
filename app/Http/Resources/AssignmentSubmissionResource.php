<?php

namespace App\Http\Resources;
use App\attachment;
use Modules\Assigments\Entities\AssignmentLesson;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentSubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'user_id' => $this['id'],
            'firstname' => $this['firstname'],
            'lastname' => $this['lastname'],
            'fullname' => $this['fullname'],
            'arabicname' => $this['arabicname'] ,
            'picture' => isset($this['picture']) ? attachment::find($this['picture']) : null,
            'attachment_id' => isset($this['userAssignment'][0]['attachment_id']) ? attachment::find($this['userAssignment'][0]['attachment_id']) : null,
            'submit_date' => isset($this['userAssignment'][0]['submit_date']) ? $this['userAssignment'][0]['submit_date'] : null,
            'content' => isset($this['userAssignment'][0]['content']) ? $this['userAssignment'][0]['content'] : null,
            'override' => isset($this['userAssignment'][0]['override']) ? $this['userAssignment'][0]['override'] : null,
            'status_id' => isset($this['userAssignment'][0]['status_id']) ? $this['userAssignment'][0]['status_id'] : 2,
            'feedback' => isset($this['userAssignment'][0]['feedback']) ? $this['userAssignment'][0]['feedback'] : null,
            'grade' => isset($this['userAssignment'][0]['grade']) ? $this['userAssignment'][0]['grade'] : null,
            'assignment_lesson_id' => isset($this['userAssignment'][0]['assignment_lesson_id']) ? $this['userAssignment'][0]['assignment_lesson_id'] : null,
            'corrected_file' => isset($this['userAssignment'][0]['corrected_file']) ? attachment::find($this['userAssignment'][0]['corrected_file']) : null,
            'allow_edit_answer' => isset($this['userAssignment'][0]['assignment_lesson_id']) ? AssignmentLesson::find($this['userAssignment'][0]['assignment_lesson_id'])->allow_edit_answer : null,

        ];
    }
}
