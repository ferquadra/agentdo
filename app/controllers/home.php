<?php
class HomeController extends Controller
{
    public function index(Request $request)
    {
        $this->view('home/index', array(
            'title' => APP_NAME,
            'page' => 'home',
            'animateLogo' => true,
        ));
    }
}
