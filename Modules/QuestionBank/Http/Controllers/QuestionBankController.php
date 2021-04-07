<?php

namespace Modules\QuestionBank\Http\Controllers;

use App\Classes;
use App\Course;
use App\CourseSegment;
use App\GradeCategory;
use App\Http\Controllers\GradeCategoryController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsAnswer;
use Symfony\Component\Console\Question\Question;
use Validator;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\QuestionsCategory;
use Modules\QuestionBank\Entities\QuestionsType;
use App\Component;
use App\LastAction;
use Modules\QuestionBank\Entities\QuestionAnswer;

class QuestionBankController extends Controller
{

    public function install_question_bank()
    {
        if (\Spatie\Permission\Models\Permission::whereName('question/add')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }

        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/category/add','title' => 'add question category']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/category/delete','title' => 'delete question category']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/category/update','title' => 'update question category']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/category/get','title' => 'get question categories']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/add','title' => 'add question']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/update','title' => 'update question']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/get','title' => 'get question']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/delete','title' => 'delete question']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/random','title' => 'get random questions']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/add-answer','title' => 'add question answer']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/delete-answer','title' => 'delete question answer']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/add','title' => 'add quiz']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/update','title' => 'update quiz']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/delete','title' => 'delete quiz']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/get','title' => 'get quiz']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/add-quiz-lesson','title' => 'add quiz lesson']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/grading-method','title' => 'get grading method']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/update-quiz-lesson','title' => 'update quiz lesson']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/destroy-quiz-lesson','title' => 'destroy quiz lesson']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/get-all-types','title' => 'get all quiz types']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/get-all-categories','title' => 'get all quiz categories']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/sort','title' => 'sort quiz']);
        // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/sortup','title' => 'sort up quiz']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/get-quiz-lesson','title' => 'get quiz lesson']);
        // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/store-user-quiz','title' => 'store user quiz']);
        // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/store-user-quiz-answer','title' => 'store user quiz answer']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/get-all-quizes','title' => 'get all quizes']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/get-student-in-quiz','title' => 'get student in quiz']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/get-student-answer-quiz','title' => 'get student answer quiz']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/get-all-students-answer','title' => 'get all students answer']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/answer','title' => 'Answer quiz']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/detailes','title' => 'Quiz Details']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/correct-user-quiz','title' => 'correct user quiz']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/get-grade-category','title' => 'get quiz grade category']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/toggle','title' => 'toggle quiz']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/get-attempts','title' => 'get all attempts of user']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/quiz/getStudentinQuiz','title' => 'get Student in Quiz']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/quiz/store_user_quiz','title' => 'store user quiz']);
        // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/get-users-all-attempts','title' => 'get all users attempts']);
        // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/get-fully-detailed-attempt','title' => 'get fully detailed attempts']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/grade-user-quiz','title' => 'grade user quiz']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/override','title' => 'quiz override']);

        $teacher_permissions=['question/category/add','question/category/delete','question/category/update','question/category/get','question/add','question/update',
        'question/get','question/delete','question/random','question/add-answer','question/delete-answer','quiz/add','quiz/update','quiz/delete','quiz/get',
        'quiz/add-quiz-lesson','quiz/grading-method','quiz/update-quiz-lesson','quiz/destroy-quiz-lesson','quiz/get-all-types','quiz/get-all-categories',
        'quiz/sort','quiz/get-quiz-lesson','quiz/get-all-quizes','quiz/get-student-in-quiz','quiz/get-student-answer-quiz','quiz/get-all-students-answer',
        'quiz/detailes','quiz/correct-user-quiz','quiz/get-grade-category','quiz/toggle','quiz/get-attempts','site/quiz/getStudentinQuiz','quiz/grade-user-quiz',
        'quiz/override'];
        $tecaher = \Spatie\Permission\Models\Role::find(4);
        $tecaher->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $teacher_permissions)->get());

        $student_permissions=['quiz/get','quiz/answer','quiz/correct-user-quiz','quiz/get-attempts','site/quiz/store_user_quiz'];
        $student = \Spatie\Permission\Models\Role::find(3);
        $student->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $student_permissions)->get());
        $parent = \Spatie\Permission\Models\Role::find(7);
        $parent->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $student_permissions)->get());

        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('site/quiz/getStudentinQuiz');
        $role->givePermissionTo('site/quiz/store_user_quiz');
        $role->givePermissionTo('question/add');
        $role->givePermissionTo('question/category/add');
        $role->givePermissionTo('question/category/update');
        $role->givePermissionTo('question/category/delete');
        $role->givePermissionTo('question/category/get');
        $role->givePermissionTo('question/update');
        $role->givePermissionTo('question/delete');
        $role->givePermissionTo('question/get');
        $role->givePermissionTo('quiz/get');
        $role->givePermissionTo('question/random');
        $role->givePermissionTo('question/add-answer');
        $role->givePermissionTo('question/delete-answer');
        $role->givePermissionTo('quiz/add');
        $role->givePermissionTo('quiz/update');
        $role->givePermissionTo('quiz/sort');
        // $role->givePermissionTo('quiz/sortup');
        $role->givePermissionTo('quiz/delete');
        $role->givePermissionTo('quiz/add-quiz-lesson');
        $role->givePermissionTo('quiz/update-quiz-lesson');
        $role->givePermissionTo('quiz/destroy-quiz-lesson');
        $role->givePermissionTo('quiz/get-all-types');
        $role->givePermissionTo('quiz/get-all-categories');
        $role->givePermissionTo('quiz/get-quiz-lesson');
        // $role->givePermissionTo('quiz/store-user-quiz');
        // $role->givePermissionTo('quiz/store-user-quiz-answer');
        $role->givePermissionTo('quiz/get-all-quizes');
        $role->givePermissionTo('quiz/get-student-in-quiz');
        $role->givePermissionTo('quiz/get-student-answer-quiz');
        $role->givePermissionTo('quiz/get-all-students-answer');
        $role->givePermissionTo('quiz/answer');
        $role->givePermissionTo('quiz/correct-user-quiz');
        $role->givePermissionTo('quiz/get-grade-category');
        $role->givePermissionTo('quiz/toggle');
        $role->givePermissionTo('quiz/grading-method');
        $role->givePermissionTo('quiz/get-attempts');
        $role->givePermissionTo('quiz/grade-user-quiz');
        $role->givePermissionTo('quiz/override');
        $role->givePermissionTo('quiz/detailes');

        
        Component::create([
            'name' => 'Quiz',
            'module'=>'QuestionBank',
            'model' => 'quiz',
            'type' => 3,
            'active' => 1
        ]);

        $QuesTypes=array(
            array('name' => 'True/False'),
            array('name' => 'MCQ'),
            array('name' => 'Match'),
            array('name' => 'Essay'),
            array('name' => 'Paragraph'),

        );
        QuestionsType::insert($QuesTypes);

        // $QuesCateg=array(
        //     array('name' => 'Lesson One'),
        //     array('name' => 'Lesson Two'),
        //     array('name' => 'Lesson Three'),
        //     array('name' => 'Lesson Four'),
        //     array('name' => 'Lesson Five'),
        // );
        // QuestionsCategory::insert($QuesCateg);

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'Question_Category_id' => 'array',
            'Question_Category_id.*' => 'integer|exists:questions_categories,id',
            'year' => 'nullable|exists:academic_years,id',
            'type' => 'nullable|exists:academic_types,id',
            'level' => 'nullable|exists:levels,id',
            'class' => 'integer|exists:classes,id',
            'segment' => 'nullable|exists:segments,id',
            'course_id' => 'integer|exists:courses,id',
            'question_type' => 'array',
            'question_type.*' => 'integer|exists:questions_types,id',
            'search' => 'nullable',
            'lastpage' => 'bool'
        ]);
        if(isset($request->year)){
            $cs = [];
            $couresegs = GradeCategoryController::getCourseSegment($request);
            if(count($couresegs) == 0)
                return HelperController::api_response_format(200, collect($cs)->paginate(HelperController::GetPaginate($request)), __('messages.error.not_found') );

            foreach($couresegs as $one){
                $cc=CourseSegment::find($one);
                $cs[]=$cc->course_id;
            }
            $cs= array_unique($cs);
            $questions = Questions::whereIn('course_id',$cs)->where('survey',0)->with('question_answer');
        }else{
            $questions = Questions::where('survey',0)->with('question_answer');
        }

        if($request->filled('search'))
        {
           $questions->where('text', 'LIKE' , "%$request->search%");
        }
        if(isset($request->course_id)) {
            $questions->where('course_id', $request->course_id);
        }
        if (isset($request->Question_Category_id)) {
            $questions->whereIn('question_category_id', $request->Question_Category_id);
        }
        if (isset($request->question_type)) {
            $questions->whereIn('question_type_id', $request->question_type);
        }
        if (isset($request->Category_id)) {
            $questions->where('category_id', $request->Category_id);
        }
        $Questions = $questions->with('childeren.question_answer')->get();
        $question=array();
        foreach ($Questions as $ques){
            if($ques->parent==null){
                array_push($question,$ques);
            }
        }
        $Questions = $this->QuestionData($question);
        if(isset($request->lastpage) && $request->lastpage == true){
            $request['page'] = collect($Questions)->paginate(HelperController::GetPaginate($request))->lastPage();
        }
        return HelperController::api_response_format(200, collect($Questions)->paginate(HelperController::GetPaginate($request)));
    }

    public function QuestionData($questions, $type = 0)
    {
        if ($type == 0) {
            foreach ($questions as $question) {
                // dd($question;
                $question->category;
                $question->question_type;
                $question->question_category;
                $question->question_course;
                $question->question_answer;

                $question->childeren;
                foreach($question->childeren as $single){
                    $single->question_type;
                }
            }
            $data = $questions;
        } else {
            $question = $questions;
            $question->category;
            $question->question_type;
            $question->question_category;
            $question->question_course;
            $question->question_answer;
            $question->childeren;
            foreach($question->childeren as $single){
                $single->question_type;
            }

            $data = $question;
        }
        return $data;
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function getRandomQuestion(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'randomNumber' => 'required|integer|min:1'
        ]);
        $questions = Questions::inRandomOrder()
            ->where('course_id', $request->course_id)
            ->where('parent', null)
            ->limit($request->randomNumber)
            ->with('childeren.question_answer')
            ->get();

        $questions = $this->QuestionData($questions);

        return HelperController::api_response_format(200, $questions);
    }

    /**
     * @Description: create  multi Questions
     * @param : Request to access Question[0][text] and type if type 1 (True/False)
     *          access Question[0][answers][0] , Question[0][Is_True][0] and so on
     * @return: MSG => Question Created Successfully
     */
    public static function CreateOrFirstQuestion($Question,$parent = null)
    {
        $valid = Validator::make($Question, [
            'Question_Type_id' => 'required|integer|exists:questions_types,id',
            'text' => 'required_if:Question_Type_id,==,4|required_if:Question_Type_id,==,5',
            'mark' => 'required|min:0',
            'Question_Category_id' => 'exists:questions_categories,id',
            // 'Category_id' => 'required|exists:categories,id',
            'course_id' => 'exists:courses,id',
            'parent' => 'integer|exists:questions,id',
            'survey' => 'boolean',
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(400, $valid->errors(), __('messages.error.try_again'));
        }
     
        $arr = array();
        if (isset($Question['parent'])) {
            $arr = Questions::where('id', $Question['parent'])->where('question_type_id', 5)->pluck('id')->first();
        }
        if (!isset($arr)) {
            return HelperController::api_response_format(400, null, __('messages.error.data_invalid'));
        }
        if(isset($Question['course_id']))
            LastAction::lastActionInCourse($Question['course_id']);        

        $Questions = collect([]);
        $cat = Questions::firstOrCreate([
            'text' => ($Question['text'] == null) ? "Match the correct Answer" : $Question['text'],
            'mark' => $Question['mark'],
            'And_why' => ($Question['Question_Type_id'] == 1) ? $Question['And_why'] : null,
            'And_why_mark' => ($Question['Question_Type_id'] == 1 && $Question['And_why'] == 1) ? $Question['And_why_mark'] : null,
            // 'category_id' => $Question['Category_id'],
            'parent' => $parent,
            'question_type_id' => $Question['Question_Type_id'],
            'question_category_id' => isset($Question['Question_Category_id']) ? $Question['Question_Category_id'] : null,
            'course_id' => isset($Question['course_id']) ? $Question['course_id'] : null,
            'survey' => isset($Question['survey']) ? $Question['survey'] : 0,
        ]);

        $Questions->push($cat);
        return $cat;
    }

    public static function CreateQuestion($Question,$parent=null)
    {
        $valid = Validator::make($Question, [
            'Question_Type_id' => 'required|integer|exists:questions_types,id',
            'text' => 'required_if:Question_Type_id,==,4|required_if:Question_Type_id,==,5',
            'mark' => 'required|min:0',
            'Question_Category_id' => 'exists:questions_categories,id',
            // 'Category_id' => 'required|exists:categories,id',
            'course_id' => 'exists:courses,id',
            'parent' => 'integer|exists:questions,id',
            'survey' => 'boolean'
        ]);

        if ($valid->fails()) {
            return HelperController::api_response_format(400, $valid->errors(), __('messages.error.try_again'));
        }

        $Questions = collect([]);
        $arr = array();

        if (isset($Question['parent'])) {
            $arr = Questions::where('id', $Question['parent'])->where('question_type_id', 5)->pluck('id')->first();
        }
        if (!isset($arr)) {
            return HelperController::api_response_format(400, null, __('messages.error.data_invalid'));
        }
        if( isset($Question['course_id']))
            LastAction::lastActionInCourse($Question['course_id']);        
        $cat = Questions::Create([
            'text' => ($Question['text'] == null) ? "Match the correct Answer" : $Question['text'],
            'mark' => $Question['mark'],
            'parent' => $parent,
            'And_why' => ($Question['Question_Type_id'] == 1) ? $Question['And_why'] : null,
            'And_why_mark' => ($Question['Question_Type_id'] == 1 && $Question['And_why'] == 1) ? $Question['And_why_mark'] : null,
            // 'category_id' => $Question['Category_id'],
            'question_type_id' => $Question['Question_Type_id'],
            'question_category_id' => isset($Question['Question_Category_id']) ? $Question['Question_Category_id'] : null,
            'course_id' => isset($Question['course_id']) ? $Question['course_id'] : null,
            'survey' => isset($Question['survey']) ? $Question['survey'] : 0,
        ]);
        $Questions->push($cat);
        return $cat;
    }

    public function TrueFalse($Question,$parent)
    {
        $validator = Validator::make($Question, [
            'answers' => 'required|array|distinct|min:2|max:2',
            'text' => 'required|string',
            'answers.*' => 'required|boolean|distinct',
            'And_why' => 'integer|required',
            'And_why_mark' => 'min:0|required_if:And_why,==,1',
            'Is_True' => 'required|boolean',
            'survey' => 'boolean'
        ]);

        if ($validator->fails()) {
            return HelperController::api_response_format(400, $validator->errors(), __('messages.error.try_again'));
        }

        $cat = $this::CreateOrFirstQuestion($Question,$parent);
        if (isset($cat->id)) {
            $is_true = 0;
            $Trues = null;

            foreach ($Question['answers'] as $answer) {
                if ($is_true == $Question['Is_True']) {
                    $Trues = 1;
                } else {
                    $Trues = 0;
                }
                QuestionsAnswer::firstOrCreate([
                    'question_id' => $cat->id,
                    'true_false' => $answer,
                    'content' => null,
                    'match_a' => null,
                    'match_b' => null,
                    'is_true' => $Trues
                ]);
                $is_true += 1;
            }
        }
        return $cat;
    }

    public function MCQ($Question,$parent)
    {

        $validator = Validator::make($Question, [
            'answers' => 'required|array|distinct|min:2',
            'answers.*' => 'required|string|distinct',
            'Is_True' => 'required|integer',
            'text' => 'required|string',
            'survey' => 'boolean'
        ]);

        if ($validator->fails()) {
            return HelperController::api_response_format(400, $validator->errors(), __('messages.error.try_again'));
        }
        if ($Question['Is_True'] > count($Question['answers']) - 1) {
            return HelperController::api_response_format(400, null, __('messages.error.data_invalid'));
        }
        $id = Questions:: where('text', $Question['text'])->pluck('id')->first();
        $ansA = QuestionsAnswer::where('question_id', $id)->pluck('content')->toArray();
        $result = array_diff($Question['answers'], $ansA);
        if ($result == null) {
            $questionn = Questions:: where('id', $id)->first();
            return $questionn;
        }

        $cat = $this::CreateQuestion($Question,$parent);
        if (isset($cat->id)) {

            $is_true = 0;
            $Trues = null;
            foreach ($Question['answers'] as $answer) {
                if ($is_true == $Question['Is_True']) {
                    $Trues = 1;
                } else {
                    $Trues = 0;
                }
                $answer = QuestionsAnswer::firstOrCreate([
                    'question_id' => $cat->id,
                    'true_false' => null,
                    'content' => $answer,
                    'match_a' => null,
                    'match_b' => null,
                    'is_true' => $Trues,
                ]);
                $is_true += 1;
            }
        }
        return $cat;
    }

    public function Match($Question,$parent)
    {
        $validator = Validator::make($Question, [
            'match_A' => 'required|array|min:2|distinct',
            'match_A.*' => 'required|distinct',
            'match_B' => 'required|array|distinct',
            'match_B.*' => 'required|distinct',
        ]);
        if ($validator->fails()) {
            return HelperController::api_response_format(400, $validator->errors(), __('messages.error.try_again'));
        }
        if (count($Question['match_A']) > count($Question['match_B'])) {
            return HelperController::api_response_format(400, null, __('messages.question.questions_answers_count'));
        }
        $id = Questions:: where('text', $Question['text'])->pluck('id')->first();
        $ansA = QuestionsAnswer::where('question_id', $id)->pluck('match_A')->toArray();
        $resultA = array_diff($Question['match_A'], $ansA);
        $ansB = QuestionsAnswer::where('question_id', $id)->pluck('match_B')->toArray();
        $resultB = array_diff($Question['match_B'], $ansB);
        if ($resultA == null && $resultB == null) {
            $questionRe = Questions:: where('id', $id)->first();
            return $questionRe;
        }
        $cat = $this::CreateQuestion($Question,$parent);
        if (isset($cat->id)) {
            $is_true = 0;
            foreach ($Question['match_A'] as $index => $MA) {
                foreach ($Question['match_B'] as $Secindex => $MP) {
                    $answer = QuestionsAnswer::firstOrCreate([
                        'question_id' => $cat->id,
                        'true_false' => null,
                        'content' => null,
                        'match_a' => $MA,
                        'match_b' => $MP,
                        'is_true' => ($index == $Secindex) ? 1 : 0
                    ]);
                    $is_true += 1;
                }
            }
        }

        return $cat;
    }

    public function Essay($Question,$parent)
    {
        $cat = $this::CreateOrFirstQuestion($Question,$parent);
        return $cat;
    }

    public function paragraph($Question)
    {
        $validator = Validator::make($Question, [
            'subQuestions' => 'required|array|distinct'/*|min:2*/,
            'subQuestions.*' => 'required|distinct',
            'subQuestions.*.Question_Type_id' => 'required|integer|exists:questions_types,id',
            'survey' => 'boolean'
        ]);
        if ($validator->fails()) {
            return HelperController::api_response_format(400, $validator->errors());
        }

        $cat = $this->CreateOrFirstQuestion($Question);
      //  dd($cat);
        $re = collect([]);
            foreach ($Question['subQuestions'] as $subQuestion) {
                switch ($subQuestion['Question_Type_id']) {
                    case 1: // True/false
                        $true_false = $this->TrueFalse($subQuestion, $cat->id);
                        $re->push($true_false);
                        break;
                    case 2: // MCQ
                        $mcq = $this->MCQ($subQuestion, $cat->id);
                        $re->push($mcq);
                        break;
                    case 3: // Match
//                        dd($subQuestion);
                        $match = $this->Match($subQuestion, $cat->id);
                        $re->push($match);
                        break;
                    case 4: // Essay
                        $essay = $this->Essay($subQuestion, $cat->id);
                        $re->push($essay);
                        break;
            }
        }
            return $cat;
    }

    public function store(Request $request,$type = 0)
    {
        $request->validate([
            'course_id' => 'integer|exists:courses,id',
            'Question' => 'array',
            'Question.*.Question_Type_id' => 'integer|exists:questions_types,id',
            'Question.*.survey' => 'boolean',
        ]);

        $re = collect([]);
        if(isset($request->Question))
            foreach ($request->Question as $question) {
                switch ($question['Question_Type_id']) {
                    case 1: // True/false
                        $true_false = $this->TrueFalse($question,null);
                        $re->push($true_false);
                        break;
                    case 2: // MCQ
                        $mcq = $this->MCQ($question,null);
                        $re->push($mcq);
                        break;
                    case 3: // Match
                        $match = $this->Match($question,null);
                        $re->push($match);
                        break;
                    case 4: // Essay
                        $essay = $this->Essay($question,null);
                        $re->push($essay);
                        break;
                    case 5: // para
                        $paragraph = $this->paragraph($question);
                        $paragraph->childeren;
                        $re->push($paragraph);
                        break;
                }
            }
        if($type == 0){
            return HelperController::api_response_format(200, $re, __('messages.question.add'));
        }
        else{
            return $re->pluck('id');
        }
    }

    /*updateQuestion*/
    public function updateQuestion($request)
    {
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id',
            'mark' => 'required|min:0',
            // 'category_id' => 'required|integer|exists:categories,id',
            'question_category_id' => 'integer|exists:questions_categories,id',
            'parent' => 'integer|exists:questions,id',
        ]);
        $arr = array();
        if ($request->parent) {
            $arr = Questions::where('id', $request->parent)->where('question_type_id', 5)->pluck('id')->first();
        }
        if (!isset($arr)) {
            return HelperController::api_response_format(400, null, __('messages.error.data_invalid'));
        }
        $question = Questions::find($request->question_id);
        //dd($question);

        if ($question->question_type_id != 3) {
            $request->validate([
                'text' => 'required|string|min:1',
            ]);
        }
        LastAction::lastActionInCourse($question->course_id);        

        $question->update([
            'text' => ($request->text == null) ? "Match the correct Answer" : $request->text,
            'mark' => $request->mark,
            // 'category_id' => $request->category_id,
            'parent' => (isset($request->parent) && $request->Question_Type_id != 5) ? $request->parent : null,
            'question_category_id' => isset($request->question_category_id) ? $request->question_category_id : null,
            'And_why' => ($question->question_type_id == 1) ? $request->And_why : null,
            'And_why_mark' => ($request->And_why == 1) ? $request->And_why_mark : null,
        ]);

        return $question;
    }

    public function updatesubQuestion($squestion, $parent=null,$Question_Type_id=null)
    {
        $validator = Validator::make($squestion->all(), [
            'mark' => 'required|min:0',
            // 'category_id' => 'required|integer|exists:categories,id',
            'question_category_id' => 'integer|exists:questions_categories,id',
        ]);
        if ($validator->fails()) {
            return HelperController::api_response_format(400, $validator->errors());
        }

        $question_id = Questions::where('parent', $parent)->where('question_type_id', $Question_Type_id)->pluck('id')->first();
        $question = Questions::find($question_id);

        if ($question->question_type_id != 3) {
            $squestion->validate([
                'text' => 'required|string|min:1',
            ]);
        }
        LastAction::lastActionInCourse($question->course_id);        

        $question->update([
            'text' => ($squestion['text'] == null) ? "Match the correct Answer" : $squestion['text'],
            'mark' =>$squestion['mark'],
            // 'category_id' => $squestion['category_id'],
            'parent' => $parent,
            'question_category_id' => isset($squestion['question_category_id']) ? $squestion['question_category_id'] : null,
            'And_why' => ($Question_Type_id== 1) ? $squestion['And_why'] : null,
            'And_why_mark' => Questions::CheckAndWhy($squestion),
        ]);

        return $question;
    }

    public function updateTrueFalse($request, $parent,$Question_Type_id)
    {
        $request->validate([
            'answers' => 'required|array|distinct|min:2|max:2',
            'answers.*' => 'required|boolean|distinct',
            'Is_True' => 'required|boolean',
            'And_why' => 'integer|required',
            'And_why_mark' => 'min:0|required_if:And_why,==,1'
        ]);

        if ($parent==null){
            $question = $this->updateQuestion($request,$parent);
            $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();
        }
        else {
            $question = $this->updatesubQuestion($request,$parent,$Question_Type_id);
            $answers = QuestionsAnswer::where('question_id', $question->id)->get();
        }
        $is_true = 0;
        $Trues = null;
        foreach ($answers as $answer) {
            if ($is_true == $request->Is_True) {
                $Trues = 1;
            } else {
                $Trues = 0;
            }
            $answer->update([
                'question_id' => $question->id,
                'true_false' => $request->answers[$is_true],
                'is_true' => $Trues
            ]);
            $is_true += 1;
        }
        // $question = $this->updateQuestion($request,$parent);

        return "success";
    }

    public function updateMCQ($request,$parent,$Question_Type_id)
    {
        $request->validate([
            'answers' => 'required|array|min:2|distinct',
            'answers.*' => 'required|string|min:1',
            'Is_True' => 'required|integer|min:0|max:'.(count($request->answers)-1),
        ]);
        if ($parent==null){
            $question = $this->updateQuestion($request,$parent);
            $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();

        }
        else {
            $question = $this->updatesubQuestion($request,$parent,$Question_Type_id);
            $answers = QuestionsAnswer::where('question_id', $question->id)->get();
        }
        if (count($request->answers) >= count($answers)) {
            $is_true = 0;
            $Trues = null;

            foreach ($request->answers as $answer) {
                if ($is_true == $request->Is_True) {
                    $Trues = 1;
                } else {
                    $Trues = 0;
                }
                if (!isset($answers[$is_true])) {
                    QuestionsAnswer::firstOrCreate([
                        'question_id' => $question->id,
                        'true_false' => null,
                        'content' => $answer,
                        'match_a' => null,
                        'match_b' => null,
                        'is_true' => $Trues,
                    ]);
                } else {
                    $answers[$is_true]->update([
                        'question_id' => $question->id,
                        'true_false' => null,
                        'content' => $answer,
                        'match_a' => null,
                        'match_b' => null,
                        'is_true' => $Trues,
                    ]);
                    $is_true += 1;
                }
            }
        } else {
            $is_true = 0;
            $Trues = null;
            foreach ($answers as $answer) {
                if (!isset($request->answers[$is_true])) {
                    $answer->delete();
                    continue;
                }
                if ($is_true == $request->Is_True) {
                    $Trues = 1;
                } else {
                    $Trues = 0;
                }
                $answer->update([
                    'question_id' => $question->id,
                    'true_false' => null,
                    'content' => $request->answers[$is_true],
                    'match_a' => null,
                    'match_b' => null,
                    'is_true' => $Trues,
                ]);
                $is_true += 1;

            }
        }
        return "success";
    }

    public function updateMatch($request,$parent,$Question_Type_id)
    {
        $request->validate([
            'match_A' => 'required|array|min:2|distinct',
            'match_A.*' => 'required|distinct',
            'match_B' => 'required|array|distinct',
            'match_B.*' => 'required|distinct'
        ]);
        if (count($request->match_A) > count($request->match_B)) {
            return HelperController::api_response_format(400, null, __('messages.question.questions_answers_count'));
        }

        if ($parent==null){
            $question = $this->updateQuestion($request,$parent);
            $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();

        }
        else {
            $question = $this->updatesubQuestion($request,$parent,$Question_Type_id);
            // dd($question);
            $answers = QuestionsAnswer::where('question_id', $question->id)->get();

        }

        $question = $this->updateQuestion($request);
        $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();
        if (count($request->match_A) * count($request->match_B) == count($answers)) {
            $count = 0;

            foreach ($request->match_A as $index => $MA) {
                foreach ($request->match_B as $Secindex => $MP) {
                    $answers[$count]->update([
                        'question_id' => $question->id,
                        'true_false' => null,
                        'match_a' => $MA,
                        'content' => null,
                        'match_b' => $MP,
                        'is_true' => ($index == $Secindex) ? 1 : 0
                    ]);
                    $count += 1;
                }
            }
        } elseif (count($request->match_A) * count($request->match_B) > count($answers)) {
            $count = 0;

            foreach ($request->match_A as $index => $MA) {
                foreach ($request->match_B as $Secindex => $MP) {
                    if (!isset($answers[$count])) {
                        QuestionsAnswer::firstOrCreate([
                            'question_id' => $question->id,
                            'true_false' => null,
                            'content' => null,
                            'match_a' => $MA,
                            'match_b' => $MP,
                            'is_true' => ($index == $Secindex) ? 1 : 0]);

                    } else {
                        $answers[$count]->update([
                            'question_id' => $question->id,
                            'true_false' => null,
                            'content' => null,
                            'match_a' => $MA,
                            'match_b' => $MP,
                            'is_true' => ($index == $Secindex) ? 1 : 0
                        ]);
                    }
                    $count += 1;

                }
            }
        } elseif (count($request->match_A) * count($request->match_B) < count($answers)) {
            $diff = count($answers) - (count($request->match_a) * count($request->match_B));
            for ($x = 0; $x < $diff; $x++) {
                $answers[$x]->delete();
            }
            $count = $diff;
            foreach ($request->match_A as $index => $MA) {
                foreach ($request->match_B as $Secindex => $MP) {
                    $answers[$count]->update([
                        'question_id' => $question->id,
                        'true_false' => null,
                        'content' => null,
                        'match_a' => $MA,
                        'match_b' => $MP,
                        'is_true' => ($index == $Secindex) ? 1 : 0
                    ]);
                    $count += 1;
                }
            }
        }
        return "updated sucess";
    }

    public function updateEssay($request,$parent,$Question_Type_id)
    {
        if ($parent==null){
            $question = $this->updateQuestion($request,$parent, $Question_Type_id);}
        else {
            $question = $this->updatesubQuestion($request,$parent,$Question_Type_id);
            // dd($question);
        }
        return "updated sucess";
    }

    public function updateparagraph($request)
    {
        $request->validate([
            'subQuestions' => 'required|array|distinct',//|min:2',
            'subQuestions.*' => 'required|distinct',
            'subQuestions.*.Question_Type_id' => 'required|integer|exists:questions_types,id',

        ]);
        $question = $this->updateQuestion($request);
        foreach ($request->subQuestions as $subQuestion) {
            $subQuestion = new Request($subQuestion);
            switch ($subQuestion->Question_Type_id) {
                case 1: // True/false
                    $re[] = $this->updateTrueFalse($subQuestion,$question->id,$subQuestion->Question_Type_id);
                    break;
                case 2: // MCQ
                    $re[] = $this->updateMCQ($subQuestion,$question->id,$subQuestion->Question_Type_id);
                    break;
                case 3: // Match
                    $re[] = $this->updateMatch($subQuestion,$question->id,$subQuestion['Question_Type_id']);
                    break;
                case 4: // Essay
                    $re[] = $this->updateEssay($subQuestion,$question->id,$subQuestion->Question_Type_id);
                    break;
            }
        }
        return "updated sucess";
    }
    public function update(Request $request)
    {
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id',
        ]);
        $Question = Questions::find($request->question_id);
        switch ($Question->question_type_id) {
            case 1: // True/false
                $re[] = $this->updateTrueFalse($request,null,null);
                break;
            case 2: // MCQ
                $re[] = $this->updateMCQ($request,null , null);
                break;
            case 3: // Match
                $re[] = $this->updateMatch($request,null,null);
                break;
            case 4: // Essay
                $re[] = $this->updateEssay($request,null , null);
                break;
            case 5: // para
                $re[] = $this->updateparagraph($request);
                break;

        }
        return HelperController::api_response_format(200, $re, __('messages.question.update'));
    }


    public function destroy(Request $request)
    {
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id'
        ]);

        $delete_answers=QuestionsAnswer::where('question_id',$request->question_id)->delete();
        $question = Questions::find($request->question_id);
        LastAction::lastActionInCourse($question->course_id);        
        $question->delete();
        return HelperController::api_response_format(200, [], __('messages.question.delete'));
    }

    public function addAnswer(Request $request)
    {
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id',
            'contents' => 'required|string|min:1',
            'true_false' => 'nullable|boolean',
            'match_a' => 'nullable|string|max:10',
            'match_b' => 'nullable|string|max:10',
            'is_true' => 'required|boolean',
        ]);

        $answer = QuestionsAnswer::create([
            'content'    => $request->contents,
            'true_false' => $request->true_false,
            'match_a' => $request->match_a,
            'match_b' => $request->match_b,
            'is_true' => $request->is_true,
            'question_id' => $request->question_id
        ]);

        return HelperController::api_response_format(200, $answer, __('messages.answer.add'));
    }

    public function getAllTypes(Request $request){
        return HelperController::api_response_format(200 , QuestionsType::all(['name' , 'id']));
    }

    public function getAllCategories(Request $request){
        return HelperController::api_response_format(200 , QuestionsCategory::all(['name' , 'id']));
    }
}
