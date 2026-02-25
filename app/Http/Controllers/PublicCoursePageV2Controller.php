<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Support\PublicCoursePageViewDataBuilder;
use Illuminate\View\View;

class PublicCoursePageV2Controller extends Controller
{
    public function __invoke(Course $course, PublicCoursePageViewDataBuilder $builder): View
    {
        return view('courses.public-v2', $builder->build($course));
    }
}
