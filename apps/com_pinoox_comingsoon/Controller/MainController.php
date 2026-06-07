<?php

namespace App\com_pinoox_comingsoon\Controller;

use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Controller\Controller;
use Pinoox\Portal\Auth;

class MainController extends Controller
{
    public function main()
    {
        return render('home', [
            'background' => assets('assets/images/tehran.jpg'),
        ]);
    }

    public function panel()
    {
        if (Auth::guest()) {
            redirect('/');
        }

        return render('panel');
    }

    public function save(Request $request)
    {
        $form = $request->payloadMany('title,description,twitter,instagram,linkedin,telegram');
        config('app')->setData($form)->save();

        return url();
    }
}

