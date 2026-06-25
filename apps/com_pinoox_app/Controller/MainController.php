<?php

namespace App\com_pinoox_app\Controller;

use Pinoox\Component\Kernel\Controller\Controller;
use Pinoox\Portal\View;

class MainController extends Controller
{
    public function index()
    {
        return View::render('hello', [
            'title' => 'Pinoox App',
            'message' => 'Pinoox App starter — built with Pinoox',
        ]);
    }
}
