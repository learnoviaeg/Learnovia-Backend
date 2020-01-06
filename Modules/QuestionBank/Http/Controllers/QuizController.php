<?php

namespace Modules\QuestionBank\Http\Controllers;

use App\Course;
use App\CourseSegment;
use App\Enroll;
use App\GradeCategory;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\quiz;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\userQuizAnswer;
use Modules\QuestionBank\Entities\userQuiz;
use Spatie\Permission\Models\Permission;
use Validator;
use App\Classes;
use Auth;

class QuizController extends Controller
{

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3',
            'course_id' => 'required|integer|exists:courses,id',
            'type' => 'required|in:0,1,2',
            'class' => 'required|exists:classes,id',
            /**
             * type 0 => new Question OR OLD
             * type 1 => random Questions
             * type 2 => Without Question
             */
            'is_graded' => 'required|boolean',
            'duration' => 'required|integer',
            'shuffle' => 'boolean',
            'feedback' => 'required|integer| in:1,2,3',
            /**
             * feedback 1 =>After due date,
             * feedback 2 => After submission
             * feedback 3 => No feedback
            */
        ]);
        if($request->is_graded==1 && $request->feedback == 2)
            return HelperController::api_response_format(200, null, 'invalid feedback ');

        $request->validate([
            'Question' => 'nullable|array',
            'Question.*.Question_Type_id' => 'required|integer|exists:questions_types,id',
        ]);
          $course=  Course::where('id',$request->course_id)->first();
        if(isset($request->Question)){
            foreach ($request->Question as $question) {
                switch ($question['Question_Type_id']) {
                    case 1: // True/false
                        $validator = Validator::make($question, [
                            'answers' => 'required|array|distinct|min:2|max:2',
                            'text' => 'required|string',
                            'answers.*' => 'required|boolean|distinct',
                            'And_why' => 'integer|required',
                            'And_why_mark' => 'integer|min:1|required_if:And_why,==,1',
                            'Is_True' => 'required|boolean',
                            'Question_Type_id' => 'required|integer|exists:questions_types,id',
                            'mark' => 'required|integer|min:1',
                            'Question_Category_id' => 'required|exists:questions_categories,id',
                            'Category_id' => 'required|exists:categories,id',
                            'course_id' => 'required|exists:courses,id',
                            'class' => 'required|exists:classes,id',
                            'parent' => 'integer|exists:questions,id',
                        ]);
                        if ($validator->fails()) {
                            return HelperController::api_response_format(400, $validator->errors(), 'Something went wrong');
                        }
                        break;

                    case 2: // MCQ
                        $validator = Validator::make($question, [
                            'answers' => 'required|array|distinct|min:2',
                            'answers.*' => 'required|string|distinct',
                            'Is_True' => 'required|integer',
                            'text' => 'required|string',
                            'Question_Type_id' => 'required|integer|exists:questions_types,id',
                            'mark' => 'required|integer|min:1',
                            'Question_Category_id' => 'required|exists:questions_categories,id',
                            'Category_id' => 'required|exists:categories,id',
                            'course_id' => 'required|exists:courses,id',
                            'class' => 'required|exists:classes,id',
                            'parent' => 'integer|exists:questions,id',
                        ]);

                        if ($question['Is_True'] > count($question['answers']) - 1) {
                            return HelperController::api_response_format(400, $question, 'is True invalid');
                        }

                        if ($validator->fails()) {
                            return HelperController::api_response_format(400, $validator->errors(), 'Something went wrong');
                        }
                        break;

                    case 3: // Match
                        $validator = Validator::make($question, [
                            'match_A' => 'required|array|min:2|distinct',
                            'match_A.*' => 'required|distinct',
                            'match_B' => 'required|array|distinct',
                            'match_B.*' => 'required|distinct',
                            'Question_Type_id' => 'required|integer|exists:questions_types,id',
                            'text' => 'required_if:Question_Type_id,==,4|required_if:Question_Type_id,==,5',
                            'mark' => 'required|integer|min:1',
                            'Question_Category_id' => 'required|exists:questions_categories,id',
                            'Category_id' => 'required|exists:categories,id',
                            'course_id' => 'required|exists:courses,id',
                            'class' => 'required|exists:classes,id',
                            'parent' => 'integer|exists:questions,id',
                        ]);
                        if ($validator->fails()) {
                            return HelperController::api_response_format(400, $validator->errors(), 'Something went wrong');
                        }
                        if (count($question['match_A']) > count($question['match_B'])) {
                            return HelperController::api_response_format(400, null, '  number of Questions is greater than numbers of answers ');
                        }
                        break;
                    case 5: // para
                        $validator = Validator::make($question, [
                            'Question_Type_id' => 'required|integer|exists:questions_types,id',
                            'text' => 'required_if:Question_Type_id,==,4|required_if:Question_Type_id,==,5',
                            'mark' => 'required|integer|min:1',
                            'Question_Category_id' => 'required|exists:questions_categories,id',
                            'Category_id' => 'required|exists:categories,id',
                            'course_id' => 'required|exists:courses,id',
                            'class' => 'required|exists:classes,id',
                            'parent' => 'integer|exists:questions,id',
                            'subQuestions' => 'required|array|distinct'/*|min:2*/,
                            'subQuestions.*' => 'required|distinct',
                            'subQuestions.*.Question_Type_id' => 'required|integer|exists:questions_types,id',
                        ]);
                        if ($validator->fails()) {
                            return HelperController::api_response_format(400, $validator->errors());
                        }
                        break;
                }
            }
        }
        $course_segment = CourseSegment::GetWithClassAndCourse($request->class,$request->course_id);
        if(!isset($course_segment)) {
            return HelperController::api_response_format(400, null, 'Something went wrong :doesn\'t exist course segment');
        }
        $index = Quiz::where('course_segment_id',$request->course_id)->get()->max('index');
        $Next_index = $index + 1;
        if ($request->type == 0) { // new or new
            $newQuestionsIDs = $this->storeWithNewQuestions($request);
            $oldQuestionsIDs = $this->storeWithOldQuestions($request);
            $questionsIDs = $newQuestionsIDs->merge($oldQuestionsIDs);
        } else if ($request->type == 1) { // random
            $questionsIDs = $this->storeWithRandomQuestions($request);
        } else { // create Quiz without Question
            $quiz = quiz::create([
                'name' => $request->name,
                'course_segment_id' => $course_segment->id,
                'is_graded' => $request->is_graded,
                'duration' => $request->duration,
                'created_by' => Auth::user()->id,
                'Shuffle' => quiz::checkSuffle($request),
                'index' => $Next_index,
                'feedback' => $request->feedback,
            ]);
            return HelperController::api_response_format(200, $quiz,'Quiz added Successfully');
        }

        if ($questionsIDs != null) {
            $quiz = quiz::create([
                'name' => $request->name,
                'course_segment_id' => $course_segment->id,
                'is_graded' => $request->is_graded,
                'duration' => $request->duration,
                'created_by' => Auth::user()->id,
                'Shuffle' => quiz::checkSuffle($request),
                'index' => $Next_index,
                'feedback' => $request->feedback,
            ]);

            $quiz->Question()->attach($questionsIDs);
            $quiz->Question;
            foreach ($quiz->Question as $question) {
                unset($question->pivot);
                $question->category;
                $question->question_type;
                $question->question_category;
                $question->question_course;
                $question->question_answer;
            }

            return HelperController::api_response_format(200, $quiz,'Quiz added Successfully');
        }
        return HelperController::api_response_format(200, null, 'There\'s no Questions for this course in Question Bank');
    }

    // New Questions
    public function storeWithNewQuestions(Request $request)
    {
        $questionsIDs = app('Modules\QuestionBank\Http\Controllers\QuestionBankController')->store($request, 1);
        return $questionsIDs;
    }
    // Old Questions
    public function storeWithOldQuestions(Request $request)
    {
        $request->validate([
            'oldQuestion' => 'nullable|array',
            'oldQuestion.*' => 'required|integer|exists:questions,id',
        ]);
        return $request->oldQuestion;
    }
    // Random Questions
    public function storeWithRandomQuestions(Request $request)
    {
        $request->validate([
            'randomNumber' => 'required|integer|min:1'
        ]);
        $course_segment = CourseSegment::GetWithClassAndCourse($request->class,$request->course_id);
        if(!isset($course_segment)){
            return HelperController::api_response_format(400, null, 'Something went wrong :doesn\'t exist course segment');
        }
        $questionIDs = Questions::inRandomOrder()
            ->where('course_segment_id', $course_segment->id)
            ->limit($request->randomNumber)
            ->get();

        if (count($questionIDs) != 0) {
            $questionIDs = $questionIDs->pluck('id');
            return $questionIDs;
        }

        return null;
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'name' => 'required|string|min:3',
            'course_id' => 'required|exists:courses,id',
            'class' => 'required|exists:classes,id',
            'is_graded' => 'required|boolean',
            'duration' => 'required|integer',
            /**
             * type 0 => new Question and/or OLD
             * type 1 => Old Questions
             * type 2 => Without Question
             */
            'type' => 'required|in:0,1,2',
        ]);

        $quiz = quiz::find($request->quiz_id);

        if($request->type == 0){
            $newQuestionsIDs = $this->storeWithNewQuestions($request);

            $oldQuestionsIDs = $this->storeWithOldQuestions($request);

            $questionsIDs = $newQuestionsIDs->merge($oldQuestionsIDs);
        }
        else if($request->type == 1){
            $questionsIDs = $this->storeWithOldQuestions($request);
        }
        else{
            $questionsIDs = [];
        }
        if (count($questionsIDs) == 0) { // In case of delete all questions

            $quiz->update([
                'name' => $request->name,
                'is_graded' => $request->is_graded,
                'duration' => $request->duration,
            ]);

            $quiz->Question()->detach();

            return HelperController::api_response_format(200, $quiz,'Quiz Updated Successfully');
        }

        $quiz->update([
            'name' => $request->name,
            'is_graded' => $request->is_graded,
            'duration' => $request->duration,
        ]);

        $quiz->Question()->detach();
        $quiz->Question()->attach($questionsIDs);

        $quiz->Question;

        foreach ($quiz->Question as $question) {
            unset($question->pivot);
            $question->category;
            $question->question_type;
            $question->question_category;
            $question->question_course;
            $question->question_answer;
        }
        return HelperController::api_response_format(200, $quiz,'Quiz Updated Successfully');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quiz_lessons,quiz_id',
            'lesson_id' => 'required|exists:quiz_lessons,lesson_id'
        ]);

        $quiz=QuizLesson::where('quiz_id', $request->quiz_id)->where('lesson_id',$request->lesson_id)->first();
        // destroy($request->quiz_id);
        $quiz->delete();
        return HelperController::api_response_format(200, [], 'Quiz deleted Successfully');
    }

    public function get(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id'
        ]);
        $quiz = Quiz::where('id', $request->quiz_id)->pluck('shuffle')->first();
        $qq = Quiz::where('id', $request->quiz_id)->first();
        $max_attemp= $qq->quizLessson[0]->max_attemp;
        $grade_category_id= $qq->quizLessson[0]->grade_category_id;

        $gradecat=GradeCategory::where('id',$grade_category_id)->first();
        //return $quizLesson;
        if ($quiz == 0) {
            $quiz = quiz::find($request->quiz_id);
            $quiz['max_attemp']=$max_attemp;
            $quiz['grade_category']=$gradecat;

            $Questions = $quiz->Question;
            foreach ($Questions as $Question) {
                if ($Question->question_type_id==3){
                    $Match_A=collect([]);
                    $Match_B=collect([]);

                    foreach ($Question->question_answer as $answer ){
                        $Match_A->push($answer->match_a);
                        $Match_B->push($answer->match_b);
                    }
                    $Match_A= $Match_A->unique();
                    $Match_B= $Match_B->unique();
                    unset($Question->question_answer);
                    $Question->Match_A_List = $Match_A->values()->all();
                    $Question->Match_B_List= $Match_B->values()->all();
                }

                $Question->question_answer;

            }
            return HelperController::api_response_format(200, $Questions);
        } else {
            $quiz = quiz::find($request->quiz_id);
            $quiz['max_attemp']=$max_attemp;
            $quiz['grade_category']=$gradecat;
            $shuffledQuestion = $quiz->Question->shuffle();
            foreach ($shuffledQuestion as $question) {
                if (count($question->childeren) > 0) {
                    $shuffledChildQuestion = $question->childeren->shuffle();
                    unset($question->childeren);
                    foreach ($shuffledChildQuestion as $single) {
                        $single->question_type;
                    }
                    $question->childeren = $shuffledChildQuestion;
                    foreach ($shuffledChildQuestion as $childQuestion) {
                        if ($childQuestion->question_type_id==3){
                            $Match_A=collect([]);
                            $Match_B=collect([]);

                            foreach ($childQuestion->question_answer as $answer ){
                                $Match_A->push($answer->match_a);
                                $Match_B->push($answer->match_b);
                            }
                            $Match_A= $Match_A->unique()->shuffle();
                            $Match_B= $Match_B->unique()->shuffle();
                            unset($childQuestion->question_answer);
                            $childQuestion->Match_A_List = $Match_A->values()->all();
                            $childQuestion->Match_B_List= $Match_B->values()->all();
                        }
                        $answers = $childQuestion->question_answer->shuffle();
                        $childQuestion->answers = $answers;
                        unset($childQuestion->question_answer);
                        unset($childQuestion->pivot);
                    }
                } else {
                    unset($question->childeren);
                }

                if ($question->question_type_id==3){
                    $Match_A=collect([]);
                    $Match_B=collect([]);

                    foreach ($question->question_answer as $answer ){
                        $Match_A->push($answer->match_a);
                        $Match_B->push($answer->match_b);
                    }
                    $Match_A= $Match_A->unique()->shuffle();
                    $Match_B= $Match_B->unique()->shuffle();
                    $question->Match_A_List = $Match_A->values()->all();
                    $question->Match_B_List= $Match_B->values()->all();
                }

                $question->question_answer->shuffle();


                $answers = $question->question_answer->shuffle();
                $question->answers = $answers;
                $question->question_category;
                $question->question_type;
                foreach ($question->answers as $answer) {
                    unset($answer->is_true);
                }
                unset($question->question_answer);
                unset($question->pivot);
            }
            $quiz->shuffledQuestion = $shuffledQuestion;
            unset($quiz->Question);

            // TXPDF::AddPage();
            // TXPDF::Write(0, $quiz);
            // TXPDF::Output(Storage_path('app\public\PDF\\Quiz '.$request->quiz_id.'.pdf'), 'F');

            return HelperController::api_response_format(200, $quiz);
        }
    }

    public function sortDown($quiz_id, $index)
    {

        $course_id = Quiz::where('id', $quiz_id)->pluck('course_id')->first();
        $quiz_index = Quiz::where('id', $quiz_id)->pluck('index')->first();

        $quizes = Quiz::where('course_id', $course_id)->get();
        foreach ($quizes as $quiz) {
            if ($quiz->index > $quiz_index || $quiz->index < $index) {
                continue;
            }
            if ($quiz->index != $quiz_index) {
                $quiz->update([
                    'index' => $quiz->index + 1
                ]);
            } else {
                $quiz->update([
                    'index' => $index
                ]);
            }
        }
        return $quizes;
    }

    public function SortUp($quiz_id, $index)
    {
        $course_id = Quiz::where('id', $quiz_id)->pluck('course_id');
        $quiz_index = Quiz::where('id', $quiz_id)->pluck('index')->first();
        $quizes = Quiz::where('course_id', $course_id)->get();
        foreach ($quizes as $quiz) {
            if ($quiz->index > $index || $quiz->index < $quiz_index) {
                continue;
            } elseif ($quiz->index != $quiz_index) {
                $quiz->update([
                    'index' => $quiz->index - 1
                ]);
            } else {
                $quiz->update([
                    'index' => $index
                ]);
            }
        }
        return $quizes;
    }

    public function sort(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'index' => 'required|integer'
        ]);
        $quiz_index = Quiz::where('id', $request->quiz_id)->pluck('index')->first();

        if ($quiz_index > $request->index) {
            $quizes = $this->sortDown($request->quiz_id, $request->index);
        } else {
            $quizes = $this->SortUp($request->quiz_id, $request->index);
        }
        return HelperController::api_response_format(200, $quizes, ' Successfully');
    }

    public function getAllQuizes(Request $request)
    {
        $request->validate([
            'course' => 'required_with:class|integer|exists:courses,id',
            'class' => 'required_with:course|integer|exists:classes,id',
        ]);

        if (isset($request->class)) {
            $quizes = collect([]);
            $class = Classes::with([
                'classlevel.segmentClass.courseSegment' =>
                function ($query) use ($request) {
                    $query->with(['lessons'])->where('course_id', $request->course);
                }
            ])->whereId($request->class)->first();

            foreach ($class->classlevel->segmentClass as $segmentClass) {
                foreach ($segmentClass->courseSegment as $courseSegment) {
                    foreach ($courseSegment->lessons as $lesson) {

                        foreach ($lesson->QuizLesson as $QuizLesson) {
                            $quiz = $QuizLesson->quiz;
                            foreach ($quiz->Question as $question) {
                                if (count($question->childeren) > 0) {
                                    foreach ($question->childeren as $single) {
                                        $single->question_type;
                                        $single->question_answer;
                                        unset($single->pivot);
                                    }
                                } else {
                                    unset($question->childeren);
                                }
                                $question->question_answer;
                                $question->question_category;
                                $question->question_type;
                                foreach ($question->question_answer as $answer) {
                                    unset($answer->is_true);
                                }
                                unset($question->pivot);
                            }
                            $quizes->push($quiz);
                        }
                    }
                }
            }
        } else {
            $quizes = quiz::all();
            foreach ($quizes as $quiz) {
                foreach ($quiz->Question as $question) {
                    if (count($question->childeren) > 0) {
                        foreach ($question->childeren as $single) {
                            $single->question_type;
                            $single->question_answer;
                            unset($single->pivot);
                        }
                    } else {
                        unset($question->childeren);
                    }
                    $question->question_answer;
                    $question->question_category;
                    $question->question_type;
                    foreach ($question->question_answer as $answer) {
                        unset($answer->is_true);
                    }
                    unset($question->pivot);
                }
            }
        }
        return HelperController::api_response_format(200, $quizes);
    }

    public function getStudentinQuiz(Request $request)
    {
        $request->validate([
            'quiz' => 'required|integer|exists:quiz_lessons,quiz_id',
            'lesson' => 'required|integer|exists:quiz_lessons,lesson_id'
        ]);
        $check = QuizLesson::whereQuiz_id($request->quiz)->whereLesson_id($request->lesson)->first();
        if ($check == null)
            return HelperController::api_response_format(400, null, 'This Quiz not in this lesson');
        $USERS = collect([]);
        $quiz = quiz::find($request->quiz_id);
        $quizLessons = $check->id;
        $courseSegment = $check->lesson->courseSegment; //$quiz->course->courseSegments->where('is_active',1)->first();
        $roles_id=  Permission::where('name','site/quiz/getStudentinQuiz')->roles->pluck('id');
        $enroll = $courseSegment->Enroll->whereIn('role_id', $roles_id);
        foreach ($enroll as $enrollment) {
            $userData = collect([]);
            $currentUser = $enrollment->user;
            $fullname = $currentUser->firstname . ' ' . $currentUser->lastname;
            $userData->put('id', $currentUser->id);
            $userData->put('Name', $fullname);
            $userData->put('picture', $currentUser->picture);
            $userQuiz = $currentUser->userQuiz->where('quiz_lesson_id', $quizLessons);
            $hasAnswer = 0;
            foreach ($userQuiz as $singleAccess) {
                $count = userQuizAnswer::where('user_quiz_id', $singleAccess->id)->count();
                if ($count > 0) {
                    $hasAnswer = 1;
                    break;
                }
            }
            $userData->put('hasAnswer', $hasAnswer);

            $USERS->push($userData);
        }
        return HelperController::api_response_format(200, $USERS);
    }

    public function getStudentAnswerinQuiz(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'student_id' => 'required|integer|exists:users,id',
            'lesson_id' => 'required|integer|exists:lessons,id'
        ]);
        $quizLesson = QuizLesson::where('quiz_id', $request->quiz_id)->where('lesson_id', $request->lesson_id)->first();
        if ($quizLesson == null)
            return HelperController::api_response_format(400, [], 'No avaiable date for this info');
        $userQuizes = userQuiz::with(['UserQuizAnswer.Question'])->where('quiz_lesson_id', $quizLesson->id)->where('user_id', $request->student_id)->get();
        return HelperController::api_response_format(200, $userQuizes);
    }


    public function getAllStudentsAnswerinQuiz(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id'
        ]);
        $quizLesson = QuizLesson::where('quiz_id', $request->quiz_id)->where('lesson_id', $request->lesson_id)->first();
        $students = userQuiz::where('quiz_lesson_id', $quizLesson->id)->with(['quiz_lesson', 'quiz_lesson.quiz', 'quiz_lesson.quiz.Question'])->get();
        //return $students;
        $Sts = collect([]);
        $count = 0;
        while (isset($students[$count])) {
            $userQuiz = userQuiz::where('user_id', $students[$count]->user_id)->where('quiz_lesson_id', $quizLesson->id)->pluck('id')->first();
            foreach ($students[$count]['quiz_lesson']['quiz']['question'] as $question) {
                $test = $question->userAnswer($userQuiz);
                $question->Answers = $test;
            }
            $count += 1;


            // return $students[0];
        }
        return $students;
    }

    public function getSingleQuiz(Request $request){
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id'
        ]);
        $quiz = Quiz::find($request->quiz_id);
        $qq = Quiz::where('id', $request->quiz_id)->first();
        $max_attemp= $qq->quizLessson[0]->max_attemp;
        $grade_category_id= $qq->quizLessson[0]->grade_category_id;

        $gradecat=GradeCategory::where('id',$grade_category_id)->first();
            $quiz['max_attemp']=$max_attemp;
            $quiz['grade_category']=$gradecat;

        foreach($quiz->Question as $question){
            if(count($question->childeren) > 0){
                foreach($question->childeren as $single){
                    $single->question_type;
                    $single->question_answer;
                    $question->question_category;
                    unset($single->pivot);
                }
            }
            else{
                unset($question->childeren);
            }
            $question->question_answer;
            $question->question_category;
            $question->question_type;
            unset($question->pivot);
        }

        return HelperController::api_response_format(200,$quiz);
    }
    public function toggleQuizVisibity(Request $request)
    {
        try {
            $request->validate([
                'quiz_id' => 'required|exists:quizzes,id',
                'lesson_id' => 'required|exists:quiz_lessons,lesson_id'
            ]);

            $Quiz_lesson = QuizLesson::where('quiz_id',$request->quiz_id)
                ->where('lesson_id',$request->lesson_id)->first();
            if(!isset($Quiz_lesson)){
                return HelperController::api_response_format(400, null, 'Try again , Data invalid');
            }

            $Quiz_lesson->visible = ($Quiz_lesson->visible == 1) ? 0 : 1;
            $Quiz_lesson->save();

            return HelperController::api_response_format(200, $Quiz_lesson, 'Toggle Successfully');
        } catch (Exception $ex) {
            return HelperController::api_response_format(400, null, 'Please Try again');
        }
    }
    public function gradeing_method()
    {
        return[
            [
                'id' => 1,
                'name' =>'First'
            ],
            [
                'id' => 2,
                'name' =>'Last'
            ],
            [
                'id' => 3,
                'name' =>'Average'
            ],
            [
                'id' => 4,
                'name' =>'Highest'
            ],
            [
                'id' => 5,
                'name' =>'Lowest'
            ]
    ];

    }
}
