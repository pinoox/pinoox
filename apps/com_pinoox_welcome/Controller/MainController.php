<?php

namespace App\com_pinoox_welcome\Controller;

use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Controller\Controller;
use Pinoox\Portal\View;
use Pinoox\System\Model\UserModel;

class MainController extends Controller
{
    public function pinooxjs(Request $request)
    {
        if ($request->query->has('pinoox_js') || $request->query->has('pinoox.js')) {
            return View::jsResponse('pinoox');
        }

        return redirect(url('/'));
    }

    public function home()
    {
        return View::render('hello');
    }
}

