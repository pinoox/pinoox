<?php

namespace App\com_pinoox_welcome\Controller;

use Pinoox\Component\Kernel\Controller\Controller;
use Pinoox\Portal\Lang;
use Pinoox\Portal\View;

class MainController extends Controller
{
    public function home()
    {
        $locale = app()->lang();
        View::shareSeo([
            'title' => Lang::get('~welcome.seo_title'),
            'description' => Lang::get('~welcome.seo_description'),
            'canonical' => url('/'),
            'locale' => $locale === 'fa' ? 'fa_IR' : 'en_US',
            'robots' => 'index,follow',
            'json_ld' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => Lang::get('~welcome.welcome_to_pinoox'),
                'description' => Lang::get('~welcome.seo_description'),
                'url' => url('/'),
            ],
        ]);

        return View::render('main', [
            'bootstrap' => [
                'locale' => $locale,
                'direction' => Lang::get('~welcome.direction'),
                'url' => [
                    'MANAGER' => url()->package('com_pinoox_manager')->app(),
                ],
            ],
        ]);
    }
}
