<?php



namespace App\com_pinoox_manager\Controller;



use App\com_pinoox_manager\Controller\AppController;

use Pinoox\Component\Pinion\Concerns\PinionUploadActions;

use Pinoox\Component\Kernel\Controller\ApiController;



class PinionController extends ApiController

{

    use PinionUploadActions;



    protected function pinionDefaults(): array

    {

        return [

            'destination' => AppController::manualPath,

            'extensions' => ['pinx'],

            'mode' => 'local',

            'storage' => false,

            'record' => false,

        ];

    }

}
