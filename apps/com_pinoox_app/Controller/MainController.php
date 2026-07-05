<?php

namespace App\com_pinoox_app\Controller;

use Pinoox\Component\Kernel\Controller\Controller;
use Pinoox\Portal\View;

class MainController extends Controller
{
    /**
     * Return View::render(...) (string), not echo/exit — Pinoox 3 builds the HTTP response from the return value.
     */
    public function index()
    {
        return View::render('hello', [
            'title' => 'Pinoox App',
            'message' => 'Pinoox App starter — built with Pinoox',
        ]);
    }
}
