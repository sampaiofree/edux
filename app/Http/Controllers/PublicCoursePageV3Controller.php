<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Support\PublicCoursePageViewDataBuilder;
use Illuminate\View\View;

class PublicCoursePageV3Controller extends Controller
{
    public function __invoke(Course $course, PublicCoursePageViewDataBuilder $builder): View
    {
        return view('courses.public-v3', $builder->build($course));
    }
}
